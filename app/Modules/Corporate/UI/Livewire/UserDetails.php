<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;

#[Layout('components.layouts.app')]
#[Title('Perfil do Usuário - Administrativo')]
class UserDetails extends Component
{
    public User $user;

    public function mount(int $id)
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
        $this->user = User::with(['roles', 'permissions', 'unidade'])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.corporate.user-details');
    }
}