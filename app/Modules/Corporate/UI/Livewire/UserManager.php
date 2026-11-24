<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    // --- ETAPA 2: ROLE, TRAVA POR VÍNCULO (UNIDADE/CURSO/TURNO) E CENTROS DE CUSTO ---
    public string $roleName = '';
    public array $rolePermissionsIds = [];
    public array $rolePermissionsPorModulo = [];

    public bool $travar_por_vinculo = false;

    // Chaves compostas idênticas ao CRUD de Ciclos:
    // $unidadesSelecionadas = ['1', '2']
    // $cursosSelecionados   = ['1-3', '2-5'] ("{unidadeId}-{cursoId}")
    // $turnosSelecionados   = ['1-3-1', '1-3-2'] ("{unidadeId}-{cursoId}-{turnoId}")
    public array $unidadesSelecionadas = [];
    public array $cursosSelecionados = [];
    public array $turnosSelecionados = [];
    public array $expiracoesVinculos = []; // ['1-3-1' => '2026-12-31']

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

    // --- ETAPA 3: PERMISSÕES EXTRAS ---
    public array $selectedExtraPermissions = [];
    public array $extraPermissionExpirations = [];
    public string $buscaPermissaoExtra = '';

    public string $modelClass = User::class;
    public array $breadcrumbs = [];

    public function mount()
    {
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
        $this->showModal = true;
    }

    public function setActiveUnidade($id): void
    {
        $this->activeUnidadeId = $id;
        $this->activeCursoId = null;
    }

    public function setActiveCurso($id): void
    {
        $this->activeCursoId = $id;
    }

    /**
     * Quando desmarca uma Unidade, remove os cursos e turnos daquela Unidade.
     */
    public function updatedUnidadesSelecionadas(): void
    {
        $ativas = array_values(array_unique(array_map('strval', $this->unidadesSelecionadas)));
        $this->unidadesSelecionadas = $ativas;

        $this->cursosSelecionados = array_values(array_filter($this->cursosSelecionados, function ($v) use ($ativas) {
            [$uId] = explode('-', (string) $v, 2);
            return in_array($uId, $ativas, true);
        }));

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($v) use ($ativas) {
            [$uId] = explode('-', (string) $v, 3);
            return in_array($uId, $ativas, true);
        }));
    }

    /**
     * Quando marca/desmarca um Curso ("{uId}-{cId}"), garante a Unidade marcada ou limpa os turnos daquele curso.
     */
    public function updatedCursosSelecionados(): void
    {
        $cursosAtivos = array_values(array_unique(array_map('strval', $this->cursosSelecionados)));
        $this->cursosSelecionados = $cursosAtivos;

        foreach ($cursosAtivos as $cKey) {
            if (str_contains($cKey, '-')) {
                [$uId] = explode('-', $cKey, 2);
                if (!in_array($uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = $uId;
                }
            }
        }

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($v) use ($cursosAtivos) {
            $p = explode('-', (string) $v);
            return count($p) === 3 && in_array("{$p[0]}-{$p[1]}", $cursosAtivos, true);
        }));
    }

    /**
     * Quando marca/desmarca um Turno ("{uId}-{cId}-{tId}"), marca automaticamente o Curso e a Unidade.
     */
    public function updatedTurnosSelecionados(): void
    {
        $this->turnosSelecionados = array_values(array_unique(array_map('strval', $this->turnosSelecionados)));

        foreach ($this->turnosSelecionados as $tKey) {
            $p = explode('-', (string) $tKey);
            if (count($p) === 3) {
                [$uId, $cId] = $p;
                $chaveCurso = "{$uId}-{$cId}";
                if (!in_array($uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = $uId;
                }
                if (!in_array($chaveCurso, $this->cursosSelecionados, true)) {
                    $this->cursosSelecionados[] = $chaveCurso;
                }
            }
        }
    }

    public function removerCombinacaoVinculo(string $comboKey): void
    {
        $this->turnosSelecionados = array_values(array_diff($this->turnosSelecionados, [$comboKey]));
        unset($this->expiracoesVinculos[$comboKey]);
    }

    public function contarTurnosDoCursoNaUnidade(int $unidadeId, int $cursoId): int
    {
        $prefixo = "{$unidadeId}-{$cursoId}-";
        return count(array_filter($this->turnosSelecionados, fn($v) => str_starts_with((string) $v, $prefixo)));
    }

    public function obterTurnosDoCursoNaUnidade(int $unidadeId, int $cursoId)
    {
        if (Schema::hasTable('unidade_curso_turno')) {
            $idsTurnos = DB::table('unidade_curso_turno')
                ->where('unidade_id', $unidadeId)
                ->where('curso_id', $cursoId)
                ->pluck('turno_id')
                ->toArray();

            if (!empty($idsTurnos)) {
                return Turno::whereIn('id', $idsTurnos)
                    ->where('status', true)
                    ->orderBy('horario_inicio')
                    ->get();
            }
        }

        $curso = Curso::with('turnosVinculados')->find($cursoId);
        if (!$curso) {
            return collect();
        }

        return method_exists($curso, 'turnosNaUnidade')
            ? $curso->turnosNaUnidade($unidadeId)
            : $curso->turnosVinculados;
    }

    public function getCombinacoesVinculoDetalhadasProperty(): array
    {
        if (empty($this->turnosSelecionados)) {
            return [];
        }

        $unidadesMap = Unidade::pluck('nome', 'id');
        $cursosMap   = Curso::pluck('nome', 'id');
        $turnosMap   = Turno::pluck('nome', 'id');

        $lista = [];
        foreach ($this->turnosSelecionados as $combo) {
            $partes = explode('-', (string) $combo);
            if (count($partes) !== 3) continue;

            [$uId, $cId, $tId] = $partes;
            $lista[] = [
                'key'          => $combo,
                'unidade_id'   => (int) $uId,
                'unidade_nome' => $unidadesMap[$uId] ?? "Unidade #{$uId}",
                'curso_id'     => (int) $cId,
                'curso_nome'   => $cursosMap[$cId] ?? "Curso #{$cId}",
                'turno_id'     => (int) $tId,
                'turno_nome'   => $turnosMap[$tId] ?? "Turno #{$tId}",
            ];
        }

        return $lista;
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
            'name'            => 'required|string|min:3|max:255',
            'email'           => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
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
            'roleName'                          => 'required|string|exists:roles,name',
            'travar_por_vinculo'                => 'boolean',
            'turnosSelecionados'                => $this->travar_por_vinculo ? 'required|array|min:1' : 'nullable|array',
            'centrosCustoEdicaoSelecionados'    => 'nullable|array',
            'centrosCustoAprovacaoSelecionados' => 'nullable|array',
            'acessoGlobalEdicao'                => 'boolean',
            'acessoGlobalAprovacao'             => 'boolean',
        ];

        $messages = [
            'roleName.required'           => 'Selecione um Grupo Principal (Role) para o usuário.',
            'turnosSelecionados.required' => 'Como a opção "Travar por vínculo" está ativa, selecione pelo menos uma combinação de Unidade, Curso e Turno.',
            'turnosSelecionados.min'      => 'Selecione pelo menos uma combinação completa de Unidade, Curso e Turno.',
        ];

        $this->validate($rules, $messages);
    }

    public function updatedRoleName()
    {
        $this->carregarPermissoesDaRole();
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
                'id'          => $perm->id,
                'name'        => $perm->name,
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

    public function save()
    {
        $this->validarEtapa1();
        $this->validarEtapa2();

        $data = [
            'name'            => $this->name,
            'email'           => strtolower(trim($this->email)),
            'codigo_protheus' => $this->codigo_protheus,
            'slug'            => Str::slug($this->name),
        ];

        if (Schema::hasColumn('users', 'travar_por_vinculo')) {
            $data['travar_por_vinculo'] = (bool) $this->travar_por_vinculo;
        }

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

        // 2. Sincroniza Combinações Exatas (Unidade + Curso + Turno + Prazo) na tabela user_vinculos_academicos
        if (Schema::hasTable('user_vinculos_academicos')) {
            DB::table('user_vinculos_academicos')->where('user_id', $user->id)->delete();

            if ($this->travar_por_vinculo && !empty($this->turnosSelecionados)) {
                $rowsInsert = [];
                foreach (array_unique($this->turnosSelecionados) as $combo) {
                    $partes = explode('-', (string) $combo);
                    if (count($partes) !== 3) continue;

                    [$uId, $cId, $tId] = $partes;
                    $expira = !empty($this->expiracoesVinculos[$combo]) ? $this->expiracoesVinculos[$combo] : null;

                    $rowsInsert[] = [
                        'user_id'    => $user->id,
                        'unidade_id' => (int) $uId,
                        'curso_id'   => (int) $cId,
                        'turno_id'   => (int) $tId,
                        'expires_at' => $expira,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($rowsInsert)) {
                    DB::table('user_vinculos_academicos')->insert($rowsInsert);
                }
            }
        }

        // Mantém sincronia nas tabelas auxiliares para compatibilidade
        if ($this->travar_por_vinculo && !empty($this->turnosSelecionados)) {
            $unidadesUnicas = collect($this->turnosSelecionados)->map(fn($v) => (int) (explode('-', (string) $v)[0] ?? 0))->unique()->filter()->values()->toArray();
            $cursosUnicos   = collect($this->turnosSelecionados)->map(fn($v) => (int) (explode('-', (string) $v)[1] ?? 0))->unique()->filter()->values()->toArray();
            $turnosUnicos   = collect($this->turnosSelecionados)->map(fn($v) => (int) (explode('-', (string) $v)[2] ?? 0))->unique()->filter()->values()->toArray();

            $user->unidades()->sync($unidadesUnicas);
            $user->cursos()->sync($cursosUnicos);
            $user->turnos()->sync($turnosUnicos);
        } else {
            $user->unidades()->detach();
            $user->cursos()->detach();
            $user->turnos()->detach();
        }

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

        // 4. Sincroniza Permissões Extras + Permissões Globais de Centro de Custo
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
        $this->travar_por_vinculo = (bool) ($user->travar_por_vinculo ?? false);

        $this->roleName = $user->roles->first()?->name ?? '';
        $this->carregarPermissoesDaRole();

        // Carrega as combinações exatas de user_vinculos_academicos
        $vinculosSalvos = Schema::hasTable('user_vinculos_academicos')
            ? DB::table('user_vinculos_academicos')->where('user_id', $user->id)->get()
            : collect();

        if ($vinculosSalvos->isNotEmpty()) {
            $this->travar_por_vinculo = true;
            foreach ($vinculosSalvos as $v) {
                $uId = (string) $v->unidade_id;
                $cId = (string) $v->curso_id;
                $tId = (string) $v->turno_id;
                $comboKey = "{$uId}-{$cId}-{$tId}";

                if (!in_array($uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = $uId;
                }
                if (!in_array("{$uId}-{$cId}", $this->cursosSelecionados, true)) {
                    $this->cursosSelecionados[] = "{$uId}-{$cId}";
                }
                if (!in_array($comboKey, $this->turnosSelecionados, true)) {
                    $this->turnosSelecionados[] = $comboKey;
                }
                if (!empty($v->expires_at)) {
                    $this->expiracoesVinculos[$comboKey] = \Carbon\Carbon::parse($v->expires_at)->format('Y-m-d');
                }
            }
        } elseif ($user->unidades->isNotEmpty()) {
            // Fallback para registros antigos que só estavam nas tabelas separadas
            $this->travar_por_vinculo = true;
            foreach ($user->unidades as $u) {
                $this->unidadesSelecionadas[] = (string) $u->id;
                foreach ($user->cursos as $c) {
                    $this->cursosSelecionados[] = "{$u->id}-{$c->id}";
                    foreach ($user->turnos as $t) {
                        $this->turnosSelecionados[] = "{$u->id}-{$c->id}-{$t->id}";
                    }
                }
            }
        }

        if (!empty($this->unidadesSelecionadas)) {
            $this->activeUnidadeId = (int) $this->unidadesSelecionadas[0];
            $primeiroCurso = collect($this->cursosSelecionados)->first(fn($c) => str_starts_with($c, "{$this->activeUnidadeId}-"));
            if ($primeiroCurso) {
                [, $cAtivo] = explode('-', $primeiroCurso, 2);
                $this->activeCursoId = (int) $cAtivo;
            }
        }

        $this->acessoGlobalEdicao = $user->permissions->contains('name', 'financeiro.visao_global.edicao')
            || $user->permissions->contains('name', 'financeiro.visao_global');
        $this->acessoGlobalAprovacao = $user->permissions->contains('name', 'financeiro.visao_global.aprovacao')
            || $user->permissions->contains('name', 'financeiro.visao_global');

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

        $this->travar_por_vinculo = false;
        $this->unidadesSelecionadas = [];
        $this->cursosSelecionados = [];
        $this->turnosSelecionados = [];
        $this->expiracoesVinculos = [];
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

        $combinacoesAtivas = $user->obterCombinacoesVinculoAtivas();
        $escopoAcademico = $user->possuiTravaPorVinculoAtiva()
            ? count($combinacoesAtivas) . ' combinação(ões) ativa(s) (Restrito por Vínculo)'
            : 'Global (Sem trava por vínculo)';

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
            'Nome Completo'          => $user->name,
            'E-mail'                 => $user->email,
            'Código Protheus'        => $user->codigo_protheus ?: 'Não vinculado',
            'Grupo Principal (Role)' => strtoupper($user->roles->first()?->name ?? 'Sem grupo'),
            'Escopo Acadêmico'       => $escopoAcademico,
            'C. Custo (Edição)'      => $ccEdicao,
            'C. Custo (Aprovação)'   => $ccAprovacao,
            'Permissões Extras'      => $qtdExtras > 0 ? "{$qtdExtras} permissão(ões) avulsa(s)" : 'Nenhuma (Apenas Role)',
            'Criado em'              => $user->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title'    => 'Perfil do Usuário',
            'icon'     => 'ph-user-circle',
            'data'     => $detalhes,
            'subtitle' => 'Visualização rápida de dados e escopo',
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
            'registros'           => $query->paginate($this->porPagina),
            'roles'               => Role::orderBy('name')->get(),
            'todasUnidades'       => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb'            => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'todosCentrosCusto'   => CentroCusto::where('disponivel_orcamento', true)->orderBy('codigo')->get(),
            'permissionsByModule' => $permissoesQuery->get()->groupBy('module'),
        ]);
    }
}