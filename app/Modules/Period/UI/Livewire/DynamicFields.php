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

    public $etapasDisponiveis = [];

    public function mount(Ciclo $ciclo)
    {
        $this->ciclo = $ciclo;
        $this->etapasDisponiveis = Etapa::orderBy('numero', 'asc')->get();
        
        // Garante que, ao abrir a página, o campo "Ordem" já sugere o próximo número vazio
        $this->atualizarProximaOrdem();
    }

    public function updatedLabel($valor)
    {
        if (!$this->campoId) {
            $this->name = Str::slug($valor, '_');
        }
    }

    // Calcula a próxima ordem livre automaticamente com base na Etapa selecionada
    public function atualizarProximaOrdem()
    {
        if (empty($this->etapa)) return;

        $maxOrdem = CampoFormulario::where('ciclo_id', $this->ciclo->id)
            ->where('etapa', $this->etapa)
            ->max('ordem') ?? 0;

        $this->ordem = $maxOrdem + 1;
    }

    // Se o utilizador trocar de Etapa no formulário, recalculamos a Ordem sugerida
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
            // Verifica se é uma opção vinda do banco (bd:tabela)
            if (isset($campo->opcoes['origem_bd'])) {
                $this->opcoes = 'bd:' . $campo->opcoes['origem_bd'];
            } else {
                // Se for um array de opções normais separadas por vírgula
                $this->opcoes = implode(', ', $campo->opcoes);
            }
        } else {
            $this->opcoes = $campo->opcoes ?? '';
        }
    }

    public function cancelarEdicao()
    {
        $this->reset([
            'campoId', 'label', 'name', 'tipo', 'largura', 'subtipo', 
            'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 'obrigatorio', 
            'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor'
        ]);
        
        // Prepara o formulário limpo já com o próximo número de ordem
        $this->atualizarProximaOrdem(); 
    }

    public function salvar()
    {
        $this->validate([
            'etapa' => 'required',
            'ordem' => 'required|integer|min:1', // min:1 Impede a introdução de ordens negativas ou zero
            'label' => 'required|min:2',
            'name' => 'required|regex:/^[a-z0-9_]+$/',
            'tipo' => 'required',
            'largura' => 'required|integer',
        ]);

        $arrayOpcoes = null;
        if (in_array($this->tipo, ['select', 'radio']) && !empty($this->opcoes)) {
            $opcoesLimpas = trim($this->opcoes);
            
            // Verifica se o Administrador digitou bd:tabela no campo de texto
            if (str_starts_with(strtolower($opcoesLimpas), 'bd:')) {
                $tabela = trim(substr($opcoesLimpas, 3));
                $arrayOpcoes = ['origem_bd' => $tabela];
            } else {
                // Se for texto normal, separa por vírgulas como antes
                $arrayOpcoes = array_map('trim', explode(',', $this->opcoes));
            }
        }

        // =================================================================
        // LÓGICA DE REORDENAÇÃO (DANÇA DAS CADEIRAS)
        // =================================================================
        if ($this->campoId) {
            // MODO EDIÇÃO
            $campo = CampoFormulario::findOrFail($this->campoId);
            $ordemAntiga = $campo->ordem;
            $ordemNova = $this->ordem;

            // Se mudou de ordem, mas permaneceu dentro da mesma etapa
            if ($ordemAntiga != $ordemNova && $campo->etapa == $this->etapa) {
                if ($ordemNova < $ordemAntiga) {
                    CampoFormulario::where('ciclo_id', $this->ciclo->id)
                        ->where('etapa', $this->etapa)
                        ->whereBetween('ordem', [$ordemNova, $ordemAntiga - 1])
                        ->increment('ordem');
                } else {
                    CampoFormulario::where('ciclo_id', $this->ciclo->id)
                        ->where('etapa', $this->etapa)
                        ->whereBetween('ordem', [$ordemAntiga + 1, $ordemNova])
                        ->decrement('ordem');
                }
            }
        } else {
            // MODO CRIAÇÃO
            // Empurra para baixo qualquer campo da mesma etapa que tenha ordem igual ou maior
            CampoFormulario::where('ciclo_id', $this->ciclo->id)
                ->where('etapa', $this->etapa)
                ->where('ordem', '>=', $this->ordem)
                ->increment('ordem');
        }

        // Salva o Registo
        CampoFormulario::updateOrCreate(
            ['id' => $this->campoId],
            [
                'ciclo_id' => empty($this->ciclo->id) ? 1 : $this->ciclo->id,
                'etapa' => $this->etapa,
                'ordem' => $this->ordem,
                'label' => $this->label,
                'name' => $this->name,
                'tipo' => $this->tipo,
                'largura' => $this->largura,
                'subtipo' => $this->subtipo,
                'tamanho_min' => empty($this->tamanho_min) ? null : $this->tamanho_min,
                'tamanho_max' => empty($this->tamanho_max) ? null : $this->tamanho_max,
                'regex_mascara' => empty($this->regex_mascara) ? null : $this->regex_mascara,
                'opcoes' => $arrayOpcoes,
                'obrigatorio' => $this->obrigatorio,
                'regras_validacao' => $this->regras_validacao,
                'depende_de' => empty($this->depende_de) ? null : $this->depende_de,
                'depende_operador' => $this->depende_operador,
                'depende_valor' => empty($this->depende_valor) ? null : $this->depende_valor,
            ]
        );

        $this->cancelarEdicao(); // Limpa e calcula o próximo espaço vazio
        session()->flash('sucesso', 'Campo salvo e ordenado com sucesso!');
    }

    public function excluir($id)
    {
        $campo = CampoFormulario::findOrFail($id);
        $ordemExcluida = $campo->ordem;
        $etapaExcluida = $campo->etapa;
        
        $campo->delete();
        
        // Puxa os campos de baixo para cima para "tapar o buraco" na listagem visual
        CampoFormulario::where('ciclo_id', $this->ciclo->id)
            ->where('etapa', $etapaExcluida)
            ->where('ordem', '>', $ordemExcluida)
            ->decrement('ordem');

        $this->atualizarProximaOrdem();
        session()->flash('sucesso', 'Campo excluído e lista reordenada com sucesso.');
    }

    public function render()
    {
        // Agrupa os campos por etapa e ordena corretamente
        $camposCadastrados = CampoFormulario::where('ciclo_id', $this->ciclo->id)
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