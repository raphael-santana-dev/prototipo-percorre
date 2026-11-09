<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessarSuriWebhookJob;
use App\Models\SuriWebhookEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuriWebhookController extends Controller
{
    public function handle(Request $request, ?string $token = null): JsonResponse
    {
        // 1. Validação do Token Secreto na URL (Path ou Query String)
        $expectedToken = (string) config('services.suri.webhook_token', '');
        $providedToken = (string) ($token ?: $request->query('token', $request->header('X-Suri-Token', '')));

        if ($expectedToken !== '' && !hash_equals($expectedToken, $providedToken)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        // 2. Leitura do Envelope Padrão da Suri
        $envelope = $request->json()->all() ?: $request->all();

        $eventId   = (string) ($envelope['id'] ?? ('suri_' . uniqid('', true)));
        $eventType = (string) ($envelope['type'] ?? 'unknown');
        $timestamp = $envelope['timestamp'] ?? null;

        if (empty($envelope) || !isset($envelope['type'])) {
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_envelope'], 200);
        }

        $chatbotId = str_contains($eventId, ':') ? explode(':', $eventId, 2)[0] : null;

        try {
            $parsedTimestamp = $timestamp ? Carbon::parse($timestamp) : now();
        } catch (\Throwable $e) {
            $parsedTimestamp = now();
        }

        try {
            // 3. Persistência Rápida com Idempotência pelo event_id ({chatbotId}:{guid})
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

            // 4. Despacho Assíncrono (Responde em < 50ms para a Suri e processa após fechar a conexão HTTP)
            if ($evento->status !== 'processed') {
                if (config('queue.default') !== 'sync' && config('services.suri.webhook_async', true)) {
                    ProcessarSuriWebhookJob::dispatch($evento->id);
                } else {
                    ProcessarSuriWebhookJob::dispatchAfterResponse($evento->id);
                }
            }

            return response()->json([
                'status'   => 'received',
                'event_id' => $eventId,
                'type'     => $eventType,
            ], 200);

        } catch (\Throwable $e) {
            // Como a Suri não possui retry, gravamos o payload bruto em log caso o banco oscile
            Log::error('SURI_WEBHOOK_EMERGENCY_FALLBACK', [
                'error'    => $e->getMessage(),
                'envelope' => $envelope,
            ]);

            return response()->json(['status' => 'logged_fallback'], 200);
        }
    }
}