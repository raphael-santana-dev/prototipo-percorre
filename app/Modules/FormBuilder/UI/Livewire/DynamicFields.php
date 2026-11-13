<?php

namespace App\Modules\FormBuilder\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\Curso;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\FormBuilder\Services\FormBuilderService;
use App\Traits\WithEditingLock;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DynamicFields extends Component
{
    use WithFileUploads;
    use WithEditingLock;

    public $campoId = null;
    public $etapa = 1;
    public $ordem = 1;
    public $label = '';
    public $name = '';
    public $tipo = 'text';
    public $largura = 12;
    public $subtipo = 'text';
    public $tamanho_min = null;
    public $tamanho_max = null;
    public $regex_mascara = '';
    public $opcoes = '';
    public $obrigatorio = false;
    public $regras_validacao = '';

    public $depende_de = '';
    public $depende_operador = '=';
    public $depende_valor = '';
    public $depende_valor_max = '';

    public string $modoCondicional = 'nenhum';
    public bool $modalCondicoesAvancadasAberto = false;
    public array $gruposCondicionais = [];
    public array $valoresSimulacaoCondicional = [];

    public array $configuracoes = [];
    public $matriz_linhas = '';
    public $matriz_colunas = '';

    public bool $painelCampoAtivo = false;
    public array $camposSelecionados = [];
    public $etapaDestinoLote = '';
    public bool $drawerFormSettingsAberto = false;

    public $slug = '';
    public $bg_image_upload;
    public $link_file_upload;
    public bool $isCicloCustomizado = false;

    public array $formSettings = [
        'bg_image'              => null,
        'bg_color'              => '#ffffff',
        'bg_opacity'            => '0.0',
        'bg_size'               => 'cover',
        'form_width'            => 'max-w-4xl',
        'translucent_card'      => false,
        'use_vacancy_limit'     => false,
        'enable_fixed_personal' => true,
        'enable_fixed_address'  => true,
        'enable_fixed_course'   => true,
    ];

    public string $contextoTipo;
    public int $contextoId;
    public string $contextoNome = '';
    public string $badgeContexto = '';

    public $etapasDisponiveis = [];

    public bool $modalFasesAberto = false;
    public array $fasesConfig = [];
    public array $paginasDisponiveis = [];
    public array $templatesDeEmail = [];

    public bool $modalConfigComboAberto = false;
    public array $comboUnidadesSelecionadas = [];
    public array $comboCursosSelecionados = [];
    public array $comboTurnosSelecionados = [];
    public $comboActiveUnidadeId = null;
    public $comboActiveCursoId = null;

    protected function service(): FormBuilderService
    {
        return app(FormBuilderService::class);
    }

    private function getContextColumn(): string
    {
        return $this->contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
    }

    public function mount($tipo, $id): void
    {
        $this->contextoTipo     = $tipo;
        $this->contextoId       = (int) $id;
        $this->resourceLockType = $tipo;
        $this->resourceLockId   = $this->contextoId;
        $this->checkAndAcquireLock();

        $ctx = $this->service()->resolverContextoInicial($tipo, $this->contextoId);
        $this->contextoNome       = $ctx['nome'];
        $this->isCicloCustomizado = $ctx['isCicloCustomizado'];
        $this->badgeContexto      = $ctx['badge'];
        $this->slug               = $ctx['slug'];

        $this->carregarEtapas();
        if ($this->etapasDisponiveis->count() > 0) {
            $this->etapa = $this->etapasDisponiveis->first()->numero;
        }

        $this->loadFormSettings();
        $this->inicializarDefaultsDicas();
        $this->atualizarProximaOrdem();
        $this->painelCampoAtivo = false;
    }

    // =========================================================================
    // SELEÇÃO EM LOTE E MOVIMENTAÇÃO
    // =========================================================================

    public function updatedCamposSelecionados(): void
    {
        $this->camposSelecionados = array_values(array_unique(array_map('strval', $this->camposSelecionados)));

        if ($this->campoId && count($this->camposSelecionados) === 1 && !in_array((string) $this->campoId, $this->camposSelecionados, true)) {
            $this->camposSelecionados[] = (string) $this->campoId;
        }

        if (count($this->camposSelecionados) > 1) {
            $this->painelCampoAtivo = false;
            $this->campoId = null;
        } elseif (count($this->camposSelecionados) === 1) {
            $unicoId = (int) $this->camposSelecionados[0];
            if ($this->campoId !== $unicoId) {
                $this->editar($unicoId);
            }
        }
    }

    public function toggleSelecionarCampo(int $id): void
    {
        $idStr = (string) $id;
        if ($this->campoId && (string) $this->campoId !== $idStr && !in_array((string) $this->campoId, $this->camposSelecionados, true)) {
            $this->camposSelecionados[] = (string) $this->campoId;
        }

        if (in_array($idStr, $this->camposSelecionados, true)) {
            $this->camposSelecionados = array_values(array_diff($this->camposSelecionados, [$idStr]));
        } else {
            $this->camposSelecionados[] = $idStr;
        }

        $this->updatedCamposSelecionados();
    }

    public function selecionarTodosDaEtapa(?int $numEtapa = null): void
    {
        $alvoEtapa = $numEtapa ?: (int) $this->etapa;
        $idsEtapa  = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('etapa', $alvoEtapa)
            ->where('tipo', '!=', 'config')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $todosJa = !empty($idsEtapa) && empty(array_diff($idsEtapa, $this->camposSelecionados));
        $this->camposSelecionados = $todosJa
            ? array_values(array_diff($this->camposSelecionados, $idsEtapa))
            : array_values(array_unique(array_merge($this->camposSelecionados, $idsEtapa)));

        if (count($this->camposSelecionados) > 1) {
            $this->painelCampoAtivo = false;
            $this->campoId = null;
        }
    }

    public function limparSelecaoCampos(): void
    {
        $this->camposSelecionados = [];
        $this->etapaDestinoLote   = '';
    }

    public function moverCamposSelecionadosParaEtapa(): void
    {
        if ($this->isLockedByOther) return;
        $destino = (int) $this->etapaDestinoLote;
        if ($destino < 1 || empty($this->camposSelecionados)) {
            $this->dispatch('erro', msg: 'Selecione uma página de destino e pelo menos um campo.');
            return;
        }

        $qtd = $this->service()->moverCamposParaEtapa($this->getContextColumn(), $this->contextoId, $this->camposSelecionados, $destino);
        if ($qtd > 0) {
            $this->etapa = $destino;
            $this->limparSelecaoCampos();
            $this->atualizarProximaOrdem();
            $this->dispatch('sucesso', msg: "{$qtd} campo(s) movido(s) para a Página #{$destino}!");
        }
    }

    public function moverSelecionadosNaMesmaEtapa(string $posicao = 'topo'): void
    {
        if ($this->isLockedByOther || empty($this->camposSelecionados)) return;
        $this->service()->moverSelecionadosNaMesmaEtapa($this->getContextColumn(), $this->contextoId, $this->camposSelecionados, $posicao);
        $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Posição dos campos selecionados atualizada!');
    }

    public function excluirCamposSelecionados(): void
    {
        if ($this->isLockedByOther || empty($this->camposSelecionados)) return;
        $qtd = count($this->camposSelecionados);
        $estavaEmEdicao = $this->campoId && in_array((string) $this->campoId, $this->camposSelecionados, true);

        $this->service()->excluirCamposEmLote($this->getContextColumn(), $this->contextoId, $this->camposSelecionados);
        $this->limparSelecaoCampos();

        $estavaEmEdicao ? $this->cancelarEdicao() : $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: "{$qtd} bloco(s) excluído(s) com sucesso!");
    }

    // =========================================================================
    // ETAPAS (PÁGINAS) E FASES TEMPORAIS
    // =========================================================================

    public function carregarEtapas(): void
    {
        $this->etapasDisponiveis = $this->service()->sincronizarEtapas($this->getContextColumn(), $this->contextoId);
    }

    public function adicionarEtapa(): void
    {
        $proxNumero = $this->etapasDisponiveis->max('numero') + 1;
        Etapa::create([$this->getContextColumn() => $this->contextoId, 'numero' => $proxNumero, 'nome' => $proxNumero . 'º Fase']);
        $this->carregarEtapas();
        $this->dispatch('sucesso', msg: 'Nova página adicionada!');
    }

    public function atualizarNomeEtapa($id, $novoNome): void
    {
        if (!empty(trim($novoNome))) {
            Etapa::where('id', $id)->update(['nome' => trim($novoNome)]);
            $this->carregarEtapas();
        }
    }

    public function excluirEtapa($id): void
    {
        $etapa = Etapa::find($id);
        if (!$etapa) return;

        $num = (int) $etapa->numero;
        $col = $this->getContextColumn();

        if ($num === 1) {
            $this->dispatch('erro', msg: 'A 1º Fase é obrigatória e não pode ser excluída.');
            return;
        }
        if (CampoFormulario::where($col, $this->contextoId)->where('etapa', $num)->where('tipo', '!=', 'config')->exists()) {
            $this->dispatch('erro', msg: 'Esta página possui campos. Mova-os ou exclua-os primeiro.');
            return;
        }

        $this->service()->excluirEtapaERenumerar($etapa, $col, $this->contextoId, $num);
        $this->carregarEtapas();

        if ((int) $this->etapa >= $num) {
            $this->etapa = max(1, (int) $this->etapa - 1);
        }
        $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Página removida e numeração atualizada!');
    }

    public function abrirModalFases(): void
    {
        $this->carregarEtapas();
        $dados = $this->service()->carregarDadosModalFases($this->getContextColumn(), $this->contextoId, $this->etapasDisponiveis);
        $this->paginasDisponiveis = $dados['paginasDisponiveis'];
        $this->fasesConfig        = $dados['fasesConfig'];
        $this->templatesDeEmail   = $dados['templatesDeEmail'];
        $this->resetErrorBag();
        $this->modalFasesAberto   = true;
    }

    public function adicionarFase(): void
    {
        $this->fasesConfig[] = ['id' => null, 'nome' => '', 'paginas' => [], 'data_liberacao' => '', 'data_encerramento' => '', 'email_template_id' => ''];
    }

    public function removerFase($index): void
    {
        $faseId = $this->fasesConfig[$index]['id'] ?? null;
        if ($faseId) \App\Models\FormularioFase::where('id', $faseId)->delete();
        unset($this->fasesConfig[$index]);
        $this->fasesConfig = array_values($this->fasesConfig);
    }

    public function salvarFases(): void
    {
        if (!empty($this->fasesConfig)) {
            $this->validate([
                'fasesConfig.*.nome'           => 'required|string|max:255',
                'fasesConfig.*.data_liberacao' => 'required|date',
                'fasesConfig.*.paginas'        => 'required|array|min:1',
            ], [
                'fasesConfig.*.nome.required'           => 'O nome da fase é obrigatório.',
                'fasesConfig.*.data_liberacao.required' => 'Defina a data de liberação da fase.',
                'fasesConfig.*.paginas.required'        => 'Selecione pelo menos uma página.',
                'fasesConfig.*.paginas.min'             => 'Selecione pelo menos uma página.',
            ]);
            $this->service()->salvarFasesTemporais($this->contextoTipo, $this->contextoId, $this->fasesConfig);
        }

        $this->modalFasesAberto = false;
        $this->dispatch('sucesso', msg: 'Fases e bloqueios de data guardados com sucesso!');
    }

    // =========================================================================
    // DICAS, TIPOS DE CAMPO E CONFIGURAÇÕES GLOBAIS
    // =========================================================================

    private function inicializarDefaultsDicas(): void
    {
        $this->configuracoes['dica_modo']      = $this->configuracoes['dica_modo'] ?? 'nenhum';
        $this->configuracoes['dica_1_texto']   = $this->configuracoes['dica_1_texto'] ?? '';
        $this->configuracoes['dica_1_tipo']    = $this->configuracoes['dica_1_tipo'] ?? 'legenda';
        $this->configuracoes['dica_1_trigger'] = $this->configuracoes['dica_1_trigger'] ?? 'hover';
        $this->configuracoes['dica_2_texto']   = $this->configuracoes['dica_2_texto'] ?? '';
        $this->configuracoes['dica_2_tipo']    = $this->configuracoes['dica_2_tipo'] ?? 'tooltip';
        $this->configuracoes['dica_2_trigger'] = $this->configuracoes['dica_2_trigger'] ?? 'hover';
    }

    public function updatedConfiguracoesDicaModo($valor): void
    {
        if ($valor === 'legenda') $this->configuracoes['dica_1_tipo'] = 'legenda';
        elseif ($valor === 'tooltip') $this->configuracoes['dica_1_tipo'] = 'tooltip';
        elseif ($valor === 'ambos') {
            $this->configuracoes['dica_1_tipo'] = $this->configuracoes['dica_1_tipo'] ?? 'legenda';
            $this->configuracoes['dica_2_tipo'] = ($this->configuracoes['dica_1_tipo'] === 'legenda') ? 'tooltip' : 'legenda';
        }
    }

    public function updatedConfiguracoesDica1Tipo($valor): void
    {
        if (($this->configuracoes['dica_modo'] ?? '') === 'ambos') {
            $this->configuracoes['dica_2_tipo'] = ($valor === 'legenda') ? 'tooltip' : 'legenda';
        }
    }

    public function updatedConfiguracoesDica2Tipo($valor): void
    {
        if (($this->configuracoes['dica_modo'] ?? '') === 'ambos') {
            $this->configuracoes['dica_1_tipo'] = ($valor === 'legenda') ? 'tooltip' : 'legenda';
        }
    }

    public function loadFormSettings(): void
    {
        $cfg = CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('name', '_form_config')->first();
        if ($cfg && $cfg->configuracoes) {
            $saved = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
            $this->formSettings = array_merge($this->formSettings, $saved ?? []);
        }
    }

    public function salvarFormSettings(): void
    {
        $tabelaFoco = $this->contextoTipo === 'ciclo' ? 'ciclos' : 'formularios';
        $this->slug = Str::slug($this->slug);

        $this->validate([
            'bg_image_upload' => 'nullable|mimes:jpg,jpeg,png,webp|max:10240',
            'slug'            => ['required', 'regex:/^[a-z0-9\-]+$/', Rule::unique($tabelaFoco, 'slug')->ignore($this->contextoId)],
        ]);

        if ($this->bg_image_upload) {
            $this->formSettings['bg_image'] = 'storage/' . $this->bg_image_upload->store('formularios/bg', 'public');
            $this->bg_image_upload = null;
        }

        ($this->contextoTipo === 'ciclo' ? Ciclo::query() : Formulario::query())->where('id', $this->contextoId)->update(['slug' => $this->slug]);

        CampoFormulario::updateOrCreate(
            [$this->getContextColumn() => $this->contextoId, 'name' => '_form_config'],
            [
                'ciclo_id'      => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
                'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
                'etapa'         => 0,
                'ordem'         => 0,
                'label'         => 'Configurações Globais',
                'tipo'          => 'config',
                'largura'       => 12,
                'configuracoes' => $this->formSettings,
            ]
        );

        $this->drawerFormSettingsAberto = false;
        $this->dispatch('sucesso', msg: 'Tema e aparência aplicados com sucesso!');
    }

    // =========================================================================
    // CRUD DO CAMPO E PROPRIEDADES COMPUTADAS
    // =========================================================================

    public function iniciarNovoCampo(): void
    {
        $this->cancelarEdicao();
        $this->painelCampoAtivo = true;
    }

    public function setTipo($tipo, $subtipo = 'text'): void
    {
        $this->camposSelecionados = [];
        $this->painelCampoAtivo   = true;
        $this->tipo               = $tipo;
        $this->subtipo            = ($tipo === 'file' && $subtipo === 'text') ? 'file' : $subtipo;
        if (!$this->campoId) $this->regex_mascara = '';
        $this->inicializarDefaultsDicas();

        if (in_array($tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']) && !$this->campoId) {
            $this->name = 'ui_' . time() . '_' . rand(10, 99);
        }

        if ($tipo === 'text') {
            $presets = [
                'cpf'   => ['mask' => '999.999.999-99', 'label' => 'CPF', 'name' => 'cpf_', 'w' => 4],
                'cnpj'  => ['mask' => '99.999.999/9999-99', 'label' => 'CNPJ', 'name' => 'cnpj_', 'w' => 4],
                'cep'   => ['mask' => '99999-999', 'label' => 'CEP', 'name' => 'cep_', 'w' => 3],
                'tel'   => ['mask' => '(99) 99999-9999', 'label' => 'Telefone / Celular', 'name' => 'telefone_', 'w' => 4],
                'email' => ['mask' => '', 'label' => 'E-mail', 'name' => 'email_', 'w' => 6],
                'money' => ['mask' => '', 'label' => 'Valor (R$)', 'name' => 'valor_', 'w' => 4],
                'date'  => ['mask' => '', 'label' => 'Data de Nascimento', 'name' => 'data_nascimento_', 'w' => 4],
            ];
            if (isset($presets[$subtipo])) {
                $p = $presets[$subtipo];
                if ($p['mask']) $this->regex_mascara = $p['mask'];
                if (!$this->campoId) {
                    $this->label   = $p['label'];
                    $this->name    = $p['name'] . rand(10, 99);
                    $this->largura = $p['w'];
                }
            }
            if ($subtipo === 'cpf') $this->configuracoes['validar_cpf'] = $this->configuracoes['validar_cpf'] ?? true;
            if ($subtipo === 'cep') {
                $this->configuracoes['viacep']                = $this->configuracoes['viacep'] ?? true;
                $this->configuracoes['criar_campos_endereco'] = $this->configuracoes['criar_campos_endereco'] ?? true;
            }
        } elseif ($tipo === 'file') {
            $this->configuracoes['tipo_arquivo']         = $this->configuracoes['tipo_arquivo'] ?? 'todos';
            $this->configuracoes['extensoes_permitidas'] = $this->configuracoes['extensoes_permitidas'] ?? 'pdf, docx, jpg, png, mp4';
            $this->configuracoes['max_size_mb']          = $this->configuracoes['max_size_mb'] ?? 10;
            $this->configuracoes['max_arquivos']         = $this->configuracoes['max_arquivos'] ?? 5;
        } elseif ($tipo === 'signature') {
            $ass = $this->configuracoes['tipo_assinante'] ?? ($subtipo !== 'text' ? $subtipo : 'usuario');
            $this->configuracoes['tipo_assinante'] = $this->subtipo = $ass;
            if (!$this->campoId) {
                $this->label = $ass === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura';
                $this->name  = Str::slug($this->label, '_') . '_' . rand(10, 99);
            }
        } elseif ($tipo === 'link') {
            $this->subtipo = 'link';
            $this->configuracoes['tipo_destino']      = $this->configuracoes['tipo_destino'] ?? 'externo';
            $this->configuracoes['permitir_download'] = $this->configuracoes['permitir_download'] ?? true;
            $this->configuracoes['abrir_preview']     = $this->configuracoes['abrir_preview'] ?? true;
        } elseif ($tipo === 'system') {
            $this->configuracoes['vincular_curso_unidade'] = $this->configuracoes['vincular_curso_unidade'] ?? true;
            $this->configuracoes['vincular_turno_curso']   = $this->configuracoes['vincular_turno_curso'] ?? true;
            if (!$this->campoId) {
                $this->label   = $subtipo === 'combo_academico' ? 'Unidade' : ucfirst($subtipo);
                $this->name    = ($subtipo === 'combo_academico' ? 'combo_acad_' : "{$subtipo}_") . rand(10, 99);
                $this->largura = 4;
            }
        }

        $this->dispatch('sincronizar-quill-builder');
    }

    public function updatedConfiguracoesTipoAssinante($valor): void
    {
        if ($this->tipo !== 'signature') return;
        $this->subtipo = $valor;
        $this->label   = $valor === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura';
        if (!$this->campoId) $this->name = Str::slug($this->label, '_') . '_' . rand(10, 99);
    }

    public function updatedConfiguracoesTipoArquivo($valor): void
    {
        if ($this->tipo !== 'file') return;
        $this->configuracoes['extensoes_permitidas'] = match ($valor) {
            'imagem'    => 'jpg, jpeg, png, webp, gif',
            'video'     => 'mp4, mov, avi, webm, mkv',
            'documento' => 'pdf, doc, docx, xls, xlsx, txt',
            default     => 'pdf, docx, jpg, png, mp4',
        };
    }

    public function updatedLabel($valor): void
    {
        $limpo = trim(html_entity_decode(strip_tags((string) $valor), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!$this->campoId && !in_array($this->tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link'])) {
            $slug = Str::slug($limpo, '_');
            $this->name = !empty($slug) ? substr($slug, 0, 50) : 'campo_' . time();
        }
    }

    #[Computed]
    public function limiteOrdem(): int
    {
        if (empty($this->etapa)) return 1;
        $count = CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('etapa', $this->etapa)->where('tipo', '!=', 'config')->count();
        if ($this->campoId) {
            $atual = CampoFormulario::find($this->campoId);
            if ($atual && $atual->etapa == $this->etapa) return max(1, $count);
        }
        return $count + 1;
    }

    public function atualizarProximaOrdem(): void
    {
        if (!empty($this->etapa)) $this->ordem = $this->limiteOrdem();
    }

    public function updatedEtapa(): void
    {
        $this->atualizarProximaOrdem();
    }

    // =========================================================================
    // MOTOR DE CONDIÇÕES DE VISIBILIDADE (SIMPLES, AVANÇADO E SIMULADOR)
    // =========================================================================

    #[Computed]
    public function operadoresCondicionais(): array
    {
        return $this->service()->getOperadoresCondicionais();
    }

    #[Computed]
    public function camposDisponiveisParaCondicao(): array
    {
        return $this->service()->getCamposDisponiveisParaCondicao($this->contextoTipo, $this->contextoId, $this->campoId ? (int) $this->campoId : null);
    }

    #[Computed]
    public function diagnosticoSimulacaoCondicional(): array
    {
        return $this->service()->diagnosticarSimulacao(
            $this->modoCondicional,
            $this->gruposCondicionais,
            $this->depende_de,
            $this->depende_operador,
            $this->depende_valor,
            $this->depende_valor_max,
            $this->valoresSimulacaoCondicional,
            $this->camposDisponiveisParaCondicao
        );
    }

    public function updatedModoCondicional(string $modo): void
    {
        if ($modo === 'nenhum') {
            $this->reset(['depende_de', 'depende_valor', 'depende_valor_max', 'gruposCondicionais']);
            $this->depende_operador = '=';
        } elseif ($modo === 'avancado') {
            if (empty($this->gruposCondicionais)) {
                $this->gruposCondicionais = [[
                    'id' => 'grp_' . uniqid(), 'conector' => 'AND',
                    'regras' => [[
                        'id' => 'rule_' . uniqid(), 'conector' => 'AND',
                        'campo' => $this->depende_de ?: '', 'operador' => $this->depende_operador ?: '=',
                        'valor' => $this->depende_valor ?: '', 'valor_max' => $this->depende_valor_max ?: '',
                    ]],
                ]];
            }
            $this->abrirModalCondicoesAvancadas();
        }
    }

    public function abrirModalCondicoesAvancadas(): void
    {
        $this->modoCondicional = 'avancado';
        if (empty($this->gruposCondicionais)) $this->adicionarGrupoCondicional();
        $this->modalCondicoesAvancadasAberto = true;
    }

    public function adicionarGrupoCondicional(string $conector = 'AND'): void
    {
        $this->gruposCondicionais[] = [
            'id' => 'grp_' . uniqid(), 'conector' => in_array($conector, ['AND', 'OR'], true) ? $conector : 'AND',
            'regras' => [['id' => 'rule_' . uniqid(), 'conector' => 'AND', 'campo' => '', 'operador' => '=', 'valor' => '', 'valor_max' => '']],
        ];
    }

    public function removerGrupoCondicional(int $gIndex): void
    {
        unset($this->gruposCondicionais[$gIndex]);
        $this->gruposCondicionais = array_values($this->gruposCondicionais);
        if (empty($this->gruposCondicionais)) $this->adicionarGrupoCondicional();
    }

    public function adicionarRegraNoGrupo(int $gIndex, string $conector = 'AND'): void
    {
        if (!isset($this->gruposCondicionais[$gIndex])) return;
        $this->gruposCondicionais[$gIndex]['regras'][] = [
            'id' => 'rule_' . uniqid(), 'conector' => in_array($conector, ['AND', 'OR'], true) ? $conector : 'AND',
            'campo' => '', 'operador' => '=', 'valor' => '', 'valor_max' => '',
        ];
    }

    public function removerRegraDoGrupo(int $gIndex, int $rIndex): void
    {
        if (!isset($this->gruposCondicionais[$gIndex]['regras'][$rIndex])) return;
        unset($this->gruposCondicionais[$gIndex]['regras'][$rIndex]);
        $this->gruposCondicionais[$gIndex]['regras'] = array_values($this->gruposCondicionais[$gIndex]['regras']);
        if (empty($this->gruposCondicionais[$gIndex]['regras'])) $this->removerGrupoCondicional($gIndex);
    }

    public function confirmarCondicoesAvancadas(): void
    {
        foreach ($this->gruposCondicionais as $gIdx => $grupo) {
            foreach ($grupo['regras'] ?? [] as $regra) {
                if (empty($regra['campo'])) {
                    $this->dispatch('erro', msg: 'Selecione o campo de referência em todas as condições do Grupo #' . ($gIdx + 1) . '.');
                    return;
                }
            }
        }
        $this->modoCondicional = 'avancado';
        $this->modalCondicoesAvancadasAberto = false;
        $this->dispatch('sucesso', msg: 'Condições aplicadas ao input! Clique em Guardar/Atualizar Bloco para persistir.');
    }

    // =========================================================================
    // EDIÇÃO, EXCLUSÃO E PERSISTÊNCIA DO CAMPO
    // =========================================================================

    public function editar($id): void
    {
        $campo = CampoFormulario::findOrFail($id);
        $this->camposSelecionados = [(string) $campo->id];
        $this->painelCampoAtivo   = true;
        $this->campoId            = $campo->id;
        $this->etapa              = $campo->etapa;
        $this->ordem              = $campo->ordem;
        $this->label              = $campo->label;
        $this->name               = $campo->name;
        $this->tipo               = $campo->tipo;
        $this->largura            = $campo->largura;
        $this->subtipo            = $campo->subtipo;
        $this->tamanho_min        = $campo->tamanho_min;
        $this->tamanho_max        = $campo->tamanho_max;
        $this->regex_mascara      = $campo->regex_mascara;
        $this->obrigatorio        = (bool) $campo->obrigatorio;
        $this->regras_validacao   = $campo->regras_validacao;
        $this->depende_de         = $campo->depende_de;
        $this->depende_operador   = $campo->depende_operador ?: '=';
        $this->depende_valor      = $campo->depende_valor;

        $this->opcoes = is_array($campo->opcoes)
            ? (isset($campo->opcoes['origem_bd']) ? 'bd:' . $campo->opcoes['origem_bd'] : implode("\n", $campo->opcoes))
            : ($campo->opcoes ?? '');

        $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
        $this->configuracoes = $config ?: [];
        $this->inicializarDefaultsDicas();

        $condSalvas = $this->configuracoes['condicoes_visibilidade'] ?? null;
        if (is_array($condSalvas) && ($condSalvas['modo'] ?? '') === 'avancado' && !empty($condSalvas['grupos'])) {
            $this->modoCondicional    = 'avancado';
            $this->gruposCondicionais = $condSalvas['grupos'];
        } elseif (!empty($campo->depende_de)) {
            $this->modoCondicional    = 'simples';
            $this->depende_valor_max  = $this->configuracoes['depende_valor_max'] ?? '';
            $this->gruposCondicionais = [];
        } else {
            $this->modoCondicional    = 'nenhum';
            $this->depende_valor_max  = '';
            $this->gruposCondicionais = [];
        }

        if ($campo->tipo === 'matriz') {
            $this->matriz_linhas  = implode("\n", $this->configuracoes['linhas'] ?? []);
            $this->matriz_colunas = implode(', ', $this->configuracoes['colunas'] ?? []);
        }
        if ($campo->tipo === 'link') $this->link_file_upload = null;

        $this->dispatch('sincronizar-quill-builder');
    }

    public function cancelarEdicao(): void
    {
        $this->camposSelecionados = [];
        $this->reset([
            'campoId', 'label', 'name', 'tipo', 'largura', 'subtipo',
            'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 'obrigatorio',
            'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor',
            'configuracoes', 'matriz_linhas', 'matriz_colunas', 'link_file_upload',
            'depende_valor_max', 'modoCondicional', 'gruposCondicionais', 'valoresSimulacaoCondicional',
        ]);
        $this->modoCondicional  = 'nenhum';
        $this->painelCampoAtivo = false;
        $this->inicializarDefaultsDicas();
        $this->atualizarProximaOrdem();
        $this->dispatch('sincronizar-quill-builder');
    }

    public function salvar(): void
    {
        $srv = $this->service();
        $this->label = $srv->limparHtmlQuill($this->label);

        $this->validate([
            'etapa'   => 'required',
            'ordem'   => 'required|integer|min:1|max:' . $this->limiteOrdem(),
            'name'    => ['required', 'regex:/^[a-z0-9_]+$/', Rule::unique('campo_formularios', 'name')->where($this->getContextColumn(), $this->contextoId)->ignore($this->campoId)],
            'tipo'    => 'required',
            'largura' => 'required|integer',
        ]);

        $arrayOpcoes = null;
        if (in_array($this->tipo, ['select', 'radio', 'check']) && !empty($this->opcoes)) {
            $opLimpas = trim($this->opcoes);
            $arrayOpcoes = str_starts_with(strtolower($opLimpas), 'bd:')
                ? ['origem_bd' => trim(substr($opLimpas, 3))]
                : array_values(array_filter(array_map('trim', explode("\n", $this->opcoes)), fn($v) => $v !== ''));
        }

        $cfg = $this->configuracoes ?? [];
        $cfg['dica_1_texto'] = $srv->limparHtmlQuill($cfg['dica_1_texto'] ?? '');
        $cfg['dica_2_texto'] = $srv->limparHtmlQuill($cfg['dica_2_texto'] ?? '');
        if (!empty($cfg['descricao'])) $cfg['descricao'] = $srv->limparHtmlQuill($cfg['descricao']);

        if ($this->tipo === 'matriz') {
            $cfg['linhas']  = array_values(array_filter(array_map('trim', explode("\n", $this->matriz_linhas ?? '')), fn($v) => $v !== ''));
            $cfg['colunas'] = array_values(array_filter(array_map('trim', explode(',', $this->matriz_colunas ?? '')), fn($v) => $v !== ''));
        } elseif ($this->tipo === 'link') {
            if (($cfg['tipo_destino'] ?? 'externo') === 'arquivo') {
                $rule = empty($cfg['arquivo_path']) ? 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480' : 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480';
                $this->validate(['link_file_upload' => $rule]);
                if ($this->link_file_upload) {
                    $path = $this->link_file_upload->store('formularios/links', 'public');
                    $cfg['arquivo_path']     = $path;
                    $cfg['arquivo_nome']     = $this->link_file_upload->getClientOriginalName();
                    $cfg['arquivo_extensao'] = strtolower($this->link_file_upload->getClientOriginalExtension());
                    $cfg['url']              = 'storage/' . $path;
                    $this->link_file_upload  = null;
                }
            } else {
                $this->validate(['configuracoes.url' => 'required|string']);
            }
        }

        $isEditing = (bool) $this->campoId;
        $criarViaCep = ($this->tipo === 'text' && $this->subtipo === 'cep' && !empty($cfg['viacep']) && (!$isEditing || !empty($cfg['criar_campos_endereco'])));
        $criarCombo  = ($this->tipo === 'system' && $this->subtipo === 'combo_academico' && !$isEditing);

        if ($criarCombo) {
            $this->subtipo      = 'unidade';
            $cfg['combo_group'] = $this->name;
            $cfg['combo_role']  = 'unidade';
            $cfg['combo_map']   = ['unidade' => $this->name, 'curso' => $this->name . '_curso', 'turno' => $this->name . '_turno'];
        }

        $srv->reposicionarOrdemCampo($this->getContextColumn(), $this->contextoId, $this->campoId ? (int) $this->campoId : null, (int) $this->etapa, (int) $this->ordem);

        if ($this->modoCondicional === 'nenhum') {
            $this->depende_de = $this->depende_valor = null;
            unset($cfg['condicoes_visibilidade'], $cfg['depende_valor_max']);
        } elseif ($this->modoCondicional === 'simples') {
            $cfg['depende_valor_max']      = $this->depende_valor_max;
            $cfg['condicoes_visibilidade'] = ['modo' => 'simples', 'grupos' => !empty($this->depende_de) ? [['id' => 'grp_simples', 'conector' => 'AND', 'regras' => [['id' => 'rule_simples', 'conector' => 'AND', 'campo' => $this->depende_de, 'operador' => $this->depende_operador ?: '=', 'valor' => (string) $this->depende_valor, 'valor_max' => (string) $this->depende_valor_max]]]] : []];
        } elseif ($this->modoCondicional === 'avancado') {
            $cfg['condicoes_visibilidade'] = ['modo' => 'avancado', 'grupos' => array_values($this->gruposCondicionais)];
            $pRegra = $this->gruposCondicionais[0]['regras'][0] ?? null;
            $this->depende_de       = $pRegra['campo'] ?? null;
            $this->depende_operador = $pRegra['operador'] ?? '=';
            $this->depende_valor    = $pRegra['valor'] ?? null;
        }

        $dados = [
            'ciclo_id'         => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
            'formulario_id'    => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
            'etapa'            => $this->etapa,
            'ordem'            => $this->ordem,
            'label'            => empty(strip_tags($this->label)) ? ($this->tipo === 'spacer' ? 'Espaço em Branco' : 'Campo Visão') : $this->label,
            'name'             => $this->name,
            'tipo'             => $this->tipo,
            'largura'          => $this->largura,
            'subtipo'          => $this->subtipo ?: 'text',
            'tamanho_min'      => $this->tamanho_min ?: null,
            'tamanho_max'      => $this->tamanho_max ?: null,
            'regex_mascara'    => $this->regex_mascara ?: null,
            'opcoes'           => $arrayOpcoes,
            'configuracoes'    => $cfg ?: null,
            'obrigatorio'      => in_array($this->tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']) ? false : (bool) $this->obrigatorio,
            'regras_validacao' => $this->regras_validacao,
            'depende_de'       => $this->depende_de ?: null,
            'depende_operador' => $this->depende_operador ?: '=',
            'depende_valor'    => $this->depende_valor ?: null,
        ];

        if ($this->campoId) {
            CampoFormulario::findOrFail($this->campoId)->update($dados);
            if ($this->tipo === 'system') $srv->sincronizarIrmasComboAcademico($this->getContextColumn(), $this->contextoId, (int) $this->campoId, $cfg);
        } else {
            CampoFormulario::create($dados);
        }

        if ($criarViaCep) $srv->gerarCamposEnderecoViaCep($this->contextoTipo, $this->contextoId, (int) $this->etapa, (int) $this->ordem, $this->name, (bool) $this->obrigatorio, !empty($cfg['exibir_no_topo']));
        if ($criarCombo)  $srv->gerarCamposComboAcademico($this->contextoTipo, $this->contextoId, (int) $this->etapa, (int) $this->ordem, $this->name, (bool) $this->obrigatorio, !empty($cfg['exibir_no_topo']), $cfg);

        $this->cancelarEdicao();
        $this->dispatch('sucesso', msg: 'Bloco configurado e estruturado com sucesso!');
    }

    public function excluir($id): void
    {
        $campo = CampoFormulario::findOrFail($id);
        $etapaExcluida = (int) $campo->etapa;
        $campo->delete();

        $this->service()->reindexarOrdensDaEtapa($this->getContextColumn(), $this->contextoId, $etapaExcluida);
        $this->camposSelecionados = array_values(array_diff($this->camposSelecionados, [(string) $id]));
        $this->campoId == $id ? $this->cancelarEdicao() : $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Bloco removido do formulário!');
    }

    // =========================================================================
    // MODAL DE COMBO ACADÊMICO (UNIDADES / CURSOS / TURNOS)
    // =========================================================================

    public function abrirModalConfigCombo(): void
    {
        $this->comboUnidadesSelecionadas = array_values(array_map('strval', (array) ($this->configuracoes['unidades_permitidas'] ?? [])));
        $this->comboCursosSelecionados   = array_values(array_map('strval', (array) ($this->configuracoes['cursos_permitidos'] ?? [])));
        $this->comboTurnosSelecionados   = array_values(array_map('strval', (array) ($this->configuracoes['turnos_permitidos'] ?? [])));
        if (!empty($this->comboUnidadesSelecionadas) && !$this->comboActiveUnidadeId) {
            $this->comboActiveUnidadeId = $this->comboUnidadesSelecionadas[0];
        }
        $this->modalConfigComboAberto = true;
    }

    public function updatedComboUnidadesSelecionadas(): void
    {
        $ativas = array_map('strval', $this->comboUnidadesSelecionadas);
        $this->comboCursosSelecionados = array_values(array_filter($this->comboCursosSelecionados, fn($i) => in_array(explode('-', (string) $i)[0] ?? '', $ativas, true)));
        $this->comboTurnosSelecionados = array_values(array_filter($this->comboTurnosSelecionados, fn($i) => in_array(explode('-', (string) $i)[0] ?? '', $ativas, true)));
    }

    public function updatedComboCursosSelecionados(): void
    {
        $cursosAtivos = array_map('strval', $this->comboCursosSelecionados);
        foreach ($cursosAtivos as $cKey) {
            if (str_contains($cKey, '-')) {
                [$uId] = explode('-', $cKey, 2);
                if (!in_array($uId, $this->comboUnidadesSelecionadas, true)) $this->comboUnidadesSelecionadas[] = $uId;
            }
        }
        $this->comboTurnosSelecionados = array_values(array_filter($this->comboTurnosSelecionados, function ($item) use ($cursosAtivos) {
            $p = explode('-', (string) $item);
            return count($p) === 3 ? in_array("{$p[0]}-{$p[1]}", $cursosAtivos, true) : true;
        }));
    }

    public function updatedComboTurnosSelecionados(): void
    {
        foreach ($this->comboTurnosSelecionados as $tKey) {
            $p = explode('-', (string) $tKey);
            if (count($p) === 3) {
                [$uId, $cId] = $p;
                if (!in_array($uId, $this->comboUnidadesSelecionadas, true)) $this->comboUnidadesSelecionadas[] = $uId;
                if (!in_array("{$uId}-{$cId}", $this->comboCursosSelecionados, true)) $this->comboCursosSelecionados[] = "{$uId}-{$cId}";
            }
        }
    }

    public function setComboActiveUnidade($id): void { $this->comboActiveUnidadeId = $id; $this->comboActiveCursoId = null; }
    public function setComboActiveCurso($id): void { $this->comboActiveCursoId = $id; }

    public function confirmarConfigComboAcademico(): void
    {
        $this->configuracoes['unidades_permitidas'] = array_values(array_unique(array_map('strval', $this->comboUnidadesSelecionadas)));
        $this->configuracoes['cursos_permitidos']   = array_values(array_unique(array_map('strval', $this->comboCursosSelecionados)));
        $this->configuracoes['turnos_permitidos']   = array_values(array_unique(array_map('strval', $this->comboTurnosSelecionados)));
        $this->modalConfigComboAberto = false;
        $this->dispatch('sucesso', msg: 'Opções aplicadas ao campo dinâmico!');
    }

    public function limparRestricaoComboAcademico(): void
    {
        $this->comboUnidadesSelecionadas = $this->comboCursosSelecionados = $this->comboTurnosSelecionados = [];
        $this->configuracoes['unidades_permitidas'] = $this->configuracoes['cursos_permitidos'] = $this->configuracoes['turnos_permitidos'] = [];
    }

    public function render()
    {
        $camposCadastrados = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('tipo', '!=', 'config')
            ->orderBy('etapa')->orderBy('ordem')->orderBy('id')->get();

        return view('livewire.form-builder.dynamic-fields', [
            'camposCadastrados' => $camposCadastrados,
            'camposPorEtapa'    => $camposCadastrados->groupBy('etapa'),
            'unidadesDb'        => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb'          => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
        ])->layout('components.layouts.app', ['title' => 'Construtor de Formulário']);
    }
}