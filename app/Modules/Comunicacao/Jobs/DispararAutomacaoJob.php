<?php

namespace App\Modules\Comunicacao\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Comunicacao\Mail\ComunicadoMail;

class DispararAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $template;
    protected $email;
    protected $contexto;

    public function __construct(EmailTemplate $template, string $email, array $contexto)
    {
        $this->template = $template;
        $this->email = $email;
        $this->contexto = $contexto;
    }

    public function handle(): void
    {
        try {
            // Traduz as variáveis do template (ex: {{estudante.nome}}) baseadas no contexto
            $assuntoTraduzido = EmailParserService::parse($this->template->assunto, $this->contexto);
            $corpoTraduzido = EmailParserService::parse($this->template->corpo, $this->contexto);

            // Envia o e-mail usando o mesmo Mailable que já criamos!
            Mail::to($this->email)->send(new ComunicadoMail($assuntoTraduzido, $corpoTraduzido));

        } catch (\Throwable $e) {
            \Log::error("Erro na automação de e-mail para {$this->email}: " . $e->getMessage());
        }
    }
}