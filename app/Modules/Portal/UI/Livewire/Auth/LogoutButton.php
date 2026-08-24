<?php

namespace App\Modules\Portal\UI\Livewire\Auth; // <-- Namespace atualizado!

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    #[On('logout')]
    public function logout()
    {
        if (Auth::guard('student')->check()) {
            Auth::guard('student')->logout();
        }
        
        if (Auth::guard('company')->check()) {
            Auth::guard('company')->logout();
        }

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function render()
    {
        return <<<'HTML'
            <div></div>
        HTML;
    }
}