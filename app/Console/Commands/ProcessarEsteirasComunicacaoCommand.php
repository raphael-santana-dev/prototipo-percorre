<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;

class ProcessarEsteirasComunicacaoCommand extends Command
{
    protected $signature = 'comunicacao:processar-esteiras';
    protected $description = 'Processa as esteiras de comunicação (drip campaigns), avaliando condições, datas fixas e atrasos relativos entre etapas.';

    public function handle(): int
    {
        $enviados = EsteiraComunicacaoService::processarEsteirasAtivas();

        if ($enviados > 0) {
            $this->info("{$enviados} disparo(s) de esteira processado(s) com sucesso.");
        }

        return self::SUCCESS;
    }
}