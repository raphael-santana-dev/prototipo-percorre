<?php

namespace App\Modules\Auth\UI\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    public function logout()
    {
        // 1. Encerra a sessão do usuário no Auth Guard (Isso disparará o evento de Logout que criamos acima!)
        Auth::logout();

        // 2. Invalida a sessão atual para remover qualquer dado residual na memória
        session()->invalidate();

        // 3. Gera um novo token CSRF para a próxima visita
        session()->regenerateToken();

        // 4. Redireciona para a tela de login
        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}