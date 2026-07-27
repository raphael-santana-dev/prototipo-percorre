<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Student\Domain\Models\Student;

#[Layout('components.layouts.app')]
#[Title('Perfil do Estudante - Administrativo')]
class StudentDetails extends Component
{
    public Student $student;

    public function mount(int $id)
    {
        abort_if(!auth()->user()->can('estudante.listar'), 403);
        
        // O Global Scope (Tenantable) atua aqui garantindo a segurança de acesso
        $this->student = Student::with('unidade')->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.student.student-details');
    }
}