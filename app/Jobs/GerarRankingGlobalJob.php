<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Ciclo;
use App\Models\Inscricao;
use App\Models\Importacao;

class GerarRankingGlobalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Limite de 1 hora
    protected $trackingId;

    public function __construct($trackingId)
    {
        $this->trackingId = $trackingId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $ciclos = Ciclo::where('status', true)->whereNotNull('regras_pontuacao')->get();
        
        $totalInscricoes = 0;
        foreach ($ciclos as $ciclo) {
            $totalInscricoes += $ciclo->inscricoes()->count();
        }

        if ($tracking) $tracking->update(['total_linhas' => $totalInscricoes]);

        $processados = 0;

        try {
            foreach ($ciclos as $ciclo) {
                // 1. Limpa os rankings antigos deste ciclo (via query única direta no DB)
                Inscricao::where('ciclo_id', $ciclo->id)->update([
                    'posicao_ranking' => null, 
                    'posicao_ranking_geral' => null,
                    'posicao_ranking_unidade' => null,
                    'posicao_ranking_curso' => null,
                ]);

                // 2. Carrega todos ordenados pela maior nota
                $inscricoes = $ciclo->inscricoes()
                    ->orderBy('pontuacao_total', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                // RANKING 1: GERAL
                foreach ($inscricoes as $index => $inscricao) {
                    $inscricao->posicao_ranking_geral = $index + 1;
                }

                // RANKING 2: POR UNIDADE
                $agrupadoUnidade = $inscricoes->whereNotNull('unidade_id')->groupBy('unidade_id');
                foreach ($agrupadoUnidade as $grupo) {
                    $pos = 1;
                    foreach ($grupo as $inscricao) { $inscricao->posicao_ranking_unidade = $pos++; }
                }

                // RANKING 3: POR CURSO (Unidade + Curso)
                $agrupadoCurso = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->groupBy(function($item) {
                    return $item->unidade_id . '-' . $item->curso_id;
                });
                foreach ($agrupadoCurso as $grupo) {
                    $pos = 1;
                    foreach ($grupo as $inscricao) { $inscricao->posicao_ranking_curso = $pos++; }
                }

                // RANKING 4: POR TURMA (Unidade + Curso + Turno)
                $agrupadoTurma = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->whereNotNull('turno_id')->groupBy(function($item) {
                    return $item->unidade_id . '-' . $item->curso_id . '-' . $item->turno_id;
                });
                foreach ($agrupadoTurma as $grupo) {
                    $pos = 1;
                    foreach ($grupo as $inscricao) { $inscricao->posicao_ranking = $pos++; }
                }

                // 3. Salva no banco de forma otimizada
                foreach ($inscricoes as $inscricao) {
                    $inscricao->saveQuietly(); // Impede gatilhos desnecessários no banco
                    $processados++;
                    
                    if ($processados % 100 === 0 && $tracking) {
                        $tracking->update(['linhas_processadas' => $processados]);
                    }
                }
            }

            if ($tracking) $tracking->update(['status' => 'concluido', 'linhas_processadas' => $processados]);

        } catch (\Throwable $e) {
            if ($tracking) {
                $tracking->update([
                    'status' => 'erro',
                    'erro_mensagem' => json_encode([['linha' => 0, 'tipo' => 'Erro Fatal', 'mensagem' => $e->getMessage()]])
                ]);
            }
        }
    }
}