<?php

namespace App\Modules\Auth\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

#[Layout('components.layouts.public')]
#[Title('Acesso ao Portal - Instituto Percorre')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function authenticate()
    {
        $this->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Muitas tentativas de login. Tente novamente em {$seconds} segundos.");
            $this->dispatch('erro', msg: "Conta bloqueada temporariamente por segurança.");
            return;
        }

        $credenciais = ['email' => $this->email, 'password' => $this->password];

        if (Auth::guard('web')->attempt($credenciais, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            return redirect()->route('dashboard');
        }

        if (Auth::guard('student')->attempt($credenciais, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            return redirect()->route('student.dashboard');
        }

        if (Auth::guard('company')->attempt($credenciais, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            return redirect()->route('company.dashboard'); 
        }

        RateLimiter::hit($throttleKey);
        
        $this->addError('email', 'As credenciais fornecidas estão incorretas.');
        $this->dispatch('erro', msg: 'E-mail ou senha incorretos. Tente novamente.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}