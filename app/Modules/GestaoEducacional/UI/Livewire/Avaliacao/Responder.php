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
        $this->periodo_id = $periodo;
        $this->turma_id = $turma;
        $this->student_id = $student;

        // 1. Identificação do Usuário Logado Corrigida
        $isStudent = auth()->guard('student')->check();
        $isProfessor = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('professor'); 

        $periodoObj = PeriodoAvaliacao::with('fases', 'criterios')->findOrFail($periodo);
        $this->criterios = $periodoObj->criterios;

        // 2. Busca as matrizes do aluno
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

        $faseAnteriorConcluida = true; 
        
        // 3. Motor de Regras e Bloqueios
        foreach ($this->avaliacoesFases as $avFase) {
            $f = $avFase->fase;
            
            $configFase = $periodoObj->fases->where('fase', $f)->first();
            $resp = $configFase ? $configFase->responsavel : '0';
            $textos = ['1' => 'Estudante', '2' => 'Professor', '3' => 'Ambos'];
            $this->responsaveisDesc[$f] = $textos[$resp] ?? 'N/A';

            $podeEditarBase = false;
            if ($isStudent && in_array($resp, ['1', '3'])) $podeEditarBase = true;
            if ($isProfessor && in_array($resp, ['2', '3'])) $podeEditarBase = true;

            // Bloqueio 1: Trava Sequencial
            if ($podeEditarBase && $periodoObj->trava_fases && !$faseAnteriorConcluida) {
                $podeEditarBase = false;
                $this->motivosBloqueio[$f] = "Aguardando conclusão da fase anterior.";
            }

            // Verifica solicitação de desbloqueio pendente
            $temSolicitacao = DB::table('avaliacao_solicitacoes')
                ->where('aluno_avaliacao_id', $avFase->id)
                ->where('status', 'pendente')
                ->exists();
            
            $this->solicitacoesPendentes[$f] = $temSolicitacao;

            // Bloqueio 2: Se já respondeu (Status = 2)
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

            // Preenche os arrays do formulário
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

            // Se todas as notas da fase foram preenchidas, marca como concluída (2)
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

        $loggedId = auth()->guard('student')->check() ? auth()->guard('student')->id() : auth()->id();

        DB::table('avaliacao_solicitacoes')->insert([
            'aluno_avaliacao_id' => $this->faseSolicitacaoId,
            'student_id' => $this->student_id, // Vincula a solicitação ao estudante correto
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
            
            // Fases 1 e 2
            if (isset($this->nps[$id][1]) && is_numeric($this->nps[$id][1])) {
                $notasParcial[] = $this->nps[$id][1];
                $notasFinal[] = $this->nps[$id][1];
            }
            if (isset($this->nps[$id][2]) && is_numeric($this->nps[$id][2])) {
                $notasParcial[] = $this->nps[$id][2];
                $notasFinal[] = $this->nps[$id][2];
            }
            
            // Fase 3
            if (isset($this->nps[$id][3]) && is_numeric($this->nps[$id][3])) {
                $notasFinal[] = $this->nps[$id][3];
            }
        }

        $this->mediaParcial = count($notasParcial) > 0 ? round(array_sum($notasParcial) / count($notasParcial), 1) : '-';
        $this->mediaFinal = count($notasFinal) > 0 ? round(array_sum($notasFinal) / count($notasFinal), 1) : '-';
    }

    public function render()
    {
        $this->calcularMedias();
        
        $layout = auth()->guard('student')->check() ? 'components.layouts.student-app' : 'components.layouts.app';
        
        return view('livewire.gestao-educacional.avaliacao.responder')
            ->layout($layout, ['title' => 'Matriz de Avaliação']);
    }
}