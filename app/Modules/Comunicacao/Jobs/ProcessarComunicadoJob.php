<?php

namespace App\Modules\Comunicacao\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Comunicacao\Mail\ComunicadoMail;

class ProcessarComunicadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Tempo alto pois podem haver milhares de e-mails
    protected $comunicado;

    public function __construct(Comunicado $comunicado)
    {
        $this->comunicado = $comunicado;
    }

    public function handle(): void
    {
        $this->comunicado->update(['status' => 'enviando']);
        
        // Pega todos os logs pendentes deste comunicado
        $logs = \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::where('comunicado_id', $this->comunicado->id)
            ->where('status', 'pendente')
            ->get();

        $isFirst = true;

        foreach ($logs as $log) {
            try {
                $mensagem = Mail::to($log->destinatario);

                // Aplica CC e BCC apenas no primeiro e-mail para evitar que as cópias recebam emails repetidos
                if ($isFirst) {
                    if (!empty($this->comunicado->cc)) $mensagem->cc($this->comunicado->cc);
                    if (!empty($this->comunicado->bcc)) $mensagem->bcc($this->comunicado->bcc);
                    $isFirst = false;
                }

                $mensagem->send(new ComunicadoMail($log->assunto, $log->corpo, $log->anexos ?? []));
                
                $log->update(['status' => 'enviado', 'data_envio' => now()]);
            } catch (\Throwable $e) {
                $log->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
            }
        }

        $this->comunicado->update(['status' => 'concluido']);
    }
}