<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\CampoFormulario;
use Illuminate\Support\Str;

class DynamicFields extends Component
{
    public Ciclo $ciclo;
    
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
    
    // Condicionais
    public $depende_de = '';
    public $depende_operador = '=';
    public $depende_valor = '';

    // NOVA PROPRIEDADE: Configurações extras em JSON (Matriz, Fundo, Redes Sociais, etc)
    public array $configuracoes = [];

    public $etapasDisponiveis = [];
    public int $cicloIdAtual;

    public function mount(int $id)
    {
        $ciclo = Ciclo::findOrFail($id);
        
        $this->ciclo = $ciclo;
        $this->cicloIdAtual = $ciclo->id;
        $this->etapasDisponiveis = Etapa::orderBy('numero', 'asc')->get();

        if ($this->etapasDisponiveis->isNotEmpty()) {
            $this->etapa = $this->etapasDisponiveis->first()->numero;
        }
        
        $this->atualizarProximaOrdem();
    }

    public function updatedLabel($valor)
    {
        // Só gera o slug automático se for cadastro novo e não for campo de design (html, divider)
        if (!$this->campoId && !in_array($this->tipo, ['html', 'divider', 'media', 'social'])) {
            $this->name = Str::slug($valor, '_');
        } elseif (!$this->campoId) {
            $this->name = 'campo_' . time(); // Campos visuais ganham um name genérico
        }
    }

    public function updatedTipo($valor)
    {
        // Força um subtipo padrão para não dar erro ao trocar de tipo principal
        if ($valor === 'text') $this->subtipo = 'text';
        if ($valor === 'html') $this->subtipo = 'p';
        if ($valor === 'media') $this->subtipo = 'image';
        
        if (in_array($valor, ['html', 'divider', 'media', 'social']) && !$this->campoId && empty($this->name)) {
             $this->name = 'ui_' . time(); 
        }
    }

    public function atualizarProximaOrdem()
    {
        if (empty($this->etapa)) return;

        $maxOrdem = CampoFormulario::where('ciclo_id', $this->cicloIdAtual)
            ->where('etapa', $this->etapa)
            ->max('ordem') ?? 0;

        $this->ordem = $maxOrdem + 1;
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

        // CARREGA AS CONFIGURAÇÕES COMPLEXAS
        $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
        $this->configuracoes = $config;

        // Facilita a edição desmembrando arrays em textos novamente para o Wire:Model
        if ($campo->tipo === 'matriz') {
            $this->configuracoes['matriz_linhas'] = implode("\n", $config['linhas'] ?? []);
            $this->configuracoes['matriz_colunas'] = implode(', ', $config['colunas'] ?? []);
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
            'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor', 'configuracoes'
        ]);
        
        $this->configuracoes = [];
        $this->atualizarProximaOrdem(); 
    }

    public function salvar()
    {
        $this->validate([
            'etapa' => 'required',
            'ordem' => 'required|integer|min:1',
            'label' => 'nullable', // Permitimos nulo porque um Divider não tem pergunta
            'name' => [
                'required', 
                'regex:/^[a-z0-9_]+$/',
                \Illuminate\Validation\Rule::unique('campo_formularios', 'name')
                    ->where('ciclo_id', $this->cicloIdAtual)
                    ->ignore($this->campoId)
            ],
            'tipo' => 'required',
            'largura' => 'required|integer',
        ], [
            'name.unique' => 'Já existe um campo com este identificador neste ciclo. Altere a pergunta.'
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

        // PREPARAÇÃO DO JSON DE CONFIGURAÇÕES COMPLEXAS
        $configToSave = $this->configuracoes;
        
        if ($this->tipo === 'matriz') {
            $configToSave['linhas'] = array_filter(array_map('trim', explode("\n", $this->configuracoes['matriz_linhas'] ?? '')));
            $configToSave['colunas'] = array_filter(array_map('trim', explode(',', $this->configuracoes['matriz_colunas'] ?? '')));
            unset($configToSave['matriz_linhas'], $configToSave['matriz_colunas']);
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

        // =================================================================
        // LÓGICA DE REORDENAÇÃO (DANÇA DAS CADEIRAS)
        // =================================================================
        if ($this->campoId) {
            $campo = CampoFormulario::findOrFail($this->campoId);
            $ordemAntiga = $campo->ordem;
            $ordemNova = $this->ordem;

            if ($ordemAntiga != $ordemNova && $campo->etapa == $this->etapa) {
                if ($ordemNova < $ordemAntiga) {
                    CampoFormulario::where('ciclo_id', $this->cicloIdAtual)->where('etapa', $this->etapa)->whereBetween('ordem', [$ordemNova, $ordemAntiga - 1])->increment('ordem');
                } else {
                    CampoFormulario::where('ciclo_id', $this->cicloIdAtual)->where('etapa', $this->etapa)->whereBetween('ordem', [$ordemAntiga + 1, $ordemNova])->decrement('ordem');
                }
            }
        } else {
            CampoFormulario::where('ciclo_id', $this->cicloIdAtual)->where('etapa', $this->etapa)->where('ordem', '>=', $this->ordem)->increment('ordem');
        }

        // =================================================================
        // PERSISTÊNCIA ROBUSTA 
        // =================================================================
        $dadosGerais = [
            'ciclo_id' => $this->cicloIdAtual, 
            'etapa' => $this->etapa,
            'ordem' => $this->ordem,
            'label' => empty($this->label) ? 'Campo Visão' : $this->label, // Backup de texto
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
        session()->flash('sucesso', 'Campo configurado e ordenado com sucesso!');
    }

    public function excluir($id)
    {
        $campo = CampoFormulario::findOrFail($id);
        $ordemExcluida = $campo->ordem;
        $etapaExcluida = $campo->etapa;
        
        $campo->delete();
        
        CampoFormulario::where('ciclo_id', $this->cicloIdAtual)->where('etapa', $etapaExcluida)->where('ordem', '>', $ordemExcluida)->decrement('ordem');
        $this->atualizarProximaOrdem();
        session()->flash('sucesso', 'Campo excluído da estrutura com sucesso.');
    }

    public function render()
    {
        $camposCadastrados = CampoFormulario::where('ciclo_id', $this->cicloIdAtual)
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