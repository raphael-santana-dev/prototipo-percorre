<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Modules\FeatureToggle\UI\Http\Middleware\CheckFeatureToggle;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // 1. RATE LIMIT E PROXY: Configuração para o GCP Load Balancer
        $middleware->trustProxies(at: [
            '130.211.0.0/22',
            '35.191.0.0/16',
        ]);

        // (Opcional: Se a VPS for 100% fechada ao Load Balancer, você pode usar '*')
        // $middleware->trustProxies(at: '*');

        $middleware->alias([
            'feature' => CheckFeatureToggle::class,
            // Se precisar dos aliases do Spatie Permission futuramente, insira aqui
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // 2. PROTEÇÃO DE DADOS: Intercepta erros para não vazar a infraestrutura
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('production')) {
                
                // Retorno seguro para requisições assíncronas (Livewire e API)
                if ($request->wantsJson() || $request->header('X-Livewire')) {
                    return response()->json([
                        'message' => 'Ocorreu um erro interno no servidor. Nossa equipe técnica já foi notificada.'
                    ], 500);
                }

                // Retorno seguro para navegação Web normal
                return response()->view('errors.500', [], 500);
            }
        });
        
    })->create();