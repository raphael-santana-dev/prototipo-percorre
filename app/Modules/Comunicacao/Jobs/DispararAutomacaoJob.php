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

class DispararAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $template;
    public $emailDestino;
    public $inscricao; 
    public $dadosExtras;

    public function __construct(EmailTemplate $template, string $emailDestino, $inscricao = null, array $dadosExtras = [])
    {
        $this->template = $template;
        $this->emailDestino = $emailDestino;
        $this->inscricao = $inscricao;
        $this->dadosExtras = $dadosExtras;
    }

    public function handle()
    {
        $htmlFormatado = EmailParserService::parseTexto($this->template->corpo, $this->inscricao, $this->dadosExtras);
        $assuntoFormatado = EmailParserService::parseTexto($this->template->assunto, $this->inscricao, $this->dadosExtras);

        Mail::html($htmlFormatado, function ($msg) use ($assuntoFormatado) {
            $msg->to($this->emailDestino)->subject($assuntoFormatado);
        });
    }
}