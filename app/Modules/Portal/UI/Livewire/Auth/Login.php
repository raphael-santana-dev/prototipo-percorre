<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

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
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Proteção contra Força Bruta (Brute Force)
        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Muitas tentativas de login. Tente novamente em {$seconds} segundos.");
            $this->dispatch('erro', msg: "Conta temporariamente bloqueada por segurança.");
            return;
        }

        $credenciais = ['email' => $this->email, 'password' => $this->password];

        // ------------------------------------------------------------------
        // CASCATA DE AUTENTICAÇÃO: O SISTEMA DESCOBRE QUEM É O USUÁRIO
        // ------------------------------------------------------------------

        // 1ª Tentativa: É um Aluno?
        if (Auth::guard('student')->attempt($credenciais, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            session()->flash('sucesso', 'Bem-vindo(a) ao Portal do Aluno!');
            return redirect()->route('student.dashboard');
        }

        // 2ª Tentativa: É um Contato de Empresa ou Gestor?
        if (Auth::guard('company')->attempt($credenciais, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            session()->flash('sucesso', 'Bem-vindo(a) ao Portal da Empresa!');
            return redirect()->route('company.dashboard'); 
        }

        // 3º Resultado: Não encontrou em nenhum dos dois "mundos"
        RateLimiter::hit($throttleKey); // Registra o erro no contador
        
        $this->addError('email', 'As credenciais fornecidas estão incorretas.');
        $this->dispatch('erro', msg: 'E-mail ou senha incorretos. Tente novamente.');
    }

    public function render()
    {
        return view('livewire.portal.auth.login');
    }
}