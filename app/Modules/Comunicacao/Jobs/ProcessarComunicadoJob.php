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
        $template = $this->comunicado->template;

        try {
            foreach ($this->comunicado->destinatarios as $index => $email) {
                
                // 1. Monta o Contexto do usuário para traduzir as variáveis
                $user = User::where('email', $email)->first();
                $inscricao = Inscricao::where('email', $email)->latest()->first();
                $contexto = ['user' => $user, 'inscricao' => $inscricao];

                // 2. Faz a tradução mágica (Parser)
                $assuntoTraduzido = EmailParserService::parse($template->assunto, $contexto);
                $corpoTraduzido = EmailParserService::parse($template->corpo, $contexto);

                // 3. Prepara o envio
                $mensagem = Mail::to($email);

                // Prevenção de Spam: Só adicionamos CC e BCC na primeira volta do laço,
                // caso contrário as pessoas em CC receberiam 1000 cópias repetidas.
                if ($index === 0) {
                    if (!empty($this->comunicado->cc)) $mensagem->cc($this->comunicado->cc);
                    if (!empty($this->comunicado->bcc)) $mensagem->bcc($this->comunicado->bcc);
                }

                // 4. Dispara
                $mensagem->send(new ComunicadoMail($assuntoTraduzido, $corpoTraduzido, $this->comunicado->anexos ?? []));
            }

            $this->comunicado->update(['status' => 'concluido']);

        } catch (\Throwable $e) {
            \Log::error('Erro ao enviar comunicado ID ' . $this->comunicado->id . ': ' . $e->getMessage());
            $this->comunicado->update(['status' => 'erro']);
        }
    }
}