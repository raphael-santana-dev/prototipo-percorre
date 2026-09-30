<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Spatie\Permission\Models\Role;
use App\Modules\ACL\Domain\Models\Permission;

#[Layout('components.layouts.app')]
#[Title('Permissões da Role - Administrativo')]
class RolePermissionManager extends Component
{
    public int $roleId;
    public string $roleName;
    
    public array $selectedPermissions = [];

    public function mount(int $roleId)
    {
        abort_if(!feature('acl.role.permissoes'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.permissoes'), 403);

        $role = Role::findOrFail($roleId);
        $this->roleId = $role->id;
        $this->roleName = $role->name;

        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function save()
    {
        $role = Role::findOrFail($this->roleId);
        
        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('sucesso', msg: 'Permissões atualizadas com sucesso!');
    }

    public function render()
    {
        $permissionsByModule = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('livewire.acl.role-permission-manager', [
            'permissionsByModule' => $permissionsByModule
        ]);
    }
}