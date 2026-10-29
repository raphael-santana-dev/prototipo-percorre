<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Spatie\Permission\Models\Role;
use App\Modules\ACL\Domain\Models\Permission;
use App\Helpers\BreadcrumbHelper;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Permissões da Role - Administrativo')]
class RolePermissionManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public int $roleId;
    public string $roleName;
    
    public array $selectedPermissions = [];
    public array $breadcrumbs = [];

    // Filtros
    public string $filtro_keyword = '';
    public string $filtro_module = '';

    public function mount(int $roleId)
    {
        //abort_if(!feature('acl.role.permissoes'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.permissoes'), 403);

        $role = Role::findOrFail($roleId);
        $this->roleId = $role->id;
        $this->roleName = $role->name;

        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Grupos (Roles)', 'url' => route('roles.index')],
            ['label' => 'Permissões: ' . strtoupper($this->roleName), 'url' => '#'],
        ];

        $this->permiteGrid = false;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'module', 'label' => 'Módulo', 'sortable' => false, 'class' => 'w-32'],
            ['key' => 'submodule', 'label' => 'Submódulo', 'sortable' => false, 'class' => 'w-40'],
            ['key' => 'description', 'label' => 'Descrição (Agrupada)', 'sortable' => false, 'class' => 'min-w-[150px]'],
            ['key' => 'actions', 'label' => 'Permissões (Ações)', 'sortable' => false, 'class' => 'text-right'],
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
        //abort_if(!feature('acl.role.permissoes'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.permissoes'), 403);

        $role = Role::findOrFail($this->roleId);
        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('sucesso', msg: 'Permissões atualizadas e salvas automaticamente!');
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

        return view('livewire.acl.role-permission-manager', [
            'registros' => $registros,
            'permissionsByModule' => $permissionsByModule,
            'modulosDisponiveis' => $modulosDisponiveis
        ]);
    }
}