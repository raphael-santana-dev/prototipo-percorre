<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use App\Modules\Auth\UI\Livewire\Login;
use App\Modules\Dashboard\UI\Livewire\Dashboard;
use App\Modules\Auth\UI\Livewire\LogoutButton;
use App\Modules\FeatureToggle\Application\Services\FeatureService;
use App\Modules\FeatureToggle\UI\Livewire\FeatureManager;
use App\Modules\ACL\UI\Livewire\RoleManager;
use App\Modules\ACL\UI\Livewire\PermissionManager;
use App\Modules\ACL\UI\Livewire\RolePermissionManager;

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

        // Registra o componente explicitamente para o Livewire não se perder no DDD
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.logout-button', LogoutButton::class);
        Livewire::component('dashboard.dashboard', Dashboard::class);

        Blade::if('feature', function (string $name) {
            return app(FeatureService::class)->isActive($name);
        });

        Livewire::component('feature-toggle.manager', FeatureManager::class);
        Livewire::component('acl.role-manager', RoleManager::class);

        Livewire::component('acl.permission-manager', PermissionManager::class);
        Livewire::component('acl.role-permission-manager', RolePermissionManager::class);

        // Força a rota de atualização do Livewire a usar o middleware web de sessões
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web');
        });
    }
}
