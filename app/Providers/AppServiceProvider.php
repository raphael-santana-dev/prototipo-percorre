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
use App\Modules\Corporate\UI\Livewire\UserManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use App\Modules\Corporate\UI\Livewire\UserExtraPermissionManager;
use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;
use App\Modules\Turno\Infrastructure\Persistence\EloquentTurnoRepository;
use App\Modules\Turno\UI\Livewire\TurnoManager;

use App\Modules\Student\UI\Livewire\Auth\Login as StudentLogin;
use App\Modules\Student\UI\Livewire\Auth\LogoutButton as StudentLogout;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TurnoRepositoryInterface::class, EloquentTurnoRepository::class);
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
        Livewire::component('corporate.user-manager', UserManager::class);
        Livewire::component('corporate.user-extra-permission-manager', UserExtraPermissionManager::class);
        Livewire::component('turno.turno-manager', TurnoManager::class);

        // Força a rota de atualização do Livewire a usar o middleware web de sessões
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web');
        });

        Livewire::component('student.auth.login', StudentLogin::class);
        Livewire::component('student.auth.logout-button', StudentLogout::class);
        Livewire::component('student.dashboard', StudentDashboard::class);
        Livewire::component('student.library', StudentLibrary::class);
        

        // Revogação Automática de Permissões Vencidas
        Event::listen(Authenticated::class, function (Authenticated $event) {
            $user = $event->user;

            // Busca IDs de permissões diretas que passaram da data de validade
            $expiredPermissionIds = DB::table('model_has_permissions')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now()->toDateString())
                ->pluck('permission_id');

            if ($expiredPermissionIds->isNotEmpty()) {
                // Remove as permissões vencidas da tabela pivô
                $user->permissions()->detach($expiredPermissionIds);
                
                // Limpa o cache do Spatie para forçar a leitura correta
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });
    }
}
