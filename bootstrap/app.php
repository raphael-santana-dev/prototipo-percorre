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

        $middleware->alias([
            'feature' => CheckFeatureToggle::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // Registo automático de *absolutamente qualquer* exceção ou erro do sistema
        $exceptions->reportable(function (\Throwable $e) {
            try {
                $request = request();
                $isConsole = app()->runningInConsole();
                
                // Define o código HTTP correto dependendo da exceção (ex: 404 para NotFound, 422 para Validation, etc.)
                $httpCode = 500;
                if (method_exists($e, 'getStatusCode')) {
                    $httpCode = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $httpCode = 422;
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $httpCode = 401;
                }

                \App\Models\ErrorLog::create([
                    'tipo' => $isConsole ? 'Background Job / CLI' : 'Requisição HTTP',
                    'http_code' => $isConsole ? null : $httpCode,
                    'mensagem' => $e->getMessage() ?: class_basename($e),
                    'arquivo' => $e->getFile(),
                    'linha' => $e->getLine(),
                    'url' => $isConsole ? null : $request->fullUrl(),
                    'metodo_http' => $isConsole ? null : $request->method(),
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'stack_trace' => $e->getTraceAsString(),
                ]);
            } catch (\Throwable $loggingException) {
                // Falha silenciosa para evitar loops infinitos caso a base de dados caia
            }
        });

        // 2. PROTEÇÃO DE DADOS: Intercepta erros graves para não vazar a infraestrutura em produção
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('production')) {
                
                // Retorno seguro para requisições assíncronas (Livewire e API)
                if ($request->wantsJson() || $request->header('X-Livewire')) {
                    return response()->json([
                        'message' => 'Ocorreu um erro interno no servidor. Nossa equipa técnica já foi notificada.'
                    ], 500);
                }

                // Retorno seguro para navegação Web normal (mantém páginas 404 nativas se existirem)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return null; // Deixa o Laravel lidar com o 404 normalmente na view padrão
                }

                return response()->view('errors.500', [], 500);
            }
        });
        
    })->create();