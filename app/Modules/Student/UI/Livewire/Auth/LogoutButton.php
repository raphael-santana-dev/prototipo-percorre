<?php

namespace App\Modules\Student\UI\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    public function logout()
    {
        Auth::guard('student')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('student.login');
    }

    public function render()
    {
        return <<<'HTML'
            <button wire:click="logout" class="flex items-center gap-2 px-3 py-2 text-sm text-indigo-100 transition-colors rounded-md hover:bg-indigo-700 hover:text-white">
                Sair
            </button>
        HTML;
    }
}