<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Conteudo;

class AtualizarStatusConteudoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // 1. Ativa as publicações agendadas (Inativas, com data de início no passado e sem expirar)
        Conteudo::where('is_active', false)
            ->whereNotNull('data_inicio')
            ->where('data_inicio', '<=', now())
            ->where(function($q) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>', now());
            })
            ->update(['is_active' => true]);

        // 2. Inativa as publicações expiradas (Ativas, com data de fim no passado)
        Conteudo::where('is_active', true)
            ->whereNotNull('data_fim')
            ->where('data_fim', '<=', now())
            ->update(['is_active' => false]);
    }
}