<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Roles - Administrativo')]
class RoleManager extends Component
{
    public string $name = '';
    public ?int $roleId = null;
    public bool $isEditMode = false;

    public function mount()
    {
        // Neste primeiro momento, apenas DEV gerencia Roles. 
        // No futuro, ADMIN com permissão específica também poderá.
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito.');
    }

    public function save()
    {
        $this->name = strtolower(trim($this->name)); // Força minúsculo

        $rules = [
            'name' => 'required|string|min:3|unique:roles,name' . ($this->roleId ? ',' . $this->roleId : '')
        ];

        $this->validate($rules);

        if ($this->isEditMode) {
            $role = Role::findOrFail($this->roleId);
            
            // Impede a renomeação das roles base
            if (in_array($role->name, ['dev', 'admin']) && $role->name !== $this->name) {
                $this->addError('name', 'Não é permitido renomear as roles nativas do sistema.');
                return;
            }

            $role->update(['name' => $this->name]);
        } else {
            Role::create(['name' => $this->name, 'guard_name' => 'web']);
        }

        $this->resetInputFields();
    }

    public function edit(int $id)
    {
        $role = Role::findOrFail($id);
        $this->roleId = $id;
        $this->name = $role->name;
        $this->isEditMode = true;
    }

    public function delete(int $id)
    {
        $role = Role::findOrFail($id);

        // Impede a exclusão das roles base
        if (in_array($role->name, ['dev', 'admin'])) {
            // Emite um alerta visual ou simplesmente ignora, aqui vamos apenas retornar
            return;
        }

        $role->delete();
        $this->resetInputFields();
    }

    public function cancel()
    {
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->roleId = null;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.acl.role-manager', [
            'roles' => Role::orderBy('name')->get()
        ]);
    }
}