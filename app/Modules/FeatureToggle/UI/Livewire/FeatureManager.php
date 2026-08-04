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

    // Campos do Formulário
    public $module;
    public $action;
    public $description;
    public $is_active = false;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['featureId', 'module', 'action', 'description', 'is_active']);

        if ($id) {
            $feature = Feature::findOrFail($id);
            $this->featureId = $feature->id;
            
            $nameParts = explode('.', $feature->name, 2);
            $this->module = $feature->module;
            $this->action = $nameParts[1] ?? $feature->name;
            $this->description = $feature->description;
            $this->is_active = $feature->is_active;
        }

        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
    }

    // Injeção de dependência do Service gerenciado pelo Laravel
    public function salvar(FeatureService $featureService)
    {
        $this->validate([
            'module' => 'required|string|min:2',
            'action' => 'required|string|min:2',
            'description' => 'required|string|max:255',
        ]);

        $moduleFinal = strtolower(trim($this->module));
        $actionFinal = strtolower(trim($this->action));
        $fullName = $moduleFinal . '.' . $actionFinal;

        if (Feature::where('name', $fullName)->where('id', '!=', $this->featureId)->exists()) {
            $this->addError('action', 'Esta feature já está cadastrada no sistema.');
            return;
        }

        if ($this->featureId) {
            $feature = Feature::findOrFail($this->featureId);
            $nomeAntigo = $feature->getOriginal('name');
            
            $feature->update([
                'module' => $moduleFinal,
                'name' => $fullName,
                'description' => $this->description,
            ]);

            // Como o service original não tem update, limpamos os caches manualmente aqui
            Cache::forget("feature_status_{$nomeAntigo}");
            Cache::forget("feature_status_{$fullName}");
        } else {
            // Usa o service estritamente como manda a arquitetura para criar
            $featureService->create($moduleFinal, $fullName, $this->description);
        }

        $this->fecharModal();
        session()->flash('sucesso', 'Feature salva com sucesso!');
    }

    public function excluir($id)
    {
        $feature = Feature::findOrFail($id);
        $featureName = $feature->name;
        $feature->delete();
        
        Cache::forget("feature_status_{$featureName}");
        session()->flash('sucesso', 'Feature excluída com sucesso.');
    }

    /**
     * SOBRESCRITA DA TRAIT:
     * O componente chama toggleStatus pelo frontend, mas aqui nós sequestramos
     * a chamada para injetar no FeatureService e respeitar o cache do DDD.
     */
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
        $query = Feature::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('module', 'asc')->orderBy('name', 'asc');
        }

        $features = $query->paginate($this->porPagina);

        return view('livewire.feature-toggle.feature-manager', [
            'registros' => $features,
        ]);
    }
}