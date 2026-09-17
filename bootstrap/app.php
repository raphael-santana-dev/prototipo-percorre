<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Modules\FeatureToggle\UI\Http\Middleware\CheckFeatureToggle;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'feature' => CheckFeatureToggle::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
       $exceptions->render(function (\Throwable $e, $request) {
            // Verifica se a aplicação está no ambiente de produção
            if (app()->environment('production')) {
                
                // Tratamento seguro para requisições do Livewire ou APIs
                if ($request->wantsJson() || $request->header('X-Livewire')) {
                    return response()->json([
                        'message' => 'Ocorreu um erro interno no servidor. Nossa equipe já foi notificada.'
                    ], 500);
                }

                // Tratamento para navegação web normal (retorna uma view genérica)
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
