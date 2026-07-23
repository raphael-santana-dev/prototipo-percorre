<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\ACL\Domain\Models\Permission;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Permissões - Administrativo')]
class PermissionManager extends Component
{
    public string $module = '';
    public string $name = '';
    public string $description = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito.');
    }

    public function save()
    {
        $this->name = strtolower(trim($this->name));
        $this->module = strtolower(trim($this->module));

        $this->validate([
            'module' => 'required|string|min:2',
            'name' => 'required|string|min:3|unique:permissions,name',
            'description' => 'required|string|max:255',
        ]);

        Permission::create([
            'module' => $this->module,
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => 'web'
        ]);

        $this->reset(['name', 'description']); 
        // Mantemos o módulo preenchido para agilizar cadastros seguidos
    }

    public function delete(int $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
    }

    public function render()
    {
        $permissionsByModule = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('livewire.acl.permission-manager', [
            'permissionsByModule' => $permissionsByModule
        ]);
    }
}