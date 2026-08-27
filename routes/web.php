<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// IMPORTAÇÕES
// ==========================================
use App\Modules\Auth\UI\Livewire\Login;
use App\Modules\Dashboard\UI\Livewire\Dashboard;
use App\Modules\FeatureToggle\UI\Livewire\FeatureManager;
use App\Modules\ACL\UI\Livewire\RoleManager;
use App\Modules\ACL\UI\Livewire\PermissionManager;
use App\Modules\ACL\UI\Livewire\RolePermissionManager;
use App\Modules\Corporate\UI\Livewire\UserManager;
use App\Modules\Corporate\UI\Livewire\UserExtraPermissionManager;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;
use App\Modules\Turno\UI\Livewire\TurnoManager;
use App\Modules\Period\UI\Livewire\PeriodManager;
use App\Modules\FormBuilder\UI\Livewire\Hub as FormBuilderHub;
use App\Modules\FormBuilder\UI\Livewire\DynamicFields;

// ==========================================
// 1. ROTAS PÚBLICAS E RECUPERAÇÃO DE SENHA
// ==========================================
Route::get('/', \App\Modules\Website\UI\Livewire\Home::class)->name('home');
Route::get('/inscricao', \App\Modules\Website\UI\Livewire\Inscricao::class)->name('publico.inscricao');

// Respostas de Formulários Públicos
Route::get('/f/{slug}', \App\Modules\Website\UI\Livewire\FormularioPublico::class)
    ->name('formularios.publico');

// Rotas de Redefinição de Senha (Precisam estar no escopo global de nomes)
Route::middleware('guest:student,company,web')->group(function () {
    Route::get('/redefinir-senha/{token}', \App\Modules\Portal\UI\Livewire\Auth\ResetPassword::class)->name('password.reset');
});

// ==========================================
// 2. PORTAL UNIFICADO E LOGIN (Visitantes)
// ==========================================
// Login do Administrador
Route::get('/login', \App\Modules\Auth\UI\Livewire\Login::class)
    ->name('login')
    ->middleware('guest:web,student,company');

// Portal Externo (Alunos e Empresas)
Route::prefix('portal')->name('portal.')->middleware('guest:student,company')->group(function () {
    Route::get('/esqueci-senha', \App\Modules\Portal\UI\Livewire\Auth\ForgotPassword::class)->name('password.request');
});

// ==========================================
// 3. SEGURANÇA GLOBAL (Usuários Logados)
// ==========================================
Route::middleware('auth:web,student,company')->group(function () {
    Route::get('/seguranca/atualizar-senha', \App\Modules\Portal\UI\Livewire\Auth\ForcePasswordChange::class)
        ->name('password.force-change');
});

// ==========================================
// 4. PAINEL ADMINISTRATIVO (Auth: Web)
// ==========================================
Route::middleware('auth')->group(function () {
    
    // --- Dashboard e Perfil ---
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/meu-perfil', \App\Modules\Auth\UI\Livewire\ProfileManager::class)->name('profile.show');

    // --- Configurações e ACL ---
    Route::get('/features', FeatureManager::class)->name('features.index');
    Route::get('/roles', RoleManager::class)->name('roles.index');
    Route::get('/permissions', PermissionManager::class)->name('permissions.index');
    Route::get('/roles/{roleId}/permissions', RolePermissionManager::class)->name('roles.permissions')->where('roleId', '[0-9]+');
    
    // --- Gestão de Usuários Corporativos ---
    Route::get('/users', UserManager::class)->name('users.index');
    Route::get('/users/{userId}/extra-permissions', UserExtraPermissionManager::class)->name('users.extra-permissions')->where('userId', '[0-9]+');
    Route::get('/users/{id}/{slug?}', \App\Modules\Corporate\UI\Livewire\UserDetails::class)->name('users.show')->where('id', '[0-9]+');

    // --- Gestão de Estudantes ---
    Route::get('/estudantes', \App\Modules\Student\UI\Livewire\StudentManager::class)->name('students.index');
    Route::get('/estudantes/{id}/{slug?}', \App\Modules\Student\UI\Livewire\StudentDetails::class)->name('students.show')->where('id', '[0-9]+');

    // --- Cadastros Base (Instituição) ---
    Route::get('/turnos', TurnoManager::class)->name('turnos.index');
    Route::get('/unidades', \App\Modules\Unidade\UI\Livewire\UnidadeManager::class)->name('unidades.index');
    Route::get('/unidades/{id}/{slug?}', \App\Modules\Unidade\UI\Livewire\UnidadeDetalhes::class)->name('unidades.show')->where('id', '[0-9]+');
    Route::get('/cursos', \App\Modules\Curso\UI\Livewire\CursoManager::class)->name('cursos.index');
    Route::get('/cursos/{id}/{slug?}', \App\Modules\Curso\UI\Livewire\CursoDetalhes::class)->name('cursos.show')->where('id', '[0-9]+');

    // --- Empresas Parceiras (Integração) ---
    Route::get('/empresas', \App\Modules\Company\UI\Livewire\EmpresaManager::class)->name('empresas.index');
    Route::get('/empresas/{id}', \App\Modules\Company\UI\Livewire\EmpresaDetalhes::class)->name('empresas.show')->where('id', '[0-9]+');

    // --- Inscrições, Ciclos e CRM ---
    Route::get('/inscricoes', \App\Modules\Registration\UI\Livewire\RegistrationManager::class)->name('inscricoes.index');
    Route::get('/inscricoes/status', \App\Modules\Registration\UI\Livewire\StatusManager::class)->name('status-inscricoes.index'); 
    Route::get('/inscricoes/{id}/{slug?}', \App\Modules\Registration\UI\Livewire\RegistrationDetails::class)->name('inscricoes.show')->where('id', '[0-9]+');
    Route::get('/ciclos', PeriodManager::class)->name('ciclos.index');
    Route::get('/ciclos/crm/{id}/{slug?}', \App\Modules\Registration\UI\Livewire\KanbanBoard::class)->name('ciclos.crm')->where('id', '[0-9]+');
    Route::get('/ciclos/regras/{id}/{slug?}', \App\Modules\Period\UI\Livewire\RegrasManager::class)->name('ciclos.regras')->where('id', '[0-9]+');
    Route::get('/ciclos/{id}/editar', \App\Modules\Period\UI\Livewire\PeriodEdit::class)->name('ciclos.edit');
    Route::get('/ciclos/{id}/{slug?}', \App\Modules\Period\UI\Livewire\PeriodDetails::class)->name('ciclos.show')->where('id', '[0-9]+');
        
    // --- Formulários Dinâmicos ---
    Route::get('/formularios', \App\Modules\Forms\UI\Livewire\FormManager::class)->name('formularios.index');
    Route::get('/formularios/respostas/{id}/{slug?}', \App\Modules\Forms\UI\Livewire\ResponseDetails::class)->name('formularios.respostas.show')->where('id', '[0-9]+');
    Route::get('/formularios/{id}/{slug?}', \App\Modules\Forms\UI\Livewire\FormDetails::class)->name('formularios.show')->where('id', '[0-9]+');
    Route::get('/construtor/{tipo}/{id}/{slug?}', DynamicFields::class)->name('construtor.campos')->where('id', '[0-9]+');
    Route::get('/form-builder', FormBuilderHub::class)->name('formbuilder.hub');
    Route::get('/formularios/novo', \App\Modules\Forms\UI\Livewire\FormEdit::class)->name('formularios.create'); // NOVA ROTA
    Route::get('/formularios/{id}/editar', \App\Modules\Forms\UI\Livewire\FormEdit::class)->name('formularios.edit'); // NOVA ROTA
    
    // --- Comunicação e Automações ---
    Route::get('/templates', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateManager::class)->name('templates.index');
    Route::get('/templates/create', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateForm::class)->name('templates.create');
    Route::get('/templates/{id}/edit', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateForm::class)->name('templates.edit');
    Route::get('/comunicados', \App\Modules\Comunicacao\UI\Livewire\Comunicado\ComunicadoManager::class)->name('comunicados.index');
    Route::get('/comunicados/create', \App\Modules\Comunicacao\UI\Livewire\Comunicado\ComunicadoForm::class)->name('comunicados.create');
    Route::get('/automacoes', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoManager::class)->name('automacoes.index');
    Route::get('/automacoes/create', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoForm::class)->name('automacoes.create');
    Route::get('/automacoes/{id}', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoDetails::class)->name('automacoes.show');
    Route::get('/automacoes/{id}/edit', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoForm::class)->name('automacoes.edit');
    Route::get('/monitor-emails', \App\Modules\Comunicacao\UI\Livewire\EmailLog\EmailLogManager::class)->name('monitor.emails');

    // --- Gestão Educacional (Matrículas, Turmas e Avaliações) ---
    Route::get('/avaliacoes/gerador', \App\Modules\GestaoEducacional\UI\Livewire\GeradorMock::class)->name('avaliacoes.gerador');
    Route::get('/avaliacoes/relatorios', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Relatorios::class)->name('avaliacoes.relatorios');
    Route::get('/avaliacoes/periodos', \App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao\Listagem::class)->name('avaliacoes.periodos.index');
    Route::get('/avaliacoes/periodos/novo', \App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao\Detalhes::class)->name('avaliacoes.periodos.create');
    Route::get('/avaliacoes/periodos/{id}/editar', \App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao\Detalhes::class)->name('avaliacoes.periodos.edit');
    Route::get('/matriculas', \App\Modules\GestaoEducacional\UI\Livewire\Matricula\Listagem::class)->name('matriculas.index');
    Route::get('/matriculas/nova', \App\Modules\GestaoEducacional\UI\Livewire\Matricula\Detalhes::class)->name('matriculas.create');
    Route::get('/matriculas/{id}/editar', \App\Modules\GestaoEducacional\UI\Livewire\Matricula\Detalhes::class)->name('matriculas.edit');
    Route::get('/turmas', \App\Modules\GestaoEducacional\UI\Livewire\Turma\Listagem::class)->name('turmas.index');
    Route::get('/turmas/nova', \App\Modules\GestaoEducacional\UI\Livewire\Turma\Detalhes::class)->name('turmas.create');
    Route::get('/turmas/{id}/editar', \App\Modules\GestaoEducacional\UI\Livewire\Turma\Detalhes::class)->name('turmas.edit');

    // --- Sistema, Auditoria e Importações ---
    Route::get('/auditoria', \App\Modules\Auditoria\UI\Livewire\AuditoriaManager::class)->name('auditoria.index');
    Route::get('/importacoes', \App\Modules\Importacao\UI\Livewire\ImportacaoManager::class)->name('importacoes.index');

    
});

// ==========================================
// 5. ROTAS COMPARTILHADAS (Admin e Estudante)
// ==========================================
Route::middleware('auth:web,student')->group(function () {
    Route::get('/dev/avaliacoes', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Listagem::class)->name('avaliacoes.index');
    Route::get('/dev/avaliacoes/{periodo}/{turma}/{student}/responder', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Responder::class)->name('avaliacoes.responder');
    Route::get('/avaliacoes/{periodo}/{turma}/{student}/pdf', [\App\Modules\GestaoEducacional\Http\Controllers\AvaliacaoPdfController::class, 'exportar'])->name('avaliacoes.pdf');
});

// ==========================================
// 6. ÁREA EXTERNA: EMPRESAS (Auth: Company)
// ==========================================
Route::prefix('empresa')->name('company.')->middleware('auth:company')->group(function () {
    Route::get('/dashboard', \App\Modules\Company\UI\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/avaliadores', \App\Modules\Company\UI\Livewire\GestoresManager::class)->name('gestores');
    Route::get('/aprendizes', \App\Modules\Company\UI\Livewire\AprendizesManager::class)->name('aprendizes');
});

// ==========================================
// 7. ÁREA EXTERNA: ALUNOS (Auth: Student)
// ==========================================
Route::prefix('alunos')->name('student.')->middleware('auth:student')->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::get('/meu-perfil', \App\Modules\Student\UI\Livewire\ProfileManager::class)->name('profile');
    Route::get('/biblioteca', StudentLibrary::class)->name('library')->middleware('feature:alunos.biblioteca');
});