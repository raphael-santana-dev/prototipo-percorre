<?php

namespace App\Modules\Comunicacao\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Comunicacao\Services\EmailTrackingService;
use App\Modules\Teste\Suri\Services\SuriApiService;
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
        $canal = $this->template->canal ?? 'email';
        $enviarEmail = in_array($canal, ['email', 'ambos']);
        $enviarWpp = in_array($canal, ['whatsapp', 'ambos']);

        $corpoMiolo = EmailParserService::parseTexto($this->template->corpo, $this->inscricao, $this->dadosExtras);
        $assuntoFormatado = EmailParserService::parseTexto($this->template->assunto, $this->inscricao, $this->dadosExtras);

        $comunicado = Comunicado::create([
            'template_id'      => $this->template->id,
            'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
            'destinatarios'    => [$this->emailDestino],
            'data_agendamento' => now(),
            'status'           => 'enviando',
        ]);

        // =====================================================================
        // DISPARO DE E-MAIL
        // =====================================================================
        if ($enviarEmail) {
            $logComunicacao = ComunicacaoLog::create([
                'tracking_token'   => (string) Str::uuid(),
                'comunicado_id'    => $comunicado->id,
                'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
                'template_id'      => $this->template->id,
                'origem'           => 'automacao',
                'gatilho'          => $this->gatilho,
                'template_nome'    => $this->template->nome . ($enviarWpp ? ' (E-mail)' : ''),
                'usuario_nome'     => 'Sistema (Automático)',
                'destinatario'     => $this->emailDestino,
                'assunto'          => $assuntoFormatado,
                'corpo'            => $corpoMiolo,
                'data_agendamento' => now(),
                'status'           => 'pendente'
            ]);

            $htmlFinalComLayout = EmailTrackingService::empacotarEmail($corpoMiolo, $logComunicacao, $assuntoFormatado);
            $logComunicacao->update(['corpo' => $htmlFinalComLayout]);
            
            $statusEnvio = 'Enviado com sucesso';
            try {
                Mail::html($htmlFinalComLayout, function ($msg) use ($assuntoFormatado) {
                    $msg->to($this->emailDestino)->subject($assuntoFormatado);
                });
                $logComunicacao->update(['status' => 'enviado', 'data_envio' => now()]);
            } catch (\Exception $e) {
                $statusEnvio = 'Erro no envio: ' . $e->getMessage();
                $logComunicacao->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
            }

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

        // =====================================================================
        // DISPARO DE WHATSAPP PELA SURI API (Gera Log na tela "Suri Testes")
        // =====================================================================
        if ($enviarWpp && !empty($this->template->suri_template_id)) {
            $telefoneCandidato = $this->inscricao->celular ?? $this->dadosExtras['celular'] ?? $this->dadosExtras['telefone'] ?? null;

            if ($telefoneCandidato) {
                $telefoneLimpo = preg_replace('/\D/', '', $telefoneCandidato);
                if (!str_starts_with($telefoneLimpo, '55') && strlen($telefoneLimpo) >= 10) {
                    $telefoneLimpo = '55' . $telefoneLimpo;
                }

                // Parseia os parâmetros de Body do template Suri
                $cleanBody = strip_tags(html_entity_decode((string) $this->template->corpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $cleanBody, $matches);
                $variaveisNomeadas = array_values(array_unique($matches[1] ?? []));

                $bodyParameters = [];
                foreach ($variaveisNomeadas as $varNome) {
                    $parsed = EmailParserService::parseTexto('{{'.$varNome.'}}', $this->inscricao, $this->dadosExtras);
                    $bodyParameters[] = strip_tags($parsed);
                }

                // Parseia os parâmetros de Botões da Suri (Links Dinâmicos type=0 c/ {{1}} ou Copy type=3 c/ example)
                $buttonsParameters = [];
                $botoesRaw = is_string($this->template->botoes) ? json_decode($this->template->botoes, true) : ($this->template->botoes ?? []);
                foreach ($botoesRaw as $btn) {
                    $tipoBtn = isset($btn['suri_type']) ? (int) $btn['suri_type'] : (empty($btn['url']) ? 2 : 0);
                    if ($tipoBtn === 0 && str_contains($btn['url'] ?? '', '{{')) {
                        $parsedBtn = EmailParserService::parseTexto('{{link_assinatura}}', $this->inscricao, $this->dadosExtras);
                        $buttonsParameters[] = strip_tags($parsedBtn);
                    } elseif ($tipoBtn === 3) {
                        $buttonsParameters[] = $btn['example'] ?? 'CODIGO';
                    }
                }

                $nomeCandidato = $this->inscricao->nome ?? $this->dadosExtras['nome'] ?? 'Candidato';
                $suriService = app(SuriApiService::class);

                // O método enviarTemplateComVariaveis já envia o POST e agora salva automaticamente no suri_test_logs!
                $resWpp = $suriService->enviarTemplateComVariaveis(
                    $nomeCandidato,
                    $telefoneLimpo,
                    $this->template->suri_template_id,
                    $bodyParameters,
                    null, false, 1,
                    $this->emailDestino, 0, null, 1, null,
                    $buttonsParameters
                );

                // Log na aba de Comunicação de Inscrições / Relatório
                ComunicacaoLog::create([
                    'tracking_token'   => null,
                    'comunicado_id'    => $comunicado->id,
                    'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
                    'template_id'      => $this->template->id,
                    'origem'           => 'automacao',
                    'gatilho'          => $this->gatilho,
                    'template_nome'    => $this->template->nome . ' (WhatsApp)',
                    'usuario_nome'     => 'Sistema (Automático)',
                    'destinatario'     => $telefoneLimpo,
                    'assunto'          => 'Mensagem WhatsApp',
                    'corpo'            => 'Visualizável no Console da Suri Lab (ID Log: ' . ($resWpp['log_id'] ?? 'N/A') . ')',
                    'data_agendamento' => now(),
                    'status'           => $resWpp['sucesso'] ? 'enviado' : 'erro',
                    'erro_mensagem'    => $resWpp['sucesso'] ? null : json_encode($resWpp['resposta']),
                    'data_envio'       => $resWpp['sucesso'] ? now() : null,
                ]);

                if ($this->inscricao) {
                    AuditoriaLog::create([
                        'tabela_alterada' => 'inscricoes',
                        'registro_id' => $this->inscricao->id,
                        'acao' => 'automacao_whatsapp',
                        'nova_informacao' => [
                            'template_utilizado' => $this->template->nome,
                            'gatilho_disparo' => $this->gatilho,
                            'destinatario' => $telefoneLimpo,
                            'status' => $resWpp['sucesso'] ? 'Enviado com sucesso' : 'Erro no envio'
                        ],
                        'usuario_nome' => 'Sistema (Automação)',
                        'usuario_role' => 'bot',
                        'ip' => '127.0.0.1',
                        'navegador' => 'Background Job Worker'
                    ]);
                }
            }
        }

        $comunicado->update(['status' => 'concluido']);
    }
}