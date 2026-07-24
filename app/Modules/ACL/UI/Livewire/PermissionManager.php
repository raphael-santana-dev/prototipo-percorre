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
    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $permissionId = null;

    // O Array mágico que permite inserir 1 ou 50 registros de uma vez
    public array $items = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
    }

    public function openModal()
    {
        $this->resetInputFields();
        // Inicia com pelo menos uma linha vazia
        $this->items = [['module' => '', 'action' => '', 'description' => '']];
        $this->showModal = true;
    }

    // Adiciona uma nova linha no frontend instantaneamente
    public function addItem()
    {
        $this->items[] = ['module' => '', 'action' => '', 'description' => ''];
    }

    // Remove uma linha específica
    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reordena os índices numéricos
    }

    public function edit(int $id)
    {
        $this->resetInputFields();
        $permission = Permission::findOrFail($id);
        
        $this->permissionId = $permission->id;
        $this->isEditMode = true;
        
        // Na edição, separamos o nome (ex: "curso.listar") para preencher o formulário
        $nameParts = explode('.', $permission->name, 2);
        
        $this->items[0] = [
            'module' => $permission->module,
            'action' => $nameParts[1] ?? $permission->name,
            'description' => $permission->description
        ];
        
        $this->showModal = true;
    }

    public function save()
    {
        // Valida todo o array de uma vez
        $this->validate([
            'items.*.module' => 'required|string|min:2',
            'items.*.action' => 'required|string|min:2',
            'items.*.description' => 'required|string|max:255',
        ], [
            'items.*.module.required' => 'O módulo é obrigatório.',
            'items.*.action.required' => 'A ação é obrigatória.',
        ]);

        foreach ($this->items as $index => $item) {
            $module = strtolower(trim($item['module']));
            $action = strtolower(trim($item['action']));
            $fullName = $module . '.' . $action;

            // Se for edição
            if ($this->isEditMode && $this->permissionId) {
                // Verifica se a nova chave já existe em OUTRA permissão
                if (Permission::where('name', $fullName)->where('id', '!=', $this->permissionId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta chave já existe.');
                    return;
                }
                
                Permission::where('id', $this->permissionId)->update([
                    'module' => $module,
                    'name' => $fullName,
                    'description' => $item['description']
                ]);
            } 
            // Se for criação em lote
            else {
                if (Permission::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", 'A permissão '.$fullName.' já existe.');
                    continue; // Pula essa, mas salva as outras (ou pode dar return para barrar tudo)
                }

                Permission::create([
                    'module' => $module,
                    'name' => $fullName,
                    'description' => $item['description'],
                    'guard_name' => 'web'
                ]);
            }
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Permissão atualizada!' : 'Permissões cadastradas com sucesso!');
    }

    public function delete(int $id)
    {
        Permission::findOrFail($id)->delete();
        session()->flash('success', 'Permissão excluída.');
    }

    private function resetInputFields()
    {
        $this->items = [];
        $this->isEditMode = false;
        $this->permissionId = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.acl.permission-manager', [
            'permissionsByModule' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module')
        ]);
    }
}