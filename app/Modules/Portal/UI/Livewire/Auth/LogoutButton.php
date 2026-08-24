<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    // Permite que o layout defina classes CSS diferentes para o botão
    public string $cssClass = 'px-5 py-2 text-sm font-bold text-white transition-transform transform rounded shadow-sm bg-[#ef4444] hover:bg-[#dc2626] hover:-translate-y-0.5 ml-2';

    public function logout()
    {
        // Desloga o guard ativo
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
        // Renderiza a view física
        return view('livewire.portal.auth.logout-button');
    }
}