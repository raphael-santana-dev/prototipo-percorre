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
    
    // Array que armazenará o nome das permissões marcadas nos checkboxes
    public array $selectedPermissions = [];

    public function mount(int $roleId)
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito.');

        $role = Role::findOrFail($roleId);
        $this->roleId = $role->id;
        $this->roleName = $role->name;

        // Preenche o array com as permissões que a role já possui no banco
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function save()
    {
        $role = Role::findOrFail($this->roleId);
        
        // O Sync apaga o que foi desmarcado e salva o que foi marcado automaticamente
        $role->syncPermissions($this->selectedPermissions);

        session()->flash('success', 'Permissões atualizadas com sucesso!');
    }

    public function render()
    {
        $permissionsByModule = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('livewire.acl.role-permission-manager', [
            'permissionsByModule' => $permissionsByModule
        ]);
    }
}