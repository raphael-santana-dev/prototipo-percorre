<?php

namespace App\Modules\Student\UI\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Inscricao;
use App\Models\OfertaVaga;
use App\Models\AlunoCicloAprendizagem;
use App\Models\Solicitacao;
use App\Models\RespostaFormulario;
use App\Models\FormularioFase;
use App\Models\User;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use Illuminate\Support\Facades\Mail;

#[Layout('components.layouts.student-app')]
#[Title('Meu Painel - Portal do Aluno')]
class Dashboard extends Component
{
    use \App\Traits\WithFichaPdfDownload;

    public bool $modalSolicitacaoAberto = false;
    public bool $queroOutroEstado = false;
    public $novaUnidadeId = '';
    public $novoCursoId = '';
    public $novoTurnoId = '';
    public string $motivoSolicitacao = '';

    public bool $modalInscricaoAberto = false;
    public $inscricaoDetalhe = null;

    public function mount(): void
    {
        $student = auth('student')->user();
        if ($student && method_exists($student, 'sincronizarInscricoesVinculadas')) {
            $student->sincronizarInscricoesVinculadas();
        }
    }

    #[Computed]
    public function inscricaoAtual(): ?Inscricao
    {
        return Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao', 'ciclo'])
            ->where('student_id', auth('student')->id())
            ->latest('id')
            ->first();
    }

    public function podeEnviarDocumentacao(?Inscricao $inscricao = null): bool
    {
        $insc = $inscricao ?: $this->inscricaoAtual;
        if (!$insc || !$insc->ciclo) {
            return false;
        }

        $temDocsExigidos = DocumentoExigido::where('ciclo_id', $insc->ciclo_id)->exists();
        $isPcd = in_array(strtolower((string) ($insc->possui_deficiencia ?? 'nao')), ['sim', '1', 'true', 's'], true);

        if (!$temDocsExigidos && !$isPcd) {
            return false;
        }

        $statusAlvoId = $insc->ciclo->status_documentacao_id ?? null;
        if (!empty($statusAlvoId)) {
            return (int) $insc->status_inscricao_id === (int) $statusAlvoId;
        }

        return true;
    }

    public function abrirModalSolicitacao(): void
    {
        $this->reset(['queroOutroEstado', 'novaUnidadeId', 'novoCursoId', 'novoTurnoId', 'motivoSolicitacao']);
        $this->resetValidation();
        $this->modalSolicitacaoAberto = true;
    }

    public function updatedQueroOutroEstado(): void
    {
        $this->reset(['novaUnidadeId', 'novoCursoId', 'novoTurnoId']);
    }

    public function updatedNovaUnidadeId(): void
    {
        $this->reset(['novoCursoId', 'novoTurnoId']);
    }

    public function updatedNovoCursoId(): void
    {
        $this->reset('novoTurnoId');
    }

    #[Computed]
    public function unidadesDisponiveisTroca()
    {
        $insc = $this->inscricaoAtual;
        if (!$insc || !$insc->ciclo_id) {
            return collect();
        }

        $ufCandidato = strtoupper(trim((string) ($insc->estado ?? '')));

        $ofertas = OfertaVaga::with('unidade')
            ->where('ciclo_id', $insc->ciclo_id)
            ->get();

        $unidades = collect();
        foreach ($ofertas as $oferta) {
            $uni = $oferta->unidade;
            if (!$uni || !in_array($uni->status, ['Ativa', 'ativa', '1', 1, true], true)) {
                continue;
            }

            if (!$this->queroOutroEstado && $ufCandidato !== '') {
                $ufUnidade = strtoupper(trim((string) ($uni->estado ?: explode('-', $uni->nome)[0])));
                if ($ufUnidade !== $ufCandidato) {
                    continue;
                }
            }

            $unidades->put($uni->id, $uni);
        }

        return $unidades->sortBy('nome')->values();
    }

    #[Computed]
    public function cursosDisponiveisTroca()
    {
        $insc = $this->inscricaoAtual;
        if (!$insc || !$insc->ciclo_id || empty($this->novaUnidadeId)) {
            return collect();
        }

        $ofertas = OfertaVaga::with('curso')
            ->where('ciclo_id', $insc->ciclo_id)
            ->where('unidade_id', (int) $this->novaUnidadeId)
            ->get();

        $cursos = collect();
        foreach ($ofertas as $oferta) {
            $cur = $oferta->curso;
            if (!$cur || !in_array($cur->status, ['Ativo', 'ativo', '1', 1, true], true)) {
                continue;
            }
            $cursos->put($cur->id, $cur);
        }

        return $cursos->sortBy('nome')->values();
    }

    #[Computed]
    public function turnosDisponiveisTroca()
    {
        $insc = $this->inscricaoAtual;
        if (!$insc || !$insc->ciclo_id || empty($this->novaUnidadeId) || empty($this->novoCursoId)) {
            return collect();
        }

        $ofertas = OfertaVaga::with('turno')
            ->where('ciclo_id', $insc->ciclo_id)
            ->where('unidade_id', (int) $this->novaUnidadeId)
            ->where('curso_id', (int) $this->novoCursoId)
            ->get();

        $turnos = collect();
        foreach ($ofertas as $oferta) {
            if ($oferta->turno) {
                $turnos->put($oferta->turno->id, $oferta->turno);
            }
        }

        return $turnos->sortBy('nome')->values();
    }

    public function salvarSolicitacao(): void
    {
        $this->validate([
            'novaUnidadeId'     => 'required|exists:unidades,id',
            'novoCursoId'       => 'required|exists:cursos,id',
            'novoTurnoId'       => 'required|exists:turnos,id',
            'motivoSolicitacao' => 'required|string|min:10|max:1000',
        ], [
            'novaUnidadeId.required'     => 'Selecione a unidade desejada.',
            'novoCursoId.required'       => 'Selecione o curso desejado.',
            'novoTurnoId.required'       => 'Selecione o turno desejado.',
            'motivoSolicitacao.required' => 'Informe o motivo da solicitação de troca.',
            'motivoSolicitacao.min'      => 'Descreva o motivo com pelo menos 10 caracteres.',
        ]);

        $student = auth('student')->user();
        $insc    = $this->inscricaoAtual;

        if (!$insc) {
            $this->dispatch('erro', msg: 'Nenhuma inscrição ativa encontrada para vincular à solicitação.');
            return;
        }

        $novaUnidade = Unidade::find($this->novaUnidadeId);
        $novoCurso   = Curso::find($this->novoCursoId);
        $novoTurno   = Turno::find($this->novoTurnoId);

        $solicitacao = Solicitacao::create([
            'tema'             => 'alteracao_academica',
            'solicitante_type' => get_class($student),
            'solicitante_id'   => $student->id,
            'justificativa'    => trim($this->motivoSolicitacao),
            'status'           => 'pendente',
            'payload'          => [
                'inscricao_id'          => $insc->id,
                'ciclo_id'              => $insc->ciclo_id,
                'quero_outro_estado'    => (bool) $this->queroOutroEstado,
                'uf_origem'             => $insc->estado,
                'unidade_anterior_id'   => $insc->unidade_id,
                'unidade_anterior_nome' => $insc->unidade->nome ?? '-',
                'curso_anterior_id'     => $insc->curso_id,
                'curso_anterior_nome'   => $insc->curso->nome ?? '-',
                'turno_anterior_id'     => $insc->turno_id,
                'turno_anterior_nome'   => $insc->turno->nome ?? '-',
                'nova_unidade_id'       => (int) $this->novaUnidadeId,
                'nova_unidade_nome'     => $novaUnidade?->nome ?? '-',
                'novo_curso_id'         => (int) $this->novoCursoId,
                'novo_curso_nome'       => $novoCurso?->nome ?? '-',
                'novo_turno_id'         => (int) $this->novoTurnoId,
                'novo_turno_nome'       => $novoTurno?->nome ?? '-',
            ],
        ]);

        $this->notificarResponsaveisTroca($solicitacao, $student, $insc, $novaUnidade?->nome, $novoCurso?->nome, $novoTurno?->nome);

        $this->modalSolicitacaoAberto = false;
        $this->dispatch('sucesso', msg: 'Solicitação de troca enviada com sucesso! Acompanhe o status pelo painel.');
    }

    protected function notificarResponsaveisTroca(
        Solicitacao $solicitacao,
        $student,
        Inscricao $inscricao,
        ?string $unidadeDestino,
        ?string $cursoDestino,
        ?string $turnoDestino
    ): void {
        try {
            $usuariosResponsaveis = collect();

            if (\Spatie\Permission\Models\Permission::where('name', 'inscricao.edicao.solicitacao_troca')->exists()) {
                $usuariosResponsaveis = User::permission('inscricao.edicao.solicitacao_troca')->get();
            }

            if ($usuariosResponsaveis->isEmpty()) {
                $usuariosResponsaveis = User::role(['dev', 'admin'])->get();
            }

            $emailsDestino = $usuariosResponsaveis->pluck('email')->filter()->unique()->values()->toArray();
            $urlPainelSolicitacoes = route('solicitacoes.index');

            foreach ($emailsDestino as $emailResp) {
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('solicitacao.alteracao_academica.criada', $emailResp, [
                    'nome_solicitante' => $student->name,
                    'justificativa'    => $solicitacao->justificativa,
                    'link_painel'      => $urlPainelSolicitacoes,
                ]);

                $assunto = "Nova Solicitação de Troca de Curso/Unidade - {$student->name}";
                $html = "<div style='font-family:sans-serif;font-size:14px;color:#111827;line-height:1.6;'>"
                    . "<h3 style='margin-bottom:8px;'>Solicitação de Troca de Unidade / Curso / Turno</h3>"
                    . "<p>O estudante <strong>" . e($student->name) . "</strong> (Inscrição #{$inscricao->id}) solicitou alteração de vínculo acadêmico:</p>"
                    . "<ul style='background:#f9fafb;padding:12px 28px;border:1px solid #e5e7eb;border-radius:8px;'>"
                    . "<li><strong>Vínculo Atual:</strong> " . e($inscricao->unidade->nome ?? '-') . " • " . e($inscricao->curso->nome ?? '-') . " • " . e($inscricao->turno->nome ?? '-') . "</li>"
                    . "<li><strong>Novo Vínculo Solicitado:</strong> " . e($unidadeDestino ?? '-') . " • " . e($cursoDestino ?? '-') . " • " . e($turnoDestino ?? '-') . "</li>"
                    . "<li><strong>Outro Estado (UF):</strong> " . ($this->queroOutroEstado ? 'Sim' : 'Não (Mesma UF)') . "</li>"
                    . "<li><strong>Motivo:</strong> " . e($solicitacao->justificativa) . "</li>"
                    . "</ul>"
                    . "<p><a href='{$urlPainelSolicitacoes}' style='display:inline-block;background:#7c3aed;color:#ffffff;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold;'>Analisar na Central de Solicitações</a></p>"
                    . "</div>";

                Mail::html($html, function ($msg) use ($emailResp, $assunto) {
                    $msg->to($emailResp)->subject($assunto);
                });
            }
        } catch (\Throwable $e) {}
    }

    public function abrirDetalhesInscricao(int $id): void
    {
        $this->inscricaoDetalhe = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao', 'ciclo'])
            ->where('student_id', auth('student')->id())
            ->findOrFail($id);

        $this->modalInscricaoAberto = true;
    }

    #[Computed]
    public function formulariosGeraisPreenchidos()
    {
        $student = auth('student')->user();
        if (!$student) {
            return collect();
        }

        $emailAluno = strtolower(trim((string) $student->email));

        $respostas = RespostaFormulario::with(['formulario.fases', 'formulario.campos'])
            ->where(function ($q) use ($student, $emailAluno) {
                $q->where('user_id', $student->id);
                if ($emailAluno !== '') {
                    $q->orWhereRaw("LOWER(respostas->>'_email_coletado') = ?", [$emailAluno]);
                }
            })
            ->latest('id')
            ->get();

        return $respostas->map(function (RespostaFormulario $resp) {
            $form = $resp->formulario;
            if (!$form) return null;

            $totalEtapas = max(1, (int) ($form->campos->where('tipo', '!=', 'config')->max('etapa') ?? 1));
            $etapaParada = (int) ($resp->etapa_parada ?? 1);
            $concluido   = $etapaParada >= $totalEtapas;

            $faseNome = 'Fase Única';
            if ($form->fases && $form->fases->isNotEmpty()) {
                $faseEncontrada = $form->fases->first(function (FormularioFase $f) use ($etapaParada) {
                    $pags = is_string($f->paginas) ? json_decode($f->paginas, true) : ($f->paginas ?? []);
                    return is_array($pags) && in_array($etapaParada, array_map('intval', $pags), true);
                });
                $faseNome = $faseEncontrada ? $faseEncontrada->nome : ($form->fases->last()->nome ?? 'Fase Geral');
            }

            return [
                'resposta_id'   => $resp->id,
                'formulario_id' => $form->id,
                'titulo'        => $form->titulo,
                'slug'          => $form->slug,
                'tipo'          => strtoupper((string) ($form->tipo ?: 'GERAL')),
                'etapa_atual'   => $etapaParada,
                'total_etapas'  => $totalEtapas,
                'fase_nome'     => $faseNome,
                'concluido'     => $concluido,
                'data_envio'    => $resp->updated_at ? $resp->updated_at->format('d/m/Y H:i') : '-',
            ];
        })->filter()->values();
    }

    public function render()
    {
        $student = auth('student')->user();
        $formulariosPendentes = [];

        $historicoInscricoes = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao', 'ciclo'])
            ->where('student_id', $student->id)
            ->latest('id')
            ->get();

        $inscricaoAtual = $historicoInscricoes->first();

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

        $minhasSolicitacoes = Solicitacao::where('solicitante_type', get_class($student))
            ->where('solicitante_id', $student->id)
            ->latest('id')
            ->get();

        $uploadLiberado = $this->podeEnviarDocumentacao($inscricaoAtual);
        $documentosPendentes = false;

        if ($inscricaoAtual && $uploadLiberado) {
            $qtdExigidos = DocumentoExigido::where('ciclo_id', $inscricaoAtual->ciclo_id)->where('is_obrigatorio', true)->count();
            $qtdAprovados = DocumentoMatricula::where('inscricao_id', $inscricaoAtual->id)
                ->whereIn('status_analise', ['valido_ia', 'aprovado_manual'])
                ->count();

            $din = is_array($inscricaoAtual->dados_dinamicos) ? $inscricaoAtual->dados_dinamicos : (json_decode($inscricaoAtual->dados_dinamicos ?? '{}', true) ?: []);
            $faltaLaudoPcd = in_array(strtolower((string) ($inscricaoAtual->possui_deficiencia ?? 'nao')), ['sim', '1', 'true', 's'], true) && empty($din['laudo_pcd']);

            $documentosPendentes = ($qtdExigidos > 0 && $qtdAprovados < $qtdExigidos) || $faltaLaudoPcd;
        }

        return view('livewire.student.dashboard.dashboard', [
            'student'                     => $student,
            'inscricao'                   => $inscricaoAtual,
            'uploadLiberado'              => $uploadLiberado,
            'historicoInscricoes'         => $historicoInscricoes,
            'formulariosPendentes'        => $formulariosPendentes,
            'formulariosGerais'           => $this->formulariosGeraisPreenchidos,
            'documentosPendentes'         => $documentosPendentes,
            'minhasSolicitacoes'          => $minhasSolicitacoes,
        ]);
    }
}