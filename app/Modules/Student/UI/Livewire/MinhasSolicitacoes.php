<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Solicitacao;

#[Layout('components.layouts.student-app')]
#[Title('Minhas Solicitações - Portal do Aluno')]
class MinhasSolicitacoes extends Component
{
    use WithPagination;

    public $modalDetalhes = false;
    public $solicitacaoAtiva = null;

    public function abrirDetalhes($id)
    {
        $this->solicitacaoAtiva = Solicitacao::with('responsavel')
            ->where('solicitante_id', auth('student')->id())
            ->findOrFail($id);
            
        $this->modalDetalhes = true;
    }

    public function render()
    {
        $student = auth('student')->user();
        
        $solicitacoes = Solicitacao::where('solicitante_type', get_class($student))
            ->where('solicitante_id', $student->id)
            ->latest()
            ->paginate(15);

        return view('livewire.student.minhas-solicitacoes', [
            'solicitacoes' => $solicitacoes
        ]);
    }
}