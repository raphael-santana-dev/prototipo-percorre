<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Inscrições - Administrativo')]
class RegistrationManager extends Component
{
    public bool $showModal = false;
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    public function render()
    {
        return view('livewire.registration.registration-manager', [
            'inscricoes' => Inscricao::orderBy('id')->get(),
        ]);
    }
}