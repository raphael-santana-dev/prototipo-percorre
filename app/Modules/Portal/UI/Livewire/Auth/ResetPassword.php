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

        $resetCallback = function ($user, $password) {
        $user->password = Hash::make($password);
        if (isset($user->must_change_password)) {
            $user->must_change_password = false;
        }
        $user->save();
        };

        $status = Password::broker('users')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('login');
        }

        $status = Password::broker('students')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('login');
        }

        $status = Password::broker('company_users')->reset($credentials, $resetCallback);
        if ($status == Password::PASSWORD_RESET) {
            session()->flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
            return redirect()->route('login');
        }

        $this->addError('email', trans($status));
    }

    public function render()
    {
        return view('livewire.portal.auth.reset-password');
    }
}