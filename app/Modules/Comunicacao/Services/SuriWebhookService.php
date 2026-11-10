<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\AuditoriaLog;
use App\Models\Inscricao;
use App\Models\InscricaoContato;
use App\Models\SuriAtendimento;
use App\Models\SuriContato;
use App\Models\SuriMensagem;
use App\Models\SuriWebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuriWebhookService
{
    /**
     * Pesos de progressão de status para evitar que eventos entregues fora de ordem
     * rebaixem uma mensagem já "read" (3) de volta para "delivered" (2) ou "sent" (1).
     */
    public const WEIGHT_ERROR     = -1;
    public const WEIGHT_SENT      = 1;
    public const WEIGHT_DELIVERED = 2;
    public const WEIGHT_READ      = 3;

    /**
     * Processa um evento salvo na tabela suri_webhook_events.
     */
    public function processarEvento(SuriWebhookEvent $evento): void
    {
        if ($evento->status === 'processed') {
            return;
        }

        $envelope  = $evento->payload;
        $type      = (string) ($envelope['type'] ?? $evento->event_type);
        $payload   = is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [];
        $dataHora  = $evento->event_timestamp ?: now();

        DB::transaction(function () use ($evento, $type, $payload, $dataHora) {
            match ($type) {
                'new-contact'        => $this->handleNewContact($payload, $dataHora),
                'message-received'   => $this->handleMessageReceived($payload, $dataHora),
                'change-queue'       => $this->handleChangeQueue($payload, $dataHora),
                'finish-attendance'  => $this->handleFinishAttendance($evento->event_id, $payload, $dataHora),
                'message-sent'       => $this->handleMessageSent($payload, $dataHora),
                'message-send-error' => $this->handleMessageSendError($payload, $dataHora),
                'message-delivered'  => $this->handleMessageDelivered($payload, $dataHora),
                'message-read'       => $this->handleMessageRead($payload, $dataHora),
                default              => null,
            };

            $evento->update([
                'status'        => 'processed',
                'processed_at'  => now(),
                'error_message' => null,
            ]);
        });
    }

    // =========================================================================
    // 1. NOVO CONTATO (new-contact)
    // =========================================================================
    protected function handleNewContact(array $payload, Carbon $timestamp): void
    {
        $userRaw = $this->getVal($payload, ['user', 'User'], []);
        if (empty($userRaw)) {
            return;
        }

        $this->sincronizarContato($userRaw, null, null, $timestamp);
    }

    // =========================================================================
    // 2. MENSAGEM RECEBIDA DO CONTATO (message-received)
    // =========================================================================
    protected function handleMessageReceived(array $payload, Carbon $timestamp): void
    {
        $userRaw      = $this->getVal($payload, ['user', 'User'], []);
        $channelRaw   = $this->getVal($payload, ['channel', 'Channel'], []);
        $attendantRaw = $this->getVal($payload, ['attendant', 'Attendant'], []);

        // Atenção da documentação: em message-received a chave é "Message" com M maiúsculo
        $msgRaw = $this->getVal($payload, ['Message', 'message'], []);

        $contato = $this->sincronizarContato($userRaw, $channelRaw, $attendantRaw, $timestamp);

        $mid       = (string) ($this->getVal($msgRaw, ['mid', 'Id', 'id']) ?: ('in_' . uniqid()));
        $texto     = (string) ($this->getVal($msgRaw, ['text', 'Text']) ?? '');
        $isEcho    = (bool) ($this->getVal($msgRaw, ['is_echo', 'IsEcho']) ?? false);
        $isDeleted = (bool) ($this->getVal($msgRaw, ['is_deleted', 'IsDeleted']) ?? false);
        $anexos    = $this->getVal($msgRaw, ['attachments', 'Attachments']);

        SuriMensagem::updateOrCreate(
            [
                'external_id' => $mid,
                'direcao'     => 'in',
            ],
            [
                'suri_contato_id'   => $contato?->id,
                'inscricao_id'      => $contato?->inscricao_id,
                'message_type'      => 0,
                'texto'             => $texto,
                'is_echo'           => $isEcho,
                'is_deleted'        => $isDeleted,
                'status'            => 'received',
                'status_weight'     => self::WEIGHT_SENT,
                'sent_timestamp_ms' => $timestamp->getTimestampMs(),
                'attendant_id'      => $this->getVal($attendantRaw, ['Id', 'id']),
                'attendant_name'    => $this->getVal($attendantRaw, ['Name', 'name']),
                'channel_id'        => $this->getVal($channelRaw, ['Id', 'id']) ?? $contato?->channel_id,
                'attachments'       => is_array($anexos) ? $anexos : null,
                'extra_payload'     => [
                    'seq'         => $this->getVal($msgRaw, ['seq']),
                    'author'      => $this->getVal($msgRaw, ['Author', 'author']),
                    'quick_reply' => $this->getVal($msgRaw, ['quick_reply']),
                    'reply_to'    => $this->getVal($msgRaw, ['reply_to']),
                ],
            ]
        );

        // Se o contato estiver vinculado a uma Inscrição do sistema e não for apenas um eco do bot,
        // registra/atualiza no histórico de contatos do candidato marcando houve_resposta = true!
        if ($contato && $contato->inscricao_id && !$isEcho) {
            $this->registrarInteracaoNoCrmInscricao(
                inscricaoId: $contato->inscricao_id,
                conteudo: $texto !== '' ? "[WhatsApp Recebido]: {$texto}" : '[WhatsApp Recebido]: Mídia / Anexo enviado pelo candidato.',
                houveResposta: true,
                autorNome: $contato->nome ?: 'Candidato (Via Suri)',
                dataContato: $timestamp
            );
        }
    }

    // =========================================================================
    // 3. TROCA DE FILA / ESTADO DE ATENDIMENTO (change-queue)
    // =========================================================================
    protected function handleChangeQueue(array $payload, Carbon $timestamp): void
    {
        $from         = (string) ($payload['from'] ?? 'None');
        $to           = (string) ($payload['to'] ?? 'None');
        $userRaw      = $this->getVal($payload, ['user', 'User'], []);
        $attendantRaw = $this->getVal($payload, ['attendant', 'Attendant'], []);

        $contato = $this->sincronizarContato($userRaw, null, $attendantRaw, $timestamp);
        if (!$contato) {
            return;
        }

        $attId    = $this->getVal($attendantRaw, ['Id', 'id']);
        $attName  = $this->getVal($attendantRaw, ['Name', 'name']);
        $attEmail = $this->getVal($attendantRaw, ['Email', 'email']);

        $meta = is_array($contato->metadata) ? $contato->metadata : [];
        $historicoFilas = $meta['queue_history'] ?? [];
        $historicoFilas[] = [
            'from'           => $from,
            'to'             => $to,
            'attendant_id'   => $attId,
            'attendant_name' => $attName,
            'timestamp'      => $timestamp->toIso8601String(),
        ];
        $meta['queue_history'] = array_slice($historicoFilas, -30); // Mantém as últimas 30 transições

        $contato->update([
            'queue_status'        => $to,
            'attendant_id'        => $to === 'None' ? null : $attId,
            'attendant_name'      => $to === 'None' ? null : $attName,
            'attendant_email'     => $to === 'None' ? null : $attEmail,
            'metadata'            => $meta,
            'ultima_interacao_em' => $timestamp,
        ]);
    }

    // =========================================================================
    // 4. FINALIZAÇÃO DE ATENDIMENTO HUMANO (finish-attendance)
    // =========================================================================
    protected function handleFinishAttendance(string $eventId, array $payload, Carbon $timestamp): void
    {
        $userRaw        = $this->getVal($payload, ['user', 'User'], []);
        $attendantRaw   = $this->getVal($payload, ['attendant', 'Attendant'], []);
        $departmentId   = $this->getVal($payload, ['departmentId', 'DepartmentId']);
        $userTags       = $this->getVal($payload, ['userTags', 'UserTags'], []);
        $tagsObsoletas  = $this->getVal($payload, ['tags', 'Tags'], []);
        $attendanceTime = (string) ($this->getVal($payload, ['attendanceTime', 'AttendanceTime']) ?? '00:00:00');
        $messages       = $this->getVal($payload, ['messages', 'Messages'], []);

        $contato = $this->sincronizarContato($userRaw, null, $attendantRaw, $timestamp);
        if (!$contato) {
            return;
        }

        $duracaoSegundos = $this->parseTimeSpanToSeconds($attendanceTime);
        $attId           = $this->getVal($attendantRaw, ['Id', 'id']);
        $attName         = $this->getVal($attendantRaw, ['Name', 'name']) ?: 'Atendente Suri';
        $attEmail        = $this->getVal($attendantRaw, ['Email', 'email']);

        SuriAtendimento::updateOrCreate(
            ['event_id' => $eventId],
            [
                'suri_contato_id'     => $contato->id,
                'inscricao_id'        => $contato->inscricao_id,
                'department_id'       => $departmentId,
                'attendant_id'        => $attId,
                'attendant_name'      => $attName,
                'attendant_email'     => $attEmail,
                'attendance_time_raw' => $attendanceTime,
                'duracao_segundos'    => $duracaoSegundos,
                'user_tags'           => !empty($userTags) ? $userTags : $tagsObsoletas,
                'messages'            => is_array($messages) ? $messages : [],
                'finalizado_em'       => $timestamp,
            ]
        );

        // Atualiza o estado do contato (volta ao fluxo automático após finalizar atendimento)
        $contato->update([
            'queue_status'        => 'None',
            'department_id'       => $departmentId ?: $contato->department_id,
            'tags'                => !empty($userTags) ? $userTags : $contato->tags,
            'ultima_interacao_em' => $timestamp,
        ]);

        // Se o contato pertence a uma Inscrição, salva o resumo do atendimento na aba Comunicação do Candidato
        if ($contato->inscricao_id) {
            $qtdMsgs = is_array($messages) ? count($messages) : 0;
            $duracaoFmt = gmdate('H:i:s', max(0, $duracaoSegundos));

            $resumoTexto = "Atendimento WhatsApp (Suri) finalizado por {$attName} (Duração: {$duracaoFmt} • {$qtdMsgs} mensagem(ns)).";

            // Extrai as últimas mensagens trocadas para facilitar leitura rápida no CRM
            if (is_array($messages) && $qtdMsgs > 0) {
                $trechos = [];
                foreach (array_slice($messages, -5) as $m) {
                    $txt = trim((string) ($m['Text'] ?? $m['text'] ?? ''));
                    if ($txt !== '') {
                        $autor = ($m['Type'] ?? '') === 'UserMessage' ? 'Candidato' : $attName;
                        $trechos[] = "{$autor}: {$txt}";
                    }
                }
                if (!empty($trechos)) {
                    $resumoTexto .= "\n" . implode("\n", $trechos);
                }
            }

            $this->registrarInteracaoNoCrmInscricao(
                inscricaoId: $contato->inscricao_id,
                conteudo: $resumoTexto,
                houveResposta: true,
                autorNome: $attName,
                dataContato: $timestamp
            );
        }
    }

    // =========================================================================
    // 5. MENSAGEM ENVIADA PELO BOT OU ATENDENTE (message-sent) [OPT-IN]
    // =========================================================================
    protected function handleMessageSent(array $payload, Carbon $timestamp): void
    {
        $userRaw      = $this->getVal($payload, ['user', 'User'], []);
        $channelRaw   = $this->getVal($payload, ['channel', 'Channel'], []);
        $attendantRaw = $this->getVal($payload, ['attendant', 'Attendant'], []);
        // Em message-sent, "message" vem em camelCase minúsculo
        $msgRaw       = $this->getVal($payload, ['message', 'Message'], []);

        $contato = $this->sincronizarContato($userRaw, $channelRaw, $attendantRaw, $timestamp);

        $msgId   = (string) ($this->getVal($msgRaw, ['id', 'Id', 'mid']) ?: ('out_' . uniqid()));
        $msgType = (int) ($this->getVal($msgRaw, ['type', 'Type']) ?? 0);
        $texto   = (string) ($this->getVal($msgRaw, ['text', 'Text']) ?? '');
        $anexos  = $this->getVal($msgRaw, ['attachments', 'Attachments'], []);

        $attId   = $this->getVal($attendantRaw, ['Id', 'id']);
        $attName = $this->getVal($attendantRaw, ['Name', 'name']);

        // Verifica se a mensagem já foi criada previamente por um evento fora de ordem (ex: message-delivered ou message-read)
        $existente = SuriMensagem::where('external_id', $msgId)->where('direcao', 'out')->first();

        $pesoAtual  = $existente ? (int) $existente->status_weight : 0;
        $novoStatus = ($existente && $pesoAtual > self::WEIGHT_SENT) ? $existente->status : 'sent';
        $novoPeso   = ($existente && $pesoAtual > self::WEIGHT_SENT) ? $pesoAtual : self::WEIGHT_SENT;

        SuriMensagem::updateOrCreate(
            [
                'external_id' => $msgId,
                'direcao'     => 'out',
            ],
            [
                'suri_contato_id'   => $contato?->id ?? $existente?->suri_contato_id,
                'inscricao_id'      => $contato?->inscricao_id ?? $existente?->inscricao_id,
                'message_type'      => $msgType,
                'texto'             => $texto,
                'status'            => $novoStatus,
                'status_weight'     => $novoPeso,
                'sent_timestamp_ms' => $existente?->sent_timestamp_ms ?: $timestamp->getTimestampMs(),
                'attendant_id'      => $attId,
                'attendant_name'    => $attName ?: 'Chatbot Suri',
                'channel_id'        => $this->getVal($channelRaw, ['Id', 'id']) ?? $contato?->channel_id,
                'attachments'       => is_array($anexos) ? $anexos : [],
                'extra_payload'     => [
                    'button'         => $msgRaw['button'] ?? null,
                    'list'           => $msgRaw['list'] ?? null,
                    'template'       => $msgRaw['template'] ?? null,
                    'catalog'        => $msgRaw['catalog'] ?? null,
                    'catalogProduct' => $msgRaw['catalogProduct'] ?? null,
                    'flow'           => $msgRaw['flow'] ?? null,
                    'payment'        => $msgRaw['payment'] ?? null,
                    'orderDetails'   => $msgRaw['orderDetails'] ?? null,
                ],
            ]
        );

        // Se foi enviada por um atendente humano para um candidato inscrito, registra no CRM
        if ($contato && $contato->inscricao_id && !empty($attId) && $texto !== '') {
            $this->registrarInteracaoNoCrmInscricao(
                inscricaoId: $contato->inscricao_id,
                conteudo: "[WhatsApp Enviado por {$attName}]: {$texto}",
                houveResposta: false,
                autorNome: $attName,
                dataContato: $timestamp
            );
        }

        $this->sincronizarLogComunicacaoInscricao($contato?->inscricao_id, $msgId, 'sent', $timestamp);
    }

    // =========================================================================
    // 6. ERRO NO ENVIO DE MENSAGEM (message-send-error) [OPT-IN]
    // =========================================================================
    protected function handleMessageSendError(array $payload, Carbon $timestamp): void
    {
        $userRaw     = $this->getVal($payload, ['user', 'User'], []);
        $channelRaw  = $this->getVal($payload, ['channel', 'Channel'], []);
        $messageId   = (string) ($this->getVal($payload, ['messageId', 'MessageId']) ?? '');
        $errorCode   = (int) ($this->getVal($payload, ['errorCode', 'ErrorCode']) ?? 0);
        $errorReason = (string) ($this->getVal($payload, ['errorReason', 'ErrorReason']) ?? 'Erro desconhecido no envio');

        $contato = $this->sincronizarContato($userRaw, $channelRaw, null, $timestamp);

        if ($messageId !== '') {
            SuriMensagem::updateOrCreate(
                [
                    'external_id' => $messageId,
                    'direcao'     => 'out',
                ],
                [
                    'suri_contato_id' => $contato?->id,
                    'inscricao_id'    => $contato?->inscricao_id,
                    'channel_id'      => $this->getVal($channelRaw, ['Id', 'id']) ?? $contato?->channel_id,
                    'status'          => 'error',
                    'status_weight'   => self::WEIGHT_ERROR,
                    'error_code'      => $errorCode,
                    'error_reason'    => $errorReason,
                    'failed_at'       => $timestamp,
                ]
            );
        }

        // Registra na Auditoria Geral do sistema para visibilidade da equipe técnica
        AuditoriaLog::create([
            'tabela_alterada'     => 'suri_mensagens',
            'registro_id'         => $contato?->inscricao_id,
            'acao'                => 'integracao_erro_put',
            'informacao_anterior' => null,
            'nova_informacao'     => [
                'origem'       => 'Webhook Suri (message-send-error)',
                'message_id'   => $messageId,
                'error_code'   => $errorCode,
                'error_reason' => $errorReason,
                'contato_nome' => $contato?->nome,
                'telefone'     => $contato?->telefone,
            ],
            'usuario_id'          => null,
            'usuario_nome'        => 'Suri Webhook',
            'usuario_role'        => 'sistema',
            'usuario_login'       => null,
            'ip'                  => request()?->ip(),
            'navegador'           => 'Suri Chatbot Webhook',
        ]);
    }

    // =========================================================================
    // 7. CONFIRMAÇÃO DE ENTREGA (message-delivered) [OPT-IN]
    // =========================================================================
    protected function handleMessageDelivered(array $payload, Carbon $timestamp): void
    {
        $this->atualizarStatusConfirmacao(
            payload: $payload,
            novoStatus: 'delivered',
            novoPeso: self::WEIGHT_DELIVERED,
            colunaTimestamp: 'delivered_at',
            timestamp: $timestamp
        );
    }

    // =========================================================================
    // 8. CONFIRMAÇÃO DE LEITURA (message-read) [OPT-IN]
    // =========================================================================
    protected function handleMessageRead(array $payload, Carbon $timestamp): void
    {
        $this->atualizarStatusConfirmacao(
            payload: $payload,
            novoStatus: 'read',
            novoPeso: self::WEIGHT_READ,
            colunaTimestamp: 'read_at',
            timestamp: $timestamp
        );
    }

    /**
     * Atualiza status de entrega/leitura por lista de messageIds e também pelo watermark (Unix ms),
     * respeitando a máquina de estados para suportar eventos entregues fora de ordem.
     */
    protected function atualizarStatusConfirmacao(
        array $payload,
        string $novoStatus,
        int $novoPeso,
        string $colunaTimestamp,
        Carbon $timestamp
    ): void {
        $userRaw    = $this->getVal($payload, ['user', 'User'], []);
        $channelRaw = $this->getVal($payload, ['channel', 'Channel'], []);
        $messageIds = $this->getVal($payload, ['messageIds', 'MessageIds'], []);
        $watermark  = $this->getVal($payload, ['watermark', 'Watermark']);

        $contato = $this->sincronizarContato($userRaw, $channelRaw, null, $timestamp);
        $channelId = $this->getVal($channelRaw, ['Id', 'id']) ?? $contato?->channel_id;

        // 1. Atualiza ou cria (caso chegue antes do message-sent) cada messageId informado
        if (is_array($messageIds)) {
            foreach ($messageIds as $mId) {
                $mIdStr = trim((string) $mId);
                if ($mIdStr === '') continue;

                $msg = SuriMensagem::firstOrNew([
                    'external_id' => $mIdStr,
                    'direcao'     => 'out',
                ]);

                if (!$msg->exists || (int) $msg->status_weight < $novoPeso) {
                    $msg->status        = $novoStatus;
                    $msg->status_weight = $novoPeso;
                }

                $msg->suri_contato_id = $msg->suri_contato_id ?: $contato?->id;
                $msg->inscricao_id    = $msg->inscricao_id ?: $contato?->inscricao_id;
                $msg->channel_id      = $msg->channel_id ?: $channelId;

                if (empty($msg->{$colunaTimestamp})) {
                    $msg->{$colunaTimestamp} = $timestamp;
                }
                if ($novoStatus === 'read' && empty($msg->delivered_at)) {
                    $msg->delivered_at = $timestamp;
                }

                $msg->save();
            }
        }

        // 2. Aplica a regra do Watermark da documentação:
        // "Todas as mensagens enviadas até esse instante (Unix ms) foram entregues / lidas"
        if ($contato && is_numeric($watermark) && (int) $watermark > 0) {
            $watermarkMs = (int) $watermark;

            $queryWatermark = SuriMensagem::where('suri_contato_id', $contato->id)
                ->where('direcao', 'out')
                ->where('status_weight', '>=', self::WEIGHT_SENT)
                ->where('status_weight', '<', $novoPeso)
                ->whereNotNull('sent_timestamp_ms')
                ->where('sent_timestamp_ms', '<=', $watermarkMs);

            $dadosUpdate = [
                'status'          => $novoStatus,
                'status_weight'   => $novoPeso,
                $colunaTimestamp  => $timestamp,
            ];

            if ($novoStatus === 'read') {
                $queryWatermark->get()->each(function (SuriMensagem $m) use ($dadosUpdate, $timestamp) {
                    if (!$m->delivered_at) {
                        $dadosUpdate['delivered_at'] = $timestamp;
                    }
                    $m->update($dadosUpdate);
                });
            } else {
                $queryWatermark->update($dadosUpdate);
            }
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES DE SINCRONIZAÇÃO COM INSCRIÇÕES E UTILITÁRIOS
    // =========================================================================

    /**
     * Sincroniza o objeto User (PascalCase) da Suri com a tabela suri_contatos
     * e faz o vínculo automático com a tabela inscricoes (por Telefone, CPF ou E-mail).
     */
    protected function sincronizarContato(
        ?array $userRaw,
        ?array $channelRaw = null,
        ?array $attendantRaw = null,
        ?Carbon $timestamp = null
    ): ?SuriContato {
        if (empty($userRaw) || !is_array($userRaw)) {
            return null;
        }

        $suriUserId = (string) ($this->getVal($userRaw, ['Id', 'id']) ?? '');
        if ($suriUserId === '') {
            return null;
        }

        $nome      = $this->getVal($userRaw, ['Name', 'name']);
        $telefone  = $this->getVal($userRaw, ['Phone', 'phone', 'Telephone']);
        $email     = $this->getVal($userRaw, ['Email', 'email']);
        $documento = $this->getVal($userRaw, ['IdentificationDocument', 'Cpf', 'cpf', 'Document']);
        $deptId    = $this->getVal($userRaw, ['DepartmentId', 'departmentId']);
        $tags      = $this->getVal($userRaw, ['UserTags', 'Tags', 'tags']);

        $channelId   = $this->getVal($channelRaw, ['Id', 'id']) ?? $this->getVal($userRaw, ['ChannelId', 'channelId']);
        $channelType = $this->getVal($channelRaw, ['ChannelType', 'channelType']) ?? $this->getVal($userRaw, ['ChannelType', 'channelType']);

        $contato = SuriContato::firstOrNew(['suri_user_id' => $suriUserId]);

        $inscricaoId = $contato->inscricao_id ?: $this->localizarInscricaoCorrespondente($telefone, $email, $documento);

        $contato->fill([
            'inscricao_id'        => $inscricaoId,
            'nome'                => $nome ?: $contato->nome,
            'telefone'            => $telefone ?: $contato->telefone,
            'email'               => $email ?: $contato->email,
            'documento'           => $documento ?: $contato->documento,
            'channel_id'          => $channelId ?: $contato->channel_id,
            'channel_type'        => $channelType !== null ? (int) $channelType : $contato->channel_type,
            'department_id'       => $deptId ?: $contato->department_id,
            'tags'                => is_array($tags) ? $tags : $contato->tags,
            'ultima_interacao_em' => $timestamp ?: now(),
        ]);

        if (!empty($attendantRaw) && is_array($attendantRaw)) {
            $attId = $this->getVal($attendantRaw, ['Id', 'id']);
            if ($attId) {
                $contato->attendant_id    = $attId;
                $contato->attendant_name  = $this->getVal($attendantRaw, ['Name', 'name']);
                $contato->attendant_email = $this->getVal($attendantRaw, ['Email', 'email']);
            }
        }

        $contato->save();

        return $contato;
    }

    protected function sincronizarLogComunicacaoInscricao(
        ?int $inscricaoId,
        ?string $externalMessageId,
        string $novoStatusWebhook,
        Carbon $timestamp,
        ?string $erroMotivo = null
    ): void {
        if (!$inscricaoId && !$externalMessageId) {
            return;
        }

        $query = \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('comunicacao_logs', 'canal'), fn($q) => $q->where('canal', 'whatsapp'));

        $log = null;
        if ($externalMessageId && \Illuminate\Support\Facades\Schema::hasColumn('comunicacao_logs', 'suri_message_id')) {
            $log = (clone $query)->where('suri_message_id', $externalMessageId)->latest('id')->first();
        }

        if (!$log && $inscricaoId) {
            $log = (clone $query)->where('inscricao_id', $inscricaoId)->latest('id')->first();
        }

        if (!$log) {
            return;
        }

        $updates = [];
        if ($externalMessageId && empty($log->suri_message_id)) {
            $updates['suri_message_id'] = $externalMessageId;
        }

        if ($novoStatusWebhook === 'sent' && $log->status === 'pendente') {
            $updates['status']     = 'enviado';
            $updates['data_envio'] = $log->data_envio ?: $timestamp;
        } elseif ($novoStatusWebhook === 'delivered') {
            if ($log->status !== 'lido') {
                $updates['status'] = 'entregue';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('comunicacao_logs', 'entregue_em') && !$log->entregue_em) {
                $updates['entregue_em'] = $timestamp;
            }
        } elseif ($novoStatusWebhook === 'read') {
            $updates['status']             = 'lido';
            $updates['aberturas_count']    = max(1, ((int) $log->aberturas_count) + 1);
            $updates['aberto_em']          = $log->aberto_em ?: $timestamp;
            $updates['ultima_abertura_em'] = $timestamp;
            if (\Illuminate\Support\Facades\Schema::hasColumn('comunicacao_logs', 'entregue_em') && !$log->entregue_em) {
                $updates['entregue_em'] = $timestamp;
            }
        } elseif ($novoStatusWebhook === 'error') {
            $updates['status']        = 'erro';
            $updates['erro_mensagem'] = $erroMotivo ?: $log->erro_mensagem;
        }

        if (!empty($updates)) {
            $log->update($updates);
        }
    }

    /**
     * Procura a Inscrição mais recente do candidato pelo Telefone (ignorando DDI 55 e variações do 9º dígito),
     * CPF ou E-mail.
     */
    protected function localizarInscricaoCorrespondente(?string $telefone, ?string $email, ?string $documento): ?int
    {
        // 1. Busca por CPF se houver
        if (!empty($documento)) {
            $docLimpo = preg_replace('/\D/', '', $documento);
            if (strlen($docLimpo) === 11) {
                $insc = Inscricao::where(function ($q) use ($docLimpo, $documento) {
                    $q->where('cpf', $docLimpo)->orWhere('cpf', $documento);
                })->latest('id')->first();

                if ($insc) return $insc->id;
            }
        }

        // 2. Busca por Telefone (tratando DDI 55 e 9º dígito do WhatsApp no Brasil)
        if (!empty($telefone)) {
            $digitos = preg_replace('/\D/', '', $telefone);
            if (str_starts_with($digitos, '55') && strlen($digitos) >= 12) {
                $digitos = substr($digitos, 2); // Remove o DDI 55
            }

            if (strlen($digitos) >= 10) {
                $ddd     = substr($digitos, 0, 2);
                $final8  = substr($digitos, -8); // Últimos 8 dígitos são imutáveis com ou sem 9º dígito

                $insc = Inscricao::whereNotNull('celular')
                    ->whereRaw("REGEXP_REPLACE(celular, '[^0-9]', '', 'g') LIKE ?", ["%{$ddd}%{$final8}"])
                    ->latest('id')
                    ->first();

                if ($insc) return $insc->id;
            }
        }

        // 3. Busca por E-mail
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $insc = Inscricao::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
                ->latest('id')
                ->first();

            if ($insc) return $insc->id;
        }

        return null;
    }

    /**
     * Registra ou atualiza a flag de WhatsApp na tabela inscricao_contatos,
     * fazendo o ícone de WhatsApp na Gestão de Inscrições ficar verde quando o candidato responde.
     */
    protected function registrarInteracaoNoCrmInscricao(
        int $inscricaoId,
        string $conteudo,
        bool $houveResposta,
        string $autorNome,
        Carbon $dataContato
    ): void {
        if (!class_exists(InscricaoContato::class)) {
            return;
        }

        // Se já houver um registro de WhatsApp nas últimas 2 horas, anexa/atualiza para não poluir com dezenas de linhas
        $ultimoContatoRecente = InscricaoContato::where('inscricao_id', $inscricaoId)
            ->where('canal', 'whatsapp')
            ->where('created_at', '>=', now()->subHours(2))
            ->latest('id')
            ->first();

        if ($ultimoContatoRecente) {
            $novoConteudo = trim($ultimoContatoRecente->conteudo . "\n" . $conteudo);
            $ultimoContatoRecente->update([
                'data_contato'   => $dataContato,
                'conteudo'       => mb_substr($novoConteudo, -3500),
                'houve_resposta' => $ultimoContatoRecente->houve_resposta || $houveResposta,
                'user_nome'      => $autorNome,
            ]);
            return;
        }

        InscricaoContato::create([
            'inscricao_id'   => $inscricaoId,
            'canal'          => 'whatsapp',
            'data_contato'   => $dataContato,
            'conteudo'       => mb_substr($conteudo, 0, 3500),
            'houve_resposta' => $houveResposta,
            'user_id'        => null,
            'user_nome'      => $autorNome,
        ]);
    }

    /**
     * Converte o TimeSpan do .NET ("[d.]hh:mm:ss[.fffffff]") para total de segundos.
     * Ex: "00:16:23.1200000" -> 983 segundos | "1.02:00:00" -> 93600 segundos.
     */
    public function parseTimeSpanToSeconds(?string $timespan): int
    {
        if (empty($timespan)) {
            return 0;
        }

        $str = trim($timespan);
        $dias = 0;

        // Remove frações de segundo (.1200000) do final
        if (preg_match('/^(.*?\d{2}:\d{2}:\d{2})\.\d+$/', $str, $m)) {
            $str = $m[1];
        }

        // Verifica se há dias na frente (ex: "2.04:30:10")
        if (preg_match('/^(\d+)\.(\d{1,2}:\d{2}:\d{2})$/', $str, $m)) {
            $dias = (int) $m[1];
            $str  = $m[2];
        }

        $partes = explode(':', $str);
        if (count($partes) !== 3) {
            return 0;
        }

        $horas    = (int) $partes[0];
        $minutos  = (int) $partes[1];
        $segundos = (int) $partes[2];

        return ($dias * 86400) + ($horas * 3600) + ($minutos * 60) + $segundos;
    }

    /**
     * Busca uma chave dentro de um array suportando variações de PascalCase e camelCase.
     */
    protected function getVal(?array $source, array $keys, mixed $default = null): mixed
    {
        if (empty($source)) {
            return $default;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null) {
                return $source[$key];
            }
        }

        return $default;
    }
}