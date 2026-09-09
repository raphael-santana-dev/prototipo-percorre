<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Models\Importacao;

class AvancarStatusNoFunilJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $inscricoesIds;

    public function __construct($trackingId, $inscricoesIds)
    {
        $this->trackingId = $trackingId;
        $this->inscricoesIds = $inscricoesIds;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $inscricoes = Inscricao::whereIn('id', $this->inscricoesIds)->get();
        $linhasProcessadas = 0;

        foreach ($inscricoes as $inscricao) {
            
            // 1. Descobrir a ordem atual do candidato no ciclo
            $pivotAtual = DB::table('ciclo_status_inscricao')
                ->where('ciclo_id', $inscricao->ciclo_id)
                ->where('status_inscricao_id', $inscricao->status_inscricao_id)
                ->first();

            $ordemAtual = $pivotAtual ? $pivotAtual->ordem : -1;

            // 2. Descobrir o PRÓXIMO status baseado na ordem
            $proximoPivot = DB::table('ciclo_status_inscricao')
                ->where('ciclo_id', $inscricao->ciclo_id)
                ->where('ordem', '>', $ordemAtual)
                ->orderBy('ordem', 'asc')
                ->first();

            if ($proximoPivot) {
                $statusNovo = StatusInscricao::find($proximoPivot->status_inscricao_id);

                if ($statusNovo) {
                    // Atualiza o banco
                    $inscricao->status_inscricao_id = $statusNovo->id;
                    $inscricao->save();

                    // Delega para o Motor Central
                    $eventoGatilho = 'inscricao.status.' . Str::slug($statusNovo->nome, '_');
                    \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $inscricao);
                }
            }

            $linhasProcessadas++;
            if ($linhasProcessadas % 10 === 0 && $tracking) $tracking->update(['linhas_processadas' => $linhasProcessadas]);
        }

        if ($tracking) $tracking->update(['status' => 'concluido', 'linhas_processadas' => $linhasProcessadas]);
    }
}