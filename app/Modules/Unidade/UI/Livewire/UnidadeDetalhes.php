<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use App\Modules\Unidade\Domain\Models\Unidade;

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

        $user = auth()->user();
        if (!$user->temVisaoGlobal('unidades')) {
            $cursosPermitidos = $user->cursos->pluck('id')->toArray();
            
            $this->unidade->load(['cursos' => function($q) use ($cursosPermitidos) {
                if (count($cursosPermitidos) > 0) {
                    $q->whereIn('cursos.id', $cursosPermitidos);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }]);
        } else {
            $this->unidade->load(['cursos']);
        }
    }

    public function render()
    {
        return view('livewire.unidade.unidade-detalhes');
    }
}