<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Models\Importacao;
use Illuminate\Support\Str;

class ProcessarStatusEmLoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $inscricoesIds;
    public $novoStatusId;

    public function __construct($trackingId, $inscricoesIds, $novoStatusId)
    {
        $this->trackingId = $trackingId;
        $this->inscricoesIds = $inscricoesIds;
        $this->novoStatusId = $novoStatusId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $statusNovo = StatusInscricao::find($this->novoStatusId);
        if (!$statusNovo) return;

        $inscricoes = Inscricao::whereIn('id', $this->inscricoesIds)->get();
        $linhasProcessadas = 0;
        
        $eventoGatilho = 'inscricao.status.' . Str::slug($statusNovo->nome, '_');

        foreach ($inscricoes as $inscricao) {
            // 1. Apenas atualiza o banco
            $inscricao->status_inscricao_id = $statusNovo->id;
            $inscricao->save();

            // 2. Delega TODA a inteligência (E-mail e Criação de Aluno) para o Motor Central
            \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $inscricao);

            $linhasProcessadas++;
            if ($linhasProcessadas % 10 === 0 && $tracking) {
                $tracking->update(['linhas_processadas' => $linhasProcessadas]);
            }
        }

        if ($tracking) {
            $tracking->update([
                'status' => 'concluido',
                'linhas_processadas' => $linhasProcessadas
            ]);
        }
    }
}