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
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Models\AuditoriaLog;

class DispararAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $template;
    public $emailDestino;
    public $inscricao; 
    public $dadosExtras;
    public $gatilho;

    public function __construct(EmailTemplate $template, string $emailDestino, $inscricao = null, array $dadosExtras = [], string $gatilho = null)
    {
        $this->template = $template;
        $this->emailDestino = $emailDestino;
        $this->inscricao = $inscricao;
        $this->dadosExtras = $dadosExtras;
        $this->gatilho = $gatilho;
    }

    public function handle()
    {
        $htmlFormatado = EmailParserService::parseTexto($this->template->corpo, $this->inscricao, $this->dadosExtras);
        $assuntoFormatado = EmailParserService::parseTexto($this->template->assunto, $this->inscricao, $this->dadosExtras);

        // 1. Registra no Log de Comunicação (Para a aba de E-mails)
        $logComunicacao = ComunicacaoLog::create([
            'comunicado_id' => null,
            'origem' => 'automação',
            'gatilho' => $this->gatilho,
            'template_nome' => $this->template->nome,
            'usuario_nome' => 'Sistema (Automático)',
            'destinatario' => $this->emailDestino,
            'assunto' => $assuntoFormatado,
            'corpo' => $htmlFormatado,
            'data_agendamento' => now(),
            'status' => 'pendente'
        ]);

        $statusEnvio = 'Enviado com sucesso';
        
        try {
            Mail::html($htmlFormatado, function ($msg) use ($assuntoFormatado) {
                $msg->to($this->emailDestino)->subject($assuntoFormatado);
            });
            $logComunicacao->update(['status' => 'enviado', 'data_envio' => now()]);
        } catch (\Exception $e) {
            $statusEnvio = 'Erro no envio: ' . $e->getMessage();
            $logComunicacao->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
        }

        // 2. Registra na Timeline do Candidato (Auditoria)
        if ($this->inscricao) {
            AuditoriaLog::create([
                'tabela_alterada' => 'inscricoes',
                'registro_id' => $this->inscricao->id,
                'acao' => 'automacao_email',
                'nova_informacao' => [
                    'assunto' => $assuntoFormatado,
                    'template_utilizado' => $this->template->nome,
                    'gatilho_disparo' => $this->gatilho,
                    'destinatario' => $this->emailDestino,
                    'status' => $statusEnvio
                ],
                'usuario_nome' => 'Sistema (Automação)',
                'usuario_role' => 'bot',
                'ip' => '127.0.0.1',
                'navegador' => 'Background Job Worker'
            ]);
        }
    }
}