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

        // 1. Tenta encontrar no mundo Administrativo (users)
        $response = Password::broker('users')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        // 2. Tenta encontrar no mundo dos Estudantes (students)
        $response = Password::broker('students')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        // 3. Tenta encontrar no mundo Corporativo (company_users)
        $response = Password::broker('company_users')->sendResetLink(['email' => $email]);
        if ($response == Password::RESET_LINK_SENT) {
            $this->status = trans($response);
            return;
        }

        // Por segurança, se não achar em nenhum (ou erro genérico), exibe mensagem padrão
        $this->errorMessage = 'Não encontramos nenhum cadastro com este e-mail em nossos sistemas.';
    }

    public function render()
    {
        return view('livewire.portal.auth.forgot-password');
    }
}