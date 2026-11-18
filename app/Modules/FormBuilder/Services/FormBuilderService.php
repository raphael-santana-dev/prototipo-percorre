<?php

namespace App\Modules\FormBuilder\Services;

use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\Formulario;
use App\Models\FormularioFase;
use App\Models\CampoFormulario;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FormBuilderService
{
    public function resolverContextoInicial(string $tipo, int $id): array
    {
        if ($tipo === 'ciclo') {
            $model = Ciclo::findOrFail($id);
            $isCustom = ($model->tipo_ciclo ?? 'regular') === 'customizado';

            return [
                'nome'               => $model->nome,
                'isCicloCustomizado' => $isCustom,
                'badge'              => $isCustom ? 'Projeto Customizado' : 'Inscrição Oficial',
                'slug'               => $model->slug ?? '',
            ];
        }

        $model = Formulario::with(['faseAprendizagem.ciclo', 'cicloSeletivo', 'unidade', 'curso'])->findOrFail($id);

        if ($model->tipo === 'aprendizagem') {
            $cicloNome = $model->faseAprendizagem->ciclo->nome ?? 'Ciclo Indefinido';
            $faseNome  = $model->faseAprendizagem->nome ?? 'Fase Indefinida';
            $nome      = "{$cicloNome} ({$faseNome})";
            $badge     = 'Avaliação de Aprendizagem';
        } elseif ($model->tipo === 'pre_inscricao') {
            $detalhes = array_filter([$model->unidade?->nome, $model->curso?->nome]);
            $sufixo   = count($detalhes) > 0 ? ' - ' . implode(' | ', $detalhes) : ' (Captação Geral)';
            $nome     = $model->titulo . $sufixo;
            $badge    = 'Pré-Inscrição (Lead)';
        } else {
            $nome  = $model->titulo;
            $badge = 'Formulário Avulso';
        }

        return [
            'nome'               => $nome,
            'isCicloCustomizado' => false,
            'badge'              => $badge,
            'slug'               => $model->slug ?? '',
        ];
    }

    public function sincronizarEtapas(string $colunaFk, int $contextoId): Collection
    {
        $etapas = Etapa::where($colunaFk, $contextoId)->orderBy('numero', 'asc')->get();

        $numerosExistentes = CampoFormulario::where($colunaFk, $contextoId)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        if (!in_array(1, $numerosExistentes)) {
            $numerosExistentes[] = 1;
        }

        foreach ($numerosExistentes as $num) {
            if (!$etapas->contains('numero', $num)) {
                $novaEtapa = Etapa::create([
                    $colunaFk => $contextoId,
                    'numero'  => $num,
                    'nome'    => $num . 'º Fase',
                ]);
                $etapas->push($novaEtapa);
            }
        }

        return $etapas->sortBy('numero')->values();
    }

    public function reindexarOrdensDaEtapa(string $colunaFk, int $contextoId, int $numeroEtapa): void
    {
        $campos = CampoFormulario::where($colunaFk, $contextoId)
            ->where('etapa', $numeroEtapa)
            ->where('tipo', '!=', 'config')
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        $ordem = 1;
        foreach ($campos as $c) {
            if ((int) $c->ordem !== $ordem) {
                $c->update(['ordem' => $ordem]);
            }
            $ordem++;
        }
    }

    public function moverCamposParaEtapa(string $colunaFk, int $contextoId, array $camposIds, int $destino): int
    {
        $camposMover = CampoFormulario::where($colunaFk, $contextoId)
            ->whereIn('id', $camposIds)
            ->where('tipo', '!=', 'config')
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        if ($camposMover->isEmpty()) {
            return 0;
        }

        $etapasAfetadas = $camposMover->pluck('etapa')->push($destino)->unique()->values()->toArray();

        DB::transaction(function () use ($colunaFk, $contextoId, $destino, $camposMover, $etapasAfetadas) {
            $maxOrdemDestino = CampoFormulario::where($colunaFk, $contextoId)
                ->where('etapa', $destino)
                ->where('tipo', '!=', 'config')
                ->whereNotIn('id', $camposMover->pluck('id'))
                ->max('ordem') ?? 0;

            foreach ($camposMover as $campo) {
                $maxOrdemDestino++;
                $campo->update([
                    'etapa' => $destino,
                    'ordem' => $maxOrdemDestino,
                ]);
            }

            foreach ($etapasAfetadas as $numEtapa) {
                $this->reindexarOrdensDaEtapa($colunaFk, $contextoId, (int) $numEtapa);
            }
        });

        return $camposMover->count();
    }

    public function moverSelecionadosNaMesmaEtapa(string $colunaFk, int $contextoId, array $camposIds, string $posicao = 'topo'): void
    {
        $etapasDosSelecionados = CampoFormulario::where($colunaFk, $contextoId)
            ->whereIn('id', $camposIds)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        DB::transaction(function () use ($colunaFk, $contextoId, $camposIds, $etapasDosSelecionados, $posicao) {
            foreach ($etapasDosSelecionados as $numEtapa) {
                $todosDaEtapa = CampoFormulario::where($colunaFk, $contextoId)
                    ->where('etapa', $numEtapa)
                    ->where('tipo', '!=', 'config')
                    ->orderBy('ordem')
                    ->orderBy('id')
                    ->get();

                $selecionados    = $todosDaEtapa->filter(fn($c) => in_array((string) $c->id, $camposIds, true));
                $naoSelecionados = $todosDaEtapa->reject(fn($c) => in_array((string) $c->id, $camposIds, true));

                $novaLista = ($posicao === 'topo')
                    ? $selecionados->concat($naoSelecionados)
                    : $naoSelecionados->concat($selecionados);

                $ordem = 1;
                foreach ($novaLista as $item) {
                    if ((int) $item->ordem !== $ordem) {
                        $item->update(['ordem' => $ordem]);
                    }
                    $ordem++;
                }
            }
        });
    }

    public function excluirCamposEmLote(string $colunaFk, int $contextoId, array $camposIds): void
    {
        $etapasAfetadas = CampoFormulario::where($colunaFk, $contextoId)
            ->whereIn('id', $camposIds)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        DB::transaction(function () use ($colunaFk, $contextoId, $camposIds, $etapasAfetadas) {
            CampoFormulario::where($colunaFk, $contextoId)
                ->whereIn('id', $camposIds)
                ->where('tipo', '!=', 'config')
                ->delete();

            foreach ($etapasAfetadas as $numEtapa) {
                $this->reindexarOrdensDaEtapa($colunaFk, $contextoId, (int) $numEtapa);
            }
        });
    }

    public function excluirEtapaERenumerar(Etapa $etapa, string $colunaFk, int $contextoId, int $numeroRemovido): void
    {
        DB::transaction(function () use ($etapa, $numeroRemovido, $colunaFk, $contextoId) {
            $etapa->delete();

            CampoFormulario::where($colunaFk, $contextoId)
                ->where('etapa', '>', $numeroRemovido)
                ->where('tipo', '!=', 'config')
                ->decrement('etapa');

            $etapasRestantes = Etapa::where($colunaFk, $contextoId)
                ->orderBy('numero', 'asc')
                ->get();

            foreach ($etapasRestantes as $idx => $etapaRestante) {
                $novoNumero = $idx + 1;
                if ((int) $etapaRestante->numero !== $novoNumero) {
                    $novoNome = preg_replace('/^(\d+)(º\s*Fase|ª\s*Fase|ª\s*Etapa|º\s*Passo)$/iu', $novoNumero . '$2', trim($etapaRestante->nome));
                    $novoNome = preg_replace('/^(Página|Etapa|Fase|Passo)\s+\d+$/iu', '$1 ' . $novoNumero, $novoNome);

                    $etapaRestante->update([
                        'numero' => $novoNumero,
                        'nome'   => $novoNome,
                    ]);
                }
            }

            if (class_exists(FormularioFase::class)) {
                $fases = FormularioFase::where($colunaFk, $contextoId)->get();
                foreach ($fases as $fase) {
                    $pags = is_string($fase->paginas) ? json_decode($fase->paginas, true) : ($fase->paginas ?? []);
                    if (is_array($pags)) {
                        $novasPags = [];
                        foreach ($pags as $p) {
                            $pInt = (int) $p;
                            if ($pInt === $numeroRemovido) continue;
                            $novasPags[] = $pInt > $numeroRemovido ? ($pInt - 1) : $pInt;
                        }
                        $fase->update(['paginas' => array_values(array_unique($novasPags))]);
                    }
                }
            }
        });
    }

    public function carregarDadosModalFases(string $colunaFk, int $contextoId, Collection $etapasDisponiveis): array
    {
        $paginasEtapas = $etapasDisponiveis->pluck('numero')->toArray();
        $paginasCampos = CampoFormulario::where($colunaFk, $contextoId)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        $todasPaginas = array_unique(array_merge($paginasEtapas, $paginasCampos));
        sort($todasPaginas);

        $paginasDisponiveis = array_values(array_filter(array_map('intval', $todasPaginas), fn($p) => $p > 0));

        $fasesDb = FormularioFase::where($colunaFk, $contextoId)
            ->orderBy('data_liberacao', 'asc')
            ->get();

        $fasesConfig = [];
        foreach ($fasesDb as $f) {
            $pags = is_string($f->paginas) ? json_decode($f->paginas, true) : ($f->paginas ?? []);
            $fasesConfig[] = [
                'id'                => $f->id,
                'nome'              => $f->nome,
                'paginas'           => is_array($pags) ? array_values(array_map('strval', $pags)) : [],
                'data_liberacao'    => $f->data_liberacao ? $f->data_liberacao->format('Y-m-d\TH:i') : '',
                'data_encerramento' => $f->data_encerramento ? $f->data_encerramento->format('Y-m-d\TH:i') : '',
                'email_template_id' => $f->email_template_id ? (string) $f->email_template_id : '',
            ];
        }

        $templates = class_exists(EmailTemplate::class)
            ? EmailTemplate::orderBy('nome')->pluck('nome', 'id')->toArray()
            : [];

        return [
            'paginasDisponiveis' => $paginasDisponiveis,
            'fasesConfig'        => $fasesConfig,
            'templatesDeEmail'   => $templates,
        ];
    }

    public function salvarFasesTemporais(string $contextoTipo, int $contextoId, array $fasesConfig): void
    {
        foreach ($fasesConfig as $cfg) {
            $dadosFase = [
                'ciclo_id'          => $contextoTipo === 'ciclo' ? $contextoId : null,
                'formulario_id'     => $contextoTipo === 'formulario' ? $contextoId : null,
                'nome'              => $cfg['nome'],
                'paginas'           => array_values(array_map('intval', $cfg['paginas'] ?? [])),
                'data_liberacao'    => $cfg['data_liberacao'],
                'data_encerramento' => !empty($cfg['data_encerramento']) ? $cfg['data_encerramento'] : null,
                'email_template_id' => !empty($cfg['email_template_id']) ? (int) $cfg['email_template_id'] : null,
            ];

            if (!empty($cfg['id'])) {
                $faseExistente = FormularioFase::find($cfg['id']);
                if ($faseExistente) {
                    $novaData = Carbon::parse($cfg['data_liberacao']);
                    if (!$faseExistente->data_liberacao || !$faseExistente->data_liberacao->equalTo($novaData)) {
                        if ($novaData->isFuture()) {
                            $dadosFase['notificacao_enviada_em'] = null;
                        }
                    }
                    $faseExistente->update($dadosFase);
                }
            } else {
                FormularioFase::create($dadosFase);
            }
        }
    }

    public function limparHtmlQuill(?string $html): string
    {
        if ($html === null) return '';
        $limpo = trim($html);
        if (in_array($limpo, ['<p><br></p>', '<p></p>', '<br>'])) {
            return '';
        }
        if (substr_count($limpo, '<p>') === 1 && str_starts_with($limpo, '<p>') && str_ends_with($limpo, '</p>')) {
            $limpo = substr($limpo, 3, -4);
        }
        return trim($limpo);
    }

    public function reposicionarOrdemCampo(string $colunaFk, int $contextoId, ?int $campoId, int $etapaNova, int $ordemNova): void
    {
        if ($campoId) {
            $campo = CampoFormulario::findOrFail($campoId);
            $ordemAntiga = (int) $campo->ordem;
            $etapaAntiga = (int) $campo->etapa;

            if ($etapaAntiga === $etapaNova) {
                if ($ordemAntiga !== $ordemNova) {
                    if ($ordemNova < $ordemAntiga) {
                        CampoFormulario::where($colunaFk, $contextoId)
                            ->where('etapa', $etapaNova)
                            ->where('tipo', '!=', 'config')
                            ->whereBetween('ordem', [$ordemNova, $ordemAntiga - 1])
                            ->increment('ordem');
                    } else {
                        CampoFormulario::where($colunaFk, $contextoId)
                            ->where('etapa', $etapaNova)
                            ->where('tipo', '!=', 'config')
                            ->whereBetween('ordem', [$ordemAntiga + 1, $ordemNova])
                            ->decrement('ordem');
                    }
                }
            } else {
                CampoFormulario::where($colunaFk, $contextoId)
                    ->where('etapa', $etapaAntiga)
                    ->where('tipo', '!=', 'config')
                    ->where('ordem', '>', $ordemAntiga)
                    ->decrement('ordem');

                CampoFormulario::where($colunaFk, $contextoId)
                    ->where('etapa', $etapaNova)
                    ->where('tipo', '!=', 'config')
                    ->where('ordem', '>=', $ordemNova)
                    ->increment('ordem');
            }
        } else {
            CampoFormulario::where($colunaFk, $contextoId)
                ->where('etapa', $etapaNova)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $ordemNova)
                ->increment('ordem');
        }
    }

    public function sincronizarIrmasComboAcademico(string $colunaFk, int $contextoId, int $campoId, array $configToSave): void
    {
        if (empty($configToSave['combo_group'])) return;

        $irmas = CampoFormulario::where($colunaFk, $contextoId)
            ->where('id', '!=', $campoId)
            ->where('tipo', 'system')
            ->get();

        foreach ($irmas as $irma) {
            $cfgIrma = is_string($irma->configuracoes) ? json_decode($irma->configuracoes, true) : ($irma->configuracoes ?? []);
            if (($cfgIrma['combo_group'] ?? '') === $configToSave['combo_group']) {
                $cfgIrma['unidades_permitidas']    = $configToSave['unidades_permitidas'] ?? [];
                $cfgIrma['cursos_permitidos']      = $configToSave['cursos_permitidos'] ?? [];
                $cfgIrma['turnos_permitidos']      = $configToSave['turnos_permitidos'] ?? [];
                $cfgIrma['filtrar_por_uf']         = $configToSave['filtrar_por_uf'] ?? false;
                $cfgIrma['vincular_curso_unidade'] = $configToSave['vincular_curso_unidade'] ?? true;
                $cfgIrma['vincular_turno_curso']   = $configToSave['vincular_turno_curso'] ?? true;
                $irma->update(['configuracoes' => $cfgIrma]);
            }
        }
    }

    public function gerarCamposEnderecoViaCep(string $contextoTipo, int $contextoId, int $etapa, int $ordemCep, string $cepFieldName, bool $obrigatorio, bool $exibirNoTopo): void
    {
        $colunaFk = $contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
        $camposEndereco = [
            ['sufixo' => 'logradouro',  'label' => 'Endereço / Logradouro', 'largura' => 6, 'obrigatorio' => $obrigatorio],
            ['sufixo' => 'numero',      'label' => 'Número',                'largura' => 3, 'obrigatorio' => $obrigatorio],
            ['sufixo' => 'complemento', 'label' => 'Complemento',           'largura' => 4, 'obrigatorio' => false],
            ['sufixo' => 'bairro',      'label' => 'Bairro',                'largura' => 4, 'obrigatorio' => $obrigatorio],
            ['sufixo' => 'cidade',      'label' => 'Cidade',                'largura' => 3, 'obrigatorio' => $obrigatorio],
            ['sufixo' => 'uf',          'label' => 'Estado (UF)',           'largura' => 1, 'obrigatorio' => $obrigatorio],
        ];

        $ordemAtual = $ordemCep;

        foreach ($camposEndereco as $item) {
            $nomeCampo = $cepFieldName . '_' . $item['sufixo'];

            $jaExiste = CampoFormulario::where($colunaFk, $contextoId)->where('name', $nomeCampo)->exists();
            if ($jaExiste) continue;

            $ordemAtual++;

            CampoFormulario::where($colunaFk, $contextoId)
                ->where('etapa', $etapa)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $ordemAtual)
                ->increment('ordem');

            CampoFormulario::create([
                'ciclo_id'      => $contextoTipo === 'ciclo' ? $contextoId : null,
                'formulario_id' => $contextoTipo === 'formulario' ? $contextoId : null,
                'etapa'         => $etapa,
                'ordem'         => $ordemAtual,
                'label'         => $item['label'],
                'name'          => $nomeCampo,
                'tipo'          => 'text',
                'subtipo'       => 'text',
                'largura'       => $item['largura'],
                'obrigatorio'   => $item['obrigatorio'],
                'configuracoes' => [
                    'viacep_parent'  => $cepFieldName,
                    'viacep_target'  => $item['sufixo'],
                    'exibir_no_topo' => $exibirNoTopo,
                ],
            ]);
        }
    }

    public function gerarCamposComboAcademico(string $contextoTipo, int $contextoId, int $etapa, int $ordemUnidade, string $baseFieldName, bool $obrigatorio, bool $exibirNoTopo, array $cfgBase): void
    {
        $colunaFk = $contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
        $camposCombo = [
            ['sufixo' => 'curso', 'subtipo' => 'curso', 'label' => 'Curso', 'largura' => 4],
            ['sufixo' => 'turno', 'subtipo' => 'turno', 'label' => 'Turno', 'largura' => 4],
        ];

        $ordemAtual = $ordemUnidade;

        foreach ($camposCombo as $item) {
            $nomeCampo = $baseFieldName . '_' . $item['sufixo'];

            $jaExiste = CampoFormulario::where($colunaFk, $contextoId)->where('name', $nomeCampo)->exists();
            if ($jaExiste) continue;

            $ordemAtual++;

            CampoFormulario::where($colunaFk, $contextoId)
                ->where('etapa', $etapa)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $ordemAtual)
                ->increment('ordem');

            CampoFormulario::create([
                'ciclo_id'      => $contextoTipo === 'ciclo' ? $contextoId : null,
                'formulario_id' => $contextoTipo === 'formulario' ? $contextoId : null,
                'etapa'         => $etapa,
                'ordem'         => $ordemAtual,
                'label'         => $item['label'],
                'name'          => $nomeCampo,
                'tipo'          => 'system',
                'subtipo'       => $item['subtipo'],
                'largura'       => $item['largura'],
                'obrigatorio'   => $obrigatorio,
                'configuracoes' => [
                    'combo_group'            => $baseFieldName,
                    'combo_role'             => $item['subtipo'],
                    'combo_parent_unidade'   => $baseFieldName,
                    'combo_parent_curso'     => $baseFieldName . '_curso',
                    'filtrar_por_uf'         => $cfgBase['filtrar_por_uf'] ?? false,
                    'vincular_curso_unidade' => $cfgBase['vincular_curso_unidade'] ?? true,
                    'vincular_turno_curso'   => $cfgBase['vincular_turno_curso'] ?? true,
                    'aplicar_regras'         => ($cfgBase['vincular_curso_unidade'] ?? true) || ($cfgBase['vincular_turno_curso'] ?? true),
                    'unidades_permitidas'    => $cfgBase['unidades_permitidas'] ?? [],
                    'cursos_permitidos'      => $cfgBase['cursos_permitidos'] ?? [],
                    'turnos_permitidos'      => $cfgBase['turnos_permitidos'] ?? [],
                    'exibir_no_topo'         => $exibirNoTopo,
                ],
            ]);
        }
    }

    public function getOperadoresCondicionais(): array
    {
        return [
            '='            => 'Igual a (=)',
            '!='           => 'Diferente de (!=)',
            'contains'     => 'Contém o texto',
            'not_contains' => 'Não contém o texto',
            'starts_with'  => 'Começa com',
            'ends_with'    => 'Termina com',
            'in'           => 'Está entre as opções (vírgula)',
            'not_in'       => 'Não está entre as opções (vírgula)',
            '>'            => 'Maior que (>)',
            '>='           => 'Maior ou igual a (>=)',
            '<'            => 'Menor que (<)',
            '<='           => 'Menor ou igual a (<=)',
            'between'      => 'Entre dois valores (Mín e Máx)',
            'not_empty'    => 'Está preenchido (Não vazio)',
            'empty'        => 'Está vazio (Em branco)',
        ];
    }

    public function getCamposDisponiveisParaCondicao(string $contextoTipo, int $contextoId, ?int $campoIdIgnorar = null): array
    {
        $lista = [];

        if ($contextoTipo === 'ciclo') {
            $lista = [
                'data_nascimento'      => '[Fixo] Data de Nascimento',
                'possui_nome_social'   => '[Fixo] Possui Nome Social? (sim/nao)',
                'possui_deficiencia'   => '[Fixo] Pessoa com Deficiência PcD? (sim/nao)',
                'natureza_deficiencia' => '[Fixo] Natureza da Deficiência',
                'estado'               => '[Fixo] Estado (UF)',
                'cidade'               => '[Fixo] Cidade',
                'regiao'               => '[Fixo] Região (centro, norte, sul, leste, oeste)',
                'unidade'              => '[Fixo] Unidade Selecionada (ID)',
                'curso'                => '[Fixo] Curso Selecionado (ID)',
                'turno'                => '[Fixo] Turno Selecionado (ID)',
            ];
        }

        $colunaFk = $contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
        $campos = CampoFormulario::where($colunaFk, $contextoId)
            ->where('tipo', '!=', 'config')
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        foreach ($campos as $c) {
            if ($campoIdIgnorar && (int) $c->id === (int) $campoIdIgnorar) continue;
            if (in_array($c->tipo, ['html', 'divider', 'media', 'spacer', 'link'], true)) continue;

            $limpo = trim(strip_tags((string) $c->label)) ?: $c->name;
            $lista[$c->name] = "Pág. #{$c->etapa} — " . Str::limit($limpo, 40) . " ({$c->name})";
        }

        return $lista;
    }

    public function converterParaTimestampSeData($valor): ?int
    {
        $s = trim((string) $valor);
        if ($s === '') return null;

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}))?/', $s, $m)) {
            return gmmktime((int) ($m[4] ?? 0), (int) ($m[5] ?? 0), 0, (int) $m[2], (int) $m[3], (int) $m[1]);
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?/', $s, $m)) {
            return gmmktime((int) ($m[4] ?? 0), (int) ($m[5] ?? 0), 0, (int) $m[2], (int) $m[1], (int) $m[3]);
        }

        return null;
    }

    public function avaliarRegraIndividual(array $regra, array $valoresSimulacao = []): bool
    {
        $campo = trim((string) ($regra['campo'] ?? ''));
        $op    = (string) ($regra['operador'] ?? '=');
        $alvo  = (string) ($regra['valor'] ?? '');
        $max   = (string) ($regra['valor_max'] ?? '');

        if ($campo === '') return false;

        $valorSimulado = $valoresSimulacao[$campo] ?? '';

        $norm = function ($v): string {
            $s = mb_strtolower(trim((string) $v), 'UTF-8');
            $unaccent = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            return $unaccent !== false ? $unaccent : $s;
        };

        $valNorm  = $norm($valorSimulado);
        $alvoNorm = $norm($alvo);
        $maxNorm  = $norm($max);

        if ($op === 'empty') return $valNorm === '';
        if ($op === 'not_empty') return $valNorm !== '';
        if ($valNorm === '') return false;

        $dtVal = $this->converterParaTimestampSeData($valNorm);
        $dtTgt = $this->converterParaTimestampSeData($alvoNorm);
        $dtMax = $this->converterParaTimestampSeData($maxNorm);

        if ($dtVal !== null && ($dtTgt !== null || in_array($op, ['in', 'not_in'], true))) {
            $alvosDatas = array_values(array_filter(
                array_map(fn($v) => $this->converterParaTimestampSeData($v), explode(',', $alvoNorm)),
                fn($v) => $v !== null
            ));

            return match ($op) {
                '='       => $dtTgt !== null && $dtVal === $dtTgt,
                '!='      => $dtTgt !== null && $dtVal !== $dtTgt,
                '>'       => $dtTgt !== null && $dtVal > $dtTgt,
                '>='      => $dtTgt !== null && $dtVal >= $dtTgt,
                '<'       => $dtTgt !== null && $dtVal < $dtTgt,
                '<='      => $dtTgt !== null && $dtVal <= $dtTgt,
                'between' => $dtTgt !== null && $dtMax !== null && $dtVal >= min($dtTgt, $dtMax) && $dtVal <= max($dtTgt, $dtMax),
                'in'      => in_array($dtVal, $alvosDatas, true),
                'not_in'  => !in_array($dtVal, $alvosDatas, true),
                default   => false,
            };
        }

        $listaValoresAtuais = array_values(array_filter(array_map($norm, explode(',', (string) $valorSimulado)), fn($i) => $i !== ''));
        $listaAlvos         = array_values(array_filter(array_map($norm, explode(',', $alvo)), fn($i) => $i !== ''));

        $numVal  = is_numeric(str_replace(',', '.', $valNorm)) ? (float) str_replace(',', '.', $valNorm) : null;
        $numAlvo = is_numeric(str_replace(',', '.', $alvoNorm)) ? (float) str_replace(',', '.', $alvoNorm) : null;
        $numMax  = is_numeric(str_replace(',', '.', $maxNorm)) ? (float) str_replace(',', '.', $maxNorm) : null;

        return match ($op) {
            '='            => $valNorm === $alvoNorm || in_array($alvoNorm, $listaValoresAtuais, true),
            '!='           => $valNorm !== $alvoNorm && !in_array($alvoNorm, $listaValoresAtuais, true),
            'contains'     => $alvoNorm !== '' && str_contains($valNorm, $alvoNorm),
            'not_contains' => $alvoNorm !== '' && !str_contains($valNorm, $alvoNorm),
            'starts_with'  => $alvoNorm !== '' && str_starts_with($valNorm, $alvoNorm),
            'ends_with'    => $alvoNorm !== '' && str_ends_with($valNorm, $alvoNorm),
            'in'           => !empty(array_intersect($listaValoresAtuais, $listaAlvos)) || in_array($valNorm, $listaAlvos, true),
            'not_in'       => empty(array_intersect($listaValoresAtuais, $listaAlvos)) && !in_array($valNorm, $listaAlvos, true),
            '>'            => $numVal !== null && $numAlvo !== null && $numVal > $numAlvo,
            '>='           => $numVal !== null && $numAlvo !== null && $numVal >= $numAlvo,
            '<'            => $numVal !== null && $numAlvo !== null && $numVal < $numAlvo,
            '<='           => $numVal !== null && $numAlvo !== null && $numVal <= $numAlvo,
            'between'      => $numVal !== null && $numAlvo !== null && $numMax !== null && $numVal >= min($numAlvo, $numMax) && $numVal <= max($numAlvo, $numMax),
            default        => false,
        };
    }

    public function diagnosticarSimulacao(
        string $modoCondicional,
        array $gruposCondicionais,
        ?string $dependeDe,
        ?string $dependeOperador,
        ?string $dependeValor,
        ?string $dependeValorMax,
        array $valoresSimulacao,
        array $camposDisponiveis
    ): array {
        $grupos = $modoCondicional === 'avancado'
            ? $gruposCondicionais
            : ($modoCondicional === 'simples' && !empty($dependeDe) ? [[
                'id'       => 'grp_simples',
                'conector' => 'AND',
                'regras'   => [[
                    'id'        => 'rule_simples',
                    'conector'  => 'AND',
                    'campo'     => $dependeDe,
                    'operador'  => $dependeOperador ?: '=',
                    'valor'     => $dependeValor,
                    'valor_max' => $dependeValorMax,
                ]],
            ]] : []);

        if (empty($grupos)) {
            return [
                'visivel'          => true,
                'expressao_humana' => 'Sempre visível (sem restrições condicionais).',
                'grupos_status'    => [],
                'regras_status'    => [],
                'campos_usados'    => [],
            ];
        }

        $gruposStatus          = [];
        $regrasStatus          = [];
        $camposUsados          = [];
        $partesExpressaoGrupos = [];
        $resultadoFinal        = null;
        $opLabels              = $this->getOperadoresCondicionais();

        foreach ($grupos as $gIdx => $grupo) {
            $regras            = $grupo['regras'] ?? [];
            $resultadoGrupo    = null;
            $partesRegrasTexto = [];

            foreach ($regras as $rIdx => $regra) {
                $campoNome = trim((string) ($regra['campo'] ?? ''));
                if ($campoNome !== '') {
                    $camposUsados[$campoNome] = $camposDisponiveis[$campoNome] ?? $campoNome;
                }

                $passou = $this->avaliarRegraIndividual($regra, $valoresSimulacao);
                $regrasStatus["{$gIdx}_{$rIdx}"] = $passou;

                $conectorRegra = strtoupper((string) ($regra['conector'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';
                $resultadoGrupo = ($resultadoGrupo === null)
                    ? $passou
                    : (($conectorRegra === 'OR') ? ($resultadoGrupo || $passou) : ($resultadoGrupo && $passou));

                $opSimbolo = $opLabels[$regra['operador'] ?? '='] ?? ($regra['operador'] ?? '=');
                $valTexto  = in_array($regra['operador'] ?? '=', ['empty', 'not_empty'], true)
                    ? ''
                    : (($regra['operador'] ?? '=') === 'between'
                        ? " [{$regra['valor']} até {$regra['valor_max']}]"
                        : " \"{$regra['valor']}\"");

                $prefixoRegra        = $rIdx > 0 ? ($conectorRegra === 'OR' ? ' OU ' : ' E ') : '';
                $partesRegrasTexto[] = $prefixoRegra . '[' . ($campoNome ?: '?') . ' ' . $opSimbolo . $valTexto . ']';
            }

            $resultadoGrupo      = $resultadoGrupo ?? false;
            $gruposStatus[$gIdx] = $resultadoGrupo;

            $conectorGrupo  = strtoupper((string) ($grupo['conector'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';
            $resultadoFinal = ($resultadoFinal === null)
                ? $resultadoGrupo
                : (($conectorGrupo === 'OR') ? ($resultadoFinal || $resultadoGrupo) : ($resultadoFinal && $resultadoGrupo));

            $prefixoGrupo            = $gIdx > 0 ? ($conectorGrupo === 'OR' ? ' OU ' : ' E ') : '';
            $partesExpressaoGrupos[] = $prefixoGrupo . '(' . implode('', $partesRegrasTexto) . ')';
        }

        return [
            'visivel'          => $resultadoFinal ?? true,
            'expressao_humana' => implode('', $partesExpressaoGrupos),
            'grupos_status'    => $gruposStatus,
            'regras_status'    => $regrasStatus,
            'campos_usados'    => $camposUsados,
        ];
    }
}