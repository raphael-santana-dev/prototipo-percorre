<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Financeiro\Domain\Models\CentroCusto;
use App\Modules\ACL\Domain\Models\Permission as CustomPermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Usuários - Administrativo')]
class UserManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    // --- CONTROLE DO WIZARD ---
    public int $etapaAtual = 1;

    // --- ETAPA 1: DADOS GERAIS ---
    public string $name = '';
    public string $email = '';
    public string $codigo_protheus = '';
    public string $password = '';
    
    public ?int $userId = null;
    public bool $isEditMode = false;
    public bool $showModal = false;

    // --- ETAPA 2: ROLE, UNIDADES/CURSOS/TURNOS E CENTROS DE CUSTO ---
    public string $roleName = '';
    public array $rolePermissionsIds = [];
    public array $rolePermissionsPorModulo = [];

    public array $unidadesSelecionadas = [];
    public array $cursosSelecionados = [];
    public array $turnosSelecionados = [];

    public $activeUnidadeId = null;
    public $activeCursoId = null;

    public bool $acessoGlobalFinanceiro = false;
    public array $centrosCustoSelecionados = [];
    public array $expiracoesCentrosCusto = [];

    public $cursosFiltrados = [];
    public $turnosFiltrados = [];

    // --- ETAPA 3: PERMISSÕES EXTRAS ---
    public array $selectedExtraPermissions = [];
    public array $extraPermissionExpirations = [];
    public string $buscaPermissaoExtra = '';

    public string $modelClass = User::class;
    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('usuario.listar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.listar'), 403);

        Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global aos centros de custo e orçamentos']
        );

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->aplicarRegrasCascata();
        $this->showModal = true;
    }

    public function setActiveUnidade($id)
    {
        $this->activeUnidadeId = $id;
        $this->activeCursoId = null;
        $this->aplicarRegrasCascata();
    }

    public function setActiveCurso($id)
    {
        $this->activeCursoId = $id;
        $this->aplicarRegrasCascata();
    }

    // --- NAVEGAÇÃO DO WIZARD ---
    public function proximoPasso()
    {
        if ($this->etapaAtual === 1) {
            $this->validarEtapa1();
            $this->etapaAtual = 2;
        } elseif ($this->etapaAtual === 2) {
            $this->validarEtapa2();
            $this->etapaAtual = 3;
        }
    }

    public function passoAnterior()
    {
        if ($this->etapaAtual > 1) {
            $this->etapaAtual--;
        }
    }

    public function irParaEtapa(int $etapa)
    {
        if ($etapa === $this->etapaAtual) {
            return;
        }

        if ($etapa > 1) {
            $this->validarEtapa1();
        }
        if ($etapa > 2) {
            $this->validarEtapa2();
        }

        $this->etapaAtual = $etapa;
    }

    private function validarEtapa1(): void
    {
        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
            'codigo_protheus' => 'nullable|string|max:50',
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:6';
        } elseif (!empty($this->password)) {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules);
    }

    private function validarEtapa2(): void
    {
        $rules = [
            'roleName' => 'required|string|exists:roles,name',
            'unidadesSelecionadas' => 'nullable|array',
            'cursosSelecionados' => 'nullable|array',
            'turnosSelecionados' => 'nullable|array',
            'centrosCustoSelecionados' => 'nullable|array',
            'acessoGlobalFinanceiro' => 'boolean',
        ];

        $messages = [
            'roleName.required' => 'Selecione um Grupo Principal (Role) para o usuário.',
        ];

        // Se o usuário selecionou alguma unidade, exige que vincule pelo menos um curso e um turno correspondente
        if (!empty($this->unidadesSelecionadas)) {
            $rules['cursosSelecionados'] = 'required|array|min:1';
            $rules['turnosSelecionados'] = 'required|array|min:1';
            $messages['cursosSelecionados.required'] = 'Selecione ao menos um Curso vinculado à(s) unidade(s) escolhida(s).';
            $messages['cursosSelecionados.min'] = 'Selecione ao menos um Curso vinculado à(s) unidade(s) escolhida(s).';
            $messages['turnosSelecionados.required'] = 'Selecione ao menos um Turno vinculado ao(s) curso(s) escolhido(s).';
            $messages['turnosSelecionados.min'] = 'Selecione ao menos um Turno vinculado ao(s) curso(s) escolhido(s).';
        }

        $this->validate($rules, $messages);
    }

    // --- REATIVIDADE DE ROLE E VÍNCULOS ---
    public function updatedRoleName()
    {
        $this->carregarPermissoesDaRole();
        $this->aplicarRegrasCascata();
    }

    public function updatedUnidadesSelecionadas()
    {
        $this->unidadesSelecionadas = array_values(array_filter(array_map('strval', $this->unidadesSelecionadas)));

        if (empty($this->unidadesSelecionadas)) {
            $this->activeUnidadeId = null;
            $this->activeCursoId = null;
        } elseif (!$this->activeUnidadeId || !in_array((string) $this->activeUnidadeId, $this->unidadesSelecionadas)) {
            $this->activeUnidadeId = $this->unidadesSelecionadas[0];
            $this->activeCursoId = null;
        }

        $this->aplicarRegrasCascata();
    }

    public function updatedCursosSelecionados()
    {
        $this->cursosSelecionados = array_values(array_filter(array_map('strval', $this->cursosSelecionados)));

        if (empty($this->cursosSelecionados)) {
            $this->activeCursoId = null;
        } elseif (!$this->activeCursoId || !in_array((string) $this->activeCursoId, $this->cursosSelecionados)) {
            $this->activeCursoId = $this->cursosSelecionados[0];
        }

        $this->aplicarRegrasCascata();
    }

    public function carregarPermissoesDaRole(): void
    {
        $this->rolePermissionsIds = [];
        $this->rolePermissionsPorModulo = [];

        if (empty($this->roleName)) {
            return;
        }

        $role = Role::with('permissions')->where('name', $this->roleName)->first();
        if (!$role) {
            return;
        }

        $this->rolePermissionsIds = $role->permissions->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $agrupadas = [];
        foreach ($role->permissions->sortBy('name') as $perm) {
            $modulo = strtoupper($perm->module ?: explode('.', $perm->name)[0] ?? 'GERAL');
            $agrupadas[$modulo][] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'description' => $perm->description,
            ];
        }
        ksort($agrupadas);
        $this->rolePermissionsPorModulo = $agrupadas;

        $this->selectedExtraPermissions = array_values(array_diff(
            array_map('strval', $this->selectedExtraPermissions),
            array_map('strval', $this->rolePermissionsIds)
        ));
    }

    public function aplicarRegrasCascata(): void
    {
        $unidadesIds = array_values(array_filter(array_map('intval', $this->unidadesSelecionadas)));

        if (empty($unidadesIds)) {
            $this->cursosFiltrados = collect();
            $this->turnosFiltrados = collect();
            $this->cursosSelecionados = [];
            $this->turnosSelecionados = [];
            $this->activeUnidadeId = null;
            $this->activeCursoId = null;
            return;
        }

        if (!$this->activeUnidadeId || !in_array((int) $this->activeUnidadeId, $unidadesIds)) {
            $this->activeUnidadeId = $unidadesIds[0];
        }

        // Busca todos os cursos que pertencem às unidades marcadas
        $cursosDasUnidadesMarcadas = Curso::with(['unidades', 'turnosVinculados'])
            ->whereIn('status', ['Ativo', 'ativo', '1', true])
            ->whereHas('unidades', function ($q) use ($unidadesIds) {
                $q->whereIn('unidades.id', $unidadesIds);
            })
            ->orderBy('nome')
            ->get();

        // Limpa cursos selecionados que não pertençam mais a nenhuma unidade marcada
        $idsCursosPermitidos = $cursosDasUnidadesMarcadas->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->cursosSelecionados = array_values(array_intersect(array_map('strval', $this->cursosSelecionados), $idsCursosPermitidos));

        // Na Coluna 2, exibe os cursos vinculados especificamente à Unidade Ativa clicada
        $this->cursosFiltrados = $cursosDasUnidadesMarcadas->filter(function ($curso) {
            return $curso->unidades->contains('id', (int) $this->activeUnidadeId);
        })->values();

        // Se o curso ativo não estiver nos cursos da unidade ativa, seleciona o primeiro curso marcado dessa unidade (se houver)
        $cursosDaUnidadeAtivaIds = $this->cursosFiltrados->pluck('id')->map(fn($id) => (string) $id)->toArray();
        if (!$this->activeCursoId || !in_array((string) $this->activeCursoId, $cursosDaUnidadeAtivaIds)) {
            $marcadosNaUnidadeAtiva = array_values(array_intersect($this->cursosSelecionados, $cursosDaUnidadeAtivaIds));
            $this->activeCursoId = $marcadosNaUnidadeAtiva[0] ?? ($cursosDaUnidadeAtivaIds[0] ?? null);
        }

        // Limpa turnos selecionados que não pertençam a nenhum curso selecionado
        if (!empty($this->cursosSelecionados)) {
            $cursosMarcadosModels = $cursosDasUnidadesMarcadas->whereIn('id', array_map('intval', $this->cursosSelecionados));
            $idsTurnosPermitidos = $cursosMarcadosModels
                ->flatMap(fn($c) => $c->turnosVinculados)
                ->pluck('id')
                ->unique()
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->turnosSelecionados = array_values(array_intersect(array_map('strval', $this->turnosSelecionados), $idsTurnosPermitidos));
        } else {
            $this->turnosSelecionados = [];
        }

        // Na Coluna 3, exibe os turnos vinculados ao Curso Ativo clicado
        if ($this->activeCursoId) {
            $cursoAtivoObj = $cursosDasUnidadesMarcadas->firstWhere('id', (int) $this->activeCursoId);
            $this->turnosFiltrados = $cursoAtivoObj ? $cursoAtivoObj->turnosVinculados->sortBy('nome')->values() : collect();
        } else {
            $this->turnosFiltrados = collect();
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

        $this->validarEtapa1();
        $this->validarEtapa2();

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
                $this->dispatch('erro', msg: 'Você não tem permissão para editar um usuário DEV.');
                return;
            }
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // 1. Sincroniza Role Principal
        $user->syncRoles([$this->roleName]);

        // 2. Sincroniza Unidades, Cursos e Turnos
        $user->unidades()->sync($this->unidadesSelecionadas);
        $user->cursos()->sync($this->cursosSelecionados);
        $user->turnos()->sync($this->turnosSelecionados);

        // 3. Sincroniza Centros de Custo
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

        // 4. Sincroniza Permissões Extras + Permissão Global Financeira
        $permVisaoGlobal = Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global aos centros de custo e orçamentos']
        );

        $syncDirectPermissions = [];
        $currentUser = auth()->user();
        $isDev = $currentUser->hasRole('dev');

        foreach ($this->selectedExtraPermissions as $permissionId) {
            $permissionId = (int) $permissionId;
            if (!$permissionId || in_array($permissionId, $this->rolePermissionsIds)) {
                continue;
            }

            $permission = CustomPermission::find($permissionId);
            if (!$permission) {
                continue;
            }

            if (!$isDev && !$currentUser->hasPermissionTo($permission->name)) {
                $this->dispatch('erro', msg: "Você não pode conceder a permissão '{$permission->name}'.");
                return;
            }

            $expiresAt = !empty($this->extraPermissionExpirations[$permissionId])
                ? $this->extraPermissionExpirations[$permissionId]
                : null;

            $syncDirectPermissions[$permissionId] = ['expires_at' => $expiresAt];
        }

        if ($this->acessoGlobalFinanceiro) {
            $syncDirectPermissions[$permVisaoGlobal->id] = ['expires_at' => null];
        } else {
            unset($syncDirectPermissions[$permVisaoGlobal->id]);
        }

        $user->permissions()->sync($syncDirectPermissions);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: 'Usuário, vínculos e permissões salvos com sucesso!');
    }

    public function edit(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos', 'turnos', 'centros_de_custo', 'permissions'])->findOrFail($id);

        if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
            $this->dispatch('erro', msg: 'Você não tem privilégios para alterar um usuário DEV.');
            return;
        }

        $this->resetInputFields();

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->codigo_protheus = $user->codigo_protheus ?? '';
        $this->password = '';

        $this->roleName = $user->roles->first()?->name ?? '';
        $this->carregarPermissoesDaRole();

        $this->unidadesSelecionadas = $user->unidades->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->cursosSelecionados = $user->cursos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->turnosSelecionados = $user->turnos->pluck('id')->map(fn($id) => (string) $id)->toArray();

        $this->activeUnidadeId = $this->unidadesSelecionadas[0] ?? null;
        $this->activeCursoId = $this->cursosSelecionados[0] ?? null;

        $this->aplicarRegrasCascata();

        $this->acessoGlobalFinanceiro = $user->permissions->contains('name', 'financeiro.visao_global');
        $this->centrosCustoSelecionados = $user->centros_de_custo->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->expiracoesCentrosCusto = [];

        foreach ($user->centros_de_custo as $cc) {
            if ($cc->pivot->expires_at) {
                $this->expiracoesCentrosCusto[$cc->id] = \Carbon\Carbon::parse($cc->pivot->expires_at)->format('Y-m-d');
            }
        }

        $directPermissions = $user->permissions()->withPivot('expires_at')->get();
        foreach ($directPermissions as $perm) {
            if ($perm->name === 'financeiro.visao_global') {
                continue;
            }
            $this->selectedExtraPermissions[] = (string) $perm->id;
            if ($perm->pivot->expires_at) {
                $this->extraPermissionExpirations[$perm->id] = date('Y-m-d', strtotime($perm->pivot->expires_at));
            }
        }

        $this->isEditMode = true;
        $this->etapaAtual = 1;
        $this->showModal = true;
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('erro', msg: 'Você não pode excluir a sua própria conta.');
            return;
        }
        if ($user->hasRole('dev')) {
            $this->dispatch('erro', msg: 'Usuários com perfil DEV não podem ser excluídos.');
            return;
        }
        $user->delete();
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: 'Usuário excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->etapaAtual = 1;
        $this->name = '';
        $this->email = '';
        $this->codigo_protheus = '';
        $this->password = '';
        $this->roleName = '';
        $this->rolePermissionsIds = [];
        $this->rolePermissionsPorModulo = [];
        $this->userId = null;
        $this->isEditMode = false;

        $this->unidadesSelecionadas = [];
        $this->cursosSelecionados = [];
        $this->turnosSelecionados = [];
        $this->activeUnidadeId = null;
        $this->activeCursoId = null;

        $this->acessoGlobalFinanceiro = false;
        $this->centrosCustoSelecionados = [];
        $this->expiracoesCentrosCusto = [];

        $this->selectedExtraPermissions = [];
        $this->extraPermissionExpirations = [];
        $this->buscaPermissaoExtra = '';

        $this->resetErrorBag();
    }

    public function showQuickDetails(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos', 'turnos', 'centros_de_custo', 'permissions'])->findOrFail($id);

        $unidadesStr = $user->unidades->count() > 0 ? implode(', ', $user->unidades->pluck('nome')->toArray()) : 'Não restrito';
        $cursosStr = $user->cursos->count() > 0 ? implode(', ', $user->cursos->pluck('nome')->toArray()) : 'Não restrito';
        
        $temVisaoGlobal = $user->permissions->contains('name', 'financeiro.visao_global');
        $ccustosStr = $temVisaoGlobal
            ? 'Permissão Global (Visão Total)'
            : ($user->centros_de_custo->count() > 0 ? implode(', ', $user->centros_de_custo->pluck('codigo')->toArray()) : 'Sem vínculo de C.Custo');

        $qtdExtras = $user->permissions->where('name', '!=', 'financeiro.visao_global')->count();

        $detalhes = [
            'Nome Completo' => $user->name,
            'E-mail' => $user->email,
            'Código Protheus' => $user->codigo_protheus ?: 'Não vinculado',
            'Grupo Principal (Role)' => strtoupper($user->roles->first()?->name ?? 'Sem grupo'),
            'Unidades Vinculadas' => $unidadesStr,
            'Cursos Vinculados' => $cursosStr,
            'Centros de Custo (Fin)' => $ccustosStr,
            'Permissões Extras' => $qtdExtras > 0 ? "{$qtdExtras} permissão(ões) avulsa(s)" : 'Nenhuma (Apenas Role)',
            'Criado em' => $user->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title' => 'Perfil do Usuário',
            'icon' => 'ph-user-circle',
            'data' => $detalhes,
            'subtitle' => 'Visualização rápida de dados e escopo'
        ]);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'name', 'label' => 'Nome / E-mail', 'sortable' => true],
            ['key' => 'roles', 'label' => 'Grupo / Escopo de Vínculos', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = User::query()->with(['roles', 'unidades', 'centros_de_custo', 'permissions']);

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('name', 'asc');
        }

        $permissoesQuery = CustomPermission::orderBy('module')->orderBy('name');
        if (!empty(trim($this->buscaPermissaoExtra))) {
            $termo = '%' . trim($this->buscaPermissaoExtra) . '%';
            $permissoesQuery->where(function ($q) use ($termo) {
                $q->where('name', 'ilike', $termo)
                  ->orWhere('description', 'ilike', $termo)
                  ->orWhere('module', 'ilike', $termo);
            });
        }

        return view('livewire.corporate.user-manager', [
            'registros' => $query->paginate($this->porPagina),
            'roles' => Role::orderBy('name')->get(),
            'todasUnidades' => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'todosCentrosCusto' => CentroCusto::where('disponivel_orcamento', true)->orderBy('codigo')->get(),
            'permissionsByModule' => $permissoesQuery->get()->groupBy('module'),
        ]);
    }
}