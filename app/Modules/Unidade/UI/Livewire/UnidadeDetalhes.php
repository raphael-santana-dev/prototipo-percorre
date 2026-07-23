<?php
namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Domain\Models\Unidade;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Unidade - Percorre')]
class UnidadeDetalhes extends Component
{
    public Unidade $unidade;

    public function mount(int $id)
    {
        abort_if(!auth()->user()->can('unidade.listar'), 403);
        // O escopo global garante que, se o usuário não puder ver essa unidade, o findOrFail retornará 404!
        $this->unidade = Unidade::with('usuarios')->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.unidade.unidade-detalhes');
    }
}