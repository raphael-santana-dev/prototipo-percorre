<?php

namespace App\Modules\Comunicacao\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class ComunicadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $assunto;
    public $corpoHtml;
    public $anexos;

    public function __construct(string $assunto, string $corpoHtml, array $anexos = [])
    {
        $this->assunto = $assunto;
        $this->corpoHtml = $corpoHtml;
        $this->anexos = $anexos;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->assunto);
    }

    // O Laravel permite injetarmos HTML bruto diretamente aqui
    public function content(): Content
    {
        return new Content(htmlString: $this->corpoHtml);
    }

    public function attachments(): array
    {
        $arquivos = [];
        foreach ($this->anexos as $caminho) {
            $arquivos[] = Attachment::fromStorageDisk('public', $caminho);
        }
        return $arquivos;
    }
}