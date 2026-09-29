<?php

namespace App\Modules\Admin\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Solicitacao;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Central de Solicitações')]
class SolicitacoesManager extends Component
{
    use WithPagination;

    public $filtroStatus = 'pendente';
    public $modalResposta = false;
    public $solicitacaoAtiva = null;
    public $textoResposta = '';
    public $acaoResposta = '';

    public $detalhesEstudante = [];
    public $detalhesVaga = '';
    public $alertaUF = false;
    public $alertaVagas = false;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin|professor'), 403, 'Acesso restrito.');
    }

    public function abrirResposta($id, $acao = 'visualizar')
    {
        $this->reset(['textoResposta', 'alertaUF', 'alertaVagas', 'detalhesEstudante', 'detalhesVaga']);
        $this->solicitacaoAtiva = Solicitacao::with(['solicitante', 'responsavel'])->findOrFail($id);
        $this->acaoResposta = $acao; 
        $this->modalResposta = true;

        if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
            $estudante = $this->solicitacaoAtiva->solicitante;
            $inscricao = \App\Models\Inscricao::where('student_id', $estudante->id ?? 0)->latest()->first();
            $payload = $this->solicitacaoAtiva->payload ?? [];

            if ($inscricao && isset($payload['nova_unidade_id'])) {
                $novaUnidade = \App\Modules\Unidade\Domain\Models\Unidade::find($payload['nova_unidade_id']);
                $novoCurso = \App\Models\Curso::find($payload['novo_curso_id']);
                $novoTurno = \App\Modules\Turno\Domain\Models\Turno::find($payload['novo_turno_id']);

                $this->detalhesEstudante = [
                    'nome' => $estudante->name ?? $estudante->nome ?? 'Candidato',
                    'email' => $estudante->email ?? 'N/A',
                    'estado_atual' => $inscricao->estado ?? 'Não Informado',
                    'nova_unidade' => $novaUnidade->nome ?? '-',
                    'estado_unidade' => $novaUnidade->estado ?? 'Não Informado',
                    'novo_curso' => $novoCurso->nome ?? '-',
                    'novo_turno' => $novoTurno->nome ?? '-',
                ];

                // ALERTA 1: Divergência de Estado (UF)
                if (!empty($inscricao->estado) && !empty($novaUnidade->estado) && strtolower($inscricao->estado) !== strtolower($novaUnidade->estado)) {
                    $this->alertaUF = true;
                }

                // ALERTA 2: Verificação de Vagas
                $oferta = \App\Models\OfertaVaga::where('ciclo_id', $inscricao->ciclo_id)
                    ->where('unidade_id', $payload['nova_unidade_id'])
                    ->where('curso_id', $payload['novo_curso_id'])
                    ->where('turno_id', $payload['novo_turno_id'])
                    ->first();

                if ($oferta) {
                    $ocupadas = \App\Models\Inscricao::where('ciclo_id', $inscricao->ciclo_id)
                        ->where('unidade_id', $payload['nova_unidade_id'])
                        ->where('curso_id', $payload['novo_curso_id'])
                        ->where('turno_id', $payload['novo_turno_id'])
                        ->whereIn('status_inscricao_id', [2, 3, 4]) // Aprovados / Matriculados
                        ->count();

                    $vagasRestantes = $oferta->vagas - $ocupadas;
                    if ($vagasRestantes <= 0) {
                        $this->alertaVagas = true;
                        $this->detalhesVaga = "Vagas Esgotadas (0 disponíveis).";
                    } else {
                        $this->detalhesVaga = "{$vagasRestantes} vaga(s) disponível(eis).";
                    }
                } else {
                    $this->alertaVagas = true;
                    $this->detalhesVaga = "Não há oferta configurada para esta turma.";
                }
            }
        }
    }

    public function confirmarResposta()
    {
        $this->validate(['textoResposta' => 'required|string|min:5'], ['textoResposta.required' => 'Insira um feedback ou justificativa para esta decisão.']);

        $statusFinal = $this->acaoResposta === 'aprovar' ? 'aprovada' : 'rejeitada';
        $payload = $this->solicitacaoAtiva->payload;
        
        $this->solicitacaoAtiva->update([
            'status' => $statusFinal,
            'resposta_admin' => $this->textoResposta,
            'responsavel_id' => auth()->id()
        ]);

        if ($statusFinal === 'aprovada') {
            if ($this->solicitacaoAtiva->tema === 'cadastro_nova_inscricao') {
                $inscricao = \App\Models\Inscricao::create($payload);
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.criada', $inscricao);
            }
            if ($this->solicitacaoAtiva->tema === 'avaliacao_aluno_fase') {
                \App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao::where('id', $payload['aluno_avaliacao_id'])->update(['status' => '1', 'data_resposta' => null]);
            }
            if ($this->solicitacaoAtiva->tema === 'avaliacao_prof_total') {
                \App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao::whereIn('id', $payload['fases_para_desbloquear'])->update(['status' => '1', 'data_resposta' => null]);
            }

            if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
                $inscricao = \App\Models\Inscricao::where('student_id', $this->solicitacaoAtiva->solicitante_id)->latest()->first();
                if ($inscricao) {
                    $inscricao->update([
                        'unidade_id' => $payload['nova_unidade_id'] ?? $inscricao->unidade_id,
                        'curso_id' => $payload['novo_curso_id'] ?? $inscricao->curso_id,
                        'turno_id' => $payload['novo_turno_id'] ?? $inscricao->turno_id,
                    ]);
                }
            }
        }

        // GATILHO: Dispara o e-mail de Aprovação ou Reprovação
        if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
            $estudante = $this->solicitacaoAtiva->solicitante;
            if ($estudante && $estudante->email) {
                $gatilho = $statusFinal === 'aprovada' ? 'solicitacao.aprovada' : 'solicitacao.rejeitada';
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar($gatilho, $estudante->email, [
                    'aluno_nome' => $estudante->name ?? $estudante->nome ?? 'Estudante',
                    'resposta_admin' => $this->textoResposta
                ]);
            }
        }

        $this->modalResposta = false;
        $this->dispatch('sucesso', msg: 'Solicitação processada com sucesso!');
    }

    public function render()
    {
        $query = Solicitacao::with('solicitante')->orderBy('created_at', 'desc');

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        if (auth()->user()->hasRole('professor') && !auth()->user()->hasRole('dev|admin')) {
            $query->where('responsavel_id', auth()->id())
                  ->orWhereNull('responsavel_id'); 
        }

        return view('livewire.admin.solicitacoes-manager', [
            'solicitacoes' => $query->paginate(15)
        ]);
    }
}