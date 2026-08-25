<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Curso;
use App\Models\User;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Curso - Administrativo')]
class CursoDetalhes extends Component
{
    public int $cursoId;

    public function mount(int $id)
    {
        abort_if(!feature('curso.visualizar'), 403, 'O módulo de visualização de cursos está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.visualizar'), 403, 'Você não tem acesso aos detalhes do curso.');
        $this->cursoId = $id; 
    }

    // ==========================================
    // PROPRIEDADES COMPUTADAS (Apenas Leitura)
    // ==========================================
    
    #[Computed]
    public function curso()
    {
        return Curso::with(['unidades', 'turnosVinculados'])->findOrFail($this->cursoId);
    }
    
    #[Computed]
    public function professoresVinculados()
    {
        return User::role('professor')
            ->whereHas('cursos', function($q) {
                $q->where('cursos.id', $this->cursoId);
            })->get();
    }

    #[Computed]
    public function inscricoesRecentes()
    {
        return $this->curso->inscricoes()->latest()->take(10)->get();
    }

    public function render()
    {
        return view('livewire.curso.curso-detalhes');
    }
}