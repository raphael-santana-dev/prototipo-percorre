<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = null;

        // Descobre qual sessão está ativa
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        } elseif (Auth::guard('student')->check()) {
            $user = Auth::guard('student')->user();
        } elseif (Auth::guard('company')->check()) {
            $user = Auth::guard('company')->user();
        }

        // Se o usuário está logado e precisa trocar a senha
        if ($user && $user->must_change_password) {
            
            // Verifica se ele já NÃO ESTÁ nas rotas permitidas (Troca de senha ou Logout)
            // Isso evita um loop infinito de redirecionamentos!
            if (!$request->routeIs('password.force-change') && 
                !$request->routeIs('logout') && 
                !$request->routeIs('portal.logout') &&
                !$request->routeIs('livewire.update')) { // Permite requisições internas do Livewire
                
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}