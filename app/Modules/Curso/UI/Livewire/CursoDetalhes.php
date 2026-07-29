<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Curso\Application\Services\CursoService;
use App\Models\Curso;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Curso - Administrativo')]
class CursoDetalhes extends Component
{
    public Curso $curso;

    public function mount(int $id, CursoService $service)
    {
        // Trava de segurança
        abort_if(!auth()->user()->can('curso.listar'), 403);
        
        // Busca o curso via DDD
        $this->curso = $service->buscarPorId($id);
        
        // Carrega as relações para exibir na tela de detalhes
        $this->curso->load(['unidades', 'turnosVinculados', 'ciclos']);
    }

    public function render()
    {
        return view('livewire.curso.curso-detalhes');
    }
}