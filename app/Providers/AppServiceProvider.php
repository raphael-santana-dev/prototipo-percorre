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

// Turno (DDD)
use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;
use App\Modules\Turno\Infrastructure\Persistence\EloquentTurnoRepository;
use App\Modules\Turno\UI\Livewire\TurnoManager;

// Unidade (DDD)
use App\Modules\Unidade\Domain\Repositories\UnidadeRepositoryInterface;
use App\Modules\Unidade\Infrastructure\Persistence\EloquentUnidadeRepository;

// Curso (DDD) - NOVO!
use App\Modules\Curso\Domain\Repositories\CursoRepositoryInterface;
use App\Modules\Curso\Infrastructure\Persistence\EloquentCursoRepository;

// Portal do Aluno
use App\Modules\Student\UI\Livewire\Auth\Login as StudentLogin;
use App\Modules\Student\UI\Livewire\Auth\LogoutButton as StudentLogout;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;

use App\Models\AuditoriaLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bindings do Padrão Repository (DDD)
        $this->app->bind(TurnoRepositoryInterface::class, EloquentTurnoRepository::class);
        $this->app->bind(UnidadeRepositoryInterface::class, EloquentUnidadeRepository::class);
        $this->app->bind(CursoRepositoryInterface::class, EloquentCursoRepository::class); // <- NOVO
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

        // ==========================================
        // REGISTRO DE COMPONENTES LIVEWIRE
        // ==========================================
        
        // Autenticação e Dashboard Corporativo
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.logout-button', LogoutButton::class);
        Livewire::component('dashboard.dashboard', Dashboard::class);
        Livewire::component('auth.profile-manager', \App\Modules\Auth\UI\Livewire\ProfileManager::class);

        // Feature Toggles
        Blade::if('feature', function (string $name) {
            return app(FeatureService::class)->isActive($name);
        });
        Livewire::component('feature-toggle.manager', FeatureManager::class);

        // Controle de Acesso (ACL) e Usuários Corporativos
        Livewire::component('acl.role-manager', RoleManager::class);
        Livewire::component('acl.permission-manager', PermissionManager::class);
        Livewire::component('acl.role-permission-manager', RolePermissionManager::class);
        Livewire::component('corporate.user-manager', UserManager::class);
        Livewire::component('corporate.user-extra-permission-manager', UserExtraPermissionManager::class);
        Livewire::component('corporate.user-details', \App\Modules\Corporate\UI\Livewire\UserDetails::class);

        // Turnos, Unidades e Cursos (Acadêmico)
        Livewire::component('turno.turno-manager', TurnoManager::class);
        Livewire::component('unidade.unidade-manager', \App\Modules\Unidade\UI\Livewire\UnidadeManager::class);
        Livewire::component('unidade.unidade-detalhes', \App\Modules\Unidade\UI\Livewire\UnidadeDetalhes::class);
        Livewire::component('curso.curso-manager', \App\Modules\Curso\UI\Livewire\CursoManager::class); // <- NOVO

        // Processos Seletivos, Ciclos e Status
        Livewire::component('period.period-manager', \App\Modules\Period\UI\Livewire\PeriodManager::class);
        Livewire::component('period.dynamic-fields', \App\Modules\Period\UI\Livewire\DynamicFields::class);
        Livewire::component('period.step-manager', \App\Modules\Period\UI\Livewire\StepManager::class);
        Livewire::component('period.period-details', \App\Modules\Period\UI\Livewire\PeriodDetails::class);
        Livewire::component('registration.registration-manager', \App\Modules\Registration\UI\Livewire\RegistrationManager::class);
        Livewire::component('registration.status-manager', \App\Modules\Registration\UI\Livewire\StatusManager::class); // <- NOVO
        Livewire::component('registration.registration-details', \App\Modules\Registration\UI\Livewire\RegistrationDetails::class);
        Livewire::component('registration.kanban-board', \App\Modules\Registration\UI\Livewire\KanbanBoard::class);
        Livewire::component('period.regras-manager', \App\Modules\Period\UI\Livewire\RegrasManager::class);

        // Website e Alunos
        Livewire::component('website.home', \App\Modules\Website\UI\Livewire\Home::class);
        Livewire::component('website.inscricao', \App\Modules\Website\UI\Livewire\Inscricao::class);
        Livewire::component('student.student-manager', \App\Modules\Student\UI\Livewire\StudentManager::class);
        Livewire::component('student.student-details', \App\Modules\Student\UI\Livewire\StudentDetails::class);

        // Portal do Aluno (Auth Isolada)
        Livewire::component('student.auth.login', StudentLogin::class);
        Livewire::component('student.auth.logout-button', StudentLogout::class);
        Livewire::component('student.dashboard', StudentDashboard::class);
        Livewire::component('student.library', StudentLibrary::class);
        Livewire::component('student.profile-manager', \App\Modules\Student\UI\Livewire\ProfileManager::class);

        Livewire::component('forms.form-manager', \App\Modules\Forms\UI\Livewire\FormManager::class);
        Livewire::component('forms.formulario', \App\Modules\Website\UI\Livewire\FormularioPublico::class);
        Livewire::component('forms.form-details', \App\Modules\Forms\UI\Livewire\FormDetails::class);
        Livewire::component('registration.kanban-board', \App\Modules\Registration\UI\Livewire\KanbanBoard::class);
        // Força a rota de atualização do Livewire a usar o middleware web de sessões
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web');
        });

        // Revogação Automática de Permissões Vencidas
        Event::listen(Authenticated::class, function (Authenticated $event) {
            $user = $event->user;

            $expiredPermissionIds = DB::table('model_has_permissions')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now()->toDateString())
                ->pluck('permission_id');

            if ($expiredPermissionIds->isNotEmpty()) {
                $user->permissions()->detach($expiredPermissionIds);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });

        // Escuta o evento nativo de Login do Laravel e registra a auditoria
        Event::listen(function (Login $event) {
            $usuario = $event->user;

            AuditoriaLog::create([
                'tabela_alterada' => 'sessao', // Apenas para identificar visualmente
                'registro_id' => $usuario->id,
                'acao' => 'login',
                'informacao_anterior' => null,
                'nova_informacao' => null, // Poderia ser vazio ou null
                'usuario_id' => $usuario->id,
                'usuario_nome' => $usuario->name,
                'usuario_role' => method_exists($usuario, 'getRoleNames') ? $usuario->getRoleNames()->first() : 'N/A',
                'usuario_login' => $usuario->email,
                'ip' => request()->ip(),
                'navegador' => request()->userAgent(),
            ]);
        });
    }
}