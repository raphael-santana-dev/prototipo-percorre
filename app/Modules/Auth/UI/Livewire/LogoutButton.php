<?php

namespace App\Modules\Auth\UI\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutButton extends Component
{
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/login');
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}