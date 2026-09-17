<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\ACL\Domain\Models\Permission;
use App\Modules\FeatureToggle\Domain\Models\Feature; // NOVO
use App\Modules\FeatureToggle\Application\Services\FeatureService; // NOVO
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Facades\Cache; // NOVO

#[Layout('components.layouts.app')]
#[Title('Gerenciar Permissões - Administrativo')]
class PermissionManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $modalAberto = false;
    public $permissionId = null;

    public $modelClass = Permission::class;
    public array $breadcrumbs = [];
     
    public $filtro_module = '';
    public $filtro_keyword = '';

    public array $items = [];
    public bool $replicar_para_features = false; // NOVO: Campo do Checkbox

    public function mount()
    {
        abort_if(!feature('acl.permissao.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.listar'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
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

    public function abrirModal($id = null)
    {
        if ($id) {
            abort_if(!feature('acl.permissao.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.editar'), 403);
        } else {
            abort_if(!feature('acl.permissao.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.criar'), 403);
        }

        $this->resetValidation();
        $this->reset(['permissionId', 'items', 'replicar_para_features']);

        if ($id) {
            $permission = Permission::findOrFail($id);
            $this->permissionId = $permission->id;
            
            $nameParts = explode('.', $permission->name, 2);
            $this->items[0] = [
                'module' => $permission->module,
                'action' => $nameParts[1] ?? $permission->name,
                'description' => $permission->description
            ];
        } else {
            $this->items = [['module' => '', 'action' => '', 'description' => '']];
        }

        $this->modalAberto = true;
    }

    public function addItem()
    {
        $this->items[] = ['module' => '', 'action' => '', 'description' => ''];
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
    }

    // NOVO: Injeção de dependência do FeatureService no método de salvar
    public function salvar(FeatureService $featureService)
    {
        if ($this->permissionId) {
            abort_if(!feature('acl.permissao.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.editar'), 403);
        } else {
            abort_if(!feature('acl.permissao.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.criar'), 403);
        }

        $this->validate([
            'items.*.module' => 'required|string|min:2',
            'items.*.action' => 'required|string|min:2',
            'items.*.description' => 'required|string|max:255',
        ], [
            'items.*.module.required' => 'O módulo é obrigatório.',
            'items.*.action.required' => 'A ação é obrigatória.',
            'items.*.description.required' => 'A descrição é obrigatória.',
        ]);

        foreach ($this->items as $index => $item) {
            $moduleFinal = strtolower(trim($item['module']));
            $actionFinal = strtolower(trim($item['action']));
            $fullName = $moduleFinal . '.' . $actionFinal;

            if ($this->permissionId) {
                if (Permission::where('name', $fullName)->where('id', '!=', $this->permissionId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta permissão já existe.');
                    return;
                }
                
                Permission::where('id', $this->permissionId)->update([
                    'module' => $moduleFinal,
                    'name' => $fullName,
                    'description' => $item['description']
                ]);
            } else {
                if (Permission::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", "A permissão {$fullName} já existe.");
                    continue; 
                }

                Permission::create([
                    'module' => $moduleFinal,
                    'name' => $fullName,
                    'description' => $item['description'],
                    'guard_name' => 'web'
                ]);
            }

            // NOVO: Replicação inteligente para o Módulo de Feature Toggles
            if ($this->replicar_para_features) {
                $featureExistente = Feature::where('name', $fullName)->first();
                
                if ($featureExistente) {
                    $featureExistente->update([
                        'module' => $moduleFinal,
                        'description' => $item['description']
                    ]);
                    Cache::forget("feature_status_{$fullName}");
                } else {
                    $featureService->create($moduleFinal, $fullName, $item['description']);
                }
            }
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $this->permissionId ? 'Permissão atualizada!' : 'Permissões cadastradas com sucesso!');
    }

    // ... (restante do código intocado: métodos excluir, getHeadersProperty e render)
    
    public function excluir($id)
    {
        abort_if(!feature('acl.permissao.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('acl.permissao.excluir'), 403);
        
        Permission::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Permissão excluída com sucesso.');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'module', 'label' => 'Módulo', 'sortable' => true],
            ['key' => 'name', 'label' => 'Permissão', 'sortable' => true],
            ['key' => 'description', 'label' => 'Descrição', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Permission::query()
            ->when($this->filtro_module, fn($q) => $q->where('module', $this->filtro_module))
            ->when($this->filtro_keyword, function($q) {
                $q->where(function($subQ) {
                    $subQ->where('name', 'like', "%{$this->filtro_keyword}%")
                         ->orWhere('description', 'like', "%{$this->filtro_keyword}%");
                });
            });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('module', 'asc')->orderBy('name', 'asc');
        }

        $permissions = $query->paginate($this->porPagina);
        $modulosDisponiveis = Permission::select('module')->distinct()->orderBy('module')->pluck('module');

        return view('livewire.acl.permission-manager', [
            'registros' => $permissions,
            'modulosDisponiveis' => $modulosDisponiveis
        ]);
    }
}