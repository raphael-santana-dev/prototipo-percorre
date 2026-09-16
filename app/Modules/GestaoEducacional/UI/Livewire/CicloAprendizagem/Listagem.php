<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\CicloAprendizagem;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\CicloAprendizagem;
use App\Models\CicloAprendizagemFase;

#[Layout('components.layouts.app')]
#[Title('Ciclos de Aprendizagem')]
class Listagem extends Component
{
    use WithPagination;

    public $modalAberto = false;
    public $busca = '';
    
    // Campos do Form
    public $nome, $data_inicio, $data_fim;
    public $fases = []; 
    // Opções de quem pode responder
    public $opcoesRespondedores = [
        'student' => 'Aprendiz (Estudante)',
        'company' => 'Gestor (Empresa)',
        'teacher' => 'Professor'
    ];

    public function mount()
    {
        // Começa com pelo menos 1 fase padrão
        $this->adicionarFase();
    }

    public function abrirModal()
    {
        $this->reset(['nome', 'data_inicio', 'data_fim', 'fases']);
        $this->adicionarFase();
        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
    }

    public function adicionarFase()
    {
        $this->fases[] = [
            'nome' => 'Fase ' . (count($this->fases) + 1),
            'respondedores' => []
        ];
    }

    public function removerFase($index)
    {
        if(count($this->fases) > 1) {
            unset($this->fases[$index]);
            $this->fases = array_values($this->fases);
        }
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|string|min:3',
            'fases' => 'required|array|min:1',
            'fases.*.nome' => 'required|string',
            'fases.*.respondedores' => 'required|array|min:1'
        ], [
            'fases.*.respondedores.required' => 'Selecione pelo menos um responsável para esta fase.'
        ]);

        $ciclo = CicloAprendizagem::create([
            'nome' => $this->nome,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
        ]);

        foreach ($this->fases as $index => $fase) {
            CicloAprendizagemFase::create([
                'ciclo_aprendizagem_id' => $ciclo->id,
                'nome' => $fase['nome'],
                'ordem' => $index + 1,
                'respondedores_permitidos' => array_keys(array_filter($fase['respondedores']))
            ]);
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: 'Ciclo de aprendizagem criado com sucesso!');
    }

    public function render()
    {
        $ciclos = CicloAprendizagem::with('fases')
            ->where('nome', 'ilike', '%' . $this->busca . '%')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.gestao-educacional.ciclo-aprendizagem.listagem', compact('ciclos'));
    }
}