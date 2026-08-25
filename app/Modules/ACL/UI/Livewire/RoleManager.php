<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Spatie\Permission\Models\Role;

use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Roles - Administrativo')]
class RoleManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $roleId = null;

    public array $items = [];

    public string $modelClass = Role::class;
    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('acl.role.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.listar'), 403, 'Acesso restrito.');
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function openModal()
    {
        abort_if(!feature('acl.role.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.criar'), 403);

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
        abort_if(!feature('acl.role.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.editar'), 403);

        $this->resetInputFields();
        $role = Role::findOrFail($id);
        
        if ($role->name === 'dev' && !auth()->user()->hasRole('dev')) {
            $this->dispatch('erro', msg: 'A Role DEV não pode ser alterada.');
            return;
        }

        $this->roleId = $role->id;
        $this->isEditMode = true;
        $this->items[0] = ['name' => $role->name];
        
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!feature('acl.role.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.editar'), 403);
        } else {
            abort_if(!feature('acl.role.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.criar'), 403);
        }

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
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Grupo atualizado!' : 'Grupos cadastrados com sucesso!');
    }

    public function delete(int $id)
    {
        abort_if(!feature('acl.role.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.role.excluir'), 403);
        
        $role = Role::findOrFail($id);
        
        if (in_array($role->name, ['dev', 'admin'])) {
            $this->dispatch('erro', msg: 'Grupos base do sistema não podem ser excluídos.');
            return;
        }

        $role->delete();
        $this->dispatch('sucesso', msg: 'Grupo excluído com sucesso.');
    }

    private function resetInputFields()
    {
        $this->items = [];
        $this->isEditMode = false;
        $this->roleId = null;
        $this->resetErrorBag();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Nome do Grupo', 'sortable' => true],
            ['key' => 'users_count', 'label' => 'Usuários Vinculados', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Role::query()->withCount('users');
        
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('name', 'asc');
        }

        return view('livewire.acl.role-manager', [
            'registros' => $query->paginate($this->porPagina)
        ]);
    }
}