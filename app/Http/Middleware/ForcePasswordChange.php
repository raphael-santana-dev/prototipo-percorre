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

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        } elseif (Auth::guard('student')->check()) {
            $user = Auth::guard('student')->user();
        } elseif (Auth::guard('company')->check()) {
            $user = Auth::guard('company')->user();
        }

        if ($user && $user->must_change_password) {
            
            if (!$request->routeIs('password.force-change') && 
                !$request->routeIs('logout') && 
                !$request->routeIs('portal.logout') &&
                !$request->routeIs('livewire.update')) {
                
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}