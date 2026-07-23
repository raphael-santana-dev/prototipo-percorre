<?php

namespace App\Modules\FeatureToggle\UI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\FeatureToggle\Application\Services\FeatureService;

class CheckFeatureToggle
{
    /**
     * Intercepta a requisição e verifica se a feature exigida está ativa.
     */
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        $featureService = app(FeatureService::class);

        if (!$featureService->isActive($featureName)) {
            // Retornamos 404 (Not Found) em vez de 403 (Forbidden) para que 
            // o usuário sequer saiba que a URL existe quando a feature estiver desligada.
            abort(404, 'Funcionalidade não encontrada ou indisponível no momento.');
        }

        return $next($request);
    }
}