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

    public function mount(int $roleId)
    {
        abort_if(!feature('acl.role.permissoes'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.permissoes'), 403);

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

    /**
     * AUTO-SAVE: É disparado em tempo real sempre que um Toggle é clicado na View
     */
    public function updatedSelectedPermissions()
    {
        abort_if(!feature('acl.role.permissoes'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.permissoes'), 403);

        $role = Role::findOrFail($this->roleId);
        
        // Sincroniza o array exato de toggles ativados com a tabela pivô do Spatie ACL
        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('sucesso', msg: 'Permissões atualizadas e salvas automaticamente!');
    }

    public function render()
    {
        // Utilizamos paginate(1000) para satisfazer o <x-table> sem quebrar o agrupamento visual na tela
        $registros = Permission::orderBy('module')->orderBy('name')->paginate(1000);
        
        $permissionsByModule = collect($registros->items())->groupBy('module');

        return view('livewire.acl.role-permission-manager', [
            'registros' => $registros,
            'permissionsByModule' => $permissionsByModule
        ]);
    }
}