<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\SuriWebhookEvent;
use App\Modules\Comunicacao\Services\SuriWebhookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuriWebhookController extends Controller
{
    public function handle(Request $request, ?string $token = null): JsonResponse
    {
        // 1. Validação opcional do Token Secreto (só exige se SURI_WEBHOOK_TOKEN estiver preenchido no .env)
        $expectedToken = trim((string) config('services.suri.webhook_token', ''));
        $providedToken = trim((string) ($token ?: $request->query('token', $request->header('X-Suri-Token', ''))));

        if ($expectedToken !== '' && !hash_equals($expectedToken, $providedToken)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        // 2. Leitura do Envelope JSON enviado pela Suri
        $envelope = $request->json()->all() ?: $request->all();

        if (empty($envelope) || !isset($envelope['type'])) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'invalid_json_or_missing_type',
            ], 200);
        }

        $eventId   = (string) ($envelope['id'] ?? ('suri_' . uniqid('', true)));
        $eventType = (string) ($envelope['type'] ?? 'unknown');
        $timestamp = $envelope['timestamp'] ?? null;
        $chatbotId = str_contains($eventId, ':') ? explode(':', $eventId, 2)[0] : null;

        try {
            $parsedTimestamp = $timestamp ? Carbon::parse($timestamp) : now();
        } catch (\Throwable $e) {
            $parsedTimestamp = now();
        }

        try {
            // 3. Salva o evento bruto na tabela suri_webhook_events (Idempotente pelo event_id)
            $evento = SuriWebhookEvent::firstOrCreate(
                ['event_id' => $eventId],
                [
                    'chatbot_id'      => $chatbotId,
                    'event_type'      => $eventType,
                    'event_timestamp' => $parsedTimestamp,
                    'payload'         => $envelope,
                    'status'          => 'pending',
                ]
            );

            // 4. Processa após enviar a resposta HTTP 200 para a Suri (funciona em Local e Produção < 5s)
            if ($evento->status !== 'processed') {
                $eventoId = $evento->id;

                app()->terminating(function () use ($eventoId) {
                    $ev = SuriWebhookEvent::find($eventoId);
                    if (!$ev || $ev->status === 'processed') {
                        return;
                    }

                    try {
                        app(SuriWebhookService::class)->processarEvento($ev);
                    } catch (\Throwable $e) {
                        $ev->update([
                            'status'        => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                        Log::error('SURI_WEBHOOK_PROCESS_ERROR: ' . $e->getMessage());
                    }
                });
            }

            return response()->json([
                'status'   => 'received',
                'event_id' => $eventId,
                'type'     => $eventType,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('SURI_WEBHOOK_EMERGENCY_FALLBACK', [
                'error'    => $e->getMessage(),
                'envelope' => $envelope,
            ]);

            return response()->json([
                'status' => 'logged_fallback',
                'debug'  => config('app.debug') ? $e->getMessage() : null,
            ], 200);
        }
    }
}