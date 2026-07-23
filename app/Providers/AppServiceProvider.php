<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use App\Modules\Auth\UI\Livewire\Login;

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

        // 1. Registra o componente explicitamente para o Livewire não se perder no DDD
        Livewire::component('auth.login', Login::class);

        // 2. Força a rota de atualização do Livewire a usar o middleware web de sessões
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web');
        });
    }
}
