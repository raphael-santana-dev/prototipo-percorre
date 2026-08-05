<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\UI\Livewire\Login;
use App\Modules\Dashboard\UI\Livewire\Dashboard;
use App\Modules\FeatureToggle\UI\Livewire\FeatureManager;
use App\Modules\ACL\UI\Livewire\RoleManager;
use App\Modules\ACL\UI\Livewire\PermissionManager;
use App\Modules\ACL\UI\Livewire\RolePermissionManager;
use App\Modules\Corporate\UI\Livewire\UserManager;
use App\Modules\Corporate\UI\Livewire\UserExtraPermissionManager;
use App\Modules\Student\UI\Livewire\Auth\Login as StudentLogin;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;
use App\Modules\Turno\UI\Livewire\TurnoManager;
use App\Modules\Period\UI\Livewire\PeriodManager;
use App\Modules\Period\UI\Livewire\StepManager;
use App\Modules\Period\UI\Livewire\DynamicFields;

Route::get('/', \App\Modules\Website\UI\Livewire\Home::class)->name('home');
Route::get('/inscricao', \App\Modules\Website\UI\Livewire\Inscricao::class)->name('publico.inscricao');

Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/features', FeatureManager::class)->name('features.index');
    Route::get('/roles', RoleManager::class)->name('roles.index');
    Route::get('/permissions', PermissionManager::class)->name('permissions.index');
    Route::get('/roles/{roleId}/permissions', RolePermissionManager::class)->name('roles.permissions');
    Route::get('/users', UserManager::class)->name('users.index');
    Route::get('/users/{userId}/extra-permissions', UserExtraPermissionManager::class)->name('users.extra-permissions');
    Route::get('/turnos', TurnoManager::class)->name('turnos.index');
    Route::get('/unidades', \App\Modules\Unidade\UI\Livewire\UnidadeManager::class)->name('unidades.index');
    Route::get('/unidades/{id}', \App\Modules\Unidade\UI\Livewire\UnidadeDetalhes::class)->name('unidades.show');
    Route::get('/estudantes', \App\Modules\Student\UI\Livewire\StudentManager::class)->name('students.index');
    Route::get('/estudantes/{id}', \App\Modules\Student\UI\Livewire\StudentDetails::class)->name('students.show');
    Route::get('/users/{id}', \App\Modules\Corporate\UI\Livewire\UserDetails::class)->name('users.show');
    Route::get('/meu-perfil', \App\Modules\Auth\UI\Livewire\ProfileManager::class)->name('profile.show');
    Route::get('/incricoes', \App\Modules\Registration\UI\Livewire\RegistrationManager::class)->name('inscricoes.index');
    Route::get('/inscricoes/{id}', \App\Modules\Registration\UI\Livewire\RegistrationDetails::class)->name('inscricoes.show');
    Route::get('/status-inscricoes', \App\Modules\Registration\UI\Livewire\StatusManager::class)->name('status-inscricoes.index');
    Route::get('/ciclos', PeriodManager::class)->name('ciclos.index');
    Route::get('/ciclos/{id}', \App\Modules\Period\UI\Livewire\PeriodDetails::class)->name('ciclos.show');
    Route::get('/ciclos/{id}/crm', \App\Modules\Registration\UI\Livewire\KanbanBoard::class)->name('ciclos.crm');

    Route::get('/etapas', StepManager::class)->name('ciclos.etapas');
    Route::get('/cursos', \App\Modules\Curso\UI\Livewire\CursoManager::class)->name('cursos.index');
    Route::get('/cursos/{id}', \App\Modules\Curso\UI\Livewire\CursoDetalhes::class)->name('cursos.show');

    Route::get('/inscricoes/status', \App\Modules\Registration\UI\Livewire\StatusManager::class)->name('status.index');

    Route::get('/auditoria', \App\Modules\Auditoria\UI\Livewire\AuditoriaManager::class)->name('auditoria.index');

    // NOVA ROTA GLOBAL DO CONSTRUTOR DE FORMULÁRIOS:
    Route::get('/construtor/{tipo}/{id}', \App\Modules\Period\UI\Livewire\DynamicFields::class)->name('construtor.campos');

    // ROTAS DO GERENCIADOR DE FORMULÁRIOS GENÉRICOS:
    Route::get('/formularios', \App\Modules\Forms\UI\Livewire\FormManager::class)->name('formularios.index');

    // ROTA PÚBLICA PARA RESPONDER:
    Route::get('/f/{slug}', \App\Modules\Website\UI\Livewire\FormularioPublico::class)->name('formularios.publico');
    Route::get('/formularios/{id}', \App\Modules\Forms\UI\Livewire\FormDetails::class)->name('formularios.show');
    Route::get('/formularios/respostas/{id}', \App\Modules\Forms\UI\Livewire\ResponseDetails::class)->name('formularios.respostas.show');
});

// ==========================================
// ÁREA DOS ALUNOS
// ==========================================
Route::prefix('alunos')->name('student.')->group(function () {
    
    // Visitantes (não logados como alunos)
    Route::middleware('guest:student')->group(function () {
        Route::get('/login', StudentLogin::class)->name('login');
    });

    // Alunos logados
    Route::middleware('auth:student')->group(function () {
        Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
        Route::get('/meu-perfil', \App\Modules\Student\UI\Livewire\ProfileManager::class)->name('profile');
        Route::get('/biblioteca', StudentLibrary::class)
            ->name('library')
            ->middleware('feature:alunos.biblioteca');
    });
});