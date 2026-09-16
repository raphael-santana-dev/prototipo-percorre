<?php

namespace App\Modules\Student\UI\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\AlunoCicloAprendizagem;

#[Layout('components.layouts.student-app')]
#[Title('Meu Painel - Portal do Aluno')]
class Dashboard extends Component
{
    public function render()
    {
        $student = auth('student')->user();
        $inscricao = null;
        $formulariosPendentes = [];

        if (!$student->matriculado) {
            $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])
                ->where('student_id', $student->id)
                ->latest()
                ->first();
        } else {
            // LÓGICA NOVA: Buscar avaliações de aprendizagem pendentes
            $avaliacoes = AlunoCicloAprendizagem::with(['faseAtual.formularios', 'ciclo'])
                ->where('student_id', $student->id)
                ->where('status', '2') // 2 = Pendente
                ->get();

            foreach ($avaliacoes as $av) {
                $respondedores = $av->faseAtual->respondedores_permitidos ?? [];
                // Se a fase atual diz que o "student" pode responder, enviamos para a tela
                if (in_array('student', $respondedores)) {
                    $formulariosPendentes[] = $av;
                }
            }
        }

        return view('livewire.student.dashboard.dashboard', [
            'student' => $student,
            'inscricao' => $inscricao,
            'formulariosPendentes' => $formulariosPendentes
        ]);
    }
}