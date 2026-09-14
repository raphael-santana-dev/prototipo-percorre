<?php

namespace App\Modules\FeatureToggle\UI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\FeatureToggle\Application\Services\FeatureService;

class CheckFeatureToggle
{
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        $featureService = app(FeatureService::class);

        if (!$featureService->isActive($featureName)) {
            abort(404, 'Funcionalidade não encontrada ou indisponível no momento.');
        }

        return $next($request);
    }
}