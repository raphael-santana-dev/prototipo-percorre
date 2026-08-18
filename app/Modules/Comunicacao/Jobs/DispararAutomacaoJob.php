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
        $assuntoTraduzido = EmailParserService::parse($this->template->assunto, $this->contexto);
        $corpoTraduzido = EmailParserService::parse($this->template->corpo, $this->contexto);

        // Cria o Log da Automação
        $log = \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::create([
            'origem' => 'automacao',
            'destinatario' => $this->email,
            'assunto' => $assuntoTraduzido,
            'corpo' => $corpoTraduzido,
            'data_agendamento' => now(),
            'status' => 'pendente'
        ]);

        try {
            Mail::to($this->email)->send(new ComunicadoMail($assuntoTraduzido, $corpoTraduzido));
            $log->update(['status' => 'enviado', 'data_envio' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
            \Log::error("Erro na automação de e-mail para {$this->email}: " . $e->getMessage());
        }
    }
}