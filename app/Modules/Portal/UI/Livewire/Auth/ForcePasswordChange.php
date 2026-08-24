<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

#[Layout('components.layouts.public')] // Usamos o layout público (limpo) para evitar que ele navegue
#[Title('Atualização de Segurança - Instituto Percorre')]
class ForcePasswordChange extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    public function salvar()
    {
        // 1. Validação de Senha Forte
        $this->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8) // Mínimo de 8 caracteres
                    ->mixedCase() // Pelo menos uma maiúscula e uma minúscula
                    ->numbers() // Pelo menos um número
                    ->symbols() // Pelo menos um caractere especial (!, @, #, $, etc)
            ]
        ], [
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.symbols' => 'A senha deve conter pelo menos um caractere especial (!, @, #, etc).',
        ]);

        // 2. Descobre quem é o usuário logado e qual o seu destino
        $user = null;
        $rotaDestino = '';

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $rotaDestino = 'dashboard';
        } elseif (Auth::guard('student')->check()) {
            $user = Auth::guard('student')->user();
            $rotaDestino = 'student.dashboard';
        } elseif (Auth::guard('company')->check()) {
            $user = Auth::guard('company')->user();
            $rotaDestino = 'company.dashboard';
        }

        // 3. Atualiza o banco de dados e remove a trava
        if ($user) {
            $user->update([
                'password' => Hash::make($this->password),
                'must_change_password' => false,
            ]);

            session()->flash('sucesso', 'Senha atualizada com sucesso! Bem-vindo(a).');
            return redirect()->route($rotaDestino);
        }
    }

    public function render()
    {
        return view('livewire.portal.auth.force-password-change');
    }
}