<?php

namespace App\Modules\FormBuilder\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;

#[Layout('components.layouts.app')]
#[Title('Central de Formulários')]
class Hub extends Component
{
    public $modalCicloAberto = false;
    public $ciclos;

    public function mount()
    {
        abort_if(!feature('formulario.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('formulario.listar'), 403, 'Acesso restrito.');
        
        // Busca ciclos recentes para facilitar a seleção na modal
        $this->ciclos = Ciclo::orderBy('id', 'desc')->take(10)->get();
    }

    public function selecionarCiclo($cicloId)
    {
        return redirect()->route('construtor.campos', ['tipo' => 'ciclo', 'id' => $cicloId]);
    }

    public function novoCiclo()
    {
        return redirect()->route('ciclos.index');
    }

    public function render()
    {
        return view('livewire.form-builder.hub');
    }
}