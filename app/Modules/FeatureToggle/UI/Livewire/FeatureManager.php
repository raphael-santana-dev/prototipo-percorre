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

#[Layout('components.layouts.app')]
#[Title('Gerenciar Features - Administrativo')]
class FeatureManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;

    public $modalAberto = false;
    public $featureId = null;

    public $modelClass = Feature::class;
    public array $breadcrumbs = [];

    // Filtros
    public $filtro_module = '';
    public $filtro_keyword = '';
    public $filtro_status = '';

    // Array para inserção múltipla / edição
    public array $items = [];

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
        $this->reset(['featureId', 'items']);

        if ($id) {
            $feature = Feature::findOrFail($id);
            $this->featureId = $feature->id;
            
            $nameParts = explode('.', $feature->name, 2);
            $this->items[0] = [
                'module' => $feature->module,
                'action' => $nameParts[1] ?? $feature->name,
                'description' => $feature->description
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

    // Injeção de dependência do Service gerenciado pelo Laravel
    public function salvar(FeatureService $featureService)
    {
        $this->validate([
            'items.*.module' => 'required|string|min:2',
            'items.*.action' => 'required|string|min:2',
            'items.*.description' => 'required|string|max:255',
        ], [
            'items.*.module.required' => 'Módulo é obrigatório.',
            'items.*.action.required' => 'Ação é obrigatória.',
            'items.*.description.required' => 'Descrição é obrigatória.',
        ]);

        foreach ($this->items as $index => $item) {
            $moduleFinal = strtolower(trim($item['module']));
            $actionFinal = strtolower(trim($item['action']));
            $fullName = $moduleFinal . '.' . $actionFinal;

            if ($this->featureId) {
                // Modo Edição (Apenas 1 item é processado)
                if (Feature::where('name', $fullName)->where('id', '!=', $this->featureId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta feature já está cadastrada.');
                    return;
                }

                $feature = Feature::findOrFail($this->featureId);
                $nomeAntigo = $feature->getOriginal('name');
                
                $feature->update([
                    'module' => $moduleFinal,
                    'name' => $fullName,
                    'description' => $item['description'],
                ]);

                Cache::forget("feature_status_{$nomeAntigo}");
                Cache::forget("feature_status_{$fullName}");
            } else {
                // Modo Criação Múltipla via Service (DDD)
                if (Feature::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", "A feature {$fullName} já existe.");
                    continue; 
                }

                $featureService->create($moduleFinal, $fullName, $item['description']);
            }
        }

        $this->fecharModal();
        session()->flash('sucesso', $this->featureId ? 'Feature atualizada!' : 'Features cadastradas com sucesso!');
    }

    public function excluir($id)
    {
        $feature = Feature::findOrFail($id);
        $featureName = $feature->name;
        $feature->delete();
        
        Cache::forget("feature_status_{$featureName}");
        session()->flash('sucesso', 'Feature excluída com sucesso.');
    }

    public function toggleStatus($id)
    {
        $feature = Feature::findOrFail($id);
        $service = app(FeatureService::class);
        
        // Passa a responsabilidade de salvar e limpar cache para o Service
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

        $features = $query->paginate($this->porPagina);
        $modulosDisponiveis = Feature::select('module')->distinct()->orderBy('module')->pluck('module');

        return view('livewire.feature-toggle.feature-manager', [
            'registros' => $features,
            'modulosDisponiveis' => $modulosDisponiveis
        ]);
    }
}