<?php

namespace App\Modules\FeatureToggle\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use App\Modules\FeatureToggle\Application\Services\FeatureService;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use Illuminate\Support\Facades\Cache;
use App\Modules\ACL\Domain\Models\Permission;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Features - Administrativo')]
class FeatureManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public $modalConfirmacaoAberto = false;
    public $featureId = null;

    public $modelClass = Feature::class;
    public array $breadcrumbs = [];

    public $filtro_module = '';
    public $filtro_keyword = '';
    public $filtro_status = '';

    public array $items = [];
    public bool $replicar_para_permissoes = false;
    public array $pendenciasReplicacao = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_module', 'filtro_keyword', 'filtro_status'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_module', 'filtro_keyword', 'filtro_status']);
        $this->resetPage();
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['featureId', 'items', 'replicar_para_permissoes', 'modalConfirmacaoAberto', 'pendenciasReplicacao']);

        if ($id) {
            $feature = Feature::findOrFail($id);
            $this->featureId = $feature->id;
            
            $parts = explode('.', $feature->name);
            $action = array_pop($parts); 
            $module = array_shift($parts); 
            $submodule = implode('.', $parts);

            $this->items[0] = [
                'module' => $module ?: $feature->module,
                'submodule' => $submodule,
                'action' => $action,
                'description' => $feature->description
            ];
        } else {
            $this->items = [['module' => '', 'submodule' => '', 'action' => '', 'description' => '']];
        }

        $this->modalAberto = true;
    }

    public function addItem()
    {
        $this->items[] = ['module' => '', 'submodule' => '', 'action' => '', 'description' => ''];
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
        $this->validate([
            'items.*.module' => 'required|string|min:2',
            'items.*.submodule' => 'nullable|string',
            'items.*.action' => 'required|string|min:2',
        ], [
            'items.*.module.required' => 'Módulo é obrigatório.',
            'items.*.action.required' => 'Ação é obrigatória.',
        ]);

        $this->pendenciasReplicacao = [];

        if ($this->replicar_para_permissoes) {
            foreach ($this->items as $item) {
                $nameParts = array_filter([trim($item['module']), trim($item['submodule'] ?? ''), trim($item['action'])]);
                $fullName = strtolower(implode('.', $nameParts));
                $correspondenciaEncontrada = false;

                if ($this->featureId) {
                    $featureAntiga = Feature::find($this->featureId);
                    if ($featureAntiga) {
                        $nomeAntigo = $featureAntiga->getOriginal('name');
                        if (Permission::where('name', $nomeAntigo)->exists()) {
                            $correspondenciaEncontrada = true;
                        }
                    }
                }

                if (!$correspondenciaEncontrada && !Permission::where('name', $fullName)->exists()) {
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
            $submoduleFinal = strtolower(trim($item['submodule'] ?? ''));
            $actionFinal = strtolower(trim($item['action']));

            $nameParts = array_filter([$moduleFinal, $submoduleFinal, $actionFinal]);
            $fullName = implode('.', $nameParts);

            $moduleColumnParts = array_filter([$moduleFinal, $submoduleFinal]);
            $moduleColumn = implode('.', $moduleColumnParts);

            $nomeAntigo = null;

            if ($this->featureId) {
                if (Feature::where('name', $fullName)->where('id', '!=', $this->featureId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta feature já está cadastrada.');
                    return;
                }

                $feature = Feature::findOrFail($this->featureId);
                $nomeAntigo = $feature->getOriginal('name');
                
                $feature->update([
                    'module' => $moduleColumn,
                    'name' => $fullName,
                    'description' => $item['description']
                ]);

                Cache::forget("feature_status_{$nomeAntigo}");
                Cache::forget("feature_status_{$fullName}");
            } else {
                if (Feature::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", "A feature {$fullName} já existe.");
                    continue; 
                }
                $featureService->create($moduleColumn, $fullName, $item['description']);
            }

            if ($this->replicar_para_permissoes) {
                $permissionTarget = null;
                
                if ($nomeAntigo) {
                    $permissionTarget = Permission::where('name', $nomeAntigo)->first();
                }
                
                if (!$permissionTarget) {
                    $permissionTarget = Permission::where('name', $fullName)->first();
                }
                
                if ($permissionTarget) {
                    $permissionTarget->update([
                        'name' => $fullName,
                        'module' => $moduleColumn,
                        'description' => $item['description']
                    ]);
                } elseif ($criarAusentes) {
                    Permission::create([
                        'name' => $fullName,
                        'guard_name' => 'web',
                        'module' => $moduleColumn,
                        'description' => $item['description']
                    ]);
                }
            }
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $this->featureId ? 'Feature e Permissão atualizadas!' : 'Cadastros realizados com sucesso!');
    }

    public function excluir($id)
    {
        $feature = Feature::findOrFail($id);
        $featureName = $feature->name;
        $feature->delete();
        
        Cache::forget("feature_status_{$featureName}");
        $this->dispatch('sucesso', msg: 'Feature excluída com sucesso.');
    }

    public function toggleStatus($id)
    {
        $feature = Feature::findOrFail($id);
        $service = app(FeatureService::class);
        $service->toggle($feature->name, !$feature->is_active);
        $this->dispatch('sucesso', msg: 'Status da feature alterado!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'module', 'label' => 'Módulo', 'sortable' => true],
            ['key' => 'name', 'label' => 'Feature', 'sortable' => true],
            ['key' => 'description', 'label' => 'Descrição', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Feature::query()
            ->when($this->filtro_module, fn($q) => $q->where('module', $this->filtro_module))
            ->when($this->filtro_status !== '', fn($q) => $q->where('is_active', $this->filtro_status))
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

        return view('livewire.feature-toggle.feature-manager', [
            'registros' => $query->paginate($this->porPagina),
            'modulosDisponiveis' => Feature::select('module')->distinct()->orderBy('module')->pluck('module')
        ]);
    }
}