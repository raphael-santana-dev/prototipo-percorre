<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Usamos o layout principal (se você tiver um layout específico para aluno, altere aqui)
#[Layout('components.layouts.student-app')]
#[Title('Meu Perfil - Portal do Estudante')]
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
        // Garante que estamos pegando a sessão do guard 'student'
        $student = auth('student')->user();
        
        $this->name = $student->name;
        $this->email = $student->email;
    }

    public function updateProfile()
    {
        $student = auth('student')->user();

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            // Valida o e-mail único na tabela students
            'email' => ['required', 'email', Rule::unique('students', 'email')->ignore($student->id)],
        ]);

        $student->update([
            'name' => $this->name,
            'email' => strtolower($this->email),
        ]);

        $this->dispatch('profile-updated');
        $this->dispatch('sucesso', msg: 'Seus dados foram atualizados com sucesso!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'A confirmação de nova senha não confere.',
        ]);

        $student = auth('student')->user();

        if (!Hash::check($this->current_password, $student->password)) {
            $this->addError('current_password', 'A senha atual está incorreta.');
            return;
        }

        $student->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('sucesso', msg: 'Senha alterada com segurança!');
    }

    public function render()
    {
        return view('livewire.student.profile-manager');
    }
}