<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Turma;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Turma;
use App\Traits\ComPadraoListagem;

class Listagem extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';

    public function getHeadersProperty()
    {
        return [
            ['key' => 'nome', 'label' => 'Nome da Turma', 'sortable' => true],
            ['key' => 'curso', 'label' => 'Curso / Unidade', 'sortable' => false],
            ['key' => 'matriculas_count', 'label' => 'Alunos', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function updatingBusca() { $this->resetPage(); }

    public function excluir($id)
    {
        $turma = Turma::withCount('matriculas')->findOrFail($id);
        
        if ($turma->matriculas_count > 0) {
            $this->dispatch('erro', msg: 'Não é possível excluir uma turma que possui alunos matriculados.');
            return;
        }

        $turma->delete();
        $this->dispatch('sucesso', msg: 'Turma excluída com sucesso.');
    }

    public function render()
    {
        // Carrega a turma com seus relacionamentos base e a contagem de alunos
        $query = Turma::with(['curso', 'unidade', 'turno', 'professores'])
            ->withCount('matriculas')
            ->where('nome', 'ilike', '%' . $this->busca . '%');

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.gestao-educacional.turma.listagem', [
            'registros' => $query->paginate($this->porPagina),
        ])->layout('components.layouts.app', ['title' => 'Gestão de Turmas']);
    }
}