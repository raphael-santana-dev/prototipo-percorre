<?php

namespace App\Modules\Admin\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Solicitacao;
use App\Models\Inscricao;
use App\Models\OfertaVaga;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.app')]
#[Title('Central de Solicitações')]
class SolicitacoesManager extends Component
{
    use WithPagination;

    public $filtroStatus = 'pendente';
    public bool $modalResposta = false;
    public $solicitacaoAtiva = null;
    public string $textoResposta = '';
    public string $acaoResposta = '';

    public array $detalhesEstudante = [];
    public string $detalhesVaga = '';
    public bool $alertaUF = false;
    public bool $alertaVagas = false;

    public function usuarioPodeAvaliar(?Solicitacao $req = null): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->hasAnyRole(['dev', 'admin'])) {
            return true;
        }

        if ($req && $req->tema === 'alteracao_academica') {
            return $user->can('inscricao.edicao.solicitacao_troca');
        }

        return $user->hasRole('professor') || $user->can('inscricao.edicao.solicitacao_troca');
    }

    public function abrirResposta($id, $acao = 'visualizar'): void
    {
        $this->reset(['textoResposta', 'alertaUF', 'alertaVagas', 'detalhesEstudante', 'detalhesVaga']);
        $this->solicitacaoAtiva = Solicitacao::with(['solicitante', 'responsavel'])->findOrFail($id);

        if ($acao !== 'visualizar' && !$this->usuarioPodeAvaliar($this->solicitacaoAtiva)) {
            $this->dispatch('erro', msg: 'Você não possui a permissão "inscricao.edicao.solicitacao_troca" para aprovar ou reprovar esta solicitação.');
            return;
        }

        $this->acaoResposta = $acao;
        $this->modalResposta = true;

        if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
            $estudante = $this->solicitacaoAtiva->solicitante;
            $payload   = is_array($this->solicitacaoAtiva->payload) ? $this->solicitacaoAtiva->payload : [];

            $inscricao = !empty($payload['inscricao_id'])
                ? Inscricao::with(['unidade', 'curso', 'turno'])->find($payload['inscricao_id'])
                : Inscricao::with(['unidade', 'curso', 'turno'])->where('student_id', $estudante->id ?? 0)->latest('id')->first();

            if ($inscricao && isset($payload['nova_unidade_id'])) {
                $novaUnidade = Unidade::find($payload['nova_unidade_id']);
                $novoCurso   = Curso::find($payload['novo_curso_id']);
                $novoTurno   = Turno::find($payload['novo_turno_id']);

                $ufInscricao = strtoupper(trim((string) ($inscricao->estado ?? '')));
                $ufUnidade   = strtoupper(trim((string) ($novaUnidade?->estado ?: explode('-', $novaUnidade?->nome ?? '')[0])));

                $this->detalhesEstudante = [
                    'inscricao_id'       => $inscricao->id,
                    'nome'               => $estudante->name ?? $inscricao->nome ?? 'Candidato',
                    'email'              => $estudante->email ?? $inscricao->email ?? 'N/A',
                    'estado_atual'       => $ufInscricao ?: 'Não informado',
                    'quero_outro_estado' => !empty($payload['quero_outro_estado']),
                    'unidade_atual'      => $payload['unidade_anterior_nome'] ?? ($inscricao->unidade->nome ?? '-'),
                    'curso_atual'        => $payload['curso_anterior_nome'] ?? ($inscricao->curso->nome ?? '-'),
                    'turno_atual'        => $payload['turno_anterior_nome'] ?? ($inscricao->turno->nome ?? '-'),
                    'nova_unidade'       => $novaUnidade->nome ?? '-',
                    'estado_unidade'     => $ufUnidade ?: 'Não informado',
                    'novo_curso'         => $novoCurso->nome ?? '-',
                    'novo_turno'         => $novoTurno->nome ?? '-',
                ];

                if ($ufInscricao !== '' && $ufUnidade !== '' && $ufInscricao !== $ufUnidade) {
                    $this->alertaUF = true;
                }

                $oferta = OfertaVaga::where('ciclo_id', $inscricao->ciclo_id)
                    ->where('unidade_id', $payload['nova_unidade_id'])
                    ->where('curso_id', $payload['novo_curso_id'])
                    ->where('turno_id', $payload['novo_turno_id'])
                    ->first();

                if ($oferta) {
                    $ocupadas = Inscricao::where('ciclo_id', $inscricao->ciclo_id)
                        ->where('unidade_id', $payload['nova_unidade_id'])
                        ->where('curso_id', $payload['novo_curso_id'])
                        ->where('turno_id', $payload['novo_turno_id'])
                        ->whereIn('status_inscricao_id', [2, 3, 4])
                        ->count();

                    $vagasRestantes = $oferta->vagas - $ocupadas;
                    if ($vagasRestantes <= 0) {
                        $this->alertaVagas = true;
                        $this->detalhesVaga = 'Vagas esgotadas para esta combinação (0 disponíveis).';
                    } else {
                        $this->detalhesVaga = "{$vagasRestantes} vaga(s) disponível(is) na turma solicitada.";
                    }
                } else {
                    $this->alertaVagas = true;
                    $this->detalhesVaga = 'Não há oferta configurada para esta combinação no ciclo.';
                }
            }
        }
    }

    public function confirmarResposta(): void
    {
        if (!$this->solicitacaoAtiva || !$this->usuarioPodeAvaliar($this->solicitacaoAtiva)) {
            $this->dispatch('erro', msg: 'Sem permissão para processar esta solicitação.');
            return;
        }

        $this->validate([
            'textoResposta' => 'required|string|min:5',
        ], [
            'textoResposta.required' => 'Informe um parecer ou justificativa para o estudante.',
            'textoResposta.min'      => 'O parecer deve ter pelo menos 5 caracteres.',
        ]);

        $statusFinal = $this->acaoResposta === 'aprovar' ? 'aprovada' : 'rejeitada';
        $payload     = is_array($this->solicitacaoAtiva->payload) ? $this->solicitacaoAtiva->payload : [];

        $this->solicitacaoAtiva->update([
            'status'         => $statusFinal,
            'resposta_admin' => trim($this->textoResposta),
            'responsavel_id' => auth()->id(),
        ]);

        if ($statusFinal === 'aprovada') {
            if ($this->solicitacaoAtiva->tema === 'cadastro_nova_inscricao') {
                $inscricao = Inscricao::create($payload);
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.criada', $inscricao);
            }

            if ($this->solicitacaoAtiva->tema === 'avaliacao_aluno_fase' && !empty($payload['aluno_avaliacao_id'])) {
                \App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao::where('id', $payload['aluno_avaliacao_id'])
                    ->update(['status' => '1', 'data_resposta' => null]);
            }

            if ($this->solicitacaoAtiva->tema === 'avaliacao_prof_total' && !empty($payload['fases_para_desbloquear'])) {
                \App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao::whereIn('id', $payload['fases_para_desbloquear'])
                    ->update(['status' => '1', 'data_resposta' => null]);
            }

            if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
                $inscricao = !empty($payload['inscricao_id'])
                    ? Inscricao::find($payload['inscricao_id'])
                    : Inscricao::where('student_id', $this->solicitacaoAtiva->solicitante_id)->latest('id')->first();

                if ($inscricao) {
                    $inscricao->update([
                        'unidade_id' => $payload['nova_unidade_id'] ?? $inscricao->unidade_id,
                        'curso_id'   => $payload['novo_curso_id'] ?? $inscricao->curso_id,
                        'turno_id'   => $payload['novo_turno_id'] ?? $inscricao->turno_id,
                    ]);
                }

                $estudante = $this->solicitacaoAtiva->solicitante;
                if ($estudante && !empty($payload['nova_unidade_id']) && isset($estudante->unidade_id)) {
                    $estudante->update(['unidade_id' => (int) $payload['nova_unidade_id']]);
                }
            }
        }

        if ($this->solicitacaoAtiva->tema === 'alteracao_academica') {
            $estudante = $this->solicitacaoAtiva->solicitante;
            if ($estudante && $estudante->email) {
                $gatilho = $statusFinal === 'aprovada' ? 'solicitacao.aprovada' : 'solicitacao.rejeitada';
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar($gatilho, $estudante->email, [
                    'aluno_nome'     => $estudante->name ?? $estudante->nome ?? 'Estudante',
                    'resposta_admin' => $this->textoResposta,
                ]);
            }
        }

        $this->modalResposta = false;
        $this->dispatch('sucesso', msg: $statusFinal === 'aprovada'
            ? 'Solicitação aprovada e vínculo da inscrição atualizado com sucesso!'
            : 'Solicitação reprovada e parecer enviado ao estudante.');
    }

    public function render()
    {
        $query = Solicitacao::with('solicitante')->orderBy('created_at', 'desc');

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        $user = auth()->user();
        if ($user && method_exists($user, 'possuiTravaPorVinculoAtiva') && $user->possuiTravaPorVinculoAtiva()) {
            $combinacoes = $user->obterCombinacoesVinculoAtivas();

            if (empty($combinacoes)) {
                $query->whereRaw('1 = 0');
            } else {
                // Localiza os IDs de estudantes e de inscrições que pertencem às combinações exatas do usuário
                $inscricoesPermitidasQuery = Inscricao::query()->where(function ($qComb) use ($combinacoes) {
                    foreach ($combinacoes as $comb) {
                        $qComb->orWhere(function ($qPar) use ($comb) {
                            $qPar->where('unidade_id', $comb['unidade_id'])
                                 ->where('curso_id', $comb['curso_id'])
                                 ->where('turno_id', $comb['turno_id']);
                        });
                    }
                });

                $studentIdsPermitidos   = (clone $inscricoesPermitidasQuery)->whereNotNull('student_id')->pluck('student_id')->unique()->toArray();
                $inscricaoIdsPermitidos = (clone $inscricoesPermitidasQuery)->pluck('id')->map(fn($id) => (string) $id)->toArray();

                $query->where(function ($qFiltro) use ($studentIdsPermitidos, $inscricaoIdsPermitidos, $user) {
                    $qFiltro->where('responsavel_id', $user->id);

                    if (!empty($studentIdsPermitidos)) {
                        $qFiltro->orWhere(function ($qStu) use ($studentIdsPermitidos) {
                            $qStu->where('solicitante_type', \App\Modules\Student\Domain\Models\Student::class)
                                 ->whereIn('solicitante_id', $studentIdsPermitidos);
                        });
                    }

                    if (!empty($inscricaoIdsPermitidos)) {
                        $qFiltro->orWhereIn(DB::raw("payload->>'inscricao_id'"), $inscricaoIdsPermitidos);
                    }
                });
            }
        }

        return view('livewire.admin.solicitacoes-manager', [
            'solicitacoes' => $query->paginate(15),
        ]);
    }
}