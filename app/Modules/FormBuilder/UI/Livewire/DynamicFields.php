<?php

namespace App\Modules\FormBuilder\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Traits\WithEditingLock;

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

    public array $configuracoes = [];
    public $matriz_linhas = '';
    public $matriz_colunas = '';

    // Controle de exibição do painel direito de configuração do input
    public bool $painelCampoAtivo = false;

    // Seleção múltipla de inputs para movimentação em lote
    public array $camposSelecionados = [];
    public $etapaDestinoLote = '';

    public bool $drawerFormSettingsAberto = false;

    public $slug = '';
    public $bg_image_upload;
    public $link_file_upload;
    public bool $isCicloCustomizado = false;

    public array $formSettings = [
        'bg_image' => null,
        'bg_color' => '#ffffff',
        'bg_opacity' => '0.0',
        'bg_size' => 'cover', 
        'form_width' => 'max-w-4xl',
        'translucent_card' => false,
        'use_vacancy_limit' => false,
        'enable_fixed_personal' => true,
        'enable_fixed_address' => true,
        'enable_fixed_course' => true,
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

    public function mount($tipo, $id)
    {
        $this->contextoTipo = $tipo;
        $this->contextoId = (int) $id;

        // REGISTO DE LOCK (BLOQUEIO DE EDIÇÃO SIMULTÂNEA)
        $this->resourceLockType = $tipo; // 'formulario' ou 'ciclo'
        $this->resourceLockId = $this->contextoId;
        $this->checkAndAcquireLock();

        if ($tipo === 'ciclo') {
            $model = \App\Models\Ciclo::findOrFail($id);
            $this->contextoNome = $model->nome;
            $this->isCicloCustomizado = ($model->tipo_ciclo ?? 'regular') === 'customizado';
            $this->badgeContexto = $this->isCicloCustomizado ? 'Projeto Customizado' : 'Inscrição Oficial';
        } else {
            $model = \App\Models\Formulario::with(['faseAprendizagem.ciclo', 'cicloSeletivo', 'unidade', 'curso'])->findOrFail($id);
            
            if ($model->tipo === 'aprendizagem') {
                $cicloNome = $model->faseAprendizagem->ciclo->nome ?? 'Ciclo Indefinido';
                $faseNome = $model->faseAprendizagem->nome ?? 'Fase Indefinida';
                $this->contextoNome = "{$cicloNome} ({$faseNome})";
                $this->badgeContexto = 'Avaliação de Aprendizagem';
                
            } elseif ($model->tipo === 'pre_inscricao') {
                $detalhes = [];
                if ($model->unidade) $detalhes[] = $model->unidade->nome;
                if ($model->curso) $detalhes[] = $model->curso->nome;
                
                $sufixo = count($detalhes) > 0 ? ' - ' . implode(' | ', $detalhes) : ' (Captação Geral)';
                $this->contextoNome = $model->titulo . $sufixo;
                $this->badgeContexto = 'Pré-Inscrição (Lead)';
                
            } else {
                $this->contextoNome = $model->titulo;
                $this->badgeContexto = 'Formulário Avulso';
            }
        }

        $this->slug = $model->slug ?? '';

        $this->carregarEtapas();

        if ($this->etapasDisponiveis->count() > 0) {
            $this->etapa = $this->etapasDisponiveis->first()->numero;
        }
        
        $this->loadFormSettings();
        $this->inicializarDefaultsDicas();
        $this->atualizarProximaOrdem();
        $this->painelCampoAtivo = false;
    }

    public function updatedCamposSelecionados(): void
    {
        $this->camposSelecionados = array_values(array_unique(array_map('strval', $this->camposSelecionados)));

        // Se o utilizador tinha 1 campo aberto em edição ($campoId) e marcou outro checkbox diferente,
        // adiciona automaticamente o campo que estava em edição ao lote para formar os 2 selecionados:
        if ($this->campoId && count($this->camposSelecionados) === 1 && !in_array((string) $this->campoId, $this->camposSelecionados, true)) {
            $this->camposSelecionados[] = (string) $this->campoId;
        }

        // Quando houver mais de 1 input selecionado, oculta imediatamente a configuração individual
        if (count($this->camposSelecionados) > 1) {
            $this->painelCampoAtivo = false;
            $this->campoId = null;
        } elseif (count($this->camposSelecionados) === 1) {
            // Se restar apenas 1 selecionado, carrega-o para edição
            $unicoId = (int) $this->camposSelecionados[0];
            if ($this->campoId !== $unicoId) {
                $this->editar($unicoId);
            }
        }
    }

    public function toggleSelecionarCampo(int $id): void
    {
        $idStr = (string) $id;

        // Se já havia um campo diferente aberto em edição ($campoId), inclui-o na seleção múltipla
        if ($this->campoId && (string) $this->campoId !== $idStr && !in_array((string) $this->campoId, $this->camposSelecionados, true)) {
            $this->camposSelecionados[] = (string) $this->campoId;
        }

        if (in_array($idStr, $this->camposSelecionados, true)) {
            $this->camposSelecionados = array_values(array_diff($this->camposSelecionados, [$idStr]));
        } else {
            $this->camposSelecionados[] = $idStr;
        }

        $this->camposSelecionados = array_values(array_unique(array_map('strval', $this->camposSelecionados)));

        if (count($this->camposSelecionados) > 1) {
            $this->painelCampoAtivo = false;
            $this->campoId = null;
        }
    }

    public function selecionarTodosDaEtapa(?int $numEtapa = null): void
    {
        $alvoEtapa = $numEtapa ?: (int) $this->etapa;

        $idsEtapa = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('etapa', $alvoEtapa)
            ->where('tipo', '!=', 'config')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $todosJaSelecionados = !empty($idsEtapa) && empty(array_diff($idsEtapa, $this->camposSelecionados));

        if ($todosJaSelecionados) {
            $this->camposSelecionados = array_values(array_diff($this->camposSelecionados, $idsEtapa));
        } else {
            $this->camposSelecionados = array_values(array_unique(array_merge($this->camposSelecionados, $idsEtapa)));
        }

        if (count($this->camposSelecionados) > 1) {
            $this->painelCampoAtivo = false;
            $this->campoId = null;
        }
    }

    public function limparSelecaoCampos(): void
    {
        $this->camposSelecionados = [];
        $this->etapaDestinoLote = '';
    }

    public function moverCamposSelecionadosParaEtapa(): void
    {
        if ($this->isLockedByOther) return;

        $destino = (int) $this->etapaDestinoLote;
        if ($destino < 1 || empty($this->camposSelecionados)) {
            $this->dispatch('erro', msg: 'Selecione uma página de destino e pelo menos um campo.');
            return;
        }

        $colunaFk = $this->getContextColumn();

        $camposMover = CampoFormulario::where($colunaFk, $this->contextoId)
            ->whereIn('id', $this->camposSelecionados)
            ->where('tipo', '!=', 'config')
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        if ($camposMover->isEmpty()) {
            return;
        }

        $etapasAfetadas = $camposMover->pluck('etapa')->push($destino)->unique()->values()->toArray();

        DB::transaction(function () use ($colunaFk, $destino, $camposMover, $etapasAfetadas) {
            $maxOrdemDestino = CampoFormulario::where($colunaFk, $this->contextoId)
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
                $this->reindexarOrdensDaEtapa((int) $numEtapa);
            }
        });

        $qtd = $camposMover->count();
        $this->etapa = $destino;
        $this->limparSelecaoCampos();
        $this->atualizarProximaOrdem();

        $this->dispatch('sucesso', msg: "{$qtd} campo(s) movido(s) para a Página #{$destino}!");
    }

    public function moverSelecionadosNaMesmaEtapa(string $posicao = 'topo'): void
    {
        if ($this->isLockedByOther || empty($this->camposSelecionados)) return;

        $colunaFk = $this->getContextColumn();

        $etapasDosSelecionados = CampoFormulario::where($colunaFk, $this->contextoId)
            ->whereIn('id', $this->camposSelecionados)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        DB::transaction(function () use ($colunaFk, $etapasDosSelecionados, $posicao) {
            foreach ($etapasDosSelecionados as $numEtapa) {
                $todosDaEtapa = CampoFormulario::where($colunaFk, $this->contextoId)
                    ->where('etapa', $numEtapa)
                    ->where('tipo', '!=', 'config')
                    ->orderBy('ordem')
                    ->orderBy('id')
                    ->get();

                $selecionados = $todosDaEtapa->filter(fn($c) => in_array((string) $c->id, $this->camposSelecionados, true));
                $naoSelecionados = $todosDaEtapa->reject(fn($c) => in_array((string) $c->id, $this->camposSelecionados, true));

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

        $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Posição dos campos selecionados atualizada!');
    }

    private function reindexarOrdensDaEtapa(int $numeroEtapa): void
    {
        $campos = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
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

    // =========================================================================
    // FASES TEMPORAIS
    // =========================================================================

    public function abrirModalFases()
    {
        $this->carregarEtapas();

        $paginasEtapas = collect($this->etapasDisponiveis)->pluck('numero')->toArray();
        $paginasCampos = \App\Models\CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('tipo', '!=', 'config')
            ->distinct()
            ->pluck('etapa')
            ->toArray();

        $todasPaginas = array_unique(array_merge($paginasEtapas, $paginasCampos));
        sort($todasPaginas);

        $this->paginasDisponiveis = array_values(array_filter(array_map('intval', $todasPaginas), fn($p) => $p > 0));

        $fasesDb = \App\Models\FormularioFase::where($this->getContextColumn(), $this->contextoId)
            ->orderBy('data_liberacao', 'asc')
            ->get();

        $this->fasesConfig = [];
        foreach ($fasesDb as $f) {
            $pags = is_string($f->paginas) ? json_decode($f->paginas, true) : ($f->paginas ?? []);
            $this->fasesConfig[] = [
                'id' => $f->id,
                'nome' => $f->nome,
                'paginas' => is_array($pags) ? array_values(array_map('strval', $pags)) : [],
                'data_liberacao' => $f->data_liberacao ? $f->data_liberacao->format('Y-m-d\TH:i') : '',
                'data_encerramento' => $f->data_encerramento ? $f->data_encerramento->format('Y-m-d\TH:i') : '',
                'email_template_id' => $f->email_template_id ? (string) $f->email_template_id : '',
            ];
        }

        $this->templatesDeEmail = class_exists(\App\Modules\Comunicacao\Domain\Models\EmailTemplate::class)
            ? \App\Modules\Comunicacao\Domain\Models\EmailTemplate::orderBy('nome')->pluck('nome', 'id')->toArray()
            : [];

        $this->resetErrorBag();
        $this->modalFasesAberto = true;
    }

    public function adicionarFase()
    {
        $this->fasesConfig[] = [
            'id' => null,
            'nome' => '',
            'paginas' => [],
            'data_liberacao' => '',
            'data_encerramento' => '',
            'email_template_id' => ''
        ];
    }

    public function removerFase($index)
    {
        $faseId = $this->fasesConfig[$index]['id'] ?? null;
        if ($faseId) {
            \App\Models\FormularioFase::where('id', $faseId)->delete();
        }
        unset($this->fasesConfig[$index]);
        $this->fasesConfig = array_values($this->fasesConfig);
    }

    public function salvarFases()
    {
        if (!empty($this->fasesConfig)) {
            $this->validate([
                'fasesConfig.*.nome' => 'required|string|max:255',
                'fasesConfig.*.data_liberacao' => 'required|date',
                'fasesConfig.*.paginas' => 'required|array|min:1',
            ], [
                'fasesConfig.*.nome.required' => 'O nome da fase é obrigatório.',
                'fasesConfig.*.data_liberacao.required' => 'Defina a data de liberação da fase.',
                'fasesConfig.*.paginas.required' => 'Selecione pelo menos uma página para esta fase.',
                'fasesConfig.*.paginas.min' => 'Selecione pelo menos uma página para esta fase.',
            ]);

            foreach ($this->fasesConfig as $cfg) {
                $dadosFase = [
                    'ciclo_id' => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
                    'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
                    'nome' => $cfg['nome'],
                    'paginas' => array_values(array_map('intval', $cfg['paginas'] ?? [])),
                    'data_liberacao' => $cfg['data_liberacao'],
                    'data_encerramento' => !empty($cfg['data_encerramento']) ? $cfg['data_encerramento'] : null,
                    'email_template_id' => !empty($cfg['email_template_id']) ? (int) $cfg['email_template_id'] : null,
                ];

                if (!empty($cfg['id'])) {
                    \App\Models\FormularioFase::where('id', $cfg['id'])->update($dadosFase);
                } else {
                    \App\Models\FormularioFase::create($dadosFase);
                }
            }
        }

        $this->modalFasesAberto = false;
        $this->dispatch('sucesso', msg: 'Fases, bloqueios de data e automações guardadas com sucesso!');
    }

    private function inicializarDefaultsDicas(): void
    {
        $this->configuracoes['dica_modo'] = $this->configuracoes['dica_modo'] ?? 'nenhum';
        $this->configuracoes['dica_1_texto'] = $this->configuracoes['dica_1_texto'] ?? '';
        $this->configuracoes['dica_1_tipo'] = $this->configuracoes['dica_1_tipo'] ?? 'legenda';
        $this->configuracoes['dica_1_trigger'] = $this->configuracoes['dica_1_trigger'] ?? 'hover';
        $this->configuracoes['dica_2_texto'] = $this->configuracoes['dica_2_texto'] ?? '';
        $this->configuracoes['dica_2_tipo'] = $this->configuracoes['dica_2_tipo'] ?? 'tooltip';
        $this->configuracoes['dica_2_trigger'] = $this->configuracoes['dica_2_trigger'] ?? 'hover';
    }

    public function updatedConfiguracoesDicaModo($valor)
    {
        if ($valor === 'legenda') {
            $this->configuracoes['dica_1_tipo'] = 'legenda';
        } elseif ($valor === 'tooltip') {
            $this->configuracoes['dica_1_tipo'] = 'tooltip';
            $this->configuracoes['dica_1_trigger'] = $this->configuracoes['dica_1_trigger'] ?? 'hover';
        } elseif ($valor === 'ambos') {
            $this->configuracoes['dica_1_tipo'] = $this->configuracoes['dica_1_tipo'] ?? 'legenda';
            $this->configuracoes['dica_2_tipo'] = ($this->configuracoes['dica_1_tipo'] === 'legenda') ? 'tooltip' : 'legenda';
            $this->configuracoes['dica_1_trigger'] = $this->configuracoes['dica_1_trigger'] ?? 'hover';
            $this->configuracoes['dica_2_trigger'] = $this->configuracoes['dica_2_trigger'] ?? 'hover';
        }
    }

    public function updatedConfiguracoesDica1Tipo($valor)
    {
        if (($this->configuracoes['dica_modo'] ?? '') === 'ambos') {
            $this->configuracoes['dica_2_tipo'] = ($valor === 'legenda') ? 'tooltip' : 'legenda';
        }
    }

    public function updatedConfiguracoesDica2Tipo($valor)
    {
        if (($this->configuracoes['dica_modo'] ?? '') === 'ambos') {
            $this->configuracoes['dica_1_tipo'] = ($valor === 'legenda') ? 'tooltip' : 'legenda';
        }
    }

    private function getContextColumn()
    {
        return $this->contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
    }

    public function carregarEtapas()
    {
        $query = \App\Models\Etapa::query();
        if ($this->contextoTipo === 'ciclo') {
            $query->where('ciclo_id', $this->contextoId);
        } else {
            $query->where('formulario_id', $this->contextoId);
        }
        
        $this->etapasDisponiveis = $query->orderBy('numero', 'asc')->get();

        $numerosExistentes = \App\Models\CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('tipo', '!=', 'config')->distinct()->pluck('etapa')->toArray();

        if (!in_array(1, $numerosExistentes)) {
            $numerosExistentes[] = 1;
        }

        foreach ($numerosExistentes as $num) {
            if (!$this->etapasDisponiveis->contains('numero', $num)) {
                $novaEtapa = \App\Models\Etapa::create([
                    $this->getContextColumn() => $this->contextoId,
                    'numero' => $num,
                    'nome' => $num . 'º Fase'
                ]);
                $this->etapasDisponiveis->push($novaEtapa);
            }
        }
        
        $this->etapasDisponiveis = $this->etapasDisponiveis->sortBy('numero')->values();
    }

    public function loadFormSettings()
    {
        $cfg = CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('name', '_form_config')->first();
        if ($cfg && $cfg->configuracoes) {
            $savedSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
            $this->formSettings = array_merge($this->formSettings, $savedSettings);
        }
    }

    public function iniciarNovoCampo(): void
    {
        $this->cancelarEdicao();
        $this->painelCampoAtivo = true;
    }

    public function setTipo($tipo, $subtipo = 'text')
    {
        $this->camposSelecionados = [];
        $this->painelCampoAtivo = true;
        $this->tipo = $tipo;
        $this->subtipo = ($tipo === 'file' && $subtipo === 'text') ? 'file' : $subtipo;
        
        if (!$this->campoId) {
            $this->regex_mascara = '';
        }

        $this->inicializarDefaultsDicas();

        if (in_array($tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']) && !$this->campoId) {
            $this->name = 'ui_' . time() . '_' . rand(10, 99); 
        }

        if ($tipo === 'social') {
            $this->configuracoes['redes_permitidas'] = $this->configuracoes['redes_permitidas'] ?? [];
        }

        if ($tipo === 'text') {
            if ($subtipo === 'cpf') {
                $this->regex_mascara = '999.999.999-99';
                $this->configuracoes['validar_cpf'] = $this->configuracoes['validar_cpf'] ?? true;
                $this->configuracoes['unico'] = $this->configuracoes['unico'] ?? false;
                if (!$this->campoId) {
                    $this->label = 'CPF';
                    $this->name = 'cpf_' . rand(10, 99);
                    $this->largura = 4;
                }
            } elseif ($subtipo === 'cnpj') {
                $this->regex_mascara = '99.999.999/9999-99';
                if (!$this->campoId) {
                    $this->label = 'CNPJ';
                    $this->name = 'cnpj_' . rand(10, 99);
                    $this->largura = 4;
                }
            } elseif ($subtipo === 'cep') {
                $this->regex_mascara = '99999-999';
                $this->configuracoes['viacep'] = $this->configuracoes['viacep'] ?? true;
                $this->configuracoes['criar_campos_endereco'] = $this->configuracoes['criar_campos_endereco'] ?? true;
                if (!$this->campoId) {
                    $this->label = 'CEP';
                    $this->name = 'cep_' . rand(10, 99);
                    $this->largura = 3;
                }
            } elseif ($subtipo === 'tel') {
                $this->regex_mascara = '(99) 99999-9999';
                if (!$this->campoId && empty($this->label)) {
                    $this->label = 'Telefone / Celular';
                    $this->name = 'telefone_' . rand(10, 99);
                    $this->largura = 4;
                }
            } elseif ($subtipo === 'email') {
                $this->configuracoes['unico'] = $this->configuracoes['unico'] ?? false; 
                if (!$this->campoId && empty($this->label)) {
                    $this->label = 'E-mail';
                    $this->name = 'email_' . rand(10, 99);
                    $this->largura = 6;
                }
            } elseif ($subtipo === 'money') {
                if (!$this->campoId && empty($this->label)) {
                    $this->label = 'Valor (R$)';
                    $this->name = 'valor_' . rand(10, 99);
                    $this->largura = 4;
                }
            } elseif ($subtipo === 'date') {
                $this->configuracoes['calcular_idade'] = $this->configuracoes['calcular_idade'] ?? false;
                if (!$this->campoId && empty($this->label)) {
                    $this->label = 'Data de Nascimento';
                    $this->name = 'data_nascimento_' . rand(10, 99);
                    $this->largura = 4;
                }
            }
        }

        if ($tipo === 'file') {
            $this->configuracoes['tipo_arquivo'] = $this->configuracoes['tipo_arquivo'] ?? 'todos';
            $this->configuracoes['aceita_multiplos'] = $this->configuracoes['aceita_multiplos'] ?? false;
            $this->configuracoes['extensoes_permitidas'] = $this->configuracoes['extensoes_permitidas'] ?? 'pdf, docx, jpg, png, mp4';
            $this->configuracoes['max_size_mb'] = $this->configuracoes['max_size_mb'] ?? 10;
            $this->configuracoes['max_arquivos'] = $this->configuracoes['max_arquivos'] ?? 5;
        }

        if ($tipo === 'signature') {
            $tipoAssinante = $this->configuracoes['tipo_assinante'] ?? ($subtipo !== 'text' ? $subtipo : 'usuario');
            $this->configuracoes['tipo_assinante'] = $tipoAssinante;
            $this->subtipo = $tipoAssinante;

            if (!$this->campoId) {
                $this->label = $tipoAssinante === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura';
                $this->name = Str::slug(strip_tags($this->label), '_') . '_' . rand(10, 99);
            }
        }

        if ($tipo === 'link') {
            $this->subtipo = 'link';
            $this->configuracoes['tipo_destino'] = $this->configuracoes['tipo_destino'] ?? 'externo';
            $this->configuracoes['url'] = $this->configuracoes['url'] ?? '';
            $this->configuracoes['permitir_download'] = $this->configuracoes['permitir_download'] ?? true;
            $this->configuracoes['abrir_preview'] = $this->configuracoes['abrir_preview'] ?? true;
        }

        if ($tipo === 'system') {
            $this->configuracoes['filtrar_por_uf'] = $this->configuracoes['filtrar_por_uf'] ?? false;
            $this->configuracoes['vincular_curso_unidade'] = $this->configuracoes['vincular_curso_unidade'] ?? true;
            $this->configuracoes['vincular_turno_curso'] = $this->configuracoes['vincular_turno_curso'] ?? true;
            $this->configuracoes['aplicar_regras'] = $this->configuracoes['aplicar_regras'] ?? true;

            if (!$this->campoId) {
                if ($subtipo === 'unidade') {
                    $this->label = 'Unidade';
                    $this->name = 'unidade_' . rand(10, 99);
                    $this->largura = 4;
                } elseif ($subtipo === 'curso') {
                    $this->label = 'Curso';
                    $this->name = 'curso_' . rand(10, 99);
                    $this->largura = 4;
                } elseif ($subtipo === 'turno') {
                    $this->label = 'Turno';
                    $this->name = 'turno_' . rand(10, 99);
                    $this->largura = 4;
                } elseif ($subtipo === 'combo_academico') {
                    $this->label = 'Unidade';
                    $this->name = 'combo_acad_' . rand(10, 99);
                    $this->largura = 4;
                    $this->configuracoes['gerar_campos_combo'] = true;
                }
            }
        }

        $this->dispatch('sincronizar-quill-builder');
    }

    public function updatedConfiguracoesTipoAssinante($valor)
    {
        if ($this->tipo !== 'signature') return;

        $this->subtipo = $valor;
        $this->label = $valor === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura';

        if (!$this->campoId) {
            $this->name = Str::slug(strip_tags($this->label), '_') . '_' . rand(10, 99);
        }
    }

    public function updatedConfiguracoesTipoArquivo($valor)
    {
        if ($this->tipo !== 'file') return;

        switch ($valor) {
            case 'imagem':
                $this->configuracoes['extensoes_permitidas'] = 'jpg, jpeg, png, webp, gif';
                break;
            case 'video':
                $this->configuracoes['extensoes_permitidas'] = 'mp4, mov, avi, webm, mkv';
                break;
            case 'documento':
                $this->configuracoes['extensoes_permitidas'] = 'pdf, doc, docx, xls, xlsx, txt';
                break;
            case 'todos':
                $this->configuracoes['extensoes_permitidas'] = 'pdf, docx, jpg, png, mp4';
                break;
        }
    }

    public function updatedLabel($valor)
    {
        $textoLimpo = trim(html_entity_decode(strip_tags((string) $valor), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (!$this->campoId && !in_array($this->tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link'])) {
            $slug = Str::slug($textoLimpo, '_');
            $this->name = !empty($slug) ? substr($slug, 0, 50) : 'campo_' . time();
        } elseif (!$this->campoId && empty($this->name)) {
            $this->name = 'campo_' . time(); 
        }
    }

    #[Computed]
    public function limiteOrdem()
    {
        if (empty($this->etapa)) return 1;

        $count = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('etapa', $this->etapa)
            ->where('tipo', '!=', 'config')
            ->count();

        if ($this->campoId) {
            $campoAtual = CampoFormulario::find($this->campoId);
            if ($campoAtual && $campoAtual->etapa == $this->etapa) {
                return max(1, $count);
            }
        }

        return $count + 1;
    }

    public function atualizarProximaOrdem()
    {
        if (empty($this->etapa)) return;
        $this->ordem = $this->limiteOrdem();
    }

    public function updatedEtapa()
    {
        $this->atualizarProximaOrdem();
    }

    public function editar($id)
    {
        $campo = CampoFormulario::findOrFail($id);
        $this->camposSelecionados = [(string) $campo->id];
        $this->painelCampoAtivo = true;
        $this->campoId = $campo->id;
        $this->etapa = $campo->etapa;
        $this->ordem = $campo->ordem;
        $this->label = $campo->label;
        $this->name = $campo->name;
        $this->tipo = $campo->tipo;
        $this->largura = $campo->largura;
        $this->subtipo = $campo->subtipo;
        $this->tamanho_min = $campo->tamanho_min;
        $this->tamanho_max = $campo->tamanho_max;
        $this->regex_mascara = $campo->regex_mascara;
        $this->obrigatorio = (bool) $campo->obrigatorio;
        $this->regras_validacao = $campo->regras_validacao;
        $this->depende_de = $campo->depende_de;
        $this->depende_operador = $campo->depende_operador;
        $this->depende_valor = $campo->depende_valor;

        if (is_array($campo->opcoes)) {
            if (isset($campo->opcoes['origem_bd'])) {
                $this->opcoes = 'bd:' . $campo->opcoes['origem_bd'];
            } else {
                $this->opcoes = implode("\n", $campo->opcoes);
            }
        } else {
            $this->opcoes = $campo->opcoes ?? '';
        }

        $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
        $this->configuracoes = $config;
        $this->inicializarDefaultsDicas();

        if ($campo->tipo === 'text' && $campo->subtipo === 'cep') {
            $this->configuracoes['viacep'] = filter_var($config['viacep'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->configuracoes['criar_campos_endereco'] = false;
        }

        if ($campo->tipo === 'text' && $campo->subtipo === 'date') {
            $this->configuracoes['calcular_idade'] = filter_var($config['calcular_idade'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        if ($campo->tipo === 'file') {
            $this->configuracoes['tipo_arquivo'] = $config['tipo_arquivo'] ?? 'todos';
            $this->configuracoes['aceita_multiplos'] = filter_var($config['aceita_multiplos'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->configuracoes['extensoes_permitidas'] = $config['extensoes_permitidas'] ?? 'pdf, docx, jpg, png, mp4';
            $this->configuracoes['max_size_mb'] = (int) ($config['max_size_mb'] ?? 10);
            $this->configuracoes['max_arquivos'] = (int) ($config['max_arquivos'] ?? 5);
        }

        if ($campo->tipo === 'matriz') {
            $this->matriz_linhas = implode("\n", $config['linhas'] ?? []);
            $this->matriz_colunas = implode(', ', $config['colunas'] ?? []);
        }
        if ($campo->tipo === 'social') {
            $this->configuracoes['redes_permitidas'] = $config['redes_permitidas'] ?? [];
        }
        if ($campo->tipo === 'signature') {
            $this->configuracoes['tipo_assinante'] = $config['tipo_assinante'] ?? ($campo->subtipo ?: 'usuario');
        }
        if ($campo->tipo === 'link') {
            $this->configuracoes['tipo_destino'] = $config['tipo_destino'] ?? 'externo';
            $this->configuracoes['url'] = $config['url'] ?? '';
            $this->configuracoes['permitir_download'] = filter_var($config['permitir_download'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->configuracoes['abrir_preview'] = filter_var($config['abrir_preview'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->configuracoes['arquivo_path'] = $config['arquivo_path'] ?? null;
            $this->configuracoes['arquivo_nome'] = $config['arquivo_nome'] ?? null;
            $this->configuracoes['arquivo_extensao'] = $config['arquivo_extensao'] ?? null;
            $this->link_file_upload = null;
        }

        $this->dispatch('sincronizar-quill-builder');
    }

    public function cancelarEdicao()
    {
        $this->camposSelecionados = [];
        $this->reset([
            'campoId', 'label', 'name', 'tipo', 'largura', 'subtipo', 
            'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 'obrigatorio', 
            'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor', 
            'configuracoes', 'matriz_linhas', 'matriz_colunas', 'link_file_upload'
        ]);
        
        $this->painelCampoAtivo = false;
        $this->configuracoes = [];
        $this->inicializarDefaultsDicas();
        $this->atualizarProximaOrdem();
        $this->dispatch('sincronizar-quill-builder');
    }

    public function salvarFormSettings()
    {
        $tabelaFoco = $this->contextoTipo === 'ciclo' ? 'ciclos' : 'formularios';

        $this->slug = Str::slug($this->slug);

        $this->validate([
            'bg_image_upload' => 'nullable|mimes:jpg,jpeg,png,webp|max:10240',
            'slug' => [
                'required',
                'regex:/^[a-z0-9\-]+$/',
                \Illuminate\Validation\Rule::unique($tabelaFoco, 'slug')->ignore($this->contextoId)
            ]
        ], [
            'slug.unique' => 'Esta URL amigável já está sendo utilizada por outro formulário.',
            'slug.regex' => 'A URL amigável só pode conter letras minúsculas, números e traços.'
        ]);

        if ($this->bg_image_upload) {
            $path = $this->bg_image_upload->store('formularios/bg', 'public');
            $this->formSettings['bg_image'] = 'storage/' . $path;
            $this->bg_image_upload = null;
        }

        if ($this->contextoTipo === 'ciclo') {
            \App\Models\Ciclo::where('id', $this->contextoId)->update(['slug' => $this->slug]);
        } else {
            \App\Models\Formulario::where('id', $this->contextoId)->update(['slug' => $this->slug]);
        }

        \App\Models\CampoFormulario::updateOrCreate(
            [
                $this->getContextColumn() => $this->contextoId, 
                'name' => '_form_config'
            ],
            [
                'ciclo_id' => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
                'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
                'etapa' => 0, 
                'ordem' => 0, 
                'label' => 'Configurações Globais',
                'tipo' => 'config', 
                'largura' => 12,
                'configuracoes' => is_array($this->formSettings) ? json_encode($this->formSettings) : $this->formSettings
            ]
        );

        $this->drawerFormSettingsAberto = false;
        $this->dispatch('sucesso', msg: 'Tema e aparência aplicados com sucesso!');
    }

    private function limparHtmlQuill(?string $html): string
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

    public function salvar()
    {
        $this->label = $this->limparHtmlQuill($this->label);

        $this->validate([
            'etapa' => 'required',
            'ordem' => 'required|integer|min:1|max:' . $this->limiteOrdem(),
            'label' => 'nullable', 
            'name' => [
                'required', 
                'regex:/^[a-z0-9_]+$/',
                \Illuminate\Validation\Rule::unique('campo_formularios', 'name')
                    ->where($this->getContextColumn(), $this->contextoId)
                    ->ignore($this->campoId)
            ],
            'tipo' => 'required',
            'largura' => 'required|integer',
        ], [
            'name.unique' => 'Já existe um campo com este identificador. Altere a pergunta.',
            'ordem.max' => 'Você não pode pular posições. A posição máxima permitida agora é ' . $this->limiteOrdem() . '.'
        ]);

        $arrayOpcoes = null;
        if (in_array($this->tipo, ['select', 'radio', 'check']) && !empty($this->opcoes)) {
            $opcoesLimpas = trim($this->opcoes);
            if (str_starts_with(strtolower($opcoesLimpas), 'bd:')) {
                $arrayOpcoes = ['origem_bd' => trim(substr($opcoesLimpas, 3))];
            } else {
                $linhas = explode("\n", $this->opcoes);
                $arrayOpcoes = array_values(array_filter(array_map('trim', $linhas), fn($v) => $v !== ''));
            }
        }

        $configToSave = $this->configuracoes ?? [];

        $configToSave['dica_modo'] = $configToSave['dica_modo'] ?? 'nenhum';
        $configToSave['dica_1_texto'] = $this->limparHtmlQuill($configToSave['dica_1_texto'] ?? '');
        $configToSave['dica_1_tipo'] = $configToSave['dica_1_tipo'] ?? ($configToSave['dica_modo'] === 'tooltip' ? 'tooltip' : 'legenda');
        $configToSave['dica_1_trigger'] = $configToSave['dica_1_trigger'] ?? 'hover';
        $configToSave['dica_2_texto'] = $this->limparHtmlQuill($configToSave['dica_2_texto'] ?? '');
        $configToSave['dica_2_tipo'] = $configToSave['dica_2_tipo'] ?? ($configToSave['dica_1_tipo'] === 'legenda' ? 'tooltip' : 'legenda');
        $configToSave['dica_2_trigger'] = $configToSave['dica_2_trigger'] ?? 'hover';

        if (!empty($configToSave['descricao'])) {
            $configToSave['descricao'] = $this->limparHtmlQuill($configToSave['descricao']);
        }

        if ($this->tipo === 'text') {
            if ($this->subtipo === 'cpf') {
                $this->regex_mascara = '999.999.999-99';
                $configToSave['validar_cpf'] = filter_var($this->configuracoes['validar_cpf'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $configToSave['unico'] = filter_var($this->configuracoes['unico'] ?? false, FILTER_VALIDATE_BOOLEAN);
            } elseif ($this->subtipo === 'email') {
                $configToSave['unico'] = filter_var($this->configuracoes['unico'] ?? false, FILTER_VALIDATE_BOOLEAN);
            } elseif ($this->subtipo === 'cnpj') {
                $this->regex_mascara = '99.999.999/9999-99';
            } elseif ($this->subtipo === 'cep') {
                $this->regex_mascara = '99999-999';
                $configToSave['viacep'] = filter_var($this->configuracoes['viacep'] ?? false, FILTER_VALIDATE_BOOLEAN);
            } elseif ($this->subtipo === 'date') {
                $configToSave['calcular_idade'] = filter_var($this->configuracoes['calcular_idade'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        if ($this->tipo === 'file') {
            $extensoesCruas = $this->configuracoes['extensoes_permitidas'] ?? 'pdf,docx,jpg,png,mp4';
            $extensoesTratadas = implode(',', array_filter(array_map(function($ext) {
                return ltrim(strtolower(trim($ext)), '.');
            }, explode(',', $extensoesCruas))));

            $configToSave['tipo_arquivo'] = $this->configuracoes['tipo_arquivo'] ?? 'todos';
            $configToSave['aceita_multiplos'] = filter_var($this->configuracoes['aceita_multiplos'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $configToSave['extensoes_permitidas'] = $extensoesTratadas ?: 'pdf,docx,jpg,png,mp4';
            $configToSave['max_size_mb'] = max(1, (int) ($this->configuracoes['max_size_mb'] ?? 10));
            $configToSave['max_arquivos'] = max(1, (int) ($this->configuracoes['max_arquivos'] ?? 5));
        }

        if ($this->tipo === 'matriz') {
            $linhasStr = $this->matriz_linhas ?? '';
            $colunasStr = $this->matriz_colunas ?? '';
            
            $configToSave['linhas'] = array_values(array_filter(array_map('trim', explode("\n", $linhasStr)), fn($val) => $val !== ''));
            $configToSave['colunas'] = array_values(array_filter(array_map('trim', explode(',', $colunasStr)), fn($val) => $val !== ''));
        }

        if ($this->tipo === 'social') {
            $configToSave['redes_permitidas'] = array_values(array_filter($this->configuracoes['redes_permitidas'] ?? []));
        }

        if ($this->tipo === 'signature') {
            $tipoAssinante = $this->configuracoes['tipo_assinante'] ?? 'usuario';
            $configToSave['tipo_assinante'] = $tipoAssinante;
            $this->subtipo = $tipoAssinante;
            if (empty(strip_tags($this->label))) {
                $this->label = $tipoAssinante === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura';
            }
        }

        if ($this->tipo === 'link') {
            $tipoDestino = $this->configuracoes['tipo_destino'] ?? 'externo';
            $configToSave['tipo_destino'] = $tipoDestino;

            if ($tipoDestino === 'arquivo') {
                $rulesArquivo = empty($this->configuracoes['arquivo_path'])
                    ? 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480'
                    : 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480';

                $this->validate([
                    'link_file_upload' => $rulesArquivo,
                ], [
                    'link_file_upload.required' => 'Selecione um arquivo (PDF, Word ou Excel) para este link.',
                    'link_file_upload.mimes' => 'O arquivo deve ser do tipo PDF, Word (doc/docx) ou Excel (xls/xlsx).',
                    'link_file_upload.max' => 'O arquivo não pode ultrapassar 20MB.',
                ]);

                if ($this->link_file_upload) {
                    $nomeOriginal = $this->link_file_upload->getClientOriginalName();
                    $extensao = strtolower($this->link_file_upload->getClientOriginalExtension());
                    $path = $this->link_file_upload->store('formularios/links', 'public');

                    $configToSave['arquivo_path'] = $path;
                    $configToSave['arquivo_nome'] = $nomeOriginal;
                    $configToSave['arquivo_extensao'] = $extensao;
                    $configToSave['url'] = 'storage/' . $path;
                    $this->link_file_upload = null;
                } else {
                    $configToSave['arquivo_path'] = $this->configuracoes['arquivo_path'] ?? null;
                    $configToSave['arquivo_nome'] = $this->configuracoes['arquivo_nome'] ?? null;
                    $configToSave['arquivo_extensao'] = $this->configuracoes['arquivo_extensao'] ?? null;
                    $configToSave['url'] = $this->configuracoes['url'] ?? null;
                }

                $configToSave['permitir_download'] = filter_var($this->configuracoes['permitir_download'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $configToSave['abrir_preview'] = filter_var($this->configuracoes['abrir_preview'] ?? true, FILTER_VALIDATE_BOOLEAN);
            } else {
                $this->validate([
                    'configuracoes.url' => 'required|string',
                ], [
                    'configuracoes.url.required' => 'Informe a URL ou caminho de destino do link.',
                ]);

                $configToSave['url'] = trim($this->configuracoes['url'] ?? '');
                $configToSave['permitir_download'] = false;
                $configToSave['abrir_preview'] = false;
            }
        }

        $isEditing = (bool) $this->campoId;
        $ordemSalva = (int) $this->ordem;
        $etapaSalva = (int) $this->etapa;
        $nameSalvo  = $this->name;
        $obrigatorioSalvo = (bool) $this->obrigatorio;
        $exibirNoTopoSalvo = filter_var($this->configuracoes['exibir_no_topo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $deveCriarEnderecoViaCep = ($this->tipo === 'text' && $this->subtipo === 'cep' && !empty($configToSave['viacep']) && (!$isEditing || !empty($this->configuracoes['criar_campos_endereco'])));

        if ($this->tipo === 'system') {
            $configToSave['filtrar_por_uf'] = filter_var($this->configuracoes['filtrar_por_uf'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $configToSave['vincular_curso_unidade'] = filter_var($this->configuracoes['vincular_curso_unidade'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $configToSave['vincular_turno_curso'] = filter_var($this->configuracoes['vincular_turno_curso'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $configToSave['aplicar_regras'] = ($configToSave['vincular_curso_unidade'] || $configToSave['vincular_turno_curso']);
        }

        $deveCriarComboAcademico = ($this->tipo === 'system' && $this->subtipo === 'combo_academico' && !$isEditing);
        if ($deveCriarComboAcademico) {
            $this->subtipo = 'unidade';
            $configToSave['combo_group'] = $nameSalvo;
            $configToSave['combo_role'] = 'unidade';
            $configToSave['combo_map'] = [
                'unidade' => $nameSalvo,
                'curso'   => $nameSalvo . '_curso',
                'turno'   => $nameSalvo . '_turno',
            ];
        }

        if ($this->campoId) {
            $campo = CampoFormulario::findOrFail($this->campoId);
            $ordemAntiga = $campo->ordem;
            $etapaAntiga = $campo->etapa;
            $ordemNova = $this->ordem;
            $etapaNova = $this->etapa;

            if ($etapaAntiga == $etapaNova) {
                if ($ordemAntiga != $ordemNova) {
                    if ($ordemNova < $ordemAntiga) {
                        CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                            ->where('etapa', $etapaNova)
                            ->where('tipo', '!=', 'config')
                            ->whereBetween('ordem', [$ordemNova, $ordemAntiga - 1])
                            ->increment('ordem');
                    } else {
                        CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                            ->where('etapa', $etapaNova)
                            ->where('tipo', '!=', 'config')
                            ->whereBetween('ordem', [$ordemAntiga + 1, $ordemNova])
                            ->decrement('ordem');
                    }
                }
            } else {
                CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                    ->where('etapa', $etapaAntiga)
                    ->where('tipo', '!=', 'config')
                    ->where('ordem', '>', $ordemAntiga)
                    ->decrement('ordem');

                CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                    ->where('etapa', $etapaNova)
                    ->where('tipo', '!=', 'config')
                    ->where('ordem', '>=', $ordemNova)
                    ->increment('ordem');
            }
        } else {
            CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                ->where('etapa', $this->etapa)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $this->ordem)
                ->increment('ordem');
        }

        if ($this->tipo === 'text' && $this->subtipo === 'cep' && !empty($configToSave['viacep'])) {
            $configToSave['viacep_map'] = $configToSave['viacep_map'] ?? [
                'logradouro'  => $nameSalvo . '_logradouro',
                'numero'      => $nameSalvo . '_numero',
                'complemento' => $nameSalvo . '_complemento',
                'bairro'      => $nameSalvo . '_bairro',
                'cidade'      => $nameSalvo . '_cidade',
                'uf'          => $nameSalvo . '_uf',
            ];
        }

        $dadosGerais = [
            'ciclo_id' => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
            'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
            'etapa' => $this->etapa,
            'ordem' => $this->ordem,
            'label' => empty(strip_tags($this->label)) ? ($this->tipo === 'spacer' ? 'Espaço em Branco' : ($this->tipo === 'link' ? ($configToSave['arquivo_nome'] ?? 'Link / Documento') : 'Campo Visão')) : $this->label, 
            'name' => $this->name,
            'tipo' => $this->tipo,
            'largura' => $this->largura,
            'subtipo' => empty($this->subtipo) ? ($this->tipo === 'file' ? 'file' : 'text') : $this->subtipo,
            'tamanho_min' => empty($this->tamanho_min) ? null : $this->tamanho_min,
            'tamanho_max' => empty($this->tamanho_max) ? null : $this->tamanho_max,
            'regex_mascara' => empty($this->regex_mascara) ? null : $this->regex_mascara,
            'opcoes' => $arrayOpcoes,
            'configuracoes' => empty($configToSave) ? null : $configToSave,
            'obrigatorio' => in_array($this->tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']) ? false : $this->obrigatorio,
            'regras_validacao' => $this->regras_validacao,
            'depende_de' => empty($this->depende_de) ? null : $this->depende_de,
            'depende_operador' => $this->depende_operador,
            'depende_valor' => empty($this->depende_valor) ? null : $this->depende_valor,
        ];

        if ($this->campoId) {
            CampoFormulario::findOrFail($this->campoId)->update($dadosGerais);
        } else {
            CampoFormulario::create($dadosGerais);
        }

        if ($deveCriarEnderecoViaCep) {
            $this->gerarCamposEnderecoViaCep($etapaSalva, $ordemSalva, $nameSalvo, $obrigatorioSalvo, $exibirNoTopoSalvo);
        }

        if ($deveCriarComboAcademico) {
            $this->gerarCamposComboAcademico($etapaSalva, $ordemSalva, $nameSalvo, $obrigatorioSalvo, $exibirNoTopoSalvo, $configToSave);
        }

        $this->cancelarEdicao(); 
        $this->dispatch('sucesso', msg: $deveCriarEnderecoViaCep 
            ? 'Campo CEP e todos os campos de endereço (ViaCEP) foram adicionados com sucesso!' 
            : 'Bloco configurado e estruturado com sucesso!');
    }

    private function gerarCamposEnderecoViaCep(int $etapa, int $ordemCep, string $cepFieldName, bool $obrigatorio, bool $exibirNoTopo): void
    {
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

            $jaExiste = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                ->where('name', $nomeCampo)
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $ordemAtual++;

            CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                ->where('etapa', $etapa)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $ordemAtual)
                ->increment('ordem');

            CampoFormulario::create([
                'ciclo_id' => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
                'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
                'etapa' => $etapa,
                'ordem' => $ordemAtual,
                'label' => $item['label'],
                'name' => $nomeCampo,
                'tipo' => 'text',
                'subtipo' => 'text',
                'largura' => $item['largura'],
                'obrigatorio' => $item['obrigatorio'],
                'configuracoes' => [
                    'viacep_parent' => $cepFieldName,
                    'viacep_target' => $item['sufixo'],
                    'exibir_no_topo' => $exibirNoTopo,
                ],
            ]);
        }
    }

    private function gerarCamposComboAcademico(int $etapa, int $ordemUnidade, string $baseFieldName, bool $obrigatorio, bool $exibirNoTopo, array $cfgBase): void
    {
        $camposCombo = [
            [
                'sufixo'  => 'curso',
                'subtipo' => 'curso',
                'label'   => 'Curso',
                'largura' => 4,
            ],
            [
                'sufixo'  => 'turno',
                'subtipo' => 'turno',
                'label'   => 'Turno',
                'largura' => 4,
            ],
        ];

        $ordemAtual = $ordemUnidade;

        foreach ($camposCombo as $item) {
            $nomeCampo = $baseFieldName . '_' . $item['sufixo'];

            $jaExiste = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                ->where('name', $nomeCampo)
                ->exists();

            if ($jaExiste) continue;

            $ordemAtual++;

            CampoFormulario::where($this->getContextColumn(), $this->contextoId)
                ->where('etapa', $etapa)
                ->where('tipo', '!=', 'config')
                ->where('ordem', '>=', $ordemAtual)
                ->increment('ordem');

            CampoFormulario::create([
                'ciclo_id'      => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
                'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
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
                    'exibir_no_topo'         => $exibirNoTopo,
                ],
            ]);
        }
    }

    public function excluir($id)
    {
        $campo = CampoFormulario::findOrFail($id);
        $ordemExcluida = $campo->ordem;
        $etapaExcluida = $campo->etapa;
        
        $campo->delete();
        
        CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('etapa', $etapaExcluida)
            ->where('tipo', '!=', 'config')
            ->where('ordem', '>', $ordemExcluida)
            ->decrement('ordem');

        $this->camposSelecionados = array_values(array_diff($this->camposSelecionados, [(string) $id]));

        if ($this->campoId == $id) {
            $this->cancelarEdicao();
        } else {
            $this->atualizarProximaOrdem();
        }

        $this->dispatch('sucesso', msg: 'Bloco removido do formulário!');
    }

    public function adicionarEtapa()
    {
        $proxNumero = $this->etapasDisponiveis->max('numero') + 1;
        \App\Models\Etapa::create([
            $this->getContextColumn() => $this->contextoId,
            'numero' => $proxNumero,
            'nome' => $proxNumero . 'º Fase'
        ]);
        $this->carregarEtapas();
        $this->dispatch('sucesso', msg: 'Nova página adicionada!');
    }

    public function atualizarNomeEtapa($id, $novoNome)
    {
        if (!empty(trim($novoNome))) {
            \App\Models\Etapa::where('id', $id)->update(['nome' => trim($novoNome)]);
            $this->carregarEtapas();
        }
    }

    public function excluirEtapa($id)
    {
        $etapa = \App\Models\Etapa::find($id);
        if (!$etapa) return;

        $numeroRemovido = (int) $etapa->numero;
        $colunaFk = $this->getContextColumn();

        if ($numeroRemovido === 1) {
            $this->dispatch('erro', msg: 'A 1º Fase é obrigatória e não pode ser excluída.');
            return;
        }
        
        $temCampos = \App\Models\CampoFormulario::where($colunaFk, $this->contextoId)
            ->where('etapa', $numeroRemovido)
            ->where('tipo', '!=', 'config')
            ->exists();
            
        if ($temCampos) {
            $this->dispatch('erro', msg: 'Esta página possui campos. Mova-os ou exclua-os primeiro.');
            return;
        }
        
        DB::transaction(function () use ($etapa, $numeroRemovido, $colunaFk) {
            // 1. Exclui a página selecionada
            $etapa->delete();

            // 2. Decrementa o número da etapa nos campos de todas as páginas seguintes
            \App\Models\CampoFormulario::where($colunaFk, $this->contextoId)
                ->where('etapa', '>', $numeroRemovido)
                ->where('tipo', '!=', 'config')
                ->decrement('etapa');

            // 3. Reordena sequencialmente (1, 2, 3...) as páginas restantes na tabela Etapas
            $etapasRestantes = \App\Models\Etapa::where($colunaFk, $this->contextoId)
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

            // 4. Atualiza também a numeração nas Fases Temporais (FormularioFase)
            if (class_exists(\App\Models\FormularioFase::class)) {
                $fases = \App\Models\FormularioFase::where($colunaFk, $this->contextoId)->get();
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

        $this->carregarEtapas();
        
        if ((int) $this->etapa === $numeroRemovido) {
            $this->etapa = max(1, $numeroRemovido - 1);
        } elseif ((int) $this->etapa > $numeroRemovido) {
            $this->etapa = (int) $this->etapa - 1;
        }

        $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Página removida e numeração das páginas seguintes atualizada!');
    }

    public function render()
    {
        $camposCadastrados = CampoFormulario::where($this->getContextColumn(), $this->contextoId)
            ->where('tipo', '!=', 'config')
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();
            
        $camposPorEtapa = $camposCadastrados->groupBy('etapa');

        return view('livewire.form-builder.dynamic-fields', [
            'camposCadastrados' => $camposCadastrados,
            'camposPorEtapa' => $camposPorEtapa
        ])->layout('components.layouts.app', ['title' => 'Construtor de Formulário']);
    }
}