<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Intercepta qualquer checagem de permissão (ex: @can, $user->can())
        Gate::before(function ($user, $ability) {
            // Se o usuário possuir a Role 'DEV', ele passa em qualquer teste de permissão
            return $user->hasRole('dev') ? true : null;
        });
    }
}
