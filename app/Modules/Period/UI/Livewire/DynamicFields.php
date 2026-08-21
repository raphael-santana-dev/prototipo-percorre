<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Str;

class DynamicFields extends Component
{
    use WithFileUploads;
    
    // Propriedades Base
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

    // Propriedades de Configuração Geral do Formulário (Aba 2)
    public $slug = '';
    public $bg_image_upload;
    public array $formSettings = [
        'bg_image' => null,
        'bg_color' => '#ffffff',
        'bg_opacity' => '0.0',
        'bg_size' => 'cover', 
        'form_width' => 'max-w-4xl',
        'translucent_card' => false,
        'use_vacancy_limit' => false,
    ];

    public string $contextoTipo; // 'ciclo' ou 'formulario'
    public int $contextoId;
    public string $contextoNome = '';

    public $etapasDisponiveis = [];

    public function mount($tipo, $id)
    {
        $this->contextoTipo = $tipo;
        $this->contextoId = $id;

        if ($tipo === 'ciclo') {
            $model = \App\Models\Ciclo::findOrFail($id);
            $this->contextoNome = $model->nome;
            $this->etapasDisponiveis = \App\Models\Etapa::orderBy('numero', 'asc')->get();
        } else {
            $model = \App\Models\Formulario::findOrFail($id);
            $this->contextoNome = $model->titulo;
            $this->etapasDisponiveis = [
                (object)['numero' => 1], (object)['numero' => 2], (object)['numero' => 3]
            ];
        }

        $this->slug = $model->slug ?? '';

        if (count($this->etapasDisponiveis) > 0) {
            $this->etapa = is_object($this->etapasDisponiveis[0]) ? $this->etapasDisponiveis[0]->numero : 1;
        }
        
        $this->loadFormSettings();
        $this->atualizarProximaOrdem();
    }

    private function getContextColumn() {
        return $this->contextoTipo === 'ciclo' ? 'ciclo_id' : 'formulario_id';
    }

    public function loadFormSettings()
    {
        $cfg = CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('name', '_form_config')->first();
        if ($cfg && $cfg->configuracoes) {
            $savedSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
            $this->formSettings = array_merge($this->formSettings, $savedSettings);
        }
    }

    public function setTipo($tipo, $subtipo = 'text')
    {
        $this->tipo = $tipo;
        $this->subtipo = $subtipo;
        
        if (in_array($tipo, ['html', 'divider', 'media', 'social']) && !$this->campoId && empty($this->name)) {
             $this->name = 'ui_' . time(); 
        }
    }

    public function updatedLabel($valor)
    {
        if (!$this->campoId && !in_array($this->tipo, ['html', 'divider', 'media', 'social'])) {
            $this->name = Str::slug($valor, '_');
        } elseif (!$this->campoId) {
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
                return $count;
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
        if (!$this->campoId) {
            $this->atualizarProximaOrdem();
        }
    }

    public function editar($id)
    {
        $campo = CampoFormulario::findOrFail($id);
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
        $this->obrigatorio = $campo->obrigatorio;
        $this->regras_validacao = $campo->regras_validacao;
        $this->depende_de = $campo->depende_de;
        $this->depende_operador = $campo->depende_operador;
        $this->depende_valor = $campo->depende_valor;

        if (is_array($campo->opcoes)) {
            if (isset($campo->opcoes['origem_bd'])) {
                $this->opcoes = 'bd:' . $campo->opcoes['origem_bd'];
            } else {
                $this->opcoes = implode(', ', $campo->opcoes);
            }
        } else {
            $this->opcoes = $campo->opcoes ?? '';
        }

        $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
        $this->configuracoes = $config;

        if ($campo->tipo === 'matriz') {
            $this->matriz_linhas = implode("\n", $config['linhas'] ?? []);
            $this->matriz_colunas = implode(', ', $config['colunas'] ?? []);
        }
        if ($campo->tipo === 'social') {
            $lines = [];
            foreach($config['redes'] ?? [] as $rede) {
                $lines[] = $rede['nome'] . '|' . $rede['url'];
            }
            $this->configuracoes['social_redes'] = implode("\n", $lines);
        }
    }

    public function cancelarEdicao()
    {
        $this->reset([
            'campoId', 'label', 'name', 'tipo', 'largura', 'subtipo', 
            'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 'obrigatorio', 
            'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor', 'configuracoes','matriz_linhas', 'matriz_colunas'
        ]);
        
        $this->configuracoes = [];
        $this->atualizarProximaOrdem(); 
    }

    public function salvarFormSettings()
    {
        $tabelaFoco = $this->contextoTipo === 'ciclo' ? 'ciclos' : 'formularios';

        $this->slug = Str::slug($this->slug);

        $this->validate([
            'bg_image_upload' => 'nullable|image|max:10240',
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
            Ciclo::where('id', $this->contextoId)->update(['slug' => $this->slug]);
        } else {
            Formulario::where('id', $this->contextoId)->update(['slug' => $this->slug]);
        }

        CampoFormulario::updateOrCreate(
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
                'configuracoes' => $this->formSettings
            ]
        );

        $this->dispatch('sucesso', msg: 'Tema e aparência aplicados com sucesso!');
    }

    public function salvar()
    {
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
                $arrayOpcoes = array_map('trim', explode(',', $this->opcoes));
            }
        }

        $configToSave = $this->configuracoes ?? [];
        
        if ($this->tipo === 'matriz') {
            $linhasStr = $this->matriz_linhas ?? '';
            $colunasStr = $this->matriz_colunas ?? '';
            
            $configToSave['linhas'] = array_values(array_filter(array_map('trim', explode("\n", $linhasStr)), fn($val) => $val !== ''));
            $configToSave['colunas'] = array_values(array_filter(array_map('trim', explode(',', $colunasStr)), fn($val) => $val !== ''));
        }
        
        if ($this->tipo === 'social') {
            $redesLines = array_filter(explode("\n", $this->configuracoes['social_redes'] ?? ''));
            $redes = [];
            foreach($redesLines as $line) {
                $parts = explode('|', $line);
                if(count($parts) >= 2) {
                    $redes[] = ['nome' => trim($parts[0]), 'url' => trim($parts[1])];
                }
            }
            $configToSave['redes'] = $redes;
            unset($configToSave['social_redes']);
        }

        if ($this->campoId) {
            $campo = CampoFormulario::findOrFail($this->campoId);
            $ordemAntiga = $campo->ordem;
            $ordemNova = $this->ordem;

            if ($ordemAntiga != $ordemNova && $campo->etapa == $this->etapa) {
                if ($ordemNova < $ordemAntiga) {
                    CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('etapa', $this->etapa)->where('tipo', '!=', 'config')->whereBetween('ordem', [$ordemNova, $ordemAntiga - 1])->increment('ordem');
                } else {
                    CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('etapa', $this->etapa)->where('tipo', '!=', 'config')->whereBetween('ordem', [$ordemAntiga + 1, $ordemNova])->decrement('ordem');
                }
            }
        } else {
            CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('etapa', $this->etapa)->where('tipo', '!=', 'config')->where('ordem', '>=', $this->ordem)->increment('ordem');
        }

        $dadosGerais = [
            'ciclo_id' => $this->contextoTipo === 'ciclo' ? $this->contextoId : null,
            'formulario_id' => $this->contextoTipo === 'formulario' ? $this->contextoId : null,
            'etapa' => $this->etapa,
            'ordem' => $this->ordem,
            'label' => empty($this->label) ? 'Campo Visão' : $this->label, 
            'name' => $this->name,
            'tipo' => $this->tipo,
            'largura' => $this->largura,
            'subtipo' => empty($this->subtipo) ? 'text' : $this->subtipo,
            'tamanho_min' => empty($this->tamanho_min) ? null : $this->tamanho_min,
            'tamanho_max' => empty($this->tamanho_max) ? null : $this->tamanho_max,
            'regex_mascara' => empty($this->regex_mascara) ? null : $this->regex_mascara,
            'opcoes' => $arrayOpcoes,
            'configuracoes' => empty($configToSave) ? null : $configToSave,
            'obrigatorio' => in_array($this->tipo, ['html', 'divider', 'media', 'social']) ? false : $this->obrigatorio,
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

        $this->cancelarEdicao(); 
        $this->dispatch('sucesso', msg: 'Bloco configurado e estruturado com sucesso!');
    }

    public function excluir($id)
    {
        $campo = CampoFormulario::findOrFail($id);
        $ordemExcluida = $campo->ordem;
        $etapaExcluida = $campo->etapa;
        
        $campo->delete();
        
        CampoFormulario::where($this->getContextColumn(), $this->contextoId)->where('etapa', $etapaExcluida)->where('tipo', '!=', 'config')->where('ordem', '>', $ordemExcluida)->decrement('ordem');
        $this->atualizarProximaOrdem();
        $this->dispatch('sucesso', msg: 'Bloco removido do formulário!');
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

        return view('livewire.period.dynamic-fields', [
            'camposCadastrados' => $camposCadastrados,
            'camposPorEtapa' => $camposPorEtapa
        ])->layout('components.layouts.app', ['title' => 'Construtor de Formulário']);
    }
}