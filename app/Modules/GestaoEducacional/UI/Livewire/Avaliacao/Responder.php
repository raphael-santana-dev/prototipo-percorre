<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Avaliacao;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;

use App\Models\Solicitacao;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\NovaSolicitacaoMail;

class Responder extends Component
{
    public $periodo_id, $turma_id, $student_id;
    public $alunoNome, $turmaNome, $periodoNome;
    public $avaliacoesFases; 
    public $criterios; 

    public $usuarioResponsavelFase = []; 
    public $permissoesFase = [];
    public $responsaveisDesc = [];
    public $podeEditarGeral = false;
    public $motivosBloqueio = [];
    public $solicitacoesPendentes = []; 

    public $modalSelfUnlock = false;
    public $modalTotalUnlock = false;

    public $nps = [];
    public $metas = [];

    public $visibilidadeFase = [];
    public $avaliacaoFinalizada = false;

    public $modalSolicitacao = false;
    public $faseSolicitacaoId = null; 
    public $faseNumero = null;
    public $motivoTexto = '';
    public $criteriosSelecionados = [];

    public $mediaParcial = '-';
    public $mediaFinal = '-';

    public function mount($periodo, $turma, $student)
    {
        abort_if(!feature('avaliacao.responder'), 403, 'A resposta de matrizes está desativada no momento.');

        $this->periodo_id = $periodo;
        $this->turma_id = $turma;
        $this->student_id = $student;

        $isStudent = auth()->guard('student')->check();
        $isProfessor = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('professor'); 
        $isDev = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('dev'); 

        // =======================================================
        // 1. ISOLAMENTO DE ACESSO E PROTEÇÃO DIRETA DE URL
        // =======================================================
        if ($isStudent) {
            abort_if(auth()->guard('student')->id() != $student, 403, 'Acesso negado. Você só pode acessar a sua própria avaliação.');
        }

        if ($isProfessor && !$isDev) {
            // Verifica na tabela pivot se o professor logado está vinculado à turma da URL
            $professorVinculado = DB::table('professor_turma')
                ->where('user_id', auth()->id())
                ->where('turma_id', $turma)
                ->exists();

            abort_if(!$professorVinculado, 403, 'Acesso restrito. Você não leciona para esta turma e não pode gerenciar esta avaliação.');
        }

        // =======================================================
        // 2. CONFIGURAÇÕES DE VISIBILIDADE E FASES
        // =======================================================
        $ocultar_fases = false;
        $aluno_responde_ambos = false;
        
        if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes_gerais')) {
            $ocultar_fases = \App\Models\ConfiguracaoGeral::where('chave', 'ocultar_fases_restritas')->value('valor') === 'true';
            $aluno_responde_ambos = \App\Models\ConfiguracaoGeral::where('chave', 'permitir_aluno_responder_ambos')->value('valor') === 'true';
        }

        $periodoObj = PeriodoAvaliacao::with('fases', 'criterios')->findOrFail($periodo);
        $this->criterios = $periodoObj->criterios;

        $this->avaliacoesFases = AlunoAvaliacao::with(['itens.criterio', 'student', 'turma', 'periodo'])
            ->where('periodo_id', $periodo)
            ->where('turma_id', $turma)
            ->where('student_id', $student)
            ->orderBy('fase', 'asc')
            ->get();

        if ($this->avaliacoesFases->isEmpty()) {
            abort(404, 'Avaliação não encontrada.');
        }

        $primeira = $this->avaliacoesFases->first();
        $this->alunoNome = $primeira->student->name;
        $this->turmaNome = $primeira->turma->nome;
        $this->periodoNome = 'Ano ' . $primeira->periodo->ano . ' / Ciclo ' . $primeira->periodo->ciclo;

        $totalFases = $this->avaliacoesFases->count();
        $fasesConcluidas = $this->avaliacoesFases->where('status', '2')->count();
        $this->avaliacaoFinalizada = ($totalFases > 0 && $fasesConcluidas === $totalFases);

        $faseAnteriorConcluida = true; 
        
        foreach ($this->avaliacoesFases as $avFase) {
            $f = $avFase->fase;
            
            $configFase = $periodoObj->fases->where('fase', $f)->first();
            $resp = $configFase ? $configFase->responsavel : '0'; 
            $textos = ['1' => 'Estudante', '2' => 'Professor', '3' => 'Ambos'];
            $this->responsaveisDesc[$f] = $textos[$resp] ?? 'N/A';

            if ($ocultar_fases) {
                if ($isStudent) {
                    $this->visibilidadeFase[$f] = in_array($resp, ['1', '3']);
                } elseif ($isProfessor) {
                    $this->visibilidadeFase[$f] = in_array($resp, ['2', '3']);
                } else {
                    $this->visibilidadeFase[$f] = true; 
                }
            } else {
                $this->visibilidadeFase[$f] = true;
            }

            // Identifica quem é o responsável "dono" da fase
            $isResponsavel = false;
            if ($isStudent) {
                if ($resp == '1') $isResponsavel = true;
                if ($resp == '3' && $aluno_responde_ambos) $isResponsavel = true;
            }
            if ($isProfessor) {
                if (in_array($resp, ['2', '3'])) $isResponsavel = true;
            }
            if ($isDev) {
                $isResponsavel = true;
            }
            $this->usuarioResponsavelFase[$f] = $isResponsavel;

            $podeEditarBase = $isResponsavel;

            if ($this->avaliacaoFinalizada && !$isDev) {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = "Matriz finalizada e bloqueada para edições.";
            } elseif ($podeEditarBase && $periodoObj->trava_fases && !$faseAnteriorConcluida && !$isDev) {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = "Aguardando conclusão da fase anterior.";
            }

            $temSolicitacao = DB::table('avaliacao_solicitacoes')->where('aluno_avaliacao_id', $avFase->id)->where('status', 'pendente')->exists();
            $this->solicitacoesPendentes[$f] = $temSolicitacao;

            // Bloqueio Total quando Respondida (se concluiu, corta a edição e vira texto)
            if ($avFase->status == '2' && !$isDev) {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = $temSolicitacao ? "Sua solicitação está em análise." : "Fase já respondida.";
            } elseif (!$podeEditarBase && !isset($this->motivosBloqueio[$f])) {
                $this->motivosBloqueio[$f] = "Acesso restrito para o seu perfil.";
            }

            $this->permissoesFase[$f] = $podeEditarBase;

            if ($avFase->status !== '2') {
                $faseAnteriorConcluida = false;
            }

            foreach ($avFase->itens as $item) {
                $this->nps[$item->criterio_id][$f] = $item->nivel_nps;
                $this->metas[$item->criterio_id][$f] = $item->aval_metas;
            }
        }

        if (in_array(true, $this->permissoesFase)) {
            $this->podeEditarGeral = true;
        }

        $this->calcularMedias();
    }

    public function salvarFase($faseNumero)
    {
        abort_if(!feature('avaliacao.responder'), 403);
        
        $av = $this->avaliacoesFases->firstWhere('fase', $faseNumero);
        
        if (!$av || !($this->permissoesFase[$faseNumero] ?? false)) {
            return;
        }

        $faseCompleta = true;

        foreach ($av->itens as $item) {
            $nota = $this->nps[$item->criterio_id][$faseNumero] ?? null;
            $textoMeta = $this->metas[$item->criterio_id][$faseNumero] ?? null;

            if ($nota !== null && $nota !== '') {
                if ($nota < 0 || $nota > 10) {
                    $this->addError("nps.{$item->criterio_id}.{$faseNumero}", "Nota 0 a 10.");
                    return;
                }
            } else {
                $faseCompleta = false;
            }

            $item->update([
                'nivel_nps' => $nota !== '' ? $nota : null,
                'aval_metas' => $textoMeta
            ]);
        }

        $av->update([
            'status' => $faseCompleta ? '2' : '1', 
            'data_resposta' => $faseCompleta ? now() : null, 
            'hora_resposta' => $faseCompleta ? now()->format('H:i') : null
        ]);

        $this->mount($this->periodo_id, $this->turma_id, $this->student_id);

        $this->dispatch('sucesso', msg: "Fase {$faseNumero} salva com sucesso!");
    }

    public function salvar()
    {
        abort_if(!feature('avaliacao.responder'), 403);
        
        foreach ($this->avaliacoesFases as $av) {
            $fase = $av->fase;
            if (!($this->permissoesFase[$fase] ?? false)) continue; 

            $faseCompleta = true;

            foreach ($av->itens as $item) {
                $nota = $this->nps[$item->criterio_id][$fase] ?? null;
                $textoMeta = $this->metas[$item->criterio_id][$fase] ?? null;

                if ($nota !== null && $nota !== '') {
                    if ($nota < 0 || $nota > 10) {
                        $this->addError("nps.{$item->criterio_id}.{$fase}", "Nota 0 a 10.");
                        return;
                    }
                } else {
                    $faseCompleta = false;
                }

                $item->update([
                    'nivel_nps' => $nota !== '' ? $nota : null,
                    'aval_metas' => $textoMeta
                ]);
            }

            $av->update([
                'status' => $faseCompleta ? '2' : '1', 
                'data_resposta' => $faseCompleta ? now() : null, 
                'hora_resposta' => $faseCompleta ? now()->format('H:i') : null
            ]);
        }      
        
        session()->flash('sucesso', 'Todas as respostas salvas com sucesso!');
        return redirect()->route('avaliacoes.index');
    }

    public function abrirModalSolicitacao($alunoAvaliacaoId, $faseNumero)
    {
        $this->reset(['motivoTexto', 'criteriosSelecionados']);
        $this->faseSolicitacaoId = $alunoAvaliacaoId;
        $this->faseNumero = $faseNumero;
        $this->modalSolicitacao = true;
    }

    public function enviarSolicitacao()
    {
        $this->validate(['motivoTexto' => 'required|string|min:10']);

        $faseCorrente = $this->avaliacoesFases->firstWhere('fase', $this->faseNumero);

        $solicitacao = Solicitacao::create([
            'tema' => 'avaliacao_aluno_fase',
            'solicitante_type' => \App\Models\Student::class,
            'solicitante_id' => auth()->guard('student')->id(),
            'justificativa' => $this->motivoTexto,
            'status' => 'pendente',
            'payload' => [
                'aluno_avaliacao_id' => $faseCorrente->id,
                'fase' => $this->faseNumero,
                'criterios_selecionados' => $this->criteriosSelecionados
            ]
        ]);

        $this->enviarNotificacaoEmail($solicitacao, auth()->guard('student')->user()->name, 'Reabertura de Fase do Aluno');

        $this->solicitacoesPendentes[$this->faseNumero] = true;
        $this->motivosBloqueio[$this->faseNumero] = "Sua solicitação de alteração está em análise.";
        $this->modalSolicitacao = false;
        $this->dispatch('sucesso', msg: 'Solicitação enviada para o professor!');
    }

    public function abrirModalSelfUnlock($faseNumero)
    {
        $this->reset(['motivoTexto']);
        $this->faseNumero = $faseNumero;
        $this->modalSelfUnlock = true;
    }

    public function executarSelfUnlock()
    {
        $this->validate(['motivoTexto' => 'required|string|min:10']);
        
        $faseCorrente = $this->avaliacoesFases->firstWhere('fase', $this->faseNumero);

        $faseCorrente->update(['status' => '1', 'data_resposta' => null]);

        Solicitacao::create([
            'tema' => 'avaliacao_prof_self_unlock',
            'solicitante_type' => User::class,
            'solicitante_id' => auth()->id(),
            'justificativa' => $this->motivoTexto,
            'resposta_admin' => 'Auto-aprovado pelo sistema antes do fechamento da matriz.',
            'status' => 'auto_aprovada',
            'payload' => ['aluno_avaliacao_id' => $faseCorrente->id, 'fase' => $this->faseNumero]
        ]);

        $this->modalSelfUnlock = false;
        $this->mount($this->periodo_id, $this->turma_id, $this->student_id); 
        $this->dispatch('sucesso', msg: 'Sua fase foi desbloqueada. Você já pode editar as notas e as demais colunas foram travadas por cascata.');
    }

    public function abrirModalTotalUnlock()
    {
        $this->reset(['motivoTexto']);
        $this->modalTotalUnlock = true;
    }

    public function enviarSolicitacaoTotal()
    {
        $this->validate(['motivoTexto' => 'required|string|min:10']);

        $fasesPermitidas = [];
        $periodoObj = PeriodoAvaliacao::with('fases')->find($this->periodo_id);
        
        foreach ($this->avaliacoesFases as $avFase) {
            $configFase = $periodoObj->fases->where('fase', $avFase->fase)->first();
            if ($configFase && in_array($configFase->responsavel, ['2', '3'])) {
                $fasesPermitidas[] = $avFase->id;
            }
        }

        $solicitacao = Solicitacao::create([
            'tema' => 'avaliacao_prof_total',
            'solicitante_type' => User::class,
            'solicitante_id' => auth()->id(),
            'justificativa' => $this->motivoTexto,
            'status' => 'pendente',
            'payload' => [
                'periodo_id' => $this->periodo_id,
                'student_id' => $this->student_id,
                'fases_para_desbloquear' => $fasesPermitidas
            ]
        ]);

        $this->enviarNotificacaoEmail($solicitacao, auth()->user()->name, 'Reabertura Total de Matriz Bloqueada');

        $this->modalTotalUnlock = false;
        $this->dispatch('sucesso', msg: 'Sua solicitação foi enviada aos administradores.');
    }

    // =======================================================
    // 3. E-MAIL RESTRITO AO PROFESSOR RESPONSÁVEL DA TURMA
    // =======================================================
    private function enviarNotificacaoEmail($solicitacao, $nome, $tipo)
    {
        $emailsMaster = User::role(['dev', 'admin'])->pluck('email')->toArray();
        
        // Pega todos os professores que lecionam exatamente nesta turma ($this->turma_id)
        $professoresIds = DB::table('professor_turma')->where('turma_id', $this->turma_id)->pluck('user_id');
        $emailsProfessores = User::whereIn('id', $professoresIds)->whereNotNull('email')->pluck('email')->toArray();

        $emailsAlvo = array_unique(array_merge($emailsMaster, $emailsProfessores));

        if (!empty($emailsAlvo)) {
            Mail::to($emailsAlvo)->send(new NovaSolicitacaoMail($solicitacao, $nome, $tipo));
        }
    }

    public function calcularMedias()
    {
        $notasParcial = [];
        $notasFinal = [];

        foreach ($this->criterios as $crit) {
            $id = $crit->id;
            
            if (isset($this->nps[$id][1]) && is_numeric($this->nps[$id][1])) {
                $notasParcial[] = $this->nps[$id][1];
                $notasFinal[] = $this->nps[$id][1];
            }
            if (isset($this->nps[$id][2]) && is_numeric($this->nps[$id][2])) {
                $notasParcial[] = $this->nps[$id][2];
                $notasFinal[] = $this->nps[$id][2];
            }
            if (isset($this->nps[$id][3]) && is_numeric($this->nps[$id][3])) {
                $notasFinal[] = $this->nps[$id][3];
            }
        }

        $this->mediaParcial = count($notasParcial) > 0 ? round(array_sum($notasParcial) / count($notasParcial), 1) : '-';
        $this->mediaFinal = count($notasFinal) > 0 ? round(array_sum($notasFinal) / count($notasFinal), 1) : '-';
    }

    public function render()
    {
        $layout = auth()->guard('student')->check() ? 'components.layouts.student-app' : 'components.layouts.app';
        
        return view('livewire.gestao-educacional.avaliacao.responder')
            ->layout($layout, ['title' => 'Matriz de Avaliação']);
    }
}