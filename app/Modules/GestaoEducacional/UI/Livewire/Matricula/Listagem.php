<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Matricula;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Matricula;
use App\Traits\ComPadraoListagem;

class Listagem extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';

    public function getHeadersProperty()
    {
        return [
            ['key' => 'numero_matricula', 'label' => 'RA / Matrícula', 'sortable' => true],
            ['key' => 'student', 'label' => 'Estudante', 'sortable' => false],
            ['key' => 'curso', 'label' => 'Curso Base', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function updatingBusca() { $this->resetPage(); }

    public function excluir($id)
    {
        $matricula = Matricula::findOrFail($id);
        $matricula->delete();
        $this->dispatch('sucesso', msg: 'Matrícula excluída e vínculo com o aluno removido.');
    }

    public function render()
    {
        $query = Matricula::with(['student', 'curso'])
            ->where('numero_matricula', 'ilike', '%' . $this->busca . '%')
            ->orWhereHas('student', function($q) {
                $q->where('name', 'ilike', '%' . $this->busca . '%')
                  ->orWhere('cpf', 'ilike', '%' . $this->busca . '%');
            });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.gestao-educacional.matricula.listagem', [
            'registros' => $query->paginate($this->porPagina),
        ])->layout('components.layouts.app', ['title' => 'Gestão de Matrículas']);
    }
}