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
    public string $action = ''; // Substituímos o $name por $action
    public string $description = '';

    public function mount() { abort_if(!auth()->user()->hasRole('dev'), 403); }

    public function save()
    {
        $this->module = strtolower(trim($this->module));
        $this->action = strtolower(trim($this->action));
        
        // Concatenação mágica!
        $fullName = $this->module . '.' . $this->action;

        $this->validate([
            'module' => 'required|string|min:2',
            'action' => 'required|string|min:2',
            'description' => 'required|string|max:255',
        ]);

        // Validação manual de unicidade usando o nome concatenado
        if (Permission::where('name', $fullName)->exists()) {
            $this->addError('action', 'Esta ação já está cadastrada neste módulo.');
            return;
        }

        Permission::create([
            'module' => $this->module,
            'name' => $fullName,
            'description' => $this->description,
            'guard_name' => 'web'
        ]);

        $this->reset(['action', 'description']); 
    }

    public function delete(int $id) { Permission::findOrFail($id)->delete(); }

    public function render()
    {
        return view('livewire.acl.permission-manager', [
            'permissionsByModule' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module')
        ]);
    }
}