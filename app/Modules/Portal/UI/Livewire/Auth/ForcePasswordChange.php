<?php

namespace App\Modules\Portal\UI\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

#[Layout('components.layouts.public')] 
#[Title('Atualização de Segurança - Instituto Percorre')]
class ForcePasswordChange extends Component
{
    public string $password = '';
    public string $password_confirmation = '';
    public function logout()
    {
        if (\Illuminate\Support\Facades\Auth::guard('student')->check()) {
            \Illuminate\Support\Facades\Auth::guard('student')->logout();
        }
        if (\Illuminate\Support\Facades\Auth::guard('company')->check()) {
            \Illuminate\Support\Facades\Auth::guard('company')->logout();
        }
        if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
        }

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }
    public function salvar()
    {
        $this->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8) 
                    ->mixedCase()
                    ->numbers() 
                    ->symbols() 
            ]
        ], [
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.symbols' => 'A senha deve conter pelo menos um caractere especial (!, @, #, etc).',
        ]);

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