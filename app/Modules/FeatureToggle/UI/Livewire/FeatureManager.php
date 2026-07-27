<?php

namespace App\Modules\FeatureToggle\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use App\Modules\FeatureToggle\Application\Services\FeatureService;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Features - Administrativo')]
class FeatureManager extends Component
{
    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $featureId = null;

    // Array para inserção múltipla
    public array $items = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->items = [['module' => '', 'action' => '', 'description' => '']];
        $this->showModal = true;
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

    public function edit(int $id)
    {
        $this->resetInputFields();
        $feature = Feature::findOrFail($id);
        
        $this->featureId = $feature->id;
        $this->isEditMode = true;
        
        $nameParts = explode('.', $feature->name, 2);
        
        $this->items[0] = [
            'module' => $feature->module,
            'action' => $nameParts[1] ?? $feature->name,
            'description' => $feature->description
        ];
        
        $this->showModal = true;
    }

    public function save()
    {
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

            if ($this->isEditMode && $this->featureId) {
                if (Feature::where('name', $fullName)->where('id', '!=', $this->featureId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta feature já existe.');
                    return;
                }
                
                Feature::where('id', $this->featureId)->update([
                    'module' => $module,
                    'name' => $fullName,
                    'description' => $item['description']
                ]);
            } else {
                if (Feature::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", 'A feature '.$fullName.' já existe.');
                    continue; 
                }

                Feature::create([
                    'module' => $module,
                    'name' => $fullName,
                    'description' => $item['description'],
                    'is_active' => false // Nasce desligada por padrão
                ]);
            }
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Feature atualizada!' : 'Features cadastradas com sucesso!');
    }

    // Função mantida para o botão de ligar/desligar na listagem
    public function toggle(FeatureService $service, string $name, bool $currentStatus) 
    {
        $service->toggle($name, !$currentStatus);
    }

    public function delete(int $id)
    {
        Feature::findOrFail($id)->delete();
        session()->flash('success', 'Feature excluída.');
    }

    private function resetInputFields()
    {
        $this->items = [];
        $this->isEditMode = false;
        $this->featureId = null;
        $this->resetErrorBag();
    }

    public function render() 
    {
        return view('livewire.feature-toggle.feature-manager', [
            'featuresByModule' => Feature::orderBy('module')->orderBy('name')->get()->groupBy('module')
        ]);
    }
}