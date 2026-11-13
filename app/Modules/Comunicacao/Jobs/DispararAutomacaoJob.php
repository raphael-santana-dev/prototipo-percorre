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
use App\Modules\Comunicacao\Services\AntiSpamComunicacaoService;
use App\Modules\Teste\Suri\Services\SuriApiService;
use App\Models\AuditoriaLog;
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
    public bool $forcarReenvio;

    public function __construct(
        EmailTemplate $template,
        string $emailDestino,
        $inscricao = null,
        array $dadosExtras = [],
        ?string $gatilho = null,
        ?string $canalForcado = null,
        bool $forcarReenvio = false
    ) {
        $this->template      = $template;
        $this->emailDestino  = $emailDestino;
        $this->inscricao     = $inscricao;
        $this->dadosExtras   = $dadosExtras;
        $this->gatilho       = $gatilho;
        $this->canalForcado  = $canalForcado;
        $this->forcarReenvio = $forcarReenvio;
    }

    public function handle()
    {
        $this->template = EmailTemplate::find($this->template->id) ?? $this->template;

        if ($this->inscricao && method_exists($this->inscricao, 'loadMissing')) {
            $this->inscricao->loadMissing(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        }

        $canalTemplate = $this->template->canal ?? 'email';
        $canalDesejado = $this->canalForcado ?: $canalTemplate;

        $enviarEmail = in_array($canalDesejado, ['email', 'ambos'], true)
            && in_array($canalTemplate, ['email', 'ambos'], true)
            && filter_var($this->emailDestino, FILTER_VALIDATE_EMAIL);

        $enviarWpp = in_array($canalDesejado, ['whatsapp', 'ambos'], true)
            && in_array($canalTemplate, ['whatsapp', 'ambos'], true);

        // =====================================================================
        // FILTRO ANTI-SPAM MULTICANAL (E-MAIL E WHATSAPP SURI)
        // =====================================================================
        if (!$this->forcarReenvio && empty($this->dadosExtras['forcar_reenvio'])) {
            $diagSpam = AntiSpamComunicacaoService::diagnosticarEnviosCandidato(
                $this->inscricao?->id,
                $this->emailDestino,
                $this->template->id,
                $this->gatilho
            );

            if ($diagSpam['ja_recebeu_email']) {
                $enviarEmail = false;
            }
            if ($diagSpam['ja_recebeu_whatsapp']) {
                $enviarWpp = false;
            }
        }

        if (!$enviarEmail && !$enviarWpp) {
            return;
        }

        $corpoMiolo = EmailParserService::parseTexto($this->template->corpo, $this->inscricao, $this->dadosExtras, $this->template);
        $assuntoFormatado = EmailParserService::parseTexto($this->template->assunto ?: $this->template->nome, $this->inscricao, $this->dadosExtras, $this->template);

        $comunicado = Comunicado::create([
            'template_id'      => $this->template->id,
            'inscricao_id'     => $this->inscricao ? $this->inscricao->id : null,
            'destinatarios'    => array_values(array_filter([$this->emailDestino, $this->inscricao?->celular])),
            'data_agendamento' => now(),
            'status'           => 'enviando',
        ]);

        $houveSucessoAlgumCanal = false;
        $houveErroAlgumCanal    = false;

        // =====================================================================
        // 1. DISPARO DE E-MAIL
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
                $houveSucessoAlgumCanal = true;
            } catch (\Throwable $e) {
                $statusEnvio = 'Erro no envio: ' . $e->getMessage();
                $logComunicacao->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
                $houveErroAlgumCanal = true;
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
        // 2. DISPARO DE WHATSAPP PELA SURI API
        // =====================================================================
        if ($enviarWpp) {
            $suriService = app(SuriApiService::class);
            $dest        = $suriService->resolverDestinatarioParaDisparo($this->inscricao, $this->dadosExtras);

            $mapaSobrescrito = is_array($this->dadosExtras['variaveis_mapeadas'] ?? null)
                ? $this->dadosExtras['variaveis_mapeadas']
                : [];

            $bodyParameters = EmailParserService::montarBodyParametersSuri(
                $this->template,
                $this->inscricao,
                $this->dadosExtras,
                $mapaSobrescrito
            );

            $buttonsParameters = [];
            $botoesRaw = is_string($this->template->botoes)
                ? (json_decode($this->template->botoes, true) ?? [])
                : ($this->template->botoes ?? []);

            foreach ($botoesRaw as $btn) {
                $tipoBtn = isset($btn['suri_type']) ? (int) $btn['suri_type'] : (empty($btn['url']) ? 2 : 0);
                if ($tipoBtn === 0 && str_contains($btn['url'] ?? '', '{{')) {
                    $colBtn = $btn['coluna_banco'] ?? 'link_assinatura';
                    $buttonsParameters[] = EmailParserService::resolverValorColuna($colBtn, $this->inscricao, $this->dadosExtras);
                } elseif ($tipoBtn === 3) {
                    $buttonsParameters[] = (string) ($btn['example'] ?? 'CODIGO');
                }
            }

            $identificadorDestinoLog = $dest['phone'] ?: ($dest['user_id'] ?: ($this->inscricao?->celular ?: 'Sem telefone'));

            // Validações prévias explícitas para nunca falhar silenciosamente
            if (empty($this->template->suri_template_id)) {
                $resWpp = [
                    'sucesso'     => false,
                    'http_status' => 422,
                    'resposta'    => ['erro' => "O template '{$this->template->nome}' não possui suri_template_id sincronizado com a Suri."],
                ];
            } elseif (empty($dest['phone']) && empty($dest['user_id'])) {
                $resWpp = [
                    'sucesso'     => false,
                    'http_status' => 422,
                    'resposta'    => ['erro' => 'Candidato não possui número de telemóvel/WhatsApp válido nem userId na Suri para realizar o disparo.'],
                ];
            } elseif (!empty($dest['phone'])) {
                $resWpp = $suriService->enviarTemplateComVariaveis(
                    userName: $dest['name'],
                    userPhone: $dest['phone'],
                    templateId: (string) $this->template->suri_template_id,
                    bodyParameters: $bodyParameters,
                    flowId: null,
                    ignorePortfolio: false,
                    responseActionType: 1,
                    email: $dest['email'],
                    gender: 0,
                    channelId: null,
                    channelType: 1,
                    defaultDepartmentId: null,
                    buttonsParameters: $buttonsParameters
                );
            } else {
                $resWpp = $suriService->enviarTemplateSimples(
                    userId: $dest['user_id'],
                    templateId: (string) $this->template->suri_template_id,
                    flowId: null,
                    ignorePortfolio: false,
                    responseActionType: 1,
                    bodyParameters: $bodyParameters,
                    buttonsParameters: $buttonsParameters
                );
            }

            $sucessoWpp = (bool) ($resWpp['sucesso'] ?? false);
            if ($sucessoWpp) {
                $houveSucessoAlgumCanal = true;
            } else {
                $houveErroAlgumCanal = true;
            }

            $erroFormatadoWpp = $sucessoWpp ? null : $this->extrairMensagemErroSuri($resWpp);

            $suriMessageId = data_get($resWpp, 'resposta.data.messageId')
                ?? data_get($resWpp, 'resposta.data.id')
                ?? data_get($resWpp, 'resposta.messageId')
                ?? data_get($resWpp, 'resposta.id');

            $contatoLocal = SuriContato::query()
                ->when($dest['user_id'] ?? null, fn($q) => $q->orWhere('suri_user_id', $dest['user_id']))
                ->when($dest['phone'] ?? null, fn($q) => $q->orWhere('telefone', $dest['phone']))
                ->when($this->inscricao, fn($q) => $q->orWhere('inscricao_id', $this->inscricao->id))
                ->first();

            if ($suriMessageId && Schema::hasTable('suri_mensagens')) {
                SuriMensagem::updateOrCreate(
                    ['external_id' => (string) $suriMessageId, 'direcao' => 'out'],
                    [
                        'suri_contato_id'   => $contatoLocal?->id,
                        'inscricao_id'      => $this->inscricao?->id,
                        'texto'             => trim(strip_tags($corpoMiolo)),
                        'status'            => $sucessoWpp ? 'sent' : 'error',
                        'status_weight'     => $sucessoWpp ? 1 : -1,
                        'error_code'        => $sucessoWpp ? null : (int) ($resWpp['http_status'] ?? 500),
                        'error_reason'      => $erroFormatadoWpp,
                        'failed_at'         => $sucessoWpp ? null : now(),
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
                'destinatario'     => (string) $identificadorDestinoLog,
                'assunto'          => 'WhatsApp: ' . $this->template->nome,
                'corpo'            => $corpoMiolo,
                'data_agendamento' => now(),
                'status'           => $sucessoWpp ? 'enviado' : 'erro',
                'erro_mensagem'    => $erroFormatadoWpp,
                'data_envio'       => $sucessoWpp ? now() : null,
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
                        'destinatario_suri'  => $identificadorDestinoLog,
                        'body_parameters'    => $bodyParameters,
                        'status'             => $sucessoWpp ? 'Enviado com sucesso' : 'Erro no envio',
                        'erro_detalhe'       => $erroFormatadoWpp,
                    ],
                    'usuario_nome' => $this->dadosExtras['disparado_por_nome'] ?? 'Sistema (Automação)',
                    'usuario_role' => 'bot',
                    'ip'           => '127.0.0.1',
                    'navegador'    => 'Background Job Worker',
                ]);
            }
        }

        $statusFinalComunicado = ($houveErroAlgumCanal && !$houveSucessoAlgumCanal) ? 'erro' : 'concluido';
        $comunicado->update(['status' => $statusFinalComunicado]);
    }

    /**
     * Extrai e formata de forma legível a mensagem de erro retornada pela API da Suri.
     */
    private function extrairMensagemErroSuri(array $resWpp): string
    {
        $httpStatus = $resWpp['http_status'] ?? 0;
        $resp       = $resWpp['resposta'] ?? [];

        if (is_string($resp)) {
            $decoded = json_decode($resp, true);
            if (is_array($decoded)) {
                $resp = $decoded;
            } else {
                return ($httpStatus ? "[HTTP {$httpStatus}] " : '') . $resp;
            }
        }

        if (is_array($resp)) {
            $msgPrincipal = data_get($resp, 'error.message')
                ?? data_get($resp, 'error')
                ?? data_get($resp, 'message')
                ?? data_get($resp, 'Message')
                ?? data_get($resp, 'errors.0.message')
                ?? data_get($resp, 'errors.0')
                ?? data_get($resp, 'erro')
                ?? data_get($resp, 'exception')
                ?? data_get($resp, 'raw_body');

            if (is_array($msgPrincipal)) {
                $msgPrincipal = json_encode($msgPrincipal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (!empty($msgPrincipal) && is_string($msgPrincipal)) {
                return ($httpStatus ? "[HTTP {$httpStatus}] " : '') . trim($msgPrincipal);
            }

            return ($httpStatus ? "[HTTP {$httpStatus}] " : '') . json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $httpStatus ? "Falha na comunicação com a API da Suri (HTTP {$httpStatus})." : 'Erro desconhecido ao comunicar com a Suri.';
    }
}