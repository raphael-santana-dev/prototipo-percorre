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
    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $roleId = null;

    // Array para inserção múltipla, contendo apenas o 'name'
    public array $items = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->items = [['name' => '']];
        $this->showModal = true;
    }

    public function addItem()
    {
        $this->items[] = ['name' => ''];
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function edit(int $id)
    {
        $this->resetInputFields();
        $role = Role::findOrFail($id);
        
        if ($role->name === 'dev' && !auth()->user()->hasRole('dev')) {
            session()->flash('error', 'A Role DEV não pode ser alterada.');
            return;
        }

        $this->roleId = $role->id;
        $this->isEditMode = true;
        $this->items[0] = ['name' => $role->name];
        
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'items.*.name' => 'required|string|min:3|max:255',
        ], [
            'items.*.name.required' => 'O nome do grupo é obrigatório.',
        ]);

        foreach ($this->items as $index => $item) {
            $name = strtolower(trim($item['name']));

            if ($this->isEditMode && $this->roleId) {
                if (Role::where('name', $name)->where('id', '!=', $this->roleId)->exists()) {
                    $this->addError("items.{$index}.name", 'Este grupo já existe.');
                    return;
                }
                
                Role::where('id', $this->roleId)->update(['name' => $name]);
            } else {
                if (Role::where('name', $name)->exists()) {
                    $this->addError("items.{$index}.name", 'O grupo '.$name.' já existe.');
                    continue; 
                }

                Role::create(['name' => $name, 'guard_name' => 'web']);
            }
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Grupo atualizado!' : 'Grupos cadastrados com sucesso!');
    }

    public function delete(int $id)
    {
        $role = Role::findOrFail($id);
        
        if (in_array($role->name, ['dev', 'admin'])) {
            session()->flash('error', 'Grupos base do sistema não podem ser excluídos.');
            return;
        }

        $role->delete();
        session()->flash('success', 'Grupo excluído com sucesso.');
    }

    private function resetInputFields()
    {
        $this->items = [];
        $this->isEditMode = false;
        $this->roleId = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.acl.role-manager', [
            'roles' => Role::withCount('users')->orderBy('name')->get()
        ]);
    }
}