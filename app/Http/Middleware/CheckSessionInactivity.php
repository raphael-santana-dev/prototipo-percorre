<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionInactivity
{
    /**
     * Guards monitorados pelo controle de inatividade.
     */
    protected array $guards = ['web', 'student', 'company'];

    /**
     * Métodos de polling automático do Livewire que NÃO contam como atividade humana.
     */
    protected array $passiveLivewireMethods = [
        '__poll',
        'pingLock',
        'monitorarProgresso',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $activeGuard = $this->getAuthenticatedGuard();

        // Se não houver usuário autenticado em nenhum guard, segue normalmente
        if (!$activeGuard) {
            return $next($request);
        }

        // Se for a própria rota de logout por inatividade, permite passar para deslogar
        if ($request->routeIs('session.timeout-logout')) {
            return $next($request);
        }

        $maxIdleSeconds = max(60, ((int) config('session.lifetime', 120)) * 60);
        $now = now()->timestamp;
        $lastActivity = (int) $request->session()->get('last_human_activity', $now);

        // 1. Verifica se o tempo de inatividade já estourou no Servidor
        if (($now - $lastActivity) >= $maxIdleSeconds) {
            return $this->forceLogout($request, $activeGuard);
        }

        // 2. Só renova o timestamp se NÃO for uma requisição automática de background (wire:poll)
        if (!$this->isPassiveBackgroundRequest($request)) {
            $request->session()->put('last_human_activity', $now);
        }

        return $next($request);
    }

    /**
     * Identifica qual Guard está autenticado na requisição atual.
     */
    protected function getAuthenticatedGuard(): ?string
    {
        foreach ($this->guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Detecta se a requisição é apenas um wire:poll automático do Livewire sem ação humana.
     */
    protected function isPassiveBackgroundRequest(Request $request): bool
    {
        if (!$request->hasHeader('X-Livewire') && !$request->is('livewire/update')) {
            return false;
        }

        $components = $request->input('components', []);
        if (empty($components) || !is_array($components)) {
            return false;
        }

        foreach ($components as $comp) {
            $calls   = $comp['calls'] ?? [];
            $updates = $comp['updates'] ?? [];

            // Se o usuário digitou ou alterou algum input (wire:model), é atividade humana
            if (!empty($updates)) {
                return false;
            }

            // Se há chamadas de métodos, verifica se alguma delas é ação humana
            foreach ($calls as $call) {
                $method = $call['method'] ?? '';
                if ($method !== '' && !in_array($method, $this->passiveLivewireMethods, true)) {
                    return false; // Chamou método real (ex: salvar, filtrar, abrirModal)
                }
            }
        }

        // Se chegou aqui, é um wire:poll puro (sem calls/updates) ou apenas pingLock/monitorarProgresso
        return true;
    }

    /**
     * Destrói a sessão de forma segura no servidor e redireciona com aviso.
     */
    protected function forceLogout(Request $request, string $guard): Response
    {
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginRoute = in_array($guard, ['student', 'company'], true) && \Illuminate\Support\Facades\Route::has('portal.login')
            ? route('portal.login', ['expired' => 1])
            : (route('login', ['expired' => 1], false) ?: '/login?expired=1');

        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return response()->json([
                'message'  => 'Sua sessão expirou por inatividade.',
                'redirect' => $loginRoute,
            ], 419);
        }

        return redirect()->to($loginRoute)
            ->with('erro', 'Sua sessão foi encerrada automaticamente por inatividade.');
    }
}