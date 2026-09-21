<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use App\Modules\Student\Domain\Models\Student;
use App\Models\AlunoCicloAprendizagem;

#[Layout('components.layouts.company')]
#[Title('Portal da Empresa - Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $usuario = Auth::guard('company')->user();

        $alunosQuery = Student::where('empresa_id', $usuario->empresa_id)
            ->where('is_active', true)
            ->where('is_aprendiz', true);
            
        if ($usuario->tipo_acesso === 'gestor_avaliador') {
            $alunosQuery->where('gestor_id', $usuario->id);
        }
        
        $alunosIds = $alunosQuery->pluck('id')->toArray();

        $avaliacoes = AlunoCicloAprendizagem::with('faseAtual')
            ->whereIn('student_id', $alunosIds)
            ->get();

        $pendentes = 0;
        $concluidas = 0;

        foreach ($avaliacoes as $av) {
            $respondedores = $av->faseAtual->respondedores_permitidos ?? [];
            
            if (in_array('company', $respondedores)) {
                if ($av->status === '2') { // 2 = Enviada/Pendente
                    $pendentes++;
                } elseif (in_array($av->status, ['3', '5'])) { // 3 = Respondida, 5 = Fechada
                    $concluidas++;
                }
            }
        }

        $metricas = [
            'total_aprendizes' => count($alunosIds),
            'avaliacoes_pendentes' => $pendentes,
            'avaliacoes_concluidas' => $concluidas,
        ];

        return view('livewire.company.dashboard', [
            'usuario' => $usuario,
            'metricas' => $metricas
        ]);
    }
}