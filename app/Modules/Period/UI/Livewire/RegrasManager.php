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
    public array $regras = []; // Guardará todas as regras ativas na tela
    public array $camposDisponiveis = [];

    public function mount($id)
    {
        $this->ciclo = Ciclo::findOrFail($id);
        
        // Puxa as regras existentes no banco de dados
        $this->regras = is_string($this->ciclo->regras_pontuacao) 
                        ? json_decode($this->ciclo->regras_pontuacao, true) 
                        : ($this->ciclo->regras_pontuacao ?? []);
        
        $this->carregarCampos();
        
        // Se não tiver nenhuma regra, já inicia com uma linha vazia para facilitar
        if (empty($this->regras)) {
            $this->addRegra();
        }
    }

    public function carregarCampos()
    {
        // Campos nativos do sistema que também podem gerar pontos
        $fixos = [
            ['name' => 'idade', 'label' => 'Idade (Calculada pela Data de Nascimento)'],
            ['name' => 'estado', 'label' => 'Estado (UF)'],
            ['name' => 'cidade', 'label' => 'Cidade'],
            ['name' => 'curso_id', 'label' => 'Curso Selecionado (ID)'],
            ['name' => 'turno_id', 'label' => 'Turno Selecionado (ID)'],
            ['name' => 'possui_deficiencia', 'label' => 'Possui Deficiência? (sim/nao)'],
        ];

        // Busca todos os campos dinâmicos que exigem resposta (ignora textos estáticos e banners)
        $dinamicos = CampoFormulario::where('ciclo_id', $this->ciclo->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->get()
            ->map(fn($c) => ['name' => $c->name, 'label' => $c->label . ' (Dinâmico)'])
            ->toArray();

        $this->camposDisponiveis = array_merge($fixos, $dinamicos);
    }

    // Adiciona uma nova linha em branco no array
    public function addRegra()
    {
        $this->regras[] = [
            'campo' => '',
            'operador' => '=',
            'valor' => '',
            'pontos' => 0
        ];
    }

    // Remove uma linha específica do array
    public function removeRegra($index)
    {
        unset($this->regras[$index]);
        $this->regras = array_values($this->regras); // Reordena os índices
    }

    public function salvar()
    {
        // Valida se as regras adicionadas estão preenchidas corretamente
        $this->validate([
            'regras.*.campo' => 'required|string',
            'regras.*.operador' => 'required|string',
            'regras.*.valor' => 'required|string',
            'regras.*.pontos' => 'required|numeric',
        ], [
            'regras.*.campo.required' => 'Escolha um campo.',
            'regras.*.valor.required' => 'Informe o valor esperado.',
            'regras.*.pontos.required' => 'A pontuação é obrigatória.',
        ]);

        // Salva tudo de uma vez no banco de dados!
        $this->ciclo->update(['regras_pontuacao' => $this->regras]);
        
        session()->flash('sucesso', 'Regras de pontuação salvas com sucesso!');
    }

    public function render()
    {
        return view('livewire.period.regras-manager');
    }
}