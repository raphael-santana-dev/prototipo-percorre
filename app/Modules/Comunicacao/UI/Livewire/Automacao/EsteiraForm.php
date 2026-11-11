<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraEtapa;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Models\Ciclo;
use App\Models\Formulario;
use App\Models\StatusInscricao;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\CampoFormulario;
use Illuminate\Support\Facades\DB;

class EsteiraForm extends Component
{
    public ?int $esteiraId = null;
    public string $nome = '';
    public string $descricao = '';
    public bool $status = true;

    public string $publico_alvo = 'inscricoes';
    public $ciclo_id = '';
    public $formulario_id = '';

    public array $status_ids = [];
    public array $unidades_ids = [];
    public array $cursos_ids = [];
    public array $turnos_ids = [];
    public string $progresso_preenchimento = 'todos';
    public array $regras_dinamicas = [];
    public string $emails_manuais_texto = '';

    public bool $modalConfigComboAberto = false;
    public array $comboUnidadesSelecionadas = [];
    public array $comboCursosSelecionados = [];
    public array $comboTurnosSelecionados = [];
    public $comboActiveUnidadeId = null;
    public $comboActiveCursoId = null;

    public bool $sair_ao_concluir_formulario = false;
    public array $status_saida_ids = [];

    public bool $revalidar_condicoes_no_envio = true;
    public bool $apenas_horario_comercial = false;

    public array $etapas = [];
    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Esteiras de Disparo', 'url' => route('esteiras.index')],
            ['label' => $id ? 'Editar Esteira' : 'Nova Esteira', 'url' => '#'],
        ];

        if ($id) {
            $esteira = EsteiraComunicacao::with('etapas.template')->findOrFail($id);
            $this->esteiraId = $esteira->id;
            $this->nome = $esteira->nome;
            $this->descricao = $esteira->descricao ?? '';
            $this->status = (bool) $esteira->status;
            $this->publico_alvo = $esteira->publico_alvo;
            $this->ciclo_id = $esteira->ciclo_id ?? '';
            $this->formulario_id = $esteira->formulario_id ?? '';

            $cond = is_array($esteira->condicoes) ? $esteira->condicoes : [];
            $this->status_ids = array_values(array_map('strval', $cond['status_ids'] ?? []));
            $this->unidades_ids = array_values(array_map('strval', $cond['unidades_ids'] ?? []));
            $this->cursos_ids = array_values(array_map('strval', $cond['cursos_ids'] ?? []));
            $this->turnos_ids = array_values(array_map('strval', $cond['turnos_ids'] ?? []));
            $this->progresso_preenchimento = $cond['progresso_preenchimento'] ?? 'todos';
            $this->regras_dinamicas = $cond['regras_dinamicas'] ?? [];
            $this->emails_manuais_texto = implode("\n", $cond['emails_manuais'] ?? []);

            $saida = is_array($esteira->condicao_saida) ? $esteira->condicao_saida : [];
            $this->sair_ao_concluir_formulario = (bool) ($saida['sair_ao_concluir_formulario'] ?? false);
            $this->status_saida_ids = array_values(array_map('strval', $saida['status_saida_ids'] ?? []));

            $this->revalidar_condicoes_no_envio = (bool) $esteira->revalidar_condicoes_no_envio;
            $this->apenas_horario_comercial = (bool) $esteira->apenas_horario_comercial;

            foreach ($esteira->etapas as $et) {
                $condEtapa = is_array($et->condicoes_etapa) ? $et->condicoes_etapa : [];
                $this->etapas[] = [
                    'id'                          => $et->id,
                    'nome'                        => $et->nome,
                    'template_id'                 => (string) $et->template_id,
                    'canal_disparo'               => $et->canal_disparo ?? ($et->template->canal ?? 'email'),
                    'tipo_acao'                   => $et->tipo_acao ?? 'enviar_email',
                    'tipo_agendamento'            => $et->tipo_agendamento ?? 'data_fixa',
                    'data_disparo'                => $et->data_disparo ? $et->data_disparo->format('Y-m-d\TH:i') : '',
                    'atraso_valor'                => $et->atraso_valor ?? 7,
                    'atraso_unidade'              => $et->atraso_unidade ?? 'dias',
                    'filtro_engajamento_anterior' => $et->filtro_engajamento_anterior ?? 'todos',
                    'variaveis_map'               => $condEtapa['variaveis_map'] ?? $this->extrairMapaPadraoDoTemplate($et->template),
                ];
            }
        } else {
            $cicloPadrao = Ciclo::where('status', true)->latest('id')->first() ?? Ciclo::latest('id')->first();
            if ($cicloPadrao) {
                $this->ciclo_id = $cicloPadrao->id;
            }
            $this->adicionarEtapa();
        }
    }

    private function extrairMapaPadraoDoTemplate(?EmailTemplate $tpl): array
    {
        if (!$tpl) return [];
        $vars = EmailParserService::extrairVariaveisDoCorpo($tpl->corpo);
        $cfg  = is_array($tpl->configuracoes_suri) ? $tpl->configuracoes_suri : (json_decode($tpl->configuracoes_suri ?? '{}', true) ?: []);
        $salvo = is_array($cfg['variable_map'] ?? null) ? $cfg['variable_map'] : [];

        $mapa = [];
        foreach ($vars as $v) {
            $mapa[$v] = $salvo[$v] ?? (is_numeric($v) ? 'nome' : strtolower($v));
        }
        return $mapa;
    }

    public function updatedEtapas($value, $key): void
    {
        // Quando o usuário troca o template_id de uma etapa (ex: etapas.0.template_id), carrega as variáveis dele
        if (preg_match('/^(\d+)\.template_id$/', (string) $key, $m)) {
            $idx = (int) $m[1];
            $tpl = $value ? EmailTemplate::find($value) : null;
            $this->etapas[$idx]['variaveis_map'] = $this->extrairMapaPadraoDoTemplate($tpl);
        }
    }

    public function abrirModalConfigCombo(): void
    {
        $this->comboUnidadesSelecionadas = array_values(array_map('strval', $this->unidades_ids));
        $this->comboCursosSelecionados   = array_values(array_map('strval', $this->cursos_ids));
        $this->comboTurnosSelecionados   = array_values(array_map('strval', $this->turnos_ids));

        if (!empty($this->comboUnidadesSelecionadas) && !$this->comboActiveUnidadeId) {
            $this->comboActiveUnidadeId = $this->comboUnidadesSelecionadas[0];
        }

        $this->modalConfigComboAberto = true;
    }

    public function updatedComboUnidadesSelecionadas(): void
    {
        $unidadesAtivas = array_map('strval', $this->comboUnidadesSelecionadas);

        $this->comboCursosSelecionados = array_values(array_filter($this->comboCursosSelecionados, function ($item) use ($unidadesAtivas) {
            $uId = explode('-', (string) $item)[0] ?? '';
            return in_array($uId, $unidadesAtivas, true);
        }));

        $this->comboTurnosSelecionados = array_values(array_filter($this->comboTurnosSelecionados, function ($item) use ($unidadesAtivas) {
            $uId = explode('-', (string) $item)[0] ?? '';
            return in_array($uId, $unidadesAtivas, true);
        }));
    }

    public function updatedComboCursosSelecionados(): void
    {
        $cursosAtivos = array_map('strval', $this->comboCursosSelecionados);

        foreach ($cursosAtivos as $cKey) {
            if (str_contains($cKey, '-')) {
                [$uId] = explode('-', $cKey, 2);
                if (!in_array($uId, $this->comboUnidadesSelecionadas, true)) {
                    $this->comboUnidadesSelecionadas[] = $uId;
                }
            }
        }

        $this->comboTurnosSelecionados = array_values(array_filter($this->comboTurnosSelecionados, function ($item) use ($cursosAtivos) {
            $partes = explode('-', (string) $item);
            if (count($partes) === 3) {
                $chaveCurso = $partes[0] . '-' . $partes[1];
                return in_array($chaveCurso, $cursosAtivos, true);
            }
            return true;
        }));
    }

    public function updatedComboTurnosSelecionados(): void
    {
        foreach ($this->comboTurnosSelecionados as $tKey) {
            $partes = explode('-', (string) $tKey);
            if (count($partes) === 3) {
                [$uId, $cId] = $partes;
                $chaveCurso = "{$uId}-{$cId}";

                if (!in_array($uId, $this->comboUnidadesSelecionadas, true)) {
                    $this->comboUnidadesSelecionadas[] = $uId;
                }
                if (!in_array($chaveCurso, $this->comboCursosSelecionados, true)) {
                    $this->comboCursosSelecionados[] = $chaveCurso;
                }
            }
        }
    }

    public function setComboActiveUnidade($id): void
    {
        $this->comboActiveUnidadeId = $id;
        $this->comboActiveCursoId = null;
    }

    public function setComboActiveCurso($id): void
    {
        $this->comboActiveCursoId = $id;
    }

    public function confirmarConfigComboAcademico(): void
    {
        $this->unidades_ids = array_values(array_unique(array_map('strval', $this->comboUnidadesSelecionadas)));
        $this->cursos_ids   = array_values(array_unique(array_map('strval', $this->comboCursosSelecionados)));
        $this->turnos_ids   = array_values(array_unique(array_map('strval', $this->comboTurnosSelecionados)));

        $this->modalConfigComboAberto = false;
        $this->dispatch('sucesso', msg: 'Filtro de Unidades, Cursos e Turnos aplicado à esteira!');
    }

    public function limparRestricaoComboAcademico(): void
    {
        $this->comboUnidadesSelecionadas = [];
        $this->comboCursosSelecionados   = [];
        $this->comboTurnosSelecionados   = [];
        $this->unidades_ids = [];
        $this->cursos_ids   = [];
        $this->turnos_ids   = [];
    }

    public function adicionarEtapa(): void
    {
        $num = count($this->etapas) + 1;
        $this->etapas[] = [
            'id'                          => null,
            'nome'                        => "Etapa {$num} - " . ($num === 1 ? '1º Disparo' : 'Follow-up / Lembrete'),
            'template_id'                 => '',
            'canal_disparo'               => 'email',
            'tipo_acao'                   => 'enviar_email',
            'tipo_agendamento'            => $num === 1 ? 'data_fixa' : 'apos_etapa_anterior',
            'data_disparo'                => now()->addHours(1)->format('Y-m-d\TH:i'),
            'atraso_valor'                => 7,
            'atraso_unidade'              => 'dias',
            'filtro_engajamento_anterior' => $num === 1 ? 'todos' : 'nao_clicou',
            'variaveis_map'               => [],
        ];
    }

    public function removerEtapa(int $index): void
    {
        if (count($this->etapas) <= 1) {
            $this->dispatch('erro', msg: 'A esteira precisa ter pelo menos 1 etapa de disparo.');
            return;
        }
        unset($this->etapas[$index]);
        $this->etapas = array_values($this->etapas);

        if (($this->etapas[0]['tipo_agendamento'] ?? '') === 'apos_etapa_anterior') {
            $this->etapas[0]['tipo_agendamento'] = 'data_fixa';
        }
    }

    public function adicionarRegraDinamica(): void
    {
        $this->regras_dinamicas[] = [
            'campo'    => '',
            'operador' => '=',
            'valor'    => '',
        ];
    }

    public function removerRegraDinamica(int $index): void
    {
        unset($this->regras_dinamicas[$index]);
        $this->regras_dinamicas = array_values($this->regras_dinamicas);
    }

    #[Computed]
    public function camposDinamicosDisponiveis()
    {
        if ($this->publico_alvo === 'inscricoes' && $this->ciclo_id) {
            return CampoFormulario::where('ciclo_id', $this->ciclo_id)
                ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
                ->orderBy('etapa')
                ->orderBy('ordem')
                ->get();
        }
        if ($this->publico_alvo === 'formulario_publico' && $this->formulario_id) {
            return CampoFormulario::where('formulario_id', $this->formulario_id)
                ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
                ->orderBy('etapa')
                ->orderBy('ordem')
                ->get();
        }
        return collect();
    }

    #[Computed]
    public function estimativaPublicoAtual(): int
    {
        $temp = new EsteiraComunicacao([
            'publico_alvo'   => $this->publico_alvo,
            'ciclo_id'       => $this->publico_alvo === 'inscricoes' ? ($this->ciclo_id ?: null) : null,
            'formulario_id'  => $this->publico_alvo === 'formulario_publico' ? ($this->formulario_id ?: null) : null,
            'condicoes'      => $this->montarArrayCondicoes(),
            'condicao_saida' => [
                'sair_ao_concluir_formulario' => $this->sair_ao_concluir_formulario,
                'status_saida_ids'            => $this->status_saida_ids,
            ],
        ]);

        return EsteiraComunicacaoService::contarPublicoElegivel($temp);
    }

    private function montarArrayCondicoes(): array
    {
        $emailsManuais = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $this->emails_manuais_texto))));

        return [
            'status_ids'              => array_values(array_map('strval', $this->status_ids)),
            'unidades_ids'            => array_values(array_map('strval', $this->unidades_ids)),
            'cursos_ids'              => array_values(array_map('strval', $this->cursos_ids)),
            'turnos_ids'              => array_values(array_map('strval', $this->turnos_ids)),
            'progresso_preenchimento' => $this->progresso_preenchimento,
            'regras_dinamicas'        => array_values(array_filter($this->regras_dinamicas, fn($r) => !empty($r['campo']))),
            'emails_manuais'          => $emailsManuais,
        ];
    }

    public function previewTemplate(int $templateId): void
    {
        $template = EmailTemplate::find($templateId);
        if (!$template) {
            $this->dispatch('erro', msg: 'Template não encontrado.');
            return;
        }

        $canal = strtoupper($template->canal ?? 'email');
        $this->dispatch('load-quick-view', [
            'title'    => 'Pré-visualização: ' . $template->nome,
            'subtitle' => "Canal: {$canal}",
            'icon'     => 'ph-eye',
            'maxWidth' => '2xl',
            'data'     => [
                'Assunto'  => '<span class="font-bold text-sm">' . e($template->assunto ?: $template->nome) . '</span>',
                'Mensagem' => '<div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl prose prose-sm max-w-none">' . $template->corpo . '</div>',
            ],
        ]);
    }

    public function salvar()
    {
        $this->validate([
            'nome'                      => 'required|string|min:3|max:255',
            'publico_alvo'              => 'required|in:inscricoes,formulario_publico,estudantes,usuarios,lista_manual',
            'etapas'                    => 'required|array|min:1',
            'etapas.*.nome'             => 'required|string|max:255',
            'etapas.*.template_id'      => 'required|exists:email_templates,id',
            'etapas.*.tipo_agendamento' => 'required|in:data_fixa,apos_etapa_anterior,imediato',
        ], [
            'nome.required'                 => 'Informe o nome da esteira de comunicação.',
            'etapas.*.nome.required'        => 'Informe o título de cada etapa.',
            'etapas.*.template_id.required' => 'Selecione o template em todas as etapas.',
        ]);

        foreach ($this->etapas as $idx => $et) {
            if ($et['tipo_agendamento'] === 'data_fixa' && empty($et['data_disparo'])) {
                $this->addError("etapas.{$idx}.data_disparo", 'Informe a data e hora do disparo desta etapa.');
                return;
            }
            if ($et['tipo_agendamento'] === 'apos_etapa_anterior' && (empty($et['atraso_valor']) || (int) $et['atraso_valor'] < 1)) {
                $this->addError("etapas.{$idx}.atraso_valor", 'Informe um intervalo válido (mínimo 1).');
                return;
            }

            $numEtapa = $idx + 1;
            $canalEtapa = $et['canal_disparo'] ?? 'email';
            $tpl = EmailTemplate::find($et['template_id']);

            if ($tpl) {
                $canalTpl = $tpl->canal ?? 'email';
                $compativel = match ($canalEtapa) {
                    'email'    => in_array($canalTpl, ['email', 'ambos'], true),
                    'whatsapp' => in_array($canalTpl, ['whatsapp', 'ambos'], true),
                    'ambos'    => $canalTpl === 'ambos',
                    default    => false,
                };

                if (!$compativel) {
                    $this->addError("etapas.{$idx}.template_id", "Etapa #{$numEtapa}: O template '{$tpl->nome}' ({$canalTpl}) não é compatível com o disparo '{$canalEtapa}'.");
                    return;
                }

                if (in_array($canalEtapa, ['whatsapp', 'ambos'], true) && empty($tpl->suri_template_id)) {
                    $this->addError("etapas.{$idx}.template_id", "Etapa #{$numEtapa}: O template '{$tpl->nome}' não está sincronizado na Suri.");
                    return;
                }
            }
        }

        DB::transaction(function () {
            $dadosEsteira = [
                'nome'                         => $this->nome,
                'descricao'                    => $this->descricao,
                'status'                       => $this->status,
                'publico_alvo'                 => $this->publico_alvo,
                'ciclo_id'                     => $this->publico_alvo === 'inscricoes' ? ($this->ciclo_id ?: null) : null,
                'formulario_id'                => $this->publico_alvo === 'formulario_publico' ? ($this->formulario_id ?: null) : null,
                'condicoes'                    => $this->montarArrayCondicoes(),
                'condicao_saida'               => [
                    'sair_ao_concluir_formulario' => $this->sair_ao_concluir_formulario,
                    'status_saida_ids'            => array_values(array_map('strval', $this->status_saida_ids)),
                ],
                'revalidar_condicoes_no_envio' => $this->revalidar_condicoes_no_envio,
                'apenas_horario_comercial'     => $this->apenas_horario_comercial,
                'criado_por'                   => auth()->id(),
            ];

            if ($this->esteiraId) {
                $esteira = EsteiraComunicacao::findOrFail($this->esteiraId);
                $esteira->update($dadosEsteira);
            } else {
                $esteira = EsteiraComunicacao::create($dadosEsteira);
                $this->esteiraId = $esteira->id;
            }

            $idsMantidos = [];
            foreach ($this->etapas as $idx => $et) {
                $dadosEtapa = [
                    'esteira_id'                  => $esteira->id,
                    'ordem'                       => $idx + 1,
                    'nome'                        => $et['nome'],
                    'template_id'                 => (int) $et['template_id'],
                    'canal_disparo'               => $et['canal_disparo'] ?? 'email',
                    'tipo_acao'                   => $et['tipo_acao'] ?? 'enviar_email',
                    'tipo_agendamento'            => ($idx === 0 && $et['tipo_agendamento'] === 'apos_etapa_anterior') ? 'data_fixa' : $et['tipo_agendamento'],
                    'data_disparo'                => $et['tipo_agendamento'] === 'data_fixa' ? $et['data_disparo'] : null,
                    'atraso_valor'                => $et['tipo_agendamento'] === 'apos_etapa_anterior' ? (int) $et['atraso_valor'] : null,
                    'atraso_unidade'              => $et['atraso_unidade'] ?? 'dias',
                    'filtro_engajamento_anterior' => $idx === 0 ? 'todos' : ($et['filtro_engajamento_anterior'] ?? 'todos'),
                    'condicoes_etapa'             => [
                        'variaveis_map' => $et['variaveis_map'] ?? [],
                    ],
                ];

                if (!empty($et['id'])) {
                    $etapaModel = EsteiraEtapa::where('esteira_id', $esteira->id)->find($et['id']);
                    if ($etapaModel) {
                        $etapaModel->update($dadosEtapa);
                        $idsMantidos[] = $etapaModel->id;
                        continue;
                    }
                }

                $novaEtapa = EsteiraEtapa::create($dadosEtapa);
                $idsMantidos[] = $novaEtapa->id;
            }

            EsteiraEtapa::where('esteira_id', $esteira->id)->whereNotIn('id', $idsMantidos)->delete();
        });

        $this->dispatch('sucesso', msg: 'Esteira de disparos configurada e agendada com sucesso!');
        return redirect()->route('esteiras.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.automacao.esteira-form', [
            'templates'        => EmailTemplate::orderBy('nome')->get(),
            'colunasBanco'     => EmailParserService::getColunasBancoDisponiveis(),
            'ciclos'           => Ciclo::orderBy('id', 'desc')->get(),
            'formularios'      => Formulario::orderBy('id', 'desc')->get(),
            'statusInscricoes' => StatusInscricao::orderBy('nome')->get(),
            'unidadesDb'       => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb'         => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
        ])->layout('components.layouts.app', [
            'title' => $this->esteiraId ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos'
        ]);
    }
}