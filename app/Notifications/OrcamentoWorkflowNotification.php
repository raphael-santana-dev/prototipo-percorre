<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrcamentoWorkflowNotification extends Notification
{
    use Queueable;

    public array $dados;

    /**
     * @param array $dados [
     *   'titulo' => string,
     *   'mensagem' => string,
     *   'detalhes' => string|null,
     *   'url' => string,
     *   'texto_botao' => string,
     *   'icone' => string,
     *   'cor' => string,
     *   'orcamento_id' => int,
     *   'ccusto' => string,
     *   'ano' => string,
     *   'status' => string,
     * ]
     */
    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Orçamento ' . ($this->dados['ano'] ?? '') . ' - CC ' . ($this->dados['ccusto'] ?? '') . '] ' . ($this->dados['titulo'] ?? 'Atualização de Orçamento'))
            ->greeting('Olá, ' . ($notifiable->name ?? 'Utilizador') . '!')
            ->line($this->dados['mensagem'] ?? '');

        if (!empty($this->dados['detalhes'])) {
            $mail->line($this->dados['detalhes']);
        }

        if (!empty($this->dados['url'])) {
            $mail->action($this->dados['texto_botao'] ?? 'Aceder ao Orçamento', $this->dados['url']);
        }

        return $mail->line('Esta é uma mensagem automática do Módulo de Gestão Orçamentária — Instituto Percorre.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'        => $this->dados['titulo'] ?? 'Atualização de Orçamento',
            'titulo'       => $this->dados['titulo'] ?? 'Atualização de Orçamento',
            'message'      => $this->dados['mensagem'] ?? '',
            'mensagem'     => $this->dados['mensagem'] ?? '',
            'detalhes'     => $this->dados['detalhes'] ?? null,
            'url'          => $this->dados['url'] ?? route('financeiro.orcamentos'),
            'action_url'   => $this->dados['url'] ?? route('financeiro.orcamentos'),
            'icon'         => $this->dados['icone'] ?? 'ph-receipt',
            'icone'        => $this->dados['icone'] ?? 'ph-receipt',
            'color'        => $this->dados['cor'] ?? 'purpura',
            'orcamento_id' => $this->dados['orcamento_id'] ?? null,
            'ccusto'       => $this->dados['ccusto'] ?? null,
            'ano'          => $this->dados['ano'] ?? null,
            'status'       => $this->dados['status'] ?? null,
        ];
    }
}