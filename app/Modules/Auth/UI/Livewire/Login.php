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

    public function mount()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }
        if (Auth::guard('student')->check()) {
            return redirect()->route('student.dashboard');
        }
        if (Auth::guard('company')->check()) {
            return redirect()->route('company.dashboard');
        }
    }

    public function authenticate()
    {
        $this->email = trim($this->email);

        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Muitas tentativas de login. Tente novamente em {$seconds} segundos.");
            $this->dispatch('erro', msg: "Conta bloqueada temporariamente por segurança.");
            return;
        }

        $credenciais = ['email' => $this->email, 'password' => $this->password];
        $manterConectado = (bool) $this->remember;

        if (Auth::guard('web')->attempt($credenciais, $manterConectado)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            session(['manter_conectado' => $manterConectado]);
            return redirect()->intended(route('dashboard'));
        }

        if (Auth::guard('student')->attempt($credenciais, $manterConectado)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            session(['manter_conectado' => $manterConectado]);
            return redirect()->intended(route('student.dashboard'));
        }

        if (Auth::guard('company')->attempt($credenciais, $manterConectado)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            session(['manter_conectado' => $manterConectado]);
            return redirect()->intended(route('company.dashboard'));
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