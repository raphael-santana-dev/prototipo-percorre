<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.public')]
#[Title('Bem-vindo - Instituto Percorre')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.website.home');
    }
}