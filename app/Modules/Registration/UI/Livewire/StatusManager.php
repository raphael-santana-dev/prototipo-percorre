<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\StatusInscricao;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Status de Inscrição - Administrativo')]
class StatusManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $statusId = null;

    public string $nome = '';
    public string $descricao = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit(int $id)
    {
        $this->resetInputFields();
        $status = StatusInscricao::findOrFail($id);
        
        $this->statusId = $status->id;
        $this->nome = $status->nome;
        $this->descricao = $status->descricao ?? '';
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'nome' => 'required|string|max:255|unique:status_inscricoes,nome,' . $this->statusId,
            'descricao' => 'nullable|string|max:500',
        ], [
            'nome.required' => 'O nome do status é obrigatório.',
            'nome.unique' => 'Este status já está cadastrado.',
        ]);

        $data = [
            'nome' => $this->nome,
            'descricao' => $this->descricao,
        ];

        if ($this->isEditMode) {
            StatusInscricao::findOrFail($this->statusId)->update($data);
        } else {
            StatusInscricao::create($data);
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Status atualizado com sucesso!' : 'Status cadastrado com sucesso!');
    }

    public function delete(int $id)
    {
        StatusInscricao::findOrFail($id)->delete();
        session()->flash('success', 'Status excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->statusId = null;
        $this->nome = '';
        $this->descricao = '';
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.registration.status-manager', [
            'statuses' => StatusInscricao::orderBy('id')->paginate(10)
        ]);
    }
}