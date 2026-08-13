<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\CampoFormulario;

#[Layout('components.layouts.app')]
#[Title('Regras de Pontuação')]
class RegrasManager extends Component
{
    public Ciclo $ciclo;
    public array $regras = []; 
    public array $camposDisponiveis = [];

    public function mount($id, ?string $slug = null)
    {
        $this->ciclo = Ciclo::findOrFail($id);
        
        $regrasDb = is_string($this->ciclo->regras_pontuacao) 
                    ? json_decode($this->ciclo->regras_pontuacao, true) 
                    : ($this->ciclo->regras_pontuacao ?? []);
                    
        // Retrocompatibilidade: Garante que regras antigas tenham as novas chaves
        $this->regras = array_map(function($regra) {
            $regra['tipo_regra'] = $regra['tipo_regra'] ?? 'padrao';
            $regra['escopo'] = $regra['escopo'] ?? 'especifico';
            return $regra;
        }, $regrasDb);
        
        $this->carregarCampos();
        
        if (empty($this->regras)) {
            $this->addRegra();
        }
    }

    public function carregarCampos()
    {
        $fixos = [
            ['name' => 'idade', 'label' => 'Idade (Calculada pela Data de Nascimento)'],
            ['name' => 'estado', 'label' => 'Estado (UF)'],
            ['name' => 'cidade', 'label' => 'Cidade'],
            ['name' => 'curso_id', 'label' => 'Curso Selecionado (ID)'],
            ['name' => 'turno_id', 'label' => 'Turno Selecionado (ID)'],
            ['name' => 'possui_deficiencia', 'label' => 'Possui Deficiência? (sim/nao)'],
        ];

        $dinamicos = CampoFormulario::where('ciclo_id', $this->ciclo->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->get()
            ->map(fn($c) => ['name' => $c->name, 'label' => $c->label . ' (Dinâmico)'])
            ->toArray();

        $this->camposDisponiveis = array_merge($fixos, $dinamicos);
    }

    public function addRegra()
    {
        $this->regras[] = [
            'tipo_regra' => 'padrao', 
            'escopo' => 'especifico', // 'todos' ou 'especifico'
            'campo' => '',
            'operador' => '=',
            'valor' => '',
            'pontos' => 0
        ];
    }

    public function removeRegra($index)
    {
        unset($this->regras[$index]);
        $this->regras = array_values($this->regras); 
    }

    public function salvar()
    {
        $rules = [
            'regras.*.tipo_regra' => 'required|in:padrao,bonus_por_acerto,multiplicador_percentual',
            'regras.*.escopo' => 'nullable|in:todos,especifico',
            'regras.*.pontos' => 'required|numeric',
        ];

        $messages = [
            'regras.*.tipo_regra.required' => 'Escolha o tipo de cálculo.',
            'regras.*.pontos.required' => 'A pontuação é obrigatória.',
            'regras.*.campo.required' => 'Escolha um campo.',
            'regras.*.valor.required' => 'Informe o valor esperado.',
        ];

        // VALIDAÇÃO DINÂMICA: Exige os campos apenas se a regra não for Global (Todos)
        foreach ($this->regras as $index => $regra) {
            $tipo = $regra['tipo_regra'] ?? 'padrao';
            $escopo = $regra['escopo'] ?? 'especifico';

            if ($tipo === 'padrao' || $escopo === 'especifico') {
                $rules["regras.{$index}.campo"] = 'required|string';
                $rules["regras.{$index}.operador"] = 'required|string';
                $rules["regras.{$index}.valor"] = 'required|string';
            } else {
                // Se for Global ('todos'), não exige campo e limpa os valores
                $rules["regras.{$index}.campo"] = 'nullable';
                $rules["regras.{$index}.operador"] = 'nullable';
                $rules["regras.{$index}.valor"] = 'nullable';
                
                $this->regras[$index]['campo'] = '';
                $this->regras[$index]['operador'] = '=';
                $this->regras[$index]['valor'] = '';
            }
        }

        $this->validate($rules, $messages);

        $this->ciclo->update(['regras_pontuacao' => $this->regras]);
        
        session()->flash('sucesso', 'Regras de pontuação salvas com sucesso!');
    }

    public function render()
    {
        return view('livewire.period.regras-manager');
    }
}