<?php

namespace App\Modules\Student\UI\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\AlunoCicloAprendizagem;
use App\Models\Solicitacao;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.student-app')]
#[Title('Meu Painel - Portal do Aluno')]
class Dashboard extends Component
{
    public $modalSolicitacaoAberto = false;
    public $novaUnidadeId = '';
    public $novoCursoId = '';
    public $novoTurnoId = '';
    public $motivoSolicitacao = '';

    public function abrirModalSolicitacao()
    {
        $this->reset(['novaUnidadeId', 'novoCursoId', 'novoTurnoId', 'motivoSolicitacao']);
        $this->modalSolicitacaoAberto = true;
    }

    public function salvarSolicitacao()
    {
        $this->validate([
            'novaUnidadeId' => 'required',
            'novoCursoId' => 'required',
            'novoTurnoId' => 'required',
            'motivoSolicitacao' => 'required|string|min:10',
        ], [
            'novaUnidadeId.required' => 'A unidade desejada é obrigatória.',
            'novoCursoId.required' => 'O curso desejado é obrigatório.',
            'novoTurnoId.required' => 'O turno desejado é obrigatório.',
            'motivoSolicitacao.required' => 'Explique o motivo da sua solicitação.',
            'motivoSolicitacao.min' => 'Forneça uma explicação mais detalhada (mín. 10 caracteres).'
        ]);

        $student = auth('student')->user();

        Solicitacao::create([
            'tema' => 'alteracao_academica',
            'solicitante_type' => get_class($student),
            'solicitante_id' => $student->id,
            'justificativa' => $this->motivoSolicitacao,
            'status' => 'pendente',
            'payload' => [
                'nova_unidade_id' => $this->novaUnidadeId,
                'novo_curso_id' => $this->novoCursoId,
                'novo_turno_id' => $this->novoTurnoId,
            ]
        ]);

        $this->modalSolicitacaoAberto = false;
        $this->dispatch('sucesso', msg: 'Sua solicitação de alteração foi enviada e será analisada!');
    }

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
            $avaliacoes = AlunoCicloAprendizagem::with(['faseAtual.formularios', 'ciclo'])
                ->where('student_id', $student->id)
                ->where('status', '2') // 2 = Pendente
                ->get();

            foreach ($avaliacoes as $av) {
                $respondedores = $av->faseAtual->respondedores_permitidos ?? [];
                if (in_array('student', $respondedores)) {
                    $formulariosPendentes[] = $av;
                }
            }
        }

        $minhasSolicitacoes = Solicitacao::where('solicitante_type', get_class($student))
            ->where('solicitante_id', $student->id)
            ->latest()
            ->get();

        return view('livewire.student.dashboard.dashboard', [
            'student' => $student,
            'inscricao' => $inscricao,
            'formulariosPendentes' => $formulariosPendentes,
            'minhasSolicitacoes' => $minhasSolicitacoes,
            'unidadesDb' => Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'cursosDb' => Curso::whereIn('status', ['Ativo', '1', true])->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}