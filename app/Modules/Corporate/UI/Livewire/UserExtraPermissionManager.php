<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Modules\ACL\Domain\Models\Permission;
use App\Helpers\BreadcrumbHelper;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Permissões Extras - Corporativo')]
class UserExtraPermissionManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public int $userId;
    public string $userName;
    
    public array $selectedPermissions = [];
    public array $expirations = [];
    public array $rolePermissions = [];
    public array $breadcrumbs = [];

    // Filtros
    public string $filtro_keyword = '';
    public string $filtro_module = '';

    public function mount(int $userId)
    {
        abort_if(!feature('usuario.permissoes_extras'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.permissoes_extras'), 403);

        $user = User::findOrFail($userId);
        
        if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
            abort(403, 'Você não tem privilégios para alterar as permissões de um usuário DEV.');
        }

        $this->userId = $user->id;
        $this->userName = $user->name;

        $this->rolePermissions = $user->getPermissionsViaRoles()->pluck('id')->toArray();

        $directPermissions = $user->permissions()->withPivot('expires_at')->get();

        foreach ($directPermissions as $perm) {
            $this->selectedPermissions[] = (string) $perm->id;
            if ($perm->pivot->expires_at) {
                $this->expirations[$perm->id] = date('Y-m-d', strtotime($perm->pivot->expires_at));
            }
        }

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Usuários', 'url' => route('users.index')],
            ['label' => 'Permissões Extras: ' . strtoupper($this->userName), 'url' => '#'],
        ];

        $this->permiteGrid = false;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'module', 'label' => 'Módulo', 'sortable' => false, 'class' => 'w-32'],
            ['key' => 'submodule', 'label' => 'Submódulo', 'sortable' => false, 'class' => 'w-40'],
            ['key' => 'description', 'label' => 'Descrição (Agrupada)', 'sortable' => false, 'class' => 'min-w-[150px]'],
            ['key' => 'actions', 'label' => 'Permissões Extras (Ações)', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_module', 'filtro_keyword'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_module', 'filtro_keyword']);
        $this->resetPage();
    }

    public function updatedSelectedPermissions()
    {
        $this->syncExtraPermissions();
    }

    public function updatedExpirations()
    {
        $this->syncExtraPermissions();
    }

    private function syncExtraPermissions()
    {
        abort_if(!feature('usuario.permissoes_extras'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('usuario.permissoes_extras'), 403);
        
        $user = User::findOrFail($this->userId);
        $syncData = [];
        $currentUser = auth()->user();
        $isDev = $currentUser->hasRole('dev');

        foreach ($this->selectedPermissions as $permissionId) {
            if (!$permissionId) continue;
            
            if (in_array((int) $permissionId, $this->rolePermissions)) {
                continue;
            }

            $permission = Permission::findById((int) $permissionId);

            if (!$isDev && !$currentUser->hasPermissionTo($permission->name)) {
                $this->dispatch('erro', msg:  "Você não tem autorização para conceder a permissão '{$permission->name}'.");
                return;
            }

            $expiresAt = !empty($this->expirations[$permissionId]) ? $this->expirations[$permissionId] : null;
            $syncData[$permissionId] = ['expires_at' => $expiresAt];
        }

        $user->permissions()->sync($syncData);
        $this->dispatch('sucesso', msg: 'Permissões extras guardadas automaticamente!');
    }

    public function render()
    {
        $query = Permission::query()
            ->when($this->filtro_module, fn($q) => $q->where('module', $this->filtro_module))
            ->when($this->filtro_keyword, function($q) {
                $q->where(function($subQ) {
                    $subQ->where('name', 'ilike', "%{$this->filtro_keyword}%")
                         ->orWhere('description', 'ilike', "%{$this->filtro_keyword}%");
                });
            });

        $registros = $query->orderBy('module')->orderBy('name')->paginate(1000);
        $permissionsByModule = collect($registros->items())->groupBy('module');
        $modulosDisponiveis = Permission::select('module')->distinct()->orderBy('module')->pluck('module');

        return view('livewire.corporate.user-extra-permission-manager', [
            'registros' => $registros,
            'permissionsByModule' => $permissionsByModule,
            'modulosDisponiveis' => $modulosDisponiveis
        ]);
    }
}