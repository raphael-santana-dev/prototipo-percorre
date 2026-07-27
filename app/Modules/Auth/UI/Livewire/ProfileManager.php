<?php

namespace App\Modules\Auth\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

#[Layout('components.layouts.app')]
#[Title('Meu Perfil - Instituto Percorre')]
class ProfileManager extends Component
{
    // Dados Pessoais
    public string $name = '';
    public string $email = '';

    // Segurança
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', Rule::unique(get_class($user), 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $this->name,
            'email' => strtolower($this->email),
        ]);

        // Dispara um evento para o navegador atualizar o nome na Navbar instantaneamente (sem F5)
        $this->dispatch('profile-updated');
        session()->flash('success_profile', 'Seus dados foram atualizados com sucesso!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $user = auth()->user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'A senha atual está incorreta.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success_password', 'Senha alterada com segurança!');
    }

    public function render()
    {
        return view('livewire.auth.profile-manager');
    }
}