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

// ==========================================
// ROTAS PÚBLICAS
// ==========================================
Route::get('/', \App\Modules\Website\UI\Livewire\Home::class)->name('home');
Route::get('/inscricao', \App\Modules\Website\UI\Livewire\Inscricao::class)->name('publico.inscricao');
Route::get('/login', Login::class)->name('login')->middleware('guest');

// Rota pública de respostas de formulários
Route::get('/f/{id}/{slug?}', \App\Modules\Website\UI\Livewire\FormularioPublico::class)
    ->name('formularios.publico')
    ->where('id', '[0-9]+');

// ==========================================
// PAINEL ADMINISTRATIVO (Autenticado)
// ==========================================
Route::middleware('auth')->group(function () {
    
    // --- 1. Dashboard e Perfil ---
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/meu-perfil', \App\Modules\Auth\UI\Livewire\ProfileManager::class)->name('profile.show');

    // --- 2. Configurações e ACL ---
    Route::get('/features', FeatureManager::class)->name('features.index');
    Route::get('/roles', RoleManager::class)->name('roles.index');
    Route::get('/permissions', PermissionManager::class)->name('permissions.index');
    Route::get('/roles/{roleId}/permissions', RolePermissionManager::class)
        ->name('roles.permissions')
        ->where('roleId', '[0-9]+');
    
    // --- 3. Usuários Corporativos ---
    Route::get('/users', UserManager::class)->name('users.index');
    Route::get('/users/{userId}/extra-permissions', UserExtraPermissionManager::class)
        ->name('users.extra-permissions')
        ->where('userId', '[0-9]+');
    Route::get('/users/{id}/{slug?}', \App\Modules\Corporate\UI\Livewire\UserDetails::class)
        ->name('users.show')
        ->where('id', '[0-9]+');

    // --- 4. Estudantes ---
    Route::get('/estudantes', \App\Modules\Student\UI\Livewire\StudentManager::class)->name('students.index');
    Route::get('/estudantes/{id}/{slug?}', \App\Modules\Student\UI\Livewire\StudentDetails::class)
        ->name('students.show')
        ->where('id', '[0-9]+');

    // --- 5. Cadastros Base (Unidades, Turnos e Cursos) ---
    Route::get('/turnos', TurnoManager::class)->name('turnos.index');
    Route::get('/unidades', \App\Modules\Unidade\UI\Livewire\UnidadeManager::class)->name('unidades.index');
    Route::get('/unidades/{id}/{slug?}', \App\Modules\Unidade\UI\Livewire\UnidadeDetalhes::class)
        ->name('unidades.show')
        ->where('id', '[0-9]+');
    Route::get('/cursos', \App\Modules\Curso\UI\Livewire\CursoManager::class)->name('cursos.index');
    Route::get('/cursos/{id}/{slug?}', \App\Modules\Curso\UI\Livewire\CursoDetalhes::class)
        ->name('cursos.show')
        ->where('id', '[0-9]+');

    // --- 6. Inscrições e CRM ---
    Route::get('/inscricoes', \App\Modules\Registration\UI\Livewire\RegistrationManager::class)->name('inscricoes.index'); // Corrigido erro de digitação ("incricoes")
    Route::get('/inscricoes/status', \App\Modules\Registration\UI\Livewire\StatusManager::class)->name('status-inscricoes.index'); 
    Route::get('/inscricoes/{id}/{slug?}', \App\Modules\Registration\UI\Livewire\RegistrationDetails::class)
        ->name('inscricoes.show')
        ->where('id', '[0-9]+');

    // --- 7. Ciclos (Semestres e Regras) ---
    Route::get('/ciclos', PeriodManager::class)->name('ciclos.index');
    Route::get('/ciclos/crm/{id}/{slug?}', \App\Modules\Registration\UI\Livewire\KanbanBoard::class)
        ->name('ciclos.crm')
        ->where('id', '[0-9]+');
    Route::get('/ciclos/regras/{id}/{slug?}', \App\Modules\Period\UI\Livewire\RegrasManager::class)
        ->name('ciclos.regras')
        ->where('id', '[0-9]+');
    Route::get('/etapas', StepManager::class)->name('ciclos.etapas'); 
    Route::get('/ciclos/{id}/{slug?}', \App\Modules\Period\UI\Livewire\PeriodDetails::class)
        ->name('ciclos.show')
        ->where('id', '[0-9]+');

    // --- 8. Formulários Dinâmicos ---
    Route::get('/formularios', \App\Modules\Forms\UI\Livewire\FormManager::class)->name('formularios.index');
    Route::get('/formularios/respostas/{id}/{slug?}', \App\Modules\Forms\UI\Livewire\ResponseDetails::class)
        ->name('formularios.respostas.show')
        ->where('id', '[0-9]+');
    Route::get('/formularios/{id}/{slug?}', \App\Modules\Forms\UI\Livewire\FormDetails::class)
        ->name('formularios.show')
        ->where('id', '[0-9]+');

    // --- 9. Construtor de Campos Dinâmicos ---
    Route::get('/construtor/{tipo}/{id}/{slug?}', \App\Modules\Period\UI\Livewire\DynamicFields::class)
        ->name('construtor.campos')
        ->where('id', '[0-9]+');

    // --- 10. Auditoria e Logs ---
    Route::get('/auditoria', \App\Modules\Auditoria\UI\Livewire\AuditoriaManager::class)->name('auditoria.index');
    
    // --- 11. Importações/Exportações ---
    Route::get('/importacoes', \App\Modules\Importacao\UI\Livewire\ImportacaoManager::class)->name('importacoes.index');

    // --- 12. Comunicação (Templates e Automação) ---
    Route::get('/templates', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateManager::class)->name('templates.index');
    Route::get('/templates/create', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateForm::class)->name('templates.create');
    Route::get('/templates/{id}/edit', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateForm::class)->name('templates.edit');
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