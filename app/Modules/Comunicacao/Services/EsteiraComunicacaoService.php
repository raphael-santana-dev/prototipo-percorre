<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\Inscricao;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use App\Models\User;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraEtapa;
use App\Modules\Comunicacao\Domain\Models\EsteiraDisparo;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class EsteiraComunicacaoService
{
    /**
     * Processa todas as esteiras ativas e suas respectivas etapas em ordem.
     */
    public static function processarEsteirasAtivas(): int
    {
        $agora = now();
        $totalEnviados = 0;

        $esteiras = EsteiraComunicacao::with(['etapas.template', 'ciclo', 'formulario'])
            ->where('status', true)
            ->get();

        foreach ($esteiras as $esteira) {
            // Se configurado para horário comercial (08h às 20h), aguarda a janela
            if ($esteira->apenas_horario_comercial && ($agora->hour < 8 || $agora->hour >= 20)) {
                continue;
            }

            $etapas = $esteira->etapas;
            if ($etapas->isEmpty()) {
                continue;
            }

            // Obtém os candidatos/usuários que pertencem ao universo da esteira
            $universo = self::obterUniversoBase($esteira);
            if ($universo->isEmpty()) {
                continue;
            }

            $etapaAnterior = null;

            foreach ($etapas as $etapa) {
                if (!$etapa->template) {
                    $etapaAnterior = $etapa;
                    continue;
                }

                // Se a etapa é de data fixa e ainda não chegou a data/hora, pula para a próxima
                if ($etapa->tipo_agendamento === 'data_fixa' && $etapa->data_disparo && $agora->lt($etapa->data_disparo)) {
                    $etapaAnterior = $etapa;
                    continue;
                }

                foreach ($universo as $item) {
                    $email = strtolower(trim((string) ($item['email'] ?? '')));
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    $entidadeId = $item['entidade_id'] ?? 0;

                    // 1. Verifica se esta etapa já foi processada para este destinatário
                    $jaProcessado = EsteiraDisparo::where('esteira_id', $esteira->id)
                        ->where('etapa_id', $etapa->id)
                        ->where('destinatario_email', $email)
                        ->where('entidade_id', $entidadeId)
                        ->exists();

                    if ($jaProcessado) {
                        continue;
                    }

                    // 2. Se for Etapa > 1, verifica dependência de tempo e engajamento da Etapa Anterior
                    if ($etapa->ordem > 1 && $etapaAnterior) {
                        $disparoAnterior = EsteiraDisparo::where('esteira_id', $esteira->id)
                            ->where('etapa_id', $etapaAnterior->id)
                            ->where('destinatario_email', $email)
                            ->where('entidade_id', $entidadeId)
                            ->where('status', 'enviado')
                            ->first();

                        // Só avança para a Etapa N se já tiver recebido a Etapa N-1
                        if (!$disparoAnterior || !$disparoAnterior->enviado_em) {
                            continue;
                        }

                        // Verifica o atraso relativo ("X dias/horas após o disparo da etapa anterior")
                        if ($etapa->tipo_agendamento === 'apos_etapa_anterior') {
                            $atraso = max(1, (int) $etapa->atraso_valor);
                            $dataPrevista = ($etapa->atraso_unidade === 'horas')
                                ? $disparoAnterior->enviado_em->copy()->addHours($atraso)
                                : $disparoAnterior->enviado_em->copy()->addDays($atraso);

                            if ($agora->lt($dataPrevista)) {
                                continue; // Ainda não deu o prazo de X dias/horas para este usuário específico
                            }
                        }

                        // Verifica Filtro de Engajamento da Etapa Anterior (Abertura / Clique)
                        if (!self::atendeFiltroEngajamentoAnterior($etapa, $disparoAnterior)) {
                            EsteiraDisparo::create([
                                'esteira_id'         => $esteira->id,
                                'etapa_id'           => $etapa->id,
                                'destinatario_email' => $email,
                                'destinatario_nome'  => $item['nome'] ?? null,
                                'entidade_tipo'      => $item['entidade_tipo'],
                                'entidade_id'        => $entidadeId,
                                'status'             => 'ignorado_engajamento',
                                'motivo_status'      => "Filtro de engajamento '{$etapa->filtro_engajamento_anterior}' não aplicável.",
                                'enviado_em'         => $agora,
                            ]);
                            continue;
                        }
                    }

                    // 3. Verifica Condição de Saída (Conversão alcançada)
                    if (self::atingiuCondicaoSaida($esteira, $item)) {
                        EsteiraDisparo::create([
                            'esteira_id'         => $esteira->id,
                            'etapa_id'           => $etapa->id,
                            'destinatario_email' => $email,
                            'destinatario_nome'  => $item['nome'] ?? null,
                            'entidade_tipo'      => $item['entidade_tipo'],
                            'entidade_id'        => $entidadeId,
                            'status'             => 'ignorado_saida',
                            'motivo_status'      => 'Destinatário atingiu a meta/condição de saída da esteira.',
                            'enviado_em'         => $agora,
                        ]);
                        continue;
                    }

                    // 4. Verifica se atende às condições da Esteira e da Etapa atual
                    if ($etapa->ordem === 1 || $esteira->revalidar_condicoes_no_envio) {
                        if (!self::atendeCondicoes($esteira, $etapa, $item)) {
                            // Na etapa 1, se não atende, apenas não entra na fila ainda.
                            // Nas etapas > 1, se já estava na esteira e deixou de atender (ex: já entregou documentos), registra como ignorado.
                            if ($etapa->ordem > 1) {
                                EsteiraDisparo::create([
                                    'esteira_id'         => $esteira->id,
                                    'etapa_id'           => $etapa->id,
                                    'destinatario_email' => $email,
                                    'destinatario_nome'  => $item['nome'] ?? null,
                                    'entidade_tipo'      => $item['entidade_tipo'],
                                    'entidade_id'        => $entidadeId,
                                    'status'             => 'ignorado_condicao',
                                    'motivo_status'      => 'Destinatário não se encontra mais nas condições exigidas pela etapa.',
                                    'enviado_em'         => $agora,
                                ]);
                            }
                            continue;
                        }
                    }

                    // 5. Executa o Disparo da Etapa
                    self::executarDisparoEtapa($esteira, $etapa, $item, $email, $entidadeId);
                    $totalEnviados++;
                }

                $etapaAnterior = $etapa;
            }
        }

        return $totalEnviados;
    }

    /**
     * Calcula quantos destinatários são elegíveis neste momento (usado no Preview da UI).
     */
    public static function contarPublicoElegivel(EsteiraComunicacao $esteira): int
    {
        $universo = self::obterUniversoBase($esteira);
        return $universo->filter(function ($item) use ($esteira) {
            return !self::atingiuCondicaoSaida($esteira, $item)
                && self::atendeCondicoes($esteira, null, $item);
        })->count();
    }

    /**
     * Levanta a base de possíveis destinatários conforme o tipo de público escolhido.
     */
    public static function obterUniversoBase(EsteiraComunicacao $esteira): Collection
    {
        $condicoes = is_array($esteira->condicoes) ? $esteira->condicoes : [];
        $resultado = collect();

        if ($esteira->publico_alvo === 'inscricoes') {
            $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao'])
                ->whereNotNull('email')
                ->where('email', '!=', '');

            if ($esteira->ciclo_id) {
                $query->where('ciclo_id', $esteira->ciclo_id);
            }

            if (!empty($condicoes['ids_especificos'])) {
                $query->whereIn('id', (array) $condicoes['ids_especificos']);
            }

            foreach ($query->get() as $insc) {
                $resultado->push([
                    'entidade_tipo'   => 'inscricao',
                    'entidade_id'     => $insc->id,
                    'email'           => $insc->email,
                    'nome'            => $insc->nome,
                    'inscricao'       => $insc,
                    'status_id'       => (string) $insc->status_inscricao_id,
                    'unidade_id'      => (string) $insc->unidade_id,
                    'curso_id'        => (string) $insc->curso_id,
                    'turno_id'        => (string) $insc->turno_id,
                    'etapa_atual'     => (int) $insc->etapa_atual,
                    'dados_dinamicos' => is_array($insc->dados_dinamicos) ? $insc->dados_dinamicos : (json_decode($insc->dados_dinamicos ?? '{}', true) ?: []),
                ]);
            }
        } elseif ($esteira->publico_alvo === 'formulario_publico' && $esteira->formulario_id) {
            $formulario = $esteira->formulario;
            $totalEtapasForm = max(1, CampoFormulario::where('formulario_id', $esteira->formulario_id)->where('tipo', '!=', 'config')->max('etapa') ?? 1);
            $camposEmail = CampoFormulario::where('formulario_id', $esteira->formulario_id)->where('subtipo', 'email')->pluck('name')->toArray();

            $respostas = RespostaFormulario::where('formulario_id', $esteira->formulario_id)->get();

            foreach ($respostas as $resp) {
                $dadosResp = is_array($resp->respostas) ? $resp->respostas : (json_decode($resp->respostas ?? '{}', true) ?: []);
                $email = $dadosResp['_email_coletado'] ?? null;

                if (!$email) {
                    foreach ($camposEmail as $cName) {
                        if (!empty($dadosResp[$cName]) && filter_var($dadosResp[$cName], FILTER_VALIDATE_EMAIL)) {
                            $email = $dadosResp[$cName];
                            break;
                        }
                    }
                }
                if (!$email && $resp->user_id) {
                    $email = User::find($resp->user_id)?->email;
                }

                if (!$email) continue;

                $nomeResp = $dadosResp['nome'] ?? $dadosResp['nome_completo'] ?? 'Participante';
                $concluido = ((int) $resp->etapa_parada >= $totalEtapasForm);

                $resultado->push([
                    'entidade_tipo'     => 'resposta_formulario',
                    'entidade_id'       => $resp->id,
                    'email'             => $email,
                    'nome'              => $nomeResp,
                    'inscricao'         => null,
                    'etapa_atual'       => $concluido ? 99 : (int) ($resp->etapa_parada ?: 1),
                    'dados_dinamicos'   => $dadosResp,
                    'titulo_formulario' => $formulario->titulo ?? 'Formulário',
                    'url_formulario'    => $formulario ? url('/f/' . $formulario->slug) : url('/'),
                ]);
            }
        } elseif ($esteira->publico_alvo === 'estudantes') {
            $query = Student::where('is_active', true)->whereNotNull('email');
            if (!empty($condicoes['unidades_ids'])) {
                $query->whereIn('unidade_id', (array) $condicoes['unidades_ids']);
            }
            if (!empty($condicoes['ids_especificos'])) {
                $query->whereIn('id', (array) $condicoes['ids_especificos']);
            }
            foreach ($query->get() as $st) {
                $resultado->push([
                    'entidade_tipo'   => 'student',
                    'entidade_id'     => $st->id,
                    'email'           => $st->email,
                    'nome'            => $st->name,
                    'inscricao'       => Inscricao::where('student_id', $st->id)->latest()->first(),
                    'unidade_id'      => (string) $st->unidade_id,
                    'dados_dinamicos' => [],
                ]);
            }
        } elseif ($esteira->publico_alvo === 'usuarios') {
            $query = User::whereNotNull('email');
            if (!empty($condicoes['ids_especificos'])) {
                $query->whereIn('id', (array) $condicoes['ids_especificos']);
            }
            foreach ($query->get() as $u) {
                $resultado->push([
                    'entidade_tipo'   => 'user',
                    'entidade_id'     => $u->id,
                    'email'           => $u->email,
                    'nome'            => $u->name,
                    'inscricao'       => null,
                    'dados_dinamicos' => [],
                ]);
            }
        } elseif ($esteira->publico_alvo === 'lista_manual') {
            $emailsManuais = $condicoes['emails_manuais'] ?? [];
            foreach ($emailsManuais as $idx => $em) {
                $emLimpo = trim((string) $em);
                if (filter_var($emLimpo, FILTER_VALIDATE_EMAIL)) {
                    $resultado->push([
                        'entidade_tipo'   => 'manual',
                        'entidade_id'     => $idx + 1,
                        'email'           => $emLimpo,
                        'nome'            => explode('@', $emLimpo)[0],
                        'inscricao'       => Inscricao::where('email', $emLimpo)->latest()->first(),
                        'dados_dinamicos' => [],
                    ]);
                }
            }
        }

        return $resultado;
    }

   

    /**
     * Avalia se o destinatário já cumpriu o objetivo e deve sair da esteira.
     */
    public static function atingiuCondicaoSaida(EsteiraComunicacao $esteira, array $item): bool
    {
        $saida = is_array($esteira->condicao_saida) ? $esteira->condicao_saida : [];

        // Sair se concluir o preenchimento do formulário/inscrição
        if (!empty($saida['sair_ao_concluir_formulario']) && ($item['etapa_atual'] ?? 1) == 99) {
            return true;
        }

        // Sair se atingir determinados Status de Inscrição (ex: Matriculado / Desistente)
        if (!empty($saida['status_saida_ids']) && $item['entidade_tipo'] === 'inscricao') {
            $statusSaida = array_map('strval', (array) $saida['status_saida_ids']);
            if (in_array((string) ($item['status_id'] ?? ''), $statusSaida, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calcula em qual etapa a esteira se encontra no momento ou se já foi finalizada,
     * retornando os dados prontos para o Badge na listagem de Esteiras (EsteiraManager).
     */
    public static function obterResumoProgressoEsteira(EsteiraComunicacao $esteira): array
    {
        $etapas = $esteira->etapas->sortBy('ordem')->values();
        $totalEtapas = $etapas->count();

        if ($totalEtapas === 0) {
            return [
                'estado' => 'sem_etapas',
                'label'  => 'Sem Etapas',
                'detalhe'=> 'Nenhuma etapa configurada',
                'cor'    => 'bg-gray-100 text-gray-600 border-gray-200',
                'icone'  => 'ph-minus-circle',
            ];
        }

        // Conta disparos enviados por etapa
        $disparosPorEtapa = EsteiraDisparo::where('esteira_id', $esteira->id)
            ->selectRaw('etapa_id, count(*) as total, sum(case when status = \'enviado\' then 1 else 0 end) as enviados')
            ->groupBy('etapa_id')
            ->get()
            ->keyBy('etapa_id');

        $totalProcessadosGeral = $disparosPorEtapa->sum('total');
        $ultimaEtapa = $etapas->last();
        $processadosUltimaEtapa = (int) ($disparosPorEtapa[$ultimaEtapa->id]->total ?? 0);

        // Verifica quantos destinatários elegíveis existem no universo hoje
        $elegiveisAtuais = self::contarPublicoElegivel($esteira);

        // Se a última etapa já foi processada para todos os elegíveis (ou se a data fixa da última etapa já passou e já houve disparos)
        $ultimaDataFixaPassou = $ultimaEtapa->tipo_agendamento === 'data_fixa'
            && $ultimaEtapa->data_disparo
            && now()->gt($ultimaEtapa->data_disparo);

        if ($processadosUltimaEtapa > 0 && ($processadosUltimaEtapa >= $elegiveisAtuais || $ultimaDataFixaPassou)) {
            return [
                'estado'  => 'finalizada',
                'label'   => 'Esteira Finalizada',
                'detalhe' => "Todas as {$totalEtapas} etapa(s) concluídas",
                'cor'     => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
                'icone'   => 'ph-check-circle',
            ];
        }

        // Descobre qual é a etapa atual em andamento (a primeira etapa que ainda possui destinatários pendentes)
        $etapaAtualObj = $etapas->first();
        foreach ($etapas as $et) {
            $procEtapa = (int) ($disparosPorEtapa[$et->id]->total ?? 0);
            if ($procEtapa === 0 || ($elegiveisAtuais > 0 && $procEtapa < $elegiveisAtuais)) {
                $etapaAtualObj = $et;
                break;
            }
            $etapaAtualObj = $et;
        }

        if ($totalProcessadosGeral === 0) {
            return [
                'estado'  => 'aguardando',
                'label'   => "Na 1ª Etapa (1/{$totalEtapas})",
                'detalhe' => 'Aguardando 1º disparo: ' . $etapaAtualObj->nome,
                'cor'     => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
                'icone'   => 'ph-clock-countdown',
            ];
        }

        return [
            'estado'  => 'em_andamento',
            'label'   => "Etapa {$etapaAtualObj->ordem} de {$totalEtapas}",
            'detalhe' => $etapaAtualObj->nome,
            'cor'     => 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800',
            'icone'   => 'ph-spinner',
        ];
    }

    /**
     * Verifica se o comportamento do usuário no e-mail da etapa anterior permite o envio da etapa atual.
     */
    private static function atendeFiltroEngajamentoAnterior(EsteiraEtapa $etapaAtual, EsteiraDisparo $disparoAnterior): bool
    {
        $filtro = $etapaAtual->filtro_engajamento_anterior ?: 'todos';
        if ($filtro === 'todos') {
            return true;
        }

        // Localiza o log em Comunicados para checar aberturas_count e cliques_count
        $comunicado = $disparoAnterior->comunicado_id
            ? Comunicado::find($disparoAnterior->comunicado_id)
            : Comunicado::where('template_id', $disparoAnterior->etapa->template_id ?? 0)
                ->where('destinatarios', 'ilike', '%' . $disparoAnterior->destinatario_email . '%')
                ->latest('id')
                ->first();

        if (!$comunicado) {
            return in_array($filtro, ['nao_abriu', 'nao_clicou'], true);
        }

        return match ($filtro) {
            'nao_abriu'  => (int) $comunicado->aberturas_count === 0,
            'abriu'      => (int) $comunicado->aberturas_count > 0,
            'nao_clicou' => (int) $comunicado->cliques_count === 0,
            'clicou'     => (int) $comunicado->cliques_count > 0,
            default      => true,
        };
    }

    /**
     * Realiza o disparo da etapa e registra na tabela de auditoria da esteira.
     */
    /**
     * Realiza o disparo da etapa e registra na tabela de auditoria da esteira.
     */
    private static function executarDisparoEtapa(
        EsteiraComunicacao $esteira,
        EsteiraEtapa $etapa,
        array $item,
        string $email,
        int $entidadeId
    ): void {
        $inscricao = $item['inscricao'] ?? null;
        
        // Mescla as respostas do formulário/inscrição com as variáveis de contexto da Esteira
        $contextoEnvio = array_merge($item['dados_dinamicos'] ?? [], [
            'nome'              => $item['nome'] ?? 'Candidato',
            'primeiro_nome'     => explode(' ', trim((string) ($item['nome'] ?? 'Candidato')))[0],
            'nome_esteira'      => $esteira->nome,
            'nome_etapa'        => $etapa->nome,
            'titulo_formulario' => $item['titulo_formulario'] ?? ($esteira->formulario->titulo ?? ''),
            'url_formulario'    => $item['url_formulario'] ?? '',
            'variaveis_mapeadas' => $etapa->condicoes_etapa['variaveis_map'] ?? []
        ]);

        // Se a ação configurada nesta etapa exigir a criação do acesso ao Portal do Aluno
        if ($etapa->tipo_acao === 'criar_aluno_enviar_email' && $inscricao) {
            if (!$inscricao->student_id) {
                $senhaPlana = Str::random(8);
                $estudante = Student::firstOrCreate(
                    ['email' => $inscricao->email],
                    [
                        'name'                 => $inscricao->nome,
                        'password'             => Hash::make($senhaPlana),
                        'is_active'            => true,
                        'must_change_password' => true,
                        'unidade_id'           => $inscricao->unidade_id,
                    ]
                );
                
                $inscricao->update(['student_id' => $estudante->id]);
                
                // Injeta as variáveis de login no e-mail
                $contextoEnvio['senha_provisoria'] = $senhaPlana;
                $contextoEnvio['link_login'] = url('/aluno/login');
            } else {
                $contextoEnvio['senha_provisoria'] = '******** (Você já possui cadastro no sistema)';
                $contextoEnvio['link_login'] = url('/aluno/login');
            }
        }

        // Cria um identificador único de gatilho para a auditoria do job
        $eventoGatilho = "esteira.{$esteira->id}.etapa.{$etapa->ordem}";

        // Enfileira o disparo utilizando a estrutura existente de comunicação
        $canalEtapa = $etapa->canal_disparo ?? ($etapa->template->canal ?? 'email');

        DispararAutomacaoJob::dispatch(
            $etapa->template,
            $email,
            $inscricao,
            $contextoEnvio,
            $eventoGatilho,
            $canalEtapa
        );

        // Registra o disparo na tabela da esteira para controle de cadência e exibição na tela de Detalhes
        EsteiraDisparo::create([
            'esteira_id'         => $esteira->id,
            'etapa_id'           => $etapa->id,
            'destinatario_email' => $email,
            'destinatario_nome'  => $item['nome'] ?? null,
            'entidade_tipo'      => $item['entidade_tipo'],
            'entidade_id'        => $entidadeId,
            'status'             => 'enviado',
            'motivo_status'      => "Etapa #{$etapa->ordem} ({$etapa->nome}) enviada com sucesso.",
            'enviado_em'         => now(),
        ]);
    }

    /**
     * Avalia se o destinatário cumpre todas as condições da Esteira + Condições da Etapa,
     * respeitando o relacionamento exato Unidade -> Curso -> Turno (chaves compostas).
     */
    public static function atendeCondicoes(EsteiraComunicacao $esteira, ?EsteiraEtapa $etapa, array $item): bool
    {
        $condicoes = is_array($esteira->condicoes) ? $esteira->condicoes : [];
        $condEtapa = ($etapa && is_array($etapa->condicoes_etapa)) ? $etapa->condicoes_etapa : [];

        // 1. Filtro de Status de Inscrição (1 ou mais status)
        $statusPermitidos = !empty($condEtapa['status_ids'])
            ? array_map('strval', $condEtapa['status_ids'])
            : (!empty($condicoes['status_ids']) ? array_map('strval', $condicoes['status_ids']) : []);

        if (!empty($statusPermitidos) && ($item['entidade_tipo'] ?? '') === 'inscricao') {
            if (!in_array((string) ($item['status_id'] ?? ''), $statusPermitidos, true)) {
                return false;
            }
        }

        // 2. Filtro Hierárquico Unidade -> Curso -> Turno (Chaves Compostas iguais ao Form Builder)
        $unidadesPermitidas = array_values(array_filter(array_map('strval', $condicoes['unidades_ids'] ?? [])));
        $cursosPermitidos   = array_values(array_filter(array_map('strval', $condicoes['cursos_ids'] ?? [])));
        $turnosPermitidos   = array_values(array_filter(array_map('strval', $condicoes['turnos_ids'] ?? [])));

        $uItem = (string) ($item['unidade_id'] ?? '');
        $cItem = (string) ($item['curso_id'] ?? '');
        $tItem = (string) ($item['turno_id'] ?? '');

        if (!empty($unidadesPermitidas)) {
            if ($uItem === '' || !in_array($uItem, $unidadesPermitidas, true)) {
                return false;
            }
        }

        if (!empty($cursosPermitidos)) {
            if ($cItem === '') {
                return false;
            }
            $chaveCompostaCurso = "{$uItem}-{$cItem}";
            // Suporta tanto chave composta "unidadeId-cursoId" quanto legado "cursoId"
            $cursoValido = in_array($chaveCompostaCurso, $cursosPermitidos, true)
                || in_array($cItem, $cursosPermitidos, true);

            if (!$cursoValido) {
                return false;
            }
        }

        if (!empty($turnosPermitidos)) {
            if ($tItem === '') {
                return false;
            }
            $chaveCompostaTurno = "{$uItem}-{$cItem}-{$tItem}";
            $chaveParcialTurno  = "{$cItem}-{$tItem}";

            $turnoValido = in_array($chaveCompostaTurno, $turnosPermitidos, true)
                || in_array($chaveParcialTurno, $turnosPermitidos, true)
                || in_array($tItem, $turnosPermitidos, true);

            if (!$turnoValido) {
                return false;
            }
        }

        // 3. Filtro de Progresso do Preenchimento
        $progressoFiltro = $condicoes['progresso_preenchimento'] ?? 'todos';
        if ($progressoFiltro === 'incompleto' && ($item['etapa_atual'] ?? 1) >= 99) {
            return false;
        }
        if ($progressoFiltro === 'finalizado' && ($item['etapa_atual'] ?? 1) != 99) {
            return false;
        }

        // 4. Regras sobre Campos Dinâmicos do Formulário (JSON)
        $regrasDinamicas = $condicoes['regras_dinamicas'] ?? [];
        if (!empty($regrasDinamicas) && is_array($regrasDinamicas)) {
            $respostas = $item['dados_dinamicos'] ?? [];
            foreach ($regrasDinamicas as $regra) {
                $campo = trim((string) ($regra['campo'] ?? ''));
                if ($campo === '') continue;

                $operador = $regra['operador'] ?? '=';
                $valorEsperado = mb_strtolower(trim((string) ($regra['valor'] ?? '')), 'UTF-8');
                $valorAtualRaw = $respostas[$campo] ?? '';
                $valorAtual = is_array($valorAtualRaw)
                    ? mb_strtolower(implode(',', $valorAtualRaw), 'UTF-8')
                    : mb_strtolower(trim((string) $valorAtualRaw), 'UTF-8');

                $passou = match ($operador) {
                    '='          => $valorAtual === $valorEsperado,
                    '!='         => $valorAtual !== $valorEsperado,
                    'contem'     => str_contains($valorAtual, $valorEsperado),
                    'preenchido' => $valorAtual !== '',
                    'vazio'      => $valorAtual === '',
                    default      => true,
                };

                if (!$passou) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Reenvia ou força o disparo imediato de um registro específico da esteira.
     */
    public static function reenviarDisparoIndividual(int $disparoId): bool
    {
        $disparo = EsteiraDisparo::with(['esteira.formulario', 'etapa.template'])->findOrFail($disparoId);
        $esteira = $disparo->esteira;
        $etapa   = $disparo->etapa;

        if (!$esteira || !$etapa || !$etapa->template) {
            return false;
        }

        $inscricao = null;
        $dadosDinamicos = [];

        if ($disparo->entidade_tipo === 'inscricao' && $disparo->entidade_id) {
            $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo'])->find($disparo->entidade_id);
            if ($inscricao) {
                $dadosDinamicos = is_array($inscricao->dados_dinamicos)
                    ? $inscricao->dados_dinamicos
                    : (json_decode($inscricao->dados_dinamicos ?? '{}', true) ?: []);
            }
        } elseif ($disparo->entidade_tipo === 'resposta_formulario' && $disparo->entidade_id) {
            $resp = RespostaFormulario::find($disparo->entidade_id);
            if ($resp) {
                $dadosDinamicos = is_array($resp->respostas)
                    ? $resp->respostas
                    : (json_decode($resp->respostas ?? '{}', true) ?: []);
            }
        } elseif ($disparo->entidade_tipo === 'student' && $disparo->entidade_id) {
            $inscricao = Inscricao::where('student_id', $disparo->entidade_id)->latest()->first();
        }

        $nome = $disparo->destinatario_nome ?: ($inscricao->nome ?? 'Candidato');
        $contextoEnvio = array_merge($dadosDinamicos, [
            'nome'              => $nome,
            'primeiro_nome'     => explode(' ', trim((string) $nome))[0],
            'nome_esteira'      => $esteira->nome,
            'nome_etapa'        => $etapa->nome,
            'titulo_formulario' => $esteira->formulario->titulo ?? '',
            'url_formulario'    => $esteira->formulario ? url('/f/' . $esteira->formulario->slug) : url('/'),
        ]);

        if ($etapa->tipo_acao === 'criar_aluno_enviar_email' && $inscricao) {
            if (!$inscricao->student_id) {
                $senhaPlana = Str::random(8);
                $estudante = Student::firstOrCreate(
                    ['email' => $inscricao->email],
                    [
                        'name'                 => $inscricao->nome,
                        'password'             => Hash::make($senhaPlana),
                        'is_active'            => true,
                        'must_change_password' => true,
                        'unidade_id'           => $inscricao->unidade_id,
                    ]
                );
                $inscricao->update(['student_id' => $estudante->id]);
                $contextoEnvio['senha_provisoria'] = $senhaPlana;
                $contextoEnvio['link_login'] = url('/aluno/login');
            } else {
                $contextoEnvio['senha_provisoria'] = '******** (Você já possui cadastro no sistema)';
                $contextoEnvio['link_login'] = url('/aluno/login');
            }
        }

        $eventoGatilho = "esteira.{$esteira->id}.etapa.{$etapa->ordem}.reenvio";

        DispararAutomacaoJob::dispatch(
            $etapa->template,
            $disparo->destinatario_email,
            $inscricao,
            $contextoEnvio,
            $eventoGatilho
        );

        $disparo->update([
            'status'        => 'enviado',
            'motivo_status' => 'Reenviado / disparado manualmente por ' . (auth()->user()->name ?? 'Administrador') . ' em ' . now()->format('d/m/Y H:i'),
            'enviado_em'    => now(),
        ]);

        return true;
    }

    /**
     * Cancela todas as próximas etapas pendentes na esteira para um destinatário específico.
     */
    public static function cancelarProximasEtapasDestinatario(int $esteiraId, string $email, ?int $entidadeId = null, ?string $nome = null, ?string $entidadeTipo = null): int
    {
        $esteira = EsteiraComunicacao::with('etapas')->findOrFail($esteiraId);
        $emailLimpo = strtolower(trim($email));
        $bloqueados = 0;

        foreach ($esteira->etapas as $etapa) {
            $query = EsteiraDisparo::where('esteira_id', $esteira->id)
                ->where('etapa_id', $etapa->id)
                ->where('destinatario_email', $emailLimpo);

            if ($entidadeId !== null) {
                $query->where('entidade_id', $entidadeId);
            }

            $existente = $query->first();

            if (!$existente) {
                EsteiraDisparo::create([
                    'esteira_id'         => $esteira->id,
                    'etapa_id'           => $etapa->id,
                    'destinatario_email' => $emailLimpo,
                    'destinatario_nome'  => $nome,
                    'entidade_tipo'      => $entidadeTipo ?? 'manual',
                    'entidade_id'        => $entidadeId ?? 0,
                    'status'             => 'cancelado_manual',
                    'motivo_status'      => 'Etapa cancelada manualmente por ' . (auth()->user()->name ?? 'Administrador'),
                    'enviado_em'         => now(),
                ]);
                $bloqueados++;
            } elseif ($existente->status !== 'enviado' && $existente->status !== 'cancelado_manual') {
                $existente->update([
                    'status'        => 'cancelado_manual',
                    'motivo_status' => 'Etapa cancelada manualmente por ' . (auth()->user()->name ?? 'Administrador'),
                ]);
                $bloqueados++;
            }
        }

        return $bloqueados;
    }

    /**
     * Reativa as etapas que haviam sido canceladas manualmente para este destinatário.
     */
    public static function reativarDestinatarioNaEsteira(int $esteiraId, string $email, ?int $entidadeId = null): int
    {
        $query = EsteiraDisparo::where('esteira_id', $esteiraId)
            ->where('destinatario_email', strtolower(trim($email)))
            ->where('status', 'cancelado_manual');

        if ($entidadeId !== null) {
            $query->where('entidade_id', $entidadeId);
        }

        return $query->delete();
    }
}