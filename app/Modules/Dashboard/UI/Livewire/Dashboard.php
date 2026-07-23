<?php

namespace App\Modules\Dashboard\UI\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard - Painel Administrativo')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard.dashboard');
    }
}