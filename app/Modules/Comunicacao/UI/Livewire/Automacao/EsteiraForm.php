<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraEtapa;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;
use App\Models\Ciclo;
use App\Models\Formulario;
use App\Models\StatusInscricao;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use App\Models\CampoFormulario;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Facades\DB;

class EsteiraForm extends Component
{
    public ?int $esteiraId = null;
    public string $nome = '';
    public string $descricao = '';
    public bool $status = true;

    // Público-Alvo e Contexto
    public string $publico_alvo = 'inscricoes';
    public $ciclo_id = '';
    public $formulario_id = '';

    // Condições de Segmentação (Múltipla Escolha)
    public array $status_ids = [];
    public array $unidades_ids = [];
    public array $cursos_ids = [];
    public array $turnos_ids = [];
    public string $progresso_preenchimento = 'todos'; // 'todos', 'incompleto', 'finalizado'
    public array $regras_dinamicas = [];
    public string $emails_manuais_texto = '';

    // Condições de Saída (Conversão)
    public bool $sair_ao_concluir_formulario = false;
    public array $status_saida_ids = [];

    // Opções de Cadência
    public bool $revalidar_condicoes_no_envio = true;
    public bool $apenas_horario_comercial = false;

    // Etapas da Esteira (Sequência 1, 2, 3...)
    public array $etapas = [];

    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        $this->breadcrumbs = BreadcrumbHelper::generate($id ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos');

        if ($id) {
            $esteira = EsteiraComunicacao::with('etapas')->findOrFail($id);
            $this->esteiraId = $esteira->id;
            $this->nome = $esteira->nome;
            $this->descricao = $esteira->descricao ?? '';
            $this->status = (bool) $esteira->status;
            $this->publico_alvo = $esteira->publico_alvo;
            $this->ciclo_id = $esteira->ciclo_id ?? '';
            $this->formulario_id = $esteira->formulario_id ?? '';

            $cond = is_array($esteira->condicoes) ? $esteira->condicoes : [];
            $this->status_ids = array_map('strval', $cond['status_ids'] ?? []);
            $this->unidades_ids = array_map('strval', $cond['unidades_ids'] ?? []);
            $this->cursos_ids = array_map('strval', $cond['cursos_ids'] ?? []);
            $this->turnos_ids = array_map('strval', $cond['turnos_ids'] ?? []);
            $this->progresso_preenchimento = $cond['progresso_preenchimento'] ?? 'todos';
            $this->regras_dinamicas = $cond['regras_dinamicas'] ?? [];
            $this->emails_manuais_texto = implode("\n", $cond['emails_manuais'] ?? []);

            $saida = is_array($esteira->condicao_saida) ? $esteira->condicao_saida : [];
            $this->sair_ao_concluir_formulario = (bool) ($saida['sair_ao_concluir_formulario'] ?? false);
            $this->status_saida_ids = array_map('strval', $saida['status_saida_ids'] ?? []);

            $this->revalidar_condicoes_no_envio = (bool) $esteira->revalidar_condicoes_no_envio;
            $this->apenas_horario_comercial = (bool) $esteira->apenas_horario_comercial;

            foreach ($esteira->etapas as $et) {
                $this->etapas[] = [
                    'id'                          => $et->id,
                    'nome'                        => $et->nome,
                    'template_id'                 => (string) $et->template_id,
                    'tipo_acao'                   => $et->tipo_acao ?? 'enviar_email',
                    'tipo_agendamento'            => $et->tipo_agendamento ?? 'data_fixa',
                    'data_disparo'                => $et->data_disparo ? $et->data_disparo->format('Y-m-d\TH:i') : '',
                    'atraso_valor'                => $et->atraso_valor ?? 7,
                    'atraso_unidade'              => $et->atraso_unidade ?? 'dias',
                    'filtro_engajamento_anterior' => $et->filtro_engajamento_anterior ?? 'todos',
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

    public function adicionarEtapa(): void
    {
        $num = count($this->etapas) + 1;
        $this->etapas[] = [
            'id'                          => null,
            'nome'                        => "Etapa {$num} - " . ($num === 1 ? '1º Disparo' : 'Follow-up / Lembrete'),
            'template_id'                 => '',
            'tipo_acao'                   => 'enviar_email',
            'tipo_agendamento'            => $num === 1 ? 'data_fixa' : 'apos_etapa_anterior',
            'data_disparo'                => now()->addHours(1)->format('Y-m-d\TH:i'),
            'atraso_valor'                => 7,
            'atraso_unidade'              => 'dias',
            'filtro_engajamento_anterior' => $num === 1 ? 'todos' : 'nao_clicou',
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

        // Garante que a nova Etapa 1 não fique como "após etapa anterior"
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
            'etapas.*.template_id.required' => 'Selecione o template de e-mail em todas as etapas.',
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
                    'tipo_acao'                   => $et['tipo_acao'] ?? 'enviar_email',
                    'tipo_agendamento'            => ($idx === 0 && $et['tipo_agendamento'] === 'apos_etapa_anterior') ? 'data_fixa' : $et['tipo_agendamento'],
                    'data_disparo'                => $et['tipo_agendamento'] === 'data_fixa' ? $et['data_disparo'] : null,
                    'atraso_valor'                => $et['tipo_agendamento'] === 'apos_etapa_anterior' ? (int) $et['atraso_valor'] : null,
                    'atraso_unidade'              => $et['atraso_unidade'] ?? 'dias',
                    'filtro_engajamento_anterior' => $idx === 0 ? 'todos' : ($et['filtro_engajamento_anterior'] ?? 'todos'),
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
        return redirect()->route('automacoes.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.automacao.esteira-form', [
            'templates'        => EmailTemplate::orderBy('nome')->get(),
            'ciclos'           => Ciclo::orderBy('id', 'desc')->get(),
            'formularios'      => Formulario::orderBy('id', 'desc')->get(),
            'statusInscricoes' => StatusInscricao::orderBy('nome')->get(),
            'unidades'         => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursos'           => Curso::whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'turnos'           => Turno::orderBy('nome')->get(),
        ])->layout('components.layouts.app', [
            'title' => $this->esteiraId ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos'
        ]);
    }
}