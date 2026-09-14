<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    public string $cssClass = 'px-5 py-2 text-sm font-bold text-white transition-transform transform rounded shadow-sm bg-[#ef4444] hover:bg-[#dc2626] hover:-translate-y-0.5 ml-2';

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

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.portal.auth.logout-button');
    }
}