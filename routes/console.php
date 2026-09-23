<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Jobs\ProcessarComunicadoJob;
use App\Modules\Teste\RDCrm\Services\RdCrmService;
use App\Modules\Conteudo\Jobs\AtualizarStatusConteudoJob; // <-- Importar o novo Job

Schedule::call(function () {
    $pendentes = Comunicado::where('status', 'pendente')
        ->where('data_agendamento', '<=', now())
        ->get();

    foreach ($pendentes as $comunicado) {
        ProcessarComunicadoJob::dispatch($comunicado);
    }
})->everyMinute();

Schedule::call(function () {
    RdCrmService::enviarNegociacoesPendentes();
})->everyMinute();

Schedule::job(new AtualizarStatusConteudoJob)->everyMinute();

Schedule::command('aprendizagem:processar-fechamentos')->dailyAt('00:05');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');