<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Jobs\ProcessarComunicadoJob;

// O Cron job roda a cada minuto procurando e-mails atrasados ou na hora exata
Schedule::call(function () {
    $pendentes = Comunicado::where('status', 'pendente')
        ->where('data_agendamento', '<=', now())
        ->get();

    foreach ($pendentes as $comunicado) {
        ProcessarComunicadoJob::dispatch($comunicado);
    }
})->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
