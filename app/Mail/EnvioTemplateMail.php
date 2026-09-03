<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;

class EnvioTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $template;
    public $inscricao;
    public $linkRetomada;

    public function __construct(EmailTemplate $template, $inscricao, $linkRetomada)
    {
        $this->template = $template;
        $this->inscricao = $inscricao;
        $this->linkRetomada = $linkRetomada;
    }

    public function build()
    {
        // Caso seu parser exija array de dados, injetamos as tags coringas aqui
        $dadosParsed = [
            'nome' => $this->inscricao->nome,
            'cpf' => $this->inscricao->cpf,
            'link_retomada' => $this->linkRetomada
        ];

        // Usa o serviço do seu Módulo de Comunicação para converter as variáveis no corpo do texto
        $corpoFormatado = EmailParserService::parse($this->template->corpo, $dadosParsed);

        return $this->subject($this->template->assunto)->html($corpoFormatado);
    }
}