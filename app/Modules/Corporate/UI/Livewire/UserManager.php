<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

    // Relacionamento 1: Centros de Custo para EDIÇÃO
    public bool $acessoGlobalEdicao = false;
    public array $centrosCustoEdicaoSelecionados = [];
    public array $expiracoesCentrosCustoEdicao = [];

    // Relacionamento 2: Centros de Custo para APROVAÇÃO
    public bool $acessoGlobalAprovacao = false;
    public array $centrosCustoAprovacaoSelecionados = [];
    public array $expiracoesCentrosCustoAprovacao = [];

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
        //abort_if(!feature('usuario.listar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.listar'), 403);

        Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global.edicao', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global a todos os centros de custo para EDIÇÃO de orçamentos']
        );

        Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global.aprovacao', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global a todos os centros de custo para APROVAÇÃO de orçamentos']
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
            'centrosCustoEdicaoSelecionados' => 'nullable|array',
            'centrosCustoAprovacaoSelecionados' => 'nullable|array',
            'acessoGlobalEdicao' => 'boolean',
            'acessoGlobalAprovacao' => 'boolean',
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

        $cursosDasUnidadesMarcadas = Curso::with(['unidades', 'turnosVinculados'])
            ->whereIn('status', ['Ativo', 'ativo', '1', true])
            ->whereHas('unidades', function ($q) use ($unidadesIds) {
                $q->whereIn('unidades.id', $unidadesIds);
            })
            ->orderBy('nome')
            ->get();

        $idsCursosPermitidos = $cursosDasUnidadesMarcadas->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->cursosSelecionados = array_values(array_intersect(array_map('strval', $this->cursosSelecionados), $idsCursosPermitidos));

        $this->cursosFiltrados = $cursosDasUnidadesMarcadas->filter(function ($curso) {
            return $curso->unidades->contains('id', (int) $this->activeUnidadeId);
        })->values();

        $cursosDaUnidadeAtivaIds = $this->cursosFiltrados->pluck('id')->map(fn($id) => (string) $id)->toArray();
        if (!$this->activeCursoId || !in_array((string) $this->activeCursoId, $cursosDaUnidadeAtivaIds)) {
            $marcadosNaUnidadeAtiva = array_values(array_intersect($this->cursosSelecionados, $cursosDaUnidadeAtivaIds));
            $this->activeCursoId = $marcadosNaUnidadeAtiva[0] ?? ($cursosDaUnidadeAtivaIds[0] ?? null);
        }

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
            //abort_if(!feature('usuario.editar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.editar'), 403);
        } else {
            //abort_if(!feature('usuario.criar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.criar'), 403);
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

        // 3. Sincroniza Centros de Custo separados por 'edicao' e 'aprovacao'
        DB::table('centro_custo_user')->where('user_id', $user->id)->delete();

        $registrosPivotCC = [];

        if (!$this->acessoGlobalEdicao) {
            foreach (array_unique($this->centrosCustoEdicaoSelecionados) as $ccId) {
                if ($ccId) {
                    $registrosPivotCC[] = [
                        'centro_custo_id' => (int) $ccId,
                        'user_id'         => $user->id,
                        'tipo_acesso'     => 'edicao',
                        'expires_at'      => !empty($this->expiracoesCentrosCustoEdicao[$ccId]) ? $this->expiracoesCentrosCustoEdicao[$ccId] : null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
            }
        }

        if (!$this->acessoGlobalAprovacao) {
            foreach (array_unique($this->centrosCustoAprovacaoSelecionados) as $ccId) {
                if ($ccId) {
                    $registrosPivotCC[] = [
                        'centro_custo_id' => (int) $ccId,
                        'user_id'         => $user->id,
                        'tipo_acesso'     => 'aprovacao',
                        'expires_at'      => !empty($this->expiracoesCentrosCustoAprovacao[$ccId]) ? $this->expiracoesCentrosCustoAprovacao[$ccId] : null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
            }
        }

        if (!empty($registrosPivotCC)) {
            DB::table('centro_custo_user')->insert($registrosPivotCC);
        }

        // 4. Sincroniza Permissões Extras + Permissões Globais de Centro de Custo (Edição / Aprovação)
        $permGlobalEdicao = Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global.edicao', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global a todos os centros de custo para EDIÇÃO de orçamentos']
        );
        $permGlobalAprovacao = Permission::firstOrCreate(
            ['name' => 'financeiro.visao_global.aprovacao', 'guard_name' => 'web'],
            ['module' => 'financeiro', 'description' => 'Acesso global a todos os centros de custo para APROVAÇÃO de orçamentos']
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

        if ($this->acessoGlobalEdicao) {
            $syncDirectPermissions[$permGlobalEdicao->id] = ['expires_at' => null];
        }
        if ($this->acessoGlobalAprovacao) {
            $syncDirectPermissions[$permGlobalAprovacao->id] = ['expires_at' => null];
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

        $this->acessoGlobalEdicao = $user->permissions->contains('name', 'financeiro.visao_global.edicao')
            || $user->permissions->contains('name', 'financeiro.visao_global');
        $this->acessoGlobalAprovacao = $user->permissions->contains('name', 'financeiro.visao_global.aprovacao')
            || $user->permissions->contains('name', 'financeiro.visao_global');

        $this->centrosCustoEdicaoSelecionados = [];
        $this->expiracoesCentrosCustoEdicao = [];
        $this->centrosCustoAprovacaoSelecionados = [];
        $this->expiracoesCentrosCustoAprovacao = [];

        foreach ($user->centros_de_custo as $cc) {
            $tipo = $cc->pivot->tipo_acesso ?? 'edicao';
            $expiraFormatado = $cc->pivot->expires_at ? \Carbon\Carbon::parse($cc->pivot->expires_at)->format('Y-m-d') : null;

            if ($tipo === 'aprovacao') {
                $this->centrosCustoAprovacaoSelecionados[] = (string) $cc->id;
                if ($expiraFormatado) {
                    $this->expiracoesCentrosCustoAprovacao[$cc->id] = $expiraFormatado;
                }
            } else {
                $this->centrosCustoEdicaoSelecionados[] = (string) $cc->id;
                if ($expiraFormatado) {
                    $this->expiracoesCentrosCustoEdicao[$cc->id] = $expiraFormatado;
                }
            }
        }

        $directPermissions = $user->permissions()->withPivot('expires_at')->get();
        foreach ($directPermissions as $perm) {
            if (in_array($perm->name, ['financeiro.visao_global', 'financeiro.visao_global.edicao', 'financeiro.visao_global.aprovacao'], true)) {
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

        $this->acessoGlobalEdicao = false;
        $this->centrosCustoEdicaoSelecionados = [];
        $this->expiracoesCentrosCustoEdicao = [];

        $this->acessoGlobalAprovacao = false;
        $this->centrosCustoAprovacaoSelecionados = [];
        $this->expiracoesCentrosCustoAprovacao = [];

        $this->selectedExtraPermissions = [];
        $this->extraPermissionExpirations = [];
        $this->buscaPermissaoExtra = '';

        $this->resetErrorBag();
    }

    public function showQuickDetails(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos', 'turnos', 'centros_de_custo_edicao', 'centros_de_custo_aprovacao', 'permissions'])->findOrFail($id);

        $unidadesStr = $user->unidades->count() > 0 ? implode(', ', $user->unidades->pluck('nome')->toArray()) : 'Não restrito';
        $cursosStr = $user->cursos->count() > 0 ? implode(', ', $user->cursos->pluck('nome')->toArray()) : 'Não restrito';
        
        $ccEdicao = $user->possuiVisaoGlobalCentrosCusto('edicao')
            ? 'Global (Todos)'
            : ($user->centros_de_custo_edicao->count() > 0 ? implode(', ', $user->centros_de_custo_edicao->pluck('codigo')->toArray()) : 'Nenhum');

        $ccAprovacao = $user->possuiVisaoGlobalCentrosCusto('aprovacao')
            ? 'Global (Todos)'
            : ($user->centros_de_custo_aprovacao->count() > 0 ? implode(', ', $user->centros_de_custo_aprovacao->pluck('codigo')->toArray()) : 'Nenhum');

        $qtdExtras = $user->permissions->whereNotIn('name', [
            'financeiro.visao_global',
            'financeiro.visao_global.edicao',
            'financeiro.visao_global.aprovacao'
        ])->count();

        $detalhes = [
            'Nome Completo' => $user->name,
            'E-mail' => $user->email,
            'Código Protheus' => $user->codigo_protheus ?: 'Não vinculado',
            'Grupo Principal (Role)' => strtoupper($user->roles->first()?->name ?? 'Sem grupo'),
            'Unidades Vinculadas' => $unidadesStr,
            'Cursos Vinculados' => $cursosStr,
            'C. Custo (Edição)' => $ccEdicao,
            'C. Custo (Aprovação)' => $ccAprovacao,
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
        $query = User::query()->with(['roles', 'unidades', 'centros_de_custo', 'centros_de_custo_edicao', 'centros_de_custo_aprovacao', 'permissions']);

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