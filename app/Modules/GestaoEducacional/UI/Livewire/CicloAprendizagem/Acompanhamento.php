<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\CicloAprendizagem;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\CicloAprendizagem;
use App\Models\AlunoCicloAprendizagem;

#[Layout('components.layouts.app')]
#[Title('Acompanhamento de Ciclos')]
class Acompanhamento extends Component
{
    use WithPagination;

    public $ciclo_id;
    public $busca = '';
    public $filtroStatus = '';

    public function mount($id)
    {
        $this->ciclo_id = $id;
        $ciclo = CicloAprendizagem::findOrFail($id);
    }

    public function render()
    {
        $ciclo = CicloAprendizagem::with('fases')->findOrFail($this->ciclo_id);
        
        $alunos = AlunoCicloAprendizagem::with(['student.empresa', 'faseAtual'])
            ->where('ciclo_aprendizagem_id', $this->ciclo_id)
            ->whereHas('student', function ($query) {
                $query->where('name', 'ilike', '%' . $this->busca . '%');
            })
            ->when($this->filtroStatus, function ($query) {
                $query->where('status', $this->filtroStatus);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.gestao-educacional.ciclo-aprendizagem.acompanhamento', compact('ciclo', 'alunos'));
    }
}