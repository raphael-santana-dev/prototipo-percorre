<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Ciclo;
use App\Models\Importacao;

class GerarRankingGlobalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $trackingId;
    protected $cicloId;

    public function __construct($trackingId, $cicloId = null)
    {
        $this->trackingId = $trackingId;
        $this->cicloId = $cicloId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $queryCiclos = Ciclo::query();
        
        if ($this->cicloId) {
            $queryCiclos->where('id', $this->cicloId);
        } else {
            $queryCiclos->where('status', true);
        }
        $ciclos = $queryCiclos->get();
        
        $totalInscricoes = 0;
        foreach ($ciclos as $ciclo) {
            $totalInscricoes += $ciclo->inscricoes()->count();
        }

        if ($tracking) $tracking->update(['total_linhas' => $totalInscricoes]);

        try {
            foreach ($ciclos as $ciclo) {
                
                DB::table('inscricoes')
                    ->where('ciclo_id', $ciclo->id)
                    ->whereNotNull('posicao_ranking_geral')
                    ->update([
                        'posicao_ranking' => null, 
                        'posicao_ranking_geral' => null,
                        'posicao_ranking_unidade' => null,
                        'posicao_ranking_curso' => null,
                    ]);

                $query = "
                    WITH RankedData AS (
                        SELECT id,
                            RANK() OVER (ORDER BY COALESCE(pontuacao_total, 0) DESC, created_at ASC) as rank_geral,
                            RANK() OVER (PARTITION BY unidade_id ORDER BY COALESCE(pontuacao_total, 0) DESC, created_at ASC) as rank_unidade,
                            RANK() OVER (PARTITION BY unidade_id, curso_id ORDER BY COALESCE(pontuacao_total, 0) DESC, created_at ASC) as rank_curso,
                            RANK() OVER (PARTITION BY unidade_id, curso_id, turno_id ORDER BY COALESCE(pontuacao_total, 0) DESC, created_at ASC) as rank_turma
                        FROM inscricoes
                        WHERE ciclo_id = :ciclo_id
                          AND deleted_at IS NULL
                    )
                    UPDATE inscricoes i
                    SET posicao_ranking_geral = r.rank_geral,
                        posicao_ranking_unidade = r.rank_unidade,
                        posicao_ranking_curso = r.rank_curso,
                        posicao_ranking = r.rank_turma
                    FROM RankedData r
                    WHERE i.id = r.id;
                ";

                DB::statement($query, ['ciclo_id' => $ciclo->id]);
            }

            if ($tracking) $tracking->update(['status' => 'concluido', 'linhas_processadas' => $totalInscricoes]);

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