<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Conteudo;
use App\Models\Ciclo;
use App\Models\Formulario;

class AtualizarStatusConteudoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $agora = now();

        // ==========================================
        // 1. CONTEÚDOS (Blog/Notícias)
        // ==========================================
        Conteudo::where('is_active', false)
            ->whereNotNull('data_inicio')
            ->where('data_inicio', '<=', $agora)
            ->where(function($q) use ($agora) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>', $agora);
            })
            ->update(['is_active' => true]);

        Conteudo::where('is_active', true)
            ->whereNotNull('data_fim')
            ->where('data_fim', '<=', $agora)
            ->update(['is_active' => false]);

        // ==========================================
        // 2. FORMULÁRIOS
        // ==========================================
        Formulario::where('status', false)
            ->whereNotNull('data_inicio')
            ->where('data_inicio', '<=', $agora)
            ->where(function($q) use ($agora) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>', $agora);
            })
            ->update(['status' => true]);

        Formulario::where('status', true)
            ->whereNotNull('data_fim')
            ->where('data_fim', '<=', $agora)
            ->update(['status' => false]);

        // ==========================================
        // 3. CICLOS DE INSCRIÇÃO
        // ==========================================
        // Inativa os ciclos expirados
        Ciclo::where('status', true)
            ->whereNotNull('data_fim')
            ->where('data_fim', '<=', $agora)
            ->update(['status' => false]);

        // Verifica os ciclos programados que chegaram na data
        $ciclosParaAtivar = Ciclo::where('status', false)
            ->whereNotNull('data_inicio')
            ->where('data_inicio', '<=', $agora)
            ->where(function($q) use ($agora) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>', $agora);
            })
            ->get();

        if ($ciclosParaAtivar->isNotEmpty()) {
            // Respeita a regra de negócio do painel: apenas 1 ciclo principal ativo[cite: 17, 18]
            Ciclo::query()->update(['status' => false]);
            
            // Ativa o(s) ciclo(s) no prazo
            Ciclo::whereIn('id', $ciclosParaAtivar->pluck('id'))->update(['status' => true]);
        }
    }
}