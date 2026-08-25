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
        abort_if(!feature('unidade.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.visualizar'), 403);
        
        $this->unidade = $service->buscarPorId($id);
        $this->unidade->load(['cursos']);
    }

    public function render()
    {
        return view('livewire.unidade.unidade-detalhes');
    }
}