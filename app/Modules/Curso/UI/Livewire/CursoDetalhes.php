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

    #[Computed]
    public function curso()
    {
        $user = auth()->user();
        
        return Curso::with([
            // Filtra os relacionamentos cruzando as tabelas pivot automaticamente pelo Eloquent
            'unidades' => function($q) use ($user) {
                if (!$user->temVisaoGlobal('cursos')) {
                    $idsUnidades = $user->unidades->pluck('id')->toArray();
                    $q->whereIn('unidades.id', count($idsUnidades) > 0 ? $idsUnidades : [0]);
                }
            },
            'turnosVinculados' => function($q) use ($user) {
                if (!$user->temVisaoGlobal('cursos')) {
                    $idsTurnos = $user->turnos->pluck('id')->toArray();
                    $q->whereIn('turnos.id', count($idsTurnos) > 0 ? $idsTurnos : [0]);
                }
            }
        ])->findOrFail($this->cursoId);
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
        return \App\Models\Inscricao::where('curso_id', $this->cursoId)
            ->apenasVinculosPermitidos()
            ->latest()
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.curso.curso-detalhes');
    }
}