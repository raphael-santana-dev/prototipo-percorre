<?php

namespace App\Modules\Comunicacao\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Modules\Comunicacao\Services\EmailTrackingService;
use App\Modules\Comunicacao\Mail\ComunicadoMail;

class ProcessarComunicadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $comunicado;

    public function __construct(Comunicado $comunicado)
    {
        $this->comunicado = $comunicado;
    }

    public function handle(): void
    {
        $this->comunicado->update(['status' => 'enviando']);
        
        $logs = ComunicacaoLog::where('comunicado_id', $this->comunicado->id)
            ->where('status', 'pendente')
            ->get();

        $isFirst = true;

        foreach ($logs as $log) {
            try {
                if (empty($log->tracking_token)) {
                    $log->update(['tracking_token' => (string) Str::uuid()]);
                }

                // Aplica o Layout Padrão e injeta o rastreamento de abertura e cliques para este destinatário
                $htmlRastreavel = EmailTrackingService::empacotarEmail($log->corpo, $log, $log->assunto);
                $log->update(['corpo' => $htmlRastreavel]);

                $mensagem = Mail::to($log->destinatario);

                if ($isFirst) {
                    if (!empty($this->comunicado->cc)) $mensagem->cc($this->comunicado->cc);
                    if (!empty($this->comunicado->bcc)) $mensagem->bcc($this->comunicado->bcc);
                    $isFirst = false;
                }

                $mensagem->send(new ComunicadoMail($log->assunto, $htmlRastreavel, $log->anexos ?? []));
                
                $log->update(['status' => 'enviado', 'data_envio' => now()]);
            } catch (\Throwable $e) {
                $log->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
            }
        }

        $this->comunicado->update(['status' => 'concluido']);
    }
}