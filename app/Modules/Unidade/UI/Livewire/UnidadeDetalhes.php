<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use App\Modules\Unidade\Domain\Models\Unidade; // Model atualizado

#[Layout('components.layouts.app')]
#[Title('Detalhes da Unidade - Administrativo')]
class UnidadeDetalhes extends Component
{
    public Unidade $unidade;

    public function mount(int $id, UnidadeService $service)
    {
        // Trava de segurança original mantida
        // abort_if(!auth()->user()->can('unidade.listar'), 403);
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        
        // Busca a unidade passando pelo Service (DDD)
        $this->unidade = $service->buscarPorId($id);
        
        // Carrega as relações (Eager Loading) para a View (Quick View e Detalhes)
        $this->unidade->load(['cursos']);
    }

    public function render()
    {
        return view('livewire.unidade.unidade-detalhes');
    }
}