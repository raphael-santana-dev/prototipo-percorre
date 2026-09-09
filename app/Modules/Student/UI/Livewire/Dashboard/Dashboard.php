<?php

namespace App\Modules\Student\UI\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;

#[Layout('components.layouts.student-app')]
#[Title('Meu Painel - Portal do Aluno')]
class Dashboard extends Component
{
    public function render()
    {
        $student = auth('student')->user();
        $inscricao = null;

        if (!$student->matriculado) {
            // Busca a última inscrição vinculada a este aluno
            $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])
                ->where('student_id', $student->id)
                ->latest()
                ->first();
        }

        return view('livewire.student.dashboard.dashboard', [
            'student' => $student,
            'inscricao' => $inscricao
        ]);
    }
}