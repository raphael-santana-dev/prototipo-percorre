<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Financeiro\Domain\Models\CentroCusto;
use Illuminate\Support\Str;

use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Usuários - Administrativo')]
class UserManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    public string $name = '';
    public string $email = '';
    public string $codigo_protheus = '';
    public string $password = '';
    public string $roleName = '';
    
    public ?int $userId = null;
    public bool $isEditMode = false;
    public bool $showModal = false;

    public array $unidadesSelecionadas = [];
    public array $cursosSelecionados = [];
    public array $turnosSelecionados = [];

    // --- NOVAS VARIÁVEIS FINANCEIRAS ---
    public bool $acessoGlobalFinanceiro = false;
    public array $centrosCustoSelecionados = [];
    public array $expiracoesCentrosCusto = [];
    // -----------------------------------

    public $cursosFiltrados = [];
    public $turnosFiltrados = [];

    public string $modelClass = User::class;
    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('usuario.listar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->aplicarRegrasCascata();
        $this->showModal = true;
    }

    public function updatedRoleName() { $this->aplicarRegrasCascata(); }
    public function updatedUnidadesSelecionadas() { $this->aplicarRegrasCascata(); }
    public function updatedCursosSelecionados() { $this->aplicarRegrasCascata(); }

    public function aplicarRegrasCascata()
    {
        if (strtolower($this->roleName) === 'professor') {
            
            if (empty($this->unidadesSelecionadas)) {
                $this->cursosFiltrados = [];
            } else {
                $this->cursosFiltrados = Curso::whereHas('unidades', function($q) {
                    $q->whereIn('unidades.id', $this->unidadesSelecionadas);
                })->whereIn('status', ['Ativo', '1', true])->get();
            }

            $idsCursosValidos = collect($this->cursosFiltrados)->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->cursosSelecionados = array_intersect($this->cursosSelecionados, $idsCursosValidos);

            if (empty($this->cursosSelecionados)) {
                $this->turnosFiltrados = [];
            } else {
                $turnosDisponiveis = collect();
                $cursosValidosObj = Curso::with('turnosVinculados')->whereIn('id', $this->cursosSelecionados)->get();
                foreach($cursosValidosObj as $curso) {
                    foreach($curso->turnosVinculados as $turno) {
                        $turnosDisponiveis->put($turno->id, $turno);
                    }
                }
                $this->turnosFiltrados = $turnosDisponiveis->values()->all();
            }

            $idsTurnosValidos = collect($this->turnosFiltrados)->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->turnosSelecionados = array_intersect($this->turnosSelecionados, $idsTurnosValidos);

        } else {
            $this->cursosFiltrados = Curso::whereIn('status', ['Ativo', '1', true])->get();
            $this->turnosFiltrados = Turno::all();
        }
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!feature('usuario.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.editar'), 403);
        } else {
            abort_if(!feature('usuario.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.criar'), 403);
        }
        
        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
            'codigo_protheus' => 'nullable|string|max:50',
            'roleName' => 'required|string|exists:roles,name',
            'unidadesSelecionadas' => 'nullable|array',
            'cursosSelecionados' => 'nullable|array',
            'turnosSelecionados' => 'nullable|array',
            'centrosCustoSelecionados' => 'nullable|array',
            'acessoGlobalFinanceiro' => 'boolean',
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:6';
        } elseif (!empty($this->password)) {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => strtolower($this->email),
            'codigo_protheus' => $this->codigo_protheus,
            'slug' => Str::slug($this->name),
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditMode) {
            $user = User::findOrFail($this->userId);
            
            if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
                $this->addError('email', 'Você não tem permissão para editar um usuário DEV.');
                return;
            }
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        $user->syncRoles([$this->roleName]);
        
        $user->unidades()->sync($this->unidadesSelecionadas);
        $user->cursos()->sync($this->cursosSelecionados);
        $user->turnos()->sync($this->turnosSelecionados);

        // --- LÓGICA DO MÓDULO FINANCEIRO ---
        
        // 1. Permissão Global (Spatie ACL)
        $permName = 'financeiro.visao_global';
        Permission::firstOrCreate(['name' => $permName], ['module' => 'financeiro', 'description' => 'Acesso global aos centros de custo e orçamentos', 'guard_name' => 'web']);
        
        if ($this->acessoGlobalFinanceiro) {
            $user->givePermissionTo($permName);
        } else {
            $user->revokePermissionTo($permName);
        }

        // 2. Sincronização dos Centros de Custo (Temporários ou Fixos)
        $syncDataCC = [];
        if (!$this->acessoGlobalFinanceiro) {
            foreach ($this->centrosCustoSelecionados as $ccId) {
                if ($ccId) {
                    $expires = !empty($this->expiracoesCentrosCusto[$ccId]) ? $this->expiracoesCentrosCusto[$ccId] : null;
                    $syncDataCC[$ccId] = ['expires_at' => $expires];
                }
            }
        }
        $user->centros_de_custo()->sync($syncDataCC);
        // ------------------------------------

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: 'Usuário e vínculos salvos com sucesso!');
    }

    public function edit(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos', 'turnos', 'centros_de_custo'])->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->codigo_protheus = $user->codigo_protheus ?? '';
        $this->password = ''; 
        
        $this->roleName = $user->roles->first()?->name ?? '';
        
        $this->unidadesSelecionadas = $user->unidades->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->cursosSelecionados = $user->cursos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->turnosSelecionados = $user->turnos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        
        $this->aplicarRegrasCascata();

        // --- CARREGA DADOS DO FINANCEIRO ---
        $this->acessoGlobalFinanceiro = $user->hasDirectPermission('financeiro.visao_global');
        
        $this->centrosCustoSelecionados = $user->centros_de_custo->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->expiracoesCentrosCusto = [];
        
        foreach ($user->centros_de_custo as $cc) {
            if ($cc->pivot->expires_at) {
                $this->expiracoesCentrosCusto[$cc->id] = \Carbon\Carbon::parse($cc->pivot->expires_at)->format('Y-m-d');
            }
        }
        // -----------------------------------

        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('erro', msg:  'Você não pode excluir a sua própria conta.');
            return;
        }
        if ($user->hasRole('dev')) {
            $this->dispatch('erro', msg:  'Usuários com perfil DEV não podem ser excluídos.');
            return;
        }
        $user->delete();
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: 'Usuário excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->email = '';
        $this->codigo_protheus = '';
        $this->password = '';
        $this->roleName = '';
        $this->userId = null;
        $this->isEditMode = false;
        
        $this->unidadesSelecionadas = [];
        $this->cursosSelecionados = [];
        $this->turnosSelecionados = [];

        $this->acessoGlobalFinanceiro = false;
        $this->centrosCustoSelecionados = [];
        $this->expiracoesCentrosCusto = [];
        
        $this->resetErrorBag();
    }

    public function showQuickDetails(int $id)
    {
        $user = User::with(['roles', 'unidades', 'centros_de_custo'])->findOrFail($id);
        
        $unidadesStr = $user->unidades->count() > 0 ? implode(', ', $user->unidades->pluck('nome')->toArray()) : 'Acesso Global';
        $ccustosStr = $user->hasDirectPermission('financeiro.visao_global') ? 'Permissão Global (Visão Total)' : ($user->centros_de_custo->count() > 0 ? implode(', ', $user->centros_de_custo->pluck('codigo')->toArray()) : 'Sem acesso a C.Custo');
        
        $detalhes = [
            'Nome Completo' => $user->name,
            'E-mail' => $user->email,
            'Código Protheus' => $user->codigo_protheus ?: 'Não vinculado',
            'Grupo Principal' => $user->roles->first()?->name ?? 'Sem grupo',
            'Unidades Vinculadas' => $unidadesStr,
            'Centros de Custo (Fin)' => $ccustosStr,
            'Criado em' => $user->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title' => 'Perfil do Usuário', 
            'icon' => 'ph-user-circle', 
            'data' => $detalhes,
            'subtitle' => 'Visualização rápida de dados'
        ]);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'name', 'label' => 'Nome / E-mail', 'sortable' => true],
            ['key' => 'roles', 'label' => 'Acesso / Unidades', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = User::query()->with(['roles', 'unidades']);
        
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('name', 'asc');
        }

        return view('livewire.corporate.user-manager', [
            'registros' => $query->paginate($this->porPagina),
            'roles' => Role::orderBy('name')->get(),
            'todasUnidades' => Unidade::whereIn('status', ['Ativa', '1', true])->get(), 
            'todosCentrosCusto' => CentroCusto::where('disponivel_orcamento', true)->orderBy('codigo')->get(),
        ]);
    }
}