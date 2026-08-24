<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

#[Layout('components.layouts.public')]
#[Title('Redefinir Senha - Instituto Percorre')]
class ResetPassword extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function redefinir()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ]
        ]);

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token' => $this->token
        ];

        // Função (callback) de como a senha deve ser salva em qualquer dos models
        $resetCallback = function ($user, $password) {
            $user->password = Hash::make($password);
            // Se tiver a trava de segurança de primeiro acesso, já libera!
            if (isset($user->must_change_password)) {
                $user->must_change_password = false;
            }
            $user->save();
        };

        // 1. Tenta no admin
        $status = Password::broker('users')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('login');
        }

        // 2. Tenta nos estudantes
        $status = Password::broker('students')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('portal.login');
        }

        // 3. Tenta nas empresas
        $status = Password::broker('company_users')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('portal.login');
        }

        // Se falhar (token inválido ou expirado)
        $this->addError('email', trans($status));
    }

    public function render()
    {
        return view('livewire.portal.auth.reset-password');
    }
}