<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Solicitacao;

class NovaSolicitacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitacao;
    public $nomeSolicitante;
    public $tipoAcao;

    public function __construct(Solicitacao $solicitacao, $nomeSolicitante, $tipoAcao)
    {
        $this->solicitacao = $solicitacao;
        $this->nomeSolicitante = $nomeSolicitante;
        $this->tipoAcao = $tipoAcao;
    }

    public function build()
    {
        return $this->subject("[Sistema] Nova Solicitação: {$this->tipoAcao}")
                    ->html("
                        <h2>Nova Solicitação Registrada</h2>
                        <p><b>Solicitante:</b> {$this->nomeSolicitante}</p>
                        <p><b>Ação Requerida:</b> {$this->tipoAcao}</p>
                        <p><b>Justificativa:</b> {$this->solicitacao->justificativa}</p>
                        <hr>
                        <p>Acesse o painel administrativo para analisar ou visualizar o log (se foi auto-aprovada).</p>
                    ");
    }
}