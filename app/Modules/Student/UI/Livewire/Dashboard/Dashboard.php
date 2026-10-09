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

    // Novos atributos para o Modal da Ficha de Inscrição Completa
    public $modalInscricaoAberto = false;
    public $inscricaoDetalhe = null;

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

        try {
            \App\Modules\Comunicacao\Services\AutomacaoService::disparar('solicitacao.criada', $student->email, [
                'aluno_nome' => $student->name ?? 'Estudante',
                'justificativa' => $this->motivoSolicitacao
            ]);
        } catch (\Exception $e) {}

        $this->modalSolicitacaoAberto = false;
        $this->dispatch('sucesso', msg: 'Sua solicitação de alteração foi enviada e será analisada!');
    }

    // Função para carregar os detalhes ocultos de uma inscrição e exibir o Modal
    public function abrirDetalhesInscricao($id)
    {
        $this->inscricaoDetalhe = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])
            ->where('student_id', auth('student')->id())
            ->findOrFail($id);
            
        $this->modalInscricaoAberto = true;
    }

    public function render()
    {
        $student = auth('student')->user();
        $formulariosPendentes = [];

        // Carrega o histórico completo de inscrições, ordenado pela mais recente
        $historicoInscricoes = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();
            
        // A inscrição atual/principal será sempre a primeira do histórico
        $inscricaoAtual = $historicoInscricoes->first();

        // Regra de formulários pendentes (se matriculado)
        if ($student->matriculado) {
            $avaliacoes = AlunoCicloAprendizagem::with(['faseAtual.formularios', 'ciclo'])
                ->where('student_id', $student->id)
                ->where('status', '2') 
                ->get();

            foreach ($avaliacoes as $av) {
                $respondedores = $av->faseAtual->respondedores_permitidos ?? [];
                if (in_array('student', $respondedores)) {
                    $formulariosPendentes[] = $av;
                }
            }
        }

        // Histórico de solicitações do Helpdesk
        $minhasSolicitacoes = Solicitacao::where('solicitante_type', get_class($student))
            ->where('solicitante_id', $student->id)
            ->latest()
            ->get();

        $documentosPendentes = false;

        if ($inscricaoAtual && $inscricaoAtual->etapa_atual < 2) {
            $temDocumentos = \App\Modules\Matricula\Domain\Models\DocumentoExigido::where('ciclo_id', $inscricaoAtual->ciclo_id)->exists();
            if ($temDocumentos) {
                $documentosPendentes = true;
            }
        }

        return view('livewire.student.dashboard.dashboard', [
            'student' => $student,
            'inscricao' => $inscricaoAtual,
            'historicoInscricoes' => $historicoInscricoes,
            'formulariosPendentes' => $formulariosPendentes,
            'documentosPendentes' => $documentosPendentes,
            'minhasSolicitacoes' => $minhasSolicitacoes,
            'unidadesDb' => Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'cursosDb' => Curso::whereIn('status', ['Ativo', '1', true])->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}