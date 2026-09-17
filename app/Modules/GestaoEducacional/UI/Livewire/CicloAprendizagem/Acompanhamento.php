<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\CicloAprendizagem;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\CicloAprendizagem;
use App\Models\AlunoCicloAprendizagem;
use App\Models\RespostaFormulario;

#[Layout('components.layouts.app')]
#[Title('Acompanhamento de Avaliações')]
class Acompanhamento extends Component
{
    use WithPagination;

    public CicloAprendizagem $ciclo;
    public $busca = '';
    public $filtroStatus = '';
    
    public $modalRespostasAberto = false;
    public $alunoSelecionado = null;
    public $respostasAluno = [];

    public function mount($id) {
        $this->ciclo = CicloAprendizagem::with('fases')->findOrFail($id);
    }

    public function updatingBusca() { $this->resetPage(); }
    public function updatingFiltroStatus() { $this->resetPage(); }

    public function verRespostas($alunoId)
    {
        $this->alunoSelecionado = \App\Modules\Student\Domain\Models\Student::find($alunoId);
        
        $formulariosIds = \App\Models\Formulario::whereIn('ciclo_aprendizagem_fase_id', $this->ciclo->fases->pluck('id'))->pluck('id');
        
        $this->respostasAluno = RespostaFormulario::with('formulario.faseAprendizagem')
            ->where('user_id', $alunoId)
            ->whereIn('formulario_id', $formulariosIds)
            ->get();
            
        $this->modalRespostasAberto = true;
    }

    public function fecharModal()
    {
        $this->modalRespostasAberto = false;
        $this->alunoSelecionado = null;
        $this->respostasAluno = [];
    }

    public function render()
    {
        $query = AlunoCicloAprendizagem::with(['student.empresa', 'faseAtual'])
            ->where('ciclo_aprendizagem_id', $this->ciclo->id);

        if ($this->busca) {
            $query->whereHas('student', function($q) {
                $q->where('name', 'ilike', '%' . $this->busca . '%')
                  ->orWhere('cpf', 'like', '%' . preg_replace('/\D/', '', $this->busca) . '%');
            });
        }

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }

        return view('livewire.gestao-educacional.ciclo-aprendizagem.acompanhamento', [
            'alunos' => $query->paginate(15)
        ]);
    }
}