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
        abort_if(!feature('estudante.visualizar'), 403, 'Acesso às fichas desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.visualizar'), 403);
        
        $this->student = Student::with('unidade')->apenasVinculosPermitidos()->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.student.student-details');
    }
}