<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use App\Modules\Auth\UI\Livewire\Login;
use Illuminate\Auth\Events\Login as UserLogin;
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

use App\Modules\Report\UI\Livewire\ChartWidget;

use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;
use App\Modules\Turno\Infrastructure\Persistence\EloquentTurnoRepository;
use App\Modules\Turno\UI\Livewire\TurnoManager;

use App\Modules\Unidade\Domain\Repositories\UnidadeRepositoryInterface;
use App\Modules\Unidade\Infrastructure\Persistence\EloquentUnidadeRepository;

use App\Modules\Curso\Domain\Repositories\CursoRepositoryInterface;
use App\Modules\Curso\Infrastructure\Persistence\EloquentCursoRepository;

use App\Modules\Portal\UI\Livewire\Auth\LogoutButton as PortalLogout;
use App\Modules\Student\UI\Livewire\Dashboard\Dashboard as StudentDashboard;
use App\Modules\Student\UI\Livewire\Dashboard\Library as StudentLibrary;

use App\Models\AuditoriaLog;
use Illuminate\Auth\Events\Logout;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->bind(TurnoRepositoryInterface::class, EloquentTurnoRepository::class);
        $this->app->bind(UnidadeRepositoryInterface::class, EloquentUnidadeRepository::class);
        $this->app->bind(CursoRepositoryInterface::class, EloquentCursoRepository::class);
    }

    public function boot(): void
    {

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('dev') ? true : null;
        });

        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.logout-button', LogoutButton::class);
        Livewire::component('dashboard.dashboard', Dashboard::class);
        Livewire::component('auth.profile-manager', \App\Modules\Auth\UI\Livewire\ProfileManager::class);
        

        Blade::if('feature', function (string $name) {
            return app(FeatureService::class)->isActive($name);
        });
        Livewire::component('feature-toggle.manager', FeatureManager::class);

        Livewire::component('acl.role-manager', RoleManager::class);
        Livewire::component('acl.permission-manager', PermissionManager::class);
        Livewire::component('acl.role-permission-manager', RolePermissionManager::class);
        Livewire::component('corporate.user-manager', UserManager::class);
        Livewire::component('corporate.user-extra-permission-manager', UserExtraPermissionManager::class);
        Livewire::component('corporate.user-details', \App\Modules\Corporate\UI\Livewire\UserDetails::class);

        Livewire::component('turno.turno-manager', TurnoManager::class);
        Livewire::component('unidade.unidade-manager', \App\Modules\Unidade\UI\Livewire\UnidadeManager::class);
        Livewire::component('unidade.unidade-detalhes', \App\Modules\Unidade\UI\Livewire\UnidadeDetalhes::class);
        Livewire::component('curso.curso-manager', \App\Modules\Curso\UI\Livewire\CursoManager::class); 

        Livewire::component('period.period-manager', \App\Modules\Period\UI\Livewire\PeriodManager::class);
        Livewire::component('period.dynamic-fields', \App\Modules\FormBuilder\UI\Livewire\DynamicFields::class);
        Livewire::component('period.period-details', \App\Modules\Period\UI\Livewire\PeriodDetails::class);
        Livewire::component('period.period-edit', \App\Modules\Period\UI\Livewire\PeriodEdit::class);
        Livewire::component('period.regras-manager', \App\Modules\Period\UI\Livewire\RegrasManager::class);

        Livewire::component('registration.registration-manager', \App\Modules\Registration\UI\Livewire\RegistrationManager::class);
        Livewire::component('registration.status-manager', \App\Modules\Registration\UI\Livewire\StatusManager::class); 
        Livewire::component('registration.registration-details', \App\Modules\Registration\UI\Livewire\RegistrationDetails::class);
        Livewire::component('registration.kanban-board', \App\Modules\Registration\UI\Livewire\KanbanBoard::class);
        
        Livewire::component('website.home', \App\Modules\Website\UI\Livewire\Home::class);
        Livewire::component('website.inscricao', \App\Modules\Website\UI\Livewire\Inscricao::class);
        Livewire::component('student.student-manager', \App\Modules\Student\UI\Livewire\StudentManager::class);
        Livewire::component('student.student-details', \App\Modules\Student\UI\Livewire\StudentDetails::class);
        Livewire::component('inscricao.retomada', \App\Modules\Registration\UI\Livewire\RetomarInscricao::class);

        Livewire::component('portal.auth.logout-button', PortalLogout::class);
        Livewire::component('student.dashboard', StudentDashboard::class);
        Livewire::component('student.library', StudentLibrary::class);
        Livewire::component('student.profile-manager', \App\Modules\Student\UI\Livewire\ProfileManager::class);

        Livewire::component('forms.form-manager', \App\Modules\Forms\UI\Livewire\FormManager::class);
        Livewire::component('forms.formulario', \App\Modules\Website\UI\Livewire\FormularioPublico::class);
        Livewire::component('forms.form-details', \App\Modules\Forms\UI\Livewire\FormDetails::class);
        Livewire::component('registration.kanban-board', \App\Modules\Registration\UI\Livewire\KanbanBoard::class);
        Livewire::component('forms.form-edit', \App\Modules\Forms\UI\Livewire\FormEdit::class);
        Livewire::component('forms.form-spreadsheet', \App\Modules\Forms\UI\Livewire\FormSpreadsheet::class);

        Livewire::component('importacao.importacao-manager', \App\Modules\Importacao\UI\Livewire\ImportacaoManager::class);
        Livewire::component('importacao.import-progress', \App\Modules\Importacao\UI\Livewire\ImportProgress::class);
        Livewire::component('importacao.importacao-config-manager', \App\Modules\Importacao\UI\Livewire\ImportacaoConfigManager::class);


        Livewire::component('comunicacao.template-form', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateForm::class);
        Livewire::component('comunicacao.template-manager', \App\Modules\Comunicacao\UI\Livewire\Template\TemplateManager::class);

        Livewire::component('comunicacao.comunicado-form', \App\Modules\Comunicacao\UI\Livewire\Comunicado\ComunicadoForm::class);
        Livewire::component('comunicacao.comunicado-manager', \App\Modules\Comunicacao\UI\Livewire\Comunicado\ComunicadoManager::class);

        Livewire::component('comunicacao.automacao-form', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoForm::class);
        Livewire::component('comunicacao.automacao-manager', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoManager::class);
        Livewire::component('comunicacao.automacao-details', \App\Modules\Comunicacao\UI\Livewire\Automacao\AutomacaoDetails::class);

        Livewire::component('comunicacao.email-log-manager', \App\Modules\Comunicacao\UI\Livewire\EmailLog\EmailLogManager::class);

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web');
        });

        Livewire::component('auditoria.auditoria-manager', \App\Modules\Auditoria\UI\Livewire\AuditoriaManager::class);

        Livewire::component('gestao-educacional.gerador-mock', \App\Modules\GestaoEducacional\UI\Livewire\GeradorMock::class);
        Livewire::component('gestao-educacional.gerador-mock-aprendizagem', \App\Modules\GestaoEducacional\UI\Livewire\GeradorMockAprendizagem::class);
        Livewire::component('gestao-educacional.avalicao.listagem', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Listagem::class);
        Livewire::component('gestao-educacional.avalicao.responder', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Responder::class);
        
        Livewire::component('gestao-educacional.avalicao.relatorios', \App\Modules\GestaoEducacional\UI\Livewire\Avaliacao\Relatorios::class);
        Livewire::component('gestao-educacional.periodo-avaliacao.listagem', \App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao\Listagem::class);
        Livewire::component('gestao-educacional.periodo-avaliacao.detalhes', \App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao\Detalhes::class);
        
        Livewire::component('gestao-educacional.matriculas.listagem', \App\Modules\GestaoEducacional\UI\Livewire\Matricula\Listagem::class);
        Livewire::component('gestao-educacional.matriculas.detalhes', \App\Modules\GestaoEducacional\UI\Livewire\Matricula\Detalhes::class);

        Livewire::component('gestao-educacional.turmas.listagem', \App\Modules\GestaoEducacional\UI\Livewire\Turma\Listagem::class);
        Livewire::component('gestao-educacional.turmas.detalhes', \App\Modules\GestaoEducacional\UI\Livewire\Turma\Detalhes::class);
        
        Livewire::component('company.aprendizes-manager', \App\Modules\Company\UI\Livewire\AprendizesManager::class);
        Livewire::component('company.dashboard', \App\Modules\Company\UI\Livewire\Dashboard::class);
        Livewire::component('company.gestores-manager', \App\Modules\Company\UI\Livewire\GestoresManager::class);

        Livewire::component('company.empresas', \App\Modules\Company\UI\Livewire\EmpresaManager::class);
        Livewire::component('company.empresas-detalhes', \App\Modules\Company\UI\Livewire\EmpresaDetalhes::class);
        
        Livewire::component('auth.forgot-password',  \App\Modules\Portal\UI\Livewire\Auth\ForgotPassword::class);
        Livewire::component('auth.force-change',  \App\Modules\Portal\UI\Livewire\Auth\ForcePasswordChange::class);
        Livewire::component('auth.reset-password',  \App\Modules\Portal\UI\Livewire\Auth\ResetPassword::class);

        Livewire::component('form.builder', \App\Modules\FormBuilder\UI\Livewire\Hub::class);

        Livewire::component('report.dashboard', \App\Modules\Report\UI\Livewire\Dashboard::class);

        Livewire::component('admin.configuracoes-gerais', \App\Modules\Admin\UI\Livewire\ConfiguracoesGeraisManager::class);
        Livewire::component('admin.solicitacoes-manager', \App\Modules\Admin\UI\Livewire\SolicitacoesManager::class);

        Livewire::component('chart-widget', ChartWidget::class);

        Livewire::component('processo-matricula.iaconfig', \App\Modules\Matricula\UI\Livewire\IaConfigManager::class);
        Livewire::component('processo-matricula.portal',  \App\Modules\Matricula\UI\Livewire\PortalMatricula::class);
        Livewire::component('processo-matricula.processo',  \App\Modules\Matricula\UI\Livewire\ProcessoMatriculaManager::class);
        Livewire::component('financeiro-orcamento.manager',  \App\Modules\Financeiro\UI\Livewire\OrcamentoManager::class);
        Livewire::component('teste-rd.crm',  \App\Modules\Teste\RDCrm\UI\Livewire\RdCrmManager::class);

        Livewire::component('aprendizagem.preinscricao',  \App\Modules\Website\UI\Livewire\PreInscricao::class);
        Livewire::component('aprendizagem.listagem',  \App\Modules\GestaoEducacional\UI\Livewire\CicloAprendizagem\Listagem::class);
        Livewire::component('aprendizagem.acompanhamento',  \App\Modules\GestaoEducacional\UI\Livewire\CicloAprendizagem\Acompanhamento::class);
        Livewire::component('aprendizagem.formulario',  \App\Modules\GestaoEducacional\UI\Livewire\FormularioAprendizagem::class);

        Livewire::component('conteudo.create',  \App\Modules\Conteudo\UI\Livewire\ConteudoForm::class);
        Livewire::component('conteudo.categorias',  \App\Modules\Conteudo\UI\Livewire\ConteudoCategoriaManager::class);
        Livewire::component('conteudo.index',  \App\Modules\Conteudo\UI\Livewire\ConteudoManager::class);
        Livewire::component('conteudo.show',  \App\Modules\Conteudo\UI\Livewire\ConteudoPublico::class);
        Livewire::component('portal.index',  \App\Modules\Conteudo\UI\Livewire\PortalNoticias::class);
    

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

        Event::listen(function (UserLogin $event) {
            $usuario = $event->user;
            
            AuditoriaLog::create([
                'tabela_alterada' => 'users', 
                'registro_id' => $usuario->id,
                'acao' => 'login',
                'informacao_anterior' => null,
                'nova_informacao' => ['mensagem' => 'Sessão iniciada com sucesso'],
                'usuario_id' => $usuario->id,
                'usuario_nome' => $usuario->name,
                'usuario_role' => method_exists($usuario, 'getRoleNames') ? $usuario->getRoleNames()->first() : 'N/A',
                'usuario_login' => $usuario->email,
                'ip' => request()->ip(),
                'navegador' => request()->userAgent(),
            ]);
        });
        Event::listen(function (Logout $event) {
            $usuario = $event->user;
            
            if ($usuario) {
                AuditoriaLog::create([
                    'tabela_alterada' => 'users',
                    'registro_id' => $usuario->id,
                    'acao' => 'logout',
                    'informacao_anterior' => null,
                    'nova_informacao' => ['mensagem' => 'Sessão encerrada (Manual ou Inatividade)'],
                    'usuario_id' => $usuario->id,
                    'usuario_nome' => $usuario->name,
                    'usuario_role' => method_exists($usuario, 'getRoleNames') ? $usuario->getRoleNames()->first() : 'N/A',
                    'usuario_login' => $usuario->email,
                    'ip' => request()->ip(),
                    'navegador' => request()->userAgent(),
                ]);
            }
        });
    }
}