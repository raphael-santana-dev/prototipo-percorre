<?php

namespace App\Modules\ACL\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\ACL\Domain\Models\Permission;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use App\Modules\FeatureToggle\Application\Services\FeatureService;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Facades\Cache;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Permissões - Administrativo')]
class PermissionManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $modalAberto = false;
    public $modalConfirmacaoAberto = false;
    public $permissionId = null;

    public $modelClass = Permission::class;
    public array $breadcrumbs = [];
     
    public $filtro_module = '';
    public $filtro_keyword = '';

    public array $items = [];
    public bool $replicar_para_features = false;
    public array $pendenciasReplicacao = [];

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
        $this->reset(['permissionId', 'items', 'replicar_para_features', 'modalConfirmacaoAberto', 'pendenciasReplicacao']);

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
        $this->modalConfirmacaoAberto = false;
    }

    public function salvar()
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

        $this->pendenciasReplicacao = [];

        if ($this->replicar_para_features) {
            foreach ($this->items as $item) {
                $fullName = strtolower(trim($item['module'])) . '.' . strtolower(trim($item['action']));
                $correspondenciaEncontrada = false;

                if ($this->permissionId) {
                    $permissionAntiga = Permission::find($this->permissionId);
                    if ($permissionAntiga) {
                        $nomeAntigo = $permissionAntiga->getOriginal('name');
                        if (Feature::where('name', $nomeAntigo)->exists()) {
                            $correspondenciaEncontrada = true;
                        }
                    }
                }

                if (!$correspondenciaEncontrada && !Feature::where('name', $fullName)->exists()) {
                    $this->pendenciasReplicacao[] = $fullName;
                }
            }
        }

        if (!empty($this->pendenciasReplicacao)) {
            $this->modalConfirmacaoAberto = true;
            return;
        }

        $this->efetivarSalvar(true);
    }

    public function confirmarCriacaoAusentes()
    {
        $this->modalConfirmacaoAberto = false;
        $this->efetivarSalvar(true);
    }

    public function ignorarCriacaoAusentes()
    {
        $this->modalConfirmacaoAberto = false;
        $this->efetivarSalvar(false);
    }

    private function efetivarSalvar(bool $criarAusentes = true)
    {
        $featureService = app(FeatureService::class);

        foreach ($this->items as $index => $item) {
            $moduleFinal = strtolower(trim($item['module']));
            $actionFinal = strtolower(trim($item['action']));
            $fullName = $moduleFinal . '.' . $actionFinal;
            $nomeAntigo = null;

            if ($this->permissionId) {
                if (Permission::where('name', $fullName)->where('id', '!=', $this->permissionId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta permissão já existe.');
                    return;
                }
                
                $permission = Permission::findOrFail($this->permissionId);
                $nomeAntigo = $permission->getOriginal('name');

                $permission->update([
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

            if ($this->replicar_para_features) {
                $featureTarget = null;
                
                if ($nomeAntigo) {
                    $featureTarget = Feature::where('name', $nomeAntigo)->first();
                }
                
                if (!$featureTarget) {
                    $featureTarget = Feature::where('name', $fullName)->first();
                }
                
                if ($featureTarget) {
                    $nomeFeatureVelha = $featureTarget->name;

                    $featureTarget->update([
                        'name' => $fullName,
                        'module' => $moduleFinal,
                        'description' => $item['description']
                    ]);

                    Cache::forget("feature_status_{$nomeFeatureVelha}");
                    Cache::forget("feature_status_{$fullName}");
                } elseif ($criarAusentes) {
                    $featureService->create($moduleFinal, $fullName, $item['description']);
                }
            }
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $this->permissionId ? 'Permissão e Feature atualizadas!' : 'Cadastros realizados com sucesso!');
    }

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

        return view('livewire.acl.permission-manager', [
            'registros' => $query->paginate($this->porPagina),
            'modulosDisponiveis' => Permission::select('module')->distinct()->orderBy('module')->pluck('module')
        ]);
    }
}