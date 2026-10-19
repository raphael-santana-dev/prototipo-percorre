<?php

namespace App\Modules\Comunicacao\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLogEvento;
use App\Modules\Comunicacao\Services\EmailTrackingService;

class EmailTrackingController extends Controller
{
    /**
     * Captura a abertura do e-mail via Pixel 1x1 e devolve um GIF transparente
     */
    public function open(Request $request, string $token)
    {
        $log = ComunicacaoLog::where('tracking_token', $token)->first();

        if ($log) {
            $agora = now();
            $infoUa = EmailTrackingService::analisarUserAgent($request->userAgent());

            $log->increment('aberturas_count');
            $log->update([
                'aberto_em'          => $log->aberto_em ?? $agora,
                'ultima_abertura_em' => $agora,
            ]);

            if ($log->comunicado_id && $log->comunicado) {
                $log->comunicado->increment('aberturas_count');
                $log->comunicado->update([
                    'aberto_em'          => $log->comunicado->aberto_em ?? $agora,
                    'ultima_abertura_em' => $agora,
                ]);
            }

            ComunicacaoLogEvento::create([
                'comunicacao_log_id'  => $log->id,
                'comunicado_id'       => $log->comunicado_id,
                'tipo'                => 'abertura',
                'ip'                  => $request->ip(),
                'user_agent'          => $request->userAgent(),
                'dispositivo'         => $infoUa['dispositivo'],
                'navegador'           => $infoUa['navegador'],
                'sistema_operacional' => $infoUa['sistema_operacional'],
            ]);
        }

        // GIF 1x1 transparente
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Captura o clique em qualquer botão/link do e-mail e redireciona para a URL real
     */
    public function click(Request $request, string $token)
    {
        $urlDestino = base64_decode((string) $request->query('url', ''), true) ?: config('app.url');
        $textoLink  = base64_decode((string) $request->query('label', ''), true) ?: 'Botão / Link';

        if (!preg_match('/^https?:\/\//i', $urlDestino)) {
            $urlDestino = url($urlDestino);
        }

        $log = ComunicacaoLog::where('tracking_token', $token)->first();

        if ($log) {
            $agora = now();
            $infoUa = EmailTrackingService::analisarUserAgent($request->userAgent());

            $log->increment('cliques_count');
            $updateLog = [
                'clicado_em'       => $log->clicado_em ?? $agora,
                'ultimo_clique_em' => $agora,
            ];

            // Se o leitor de e-mail tinha bloqueado imagens mas o usuário clicou num link, contabiliza a abertura também
            if (!$log->aberto_em) {
                $updateLog['aberto_em'] = $agora;
                $updateLog['ultima_abertura_em'] = $agora;
                $log->increment('aberturas_count');
            }
            $log->update($updateLog);

            if ($log->comunicado_id && $log->comunicado) {
                $log->comunicado->increment('cliques_count');
                $updateCom = [
                    'clicado_em'       => $log->comunicado->clicado_em ?? $agora,
                    'ultimo_clique_em' => $agora,
                ];
                if (!$log->comunicado->aberto_em) {
                    $updateCom['aberto_em'] = $agora;
                    $updateCom['ultima_abertura_em'] = $agora;
                    $log->comunicado->increment('aberturas_count');
                }
                $log->comunicado->update($updateCom);
            }

            ComunicacaoLogEvento::create([
                'comunicacao_log_id'  => $log->id,
                'comunicado_id'       => $log->comunicado_id,
                'tipo'                => 'clique',
                'url_destino'         => $urlDestino,
                'texto_link'          => $textoLink,
                'ip'                  => $request->ip(),
                'user_agent'          => $request->userAgent(),
                'dispositivo'         => $infoUa['dispositivo'],
                'navegador'           => $infoUa['navegador'],
                'sistema_operacional' => $infoUa['sistema_operacional'],
            ]);
        }

        return redirect()->away($urlDestino);
    }
}