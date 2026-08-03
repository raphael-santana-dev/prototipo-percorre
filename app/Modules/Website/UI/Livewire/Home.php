<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;

#[Layout('components.layouts.public')]
#[Title('Bem-vindo - Instituto Percorre')]
class Home extends Component
{
    public $inscricoesAbertas = false;

    public function mount()
    {
        $ciclo = Ciclo::with('campos')->where('status', true)
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->first();
        
        if ($ciclo) {
            $this->inscricoesAbertas = true;
        }
    }

    public function render()
    {
        return view('livewire.website.home');
    }
}