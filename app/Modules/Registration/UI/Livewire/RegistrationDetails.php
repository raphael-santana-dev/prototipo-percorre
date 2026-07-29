<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\StatusInscricao;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Inscrição - Administrativo')]
class RegistrationDetails extends Component
{
    public Inscricao $inscricao;

    public function mount(int $id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        
        // Eager load de todos os relacionamentos
        $this->inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao'])->findOrFail($id);
    }

    public function alterarStatus(int $statusId)
    {
        $this->inscricao->status_inscricao_id = $statusId;
        $this->inscricao->save();
        
        // Atualiza a relação em memória para a tela reagir instantaneamente
        $this->inscricao->load('statusInscricao');
        
        $this->dispatch('sucesso', msg: 'Status do candidato atualizado!');
    }

    public function render()
    {
        return view('livewire.registration.registration-details', [
            'statusInscricoesDb' => StatusInscricao::orderBy('nome')->get()
        ]);
    }
}