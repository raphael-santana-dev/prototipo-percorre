<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;

#[Layout('components.layouts.public')]
#[Title('Recuperar Senha - Instituto Percorre')]
class ForgotPassword extends Component
{
    public string $email = '';
    public string $status = '';
    public string $errorMessage = '';

    public function enviarLink()
    {
        $this->validate([
            'email' => 'required|email'
        ]);

        $this->status = '';
        $this->errorMessage = '';

        $email = $this->email;

        $response = Password::broker('users')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        $response = Password::broker('students')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        $response = Password::broker('company_users')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        $this->errorMessage = 'Não encontramos nenhum cadastro com este e-mail em nossos sistemas.';
    }

    public function render()
    {
        return view('livewire.portal.auth.forgot-password');
    }
}