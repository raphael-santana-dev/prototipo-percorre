<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FASE 0 — medição. Somente em ambiente local.
 *
 * Registra, para cada requisição (inclusive cada /livewire/update), quantas queries
 * foram executadas, o tempo somado no banco e o componente Livewire envolvido.
 * Os registros vão para storage/logs/laravel.log com o canal de debug.
 *
 * Registro: bootstrap/app.php (Laravel 12), no grupo 'web':
 *   $middleware->web(append: [\App\Http\Middleware\ContadorDeQueries::class]);
 */
class ContadorDeQueries
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->environment('local')) {
            return $next($request);
        }

        $queries = 0;
        $tempoBancoMs = 0.0;

        DB::listen(function ($query) use (&$queries, &$tempoBancoMs) {
            $queries++;
            $tempoBancoMs += $query->time;
        });

        $inicio = microtime(true);
        $response = $next($request);
        $tempoTotalMs = (microtime(true) - $inicio) * 1000;

        Log::debug('[queries]', [
            'rota'        => $request->path(),
            'componente'  => $this->componenteLivewire($request),
            'queries'     => $queries,
            'tempo_db_ms' => round($tempoBancoMs, 1),
            'tempo_total_ms' => round($tempoTotalMs, 1),
        ]);

        return $response;
    }

    private function componenteLivewire(Request $request): ?string
    {
        $snapshot = $request->input('components.0.snapshot');
        if (!is_string($snapshot)) {
            return null;
        }

        $dados = json_decode($snapshot, true);

        return $dados['memo']['name'] ?? null;
    }
}