<?php

namespace App\Mail;

use App\Modules\Financeiro\Domain\Models\Orcamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrcamentoFinalizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Orcamento $orcamento;
    public string $coordenadorNome;
    public float $valorTotal;
    public int $qtdItens;

    /**
     * Recebe a instância do orçamento finalizado e os dados do coordenador.
     */
    public function __construct(Orcamento $orcamento, string $coordenadorNome)
    {
        $this->orcamento = $orcamento->loadMissing(['centroCusto', 'itens']);
        $this->coordenadorNome = $coordenadorNome;
        $this->qtdItens = $this->orcamento->itens->count();
        
        // Calcula o valor total somando os 12 meses de todos os itens
        $this->valorTotal = $this->orcamento->itens->reduce(function ($carry, $item) {
            return $carry + (
                (float) $item->valor_jan + (float) $item->valor_fev + (float) $item->valor_mar +
                (float) $item->valor_abr + (float) $item->valor_mai + (float) $item->valor_jun +
                (float) $item->valor_jul + (float) $item->valor_ago + (float) $item->valor_set +
                (float) $item->valor_out + (float) $item->valor_nov + (float) $item->valor_dez
            );
        }, 0.0);
    }

    /**
     * Define o assunto do e-mail.
     */
    public function envelope(): Envelope
    {
        $ccNome = $this->orcamento->centroCusto 
            ? $this->orcamento->centroCusto->nome 
            : $this->orcamento->ccusto;

        return new Envelope(
            subject: "[Orçamento {$this->orcamento->ano}] Finalizado - {$ccNome} ({$this->orcamento->ccusto})",
        );
    }

    /**
     * Define a view do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.financeiro.orcamento-finalizado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}