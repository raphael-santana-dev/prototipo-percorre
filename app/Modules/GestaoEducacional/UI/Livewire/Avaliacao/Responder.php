<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Avaliacao;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;

class Responder extends Component
{
    public $periodo_id, $turma_id, $student_id;
    public $alunoNome, $turmaNome, $periodoNome;
    public $avaliacoesFases; 
    public $criterios; 

    public $permissoesFase = [];
    public $responsaveisDesc = [];
    public $podeEditarGeral = false;
    public $motivosBloqueio = [];
    public $solicitacoesPendentes = []; 

    public $nps = [];
    public $metas = [];

    // Novas variáveis de Controle
    public $visibilidadeFase = [];
    public $avaliacaoFinalizada = false;

    // Modal de Solicitação
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

        // Identificação de Perfil
        $isStudent = auth()->guard('student')->check();
        $isProfessor = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('professor'); 
        $isDev = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('dev'); 

        // Carrega Configurações Gerais do Sistema
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

        // ===============================================
        // VERIFICA SE A AVALIAÇÃO INTEIRA FOI CONCLUÍDA
        // ===============================================
        $totalFases = $this->avaliacoesFases->count();
        $fasesConcluidas = $this->avaliacoesFases->where('status', '2')->count();
        $this->avaliacaoFinalizada = ($totalFases > 0 && $fasesConcluidas === $totalFases);

        $faseAnteriorConcluida = true; 
        
        foreach ($this->avaliacoesFases as $avFase) {
            $f = $avFase->fase;
            
            $configFase = $periodoObj->fases->where('fase', $f)->first();
            $resp = $configFase ? $configFase->responsavel : '0'; // 1=Estudante, 2=Professor, 3=Ambos
            $textos = ['1' => 'Estudante', '2' => 'Professor', '3' => 'Ambos'];
            $this->responsaveisDesc[$f] = $textos[$resp] ?? 'N/A';

            // REGRA 1: Visibilidade da Fase
            if ($ocultar_fases) {
                if ($isStudent) {
                    $this->visibilidadeFase[$f] = in_array($resp, ['1', '3']);
                } elseif ($isProfessor) {
                    $this->visibilidadeFase[$f] = in_array($resp, ['2', '3']);
                } else {
                    $this->visibilidadeFase[$f] = true; // Admins veem tudo
                }
            } else {
                $this->visibilidadeFase[$f] = true;
            }

            // REGRA 2: Permissão Base de Edição
            $podeEditarBase = false;
            if ($isStudent) {
                if ($resp == '1') $podeEditarBase = true;
                if ($resp == '3' && $aluno_responde_ambos) $podeEditarBase = true;
            }
            if ($isProfessor) {
                if (in_array($resp, ['2', '3'])) $podeEditarBase = true;
            }
            if ($isDev) {
                $podeEditarBase = true; // Dev pode editar tudo
            }

            // REGRA 3: Bloqueios Operacionais e de Trava
            if ($this->avaliacaoFinalizada && !$isDev) {
                // Se a avaliação acabou, bloqueia tudo (exceto para o Dev)
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = "Matriz finalizada e bloqueada para edições.";
            } elseif ($podeEditarBase && $periodoObj->trava_fases && !$faseAnteriorConcluida && !$isDev) {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = "Aguardando conclusão da fase anterior.";
            }

            $temSolicitacao = DB::table('avaliacao_solicitacoes')->where('aluno_avaliacao_id', $avFase->id)->where('status', 'pendente')->exists();
            $this->solicitacoesPendentes[$f] = $temSolicitacao;

            // Aluno não pode editar o que já respondeu (mesmo se a avaliação não estiver 100% terminada)
            if ($isStudent && $avFase->status == '2') {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = $temSolicitacao ? "Sua solicitação está em análise." : "Fase já respondida.";
            } elseif (!$podeEditarBase && !isset($this->motivosBloqueio[$f])) {
                $this->motivosBloqueio[$f] = "Acesso restrito para o seu perfil.";
            }

            $this->permissoesFase[$f] = $podeEditarBase;

            if ($avFase->status !== '2') {
                $faseAnteriorConcluida = false;
            }

            // Preenche as variáveis do form
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

    public function salvar()
    {
        abort_if(!feature('avaliacao.responder'), 403);
        
        foreach ($this->avaliacoesFases as $av) {
            $fase = $av->fase;

            if (!($this->permissoesFase[$fase] ?? false)) {
                continue; 
            }

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
        
        session()->flash('sucesso', 'Respostas salvas com sucesso!');
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
        $this->validate([
            'criteriosSelecionados' => 'required|array|min:1',
            'motivoTexto' => 'required|string|min:10',
        ]);

        DB::table('avaliacao_solicitacoes')->insert([
            'aluno_avaliacao_id' => $this->faseSolicitacaoId,
            'student_id' => $this->student_id,
            'criterios_selecionados' => json_encode($this->criteriosSelecionados),
            'motivo' => $this->motivoTexto,
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->solicitacoesPendentes[$this->faseNumero] = true;
        $this->motivosBloqueio[$this->faseNumero] = "Sua solicitação de alteração está em análise pelo professor.";
        $this->modalSolicitacao = false;

        $this->dispatch('sucesso', msg: 'Solicitação enviada para o professor!');
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