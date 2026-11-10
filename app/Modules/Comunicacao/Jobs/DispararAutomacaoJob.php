<?php

namespace App\Modules\Comunicacao\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Comunicacao\Services\EmailTrackingService;
use App\Modules\Teste\Suri\Services\SuriApiService;
use App\Models\AuditoriaLog;
use App\Models\InscricaoContato;
use App\Models\SuriContato;
use App\Models\SuriMensagem;

class DispararAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $template;
    public $emailDestino;
    public $inscricao; 
    public $dadosExtras;
    public $gatilho;
    public ?string $canalForcado;

    public function __construct(
        EmailTemplate $template,
        string $emailDestino,
        $inscricao = null,
        array $dadosExtras = [],
        ?string $gatilho = null,
        ?string $canalForcado = null
    ) {
        $this->template     = $template;
        $this->emailDestino = $emailDestino;
        $this->inscricao    = $inscricao;
        $this->dadosExtras  = $dadosExtras;
        $this->gatilho      = $gatilho;
        $this->canalForcado = $canalForcado;
    }

    public function handle()
    {
        $canalTemplate = $this->template->canal ?? 'email';
        $canalDesejado = $this->canalForcado ?: $canalTemplate;

        // Só dispara E-mail se a regra pediu ('email' ou 'ambos') E o template suporta ('email' ou 'ambos')
        $enviarEmail = in_array($canalDesejado, ['email', 'ambos'], true)
            && in_array($canalTemplate, ['email', 'ambos'], true)
            && filter_var($this->emailDestino, FILTER_VALIDATE_EMAIL);

        // Só dispara WhatsApp se a regra pediu ('whatsapp' ou 'ambos') E o template suporta ('whatsapp' ou 'ambos')
        $enviarWpp = in_array($canalDesejado, ['whatsapp', 'ambos'], true)
            && in_array($canalTemplate, ['whatsapp', 'ambos'], true)
            && !empty($this->template->suri_template_id);

        $corpoMiolo = EmailParserService::parseTexto($this->template->corpo, $this->inscricao, $this->dadosExtras);
        $assuntoFormatado = EmailParserService::parseTexto($this->template->assunto ?: $this->template->nome, $this->inscricao, $this->dadosExtras);

        $comunicado = Comunicado::create([
            'template_id'      => $this->template->id,
            'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
            'destinatarios'    => array_values(array_filter([$this->emailDestino, $this->inscricao?->celular])),
            'data_agendamento' => now(),
            'status'           => 'enviando',
        ]);

        // =====================================================================
        // 1. DISPARO DE E-MAIL (Mantido com Mailpit / Tracking Padrão)
        // =====================================================================
        if ($enviarEmail) {
            $dadosLogEmail = [
                'tracking_token'   => (string) Str::uuid(),
                'comunicado_id'    => $comunicado->id,
                'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
                'template_id'      => $this->template->id,
                'origem'           => 'automacao',
                'gatilho'          => $this->gatilho,
                'template_nome'    => $this->template->nome . ($enviarWpp ? ' (E-mail)' : ''),
                'usuario_nome'     => $this->dadosExtras['disparado_por_nome'] ?? 'Sistema (Automático)',
                'destinatario'     => $this->emailDestino,
                'assunto'          => $assuntoFormatado,
                'corpo'            => $corpoMiolo,
                'data_agendamento' => now(),
                'status'           => 'pendente',
            ];

            if (Schema::hasColumn('comunicacao_logs', 'canal')) {
                $dadosLogEmail['canal'] = 'email';
            }

            $logComunicacao = ComunicacaoLog::create($dadosLogEmail);

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
                    'registro_id'     => $this->inscricao->id,
                    'acao'            => 'automacao_email',
                    'nova_informacao' => [
                        'assunto'            => $assuntoFormatado,
                        'template_utilizado' => $this->template->nome,
                        'gatilho_disparo'    => $this->gatilho,
                        'destinatario'       => $this->emailDestino,
                        'status'             => $statusEnvio,
                    ],
                    'usuario_nome' => $this->dadosExtras['disparado_por_nome'] ?? 'Sistema (Automação)',
                    'usuario_role' => 'bot',
                    'ip'           => '127.0.0.1',
                    'navegador'    => 'Background Job Worker',
                ]);
            }
        }

        // =====================================================================
        // 2. DISPARO DE WHATSAPP PELA SURI API (USANDO DISPARO SIMPLES)
        // =====================================================================
        if ($enviarWpp) {
            $suriService = app(SuriApiService::class);
            $userIdSuri  = $suriService->resolverUserIdParaDisparo($this->inscricao, $this->dadosExtras);

            if ($userIdSuri) {
                // Usa exclusivamente o Disparo Simples (userId + templateId)
                $resWpp = $suriService->enviarTemplateSimples(
                    userId: $userIdSuri,
                    templateId: (string) $this->template->suri_template_id
                );

                /*
                 * [MANTIDO PARA TESTES FUTUROS CONFORME SOLICITADO]
                 * Disparo com variáveis e parâmetros de botões:
                 * $resWpp = $suriService->enviarTemplateComVariaveis(
                 *     $nomeCandidato, $telefoneLimpo, $this->template->suri_template_id,
                 *     $bodyParameters, null, false, 1, $this->emailDestino, 0, null, 1, null, $buttonsParameters
                 * );
                 */

                $suriMessageId = data_get($resWpp, 'resposta.data.messageId')
                    ?? data_get($resWpp, 'resposta.data.id')
                    ?? data_get($resWpp, 'resposta.messageId')
                    ?? data_get($resWpp, 'resposta.id');

                $contatoLocal = SuriContato::where('suri_user_id', $userIdSuri)
                    ->when($this->inscricao, fn($q) => $q->orWhere('inscricao_id', $this->inscricao->id))
                    ->first();

                if ($suriMessageId && $SchemaHasSuriMsg = Schema::hasTable('suri_mensagens')) {
                    SuriMensagem::updateOrCreate(
                        ['external_id' => (string) $suriMessageId, 'direcao' => 'out'],
                        [
                            'suri_contato_id'   => $contatoLocal?->id,
                            'inscricao_id'      => $this->inscricao?->id,
                            'texto'             => trim(strip_tags($corpoMiolo)),
                            'status'            => $resWpp['sucesso'] ? 'sent' : 'error',
                            'status_weight'     => $resWpp['sucesso'] ? 1 : -1,
                            'sent_timestamp_ms' => now()->getTimestampMs(),
                            'attendant_name'    => 'Automação Suri',
                        ]
                    );
                }

                $dadosLogWpp = [
                    'tracking_token'   => null,
                    'comunicado_id'    => $comunicado->id,
                    'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
                    'template_id'      => $this->template->id,
                    'origem'           => 'automacao',
                    'gatilho'          => $this->gatilho,
                    'template_nome'    => $this->template->nome . ' (WhatsApp)',
                    'usuario_nome'     => $this->dadosExtras['disparado_por_nome'] ?? 'Sistema (Automático)',
                    'destinatario'     => $userIdSuri,
                    'assunto'          => 'WhatsApp: ' . $this->template->nome,
                    'corpo'            => $corpoMiolo,
                    'data_agendamento' => now(),
                    'status'           => $resWpp['sucesso'] ? 'enviado' : 'erro',
                    'erro_mensagem'    => $resWpp['sucesso'] ? null : json_encode($resWpp['resposta'], JSON_UNESCAPED_UNICODE),
                    'data_envio'       => $resWpp['sucesso'] ? now() : null,
                ];

                if (Schema::hasColumn('comunicacao_logs', 'canal')) {
                    $dadosLogWpp['canal'] = 'whatsapp';
                }
                if (Schema::hasColumn('comunicacao_logs', 'suri_message_id')) {
                    $dadosLogWpp['suri_message_id'] = $suriMessageId ? (string) $suriMessageId : null;
                }

                ComunicacaoLog::create($dadosLogWpp);

                if ($this->inscricao) {
                    AuditoriaLog::create([
                        'tabela_alterada' => 'inscricoes',
                        'registro_id'     => $this->inscricao->id,
                        'acao'            => 'automacao_whatsapp',
                        'nova_informacao' => [
                            'template_utilizado' => $this->template->nome,
                            'suri_template_id'   => $this->template->suri_template_id,
                            'gatilho_disparo'    => $this->gatilho,
                            'destinatario_suri'  => $userIdSuri,
                            'status'             => $resWpp['sucesso'] ? 'Enviado com sucesso' : 'Erro no envio',
                        ],
                        'usuario_nome' => $this->dadosExtras['disparado_por_nome'] ?? 'Sistema (Automação)',
                        'usuario_role' => 'bot',
                        'ip'           => '127.0.0.1',
                        'navegador'    => 'Background Job Worker',
                    ]);
                }
            }
        }

        $comunicado->update(['status' => 'concluido']);
    }
}