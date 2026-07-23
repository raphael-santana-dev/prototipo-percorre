<?php

namespace App\Modules\Auth\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.auth')]
#[Title('Login - Painel Administrativo')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function authenticate()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return redirect()->intended('/dashboard'); // Rota que criaremos no futuro
        }

        $this->addError('email', 'As credenciais fornecidas estão incorretas.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}