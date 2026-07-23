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
    public string $module = '';
    public string $name = '';
    public string $description = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    public function addFeature(FeatureService $service)
    {
        $this->validate([
            'module' => 'required|string|min:2',
            'name' => 'required|string|min:3|unique:features,name',
            'description' => 'required|string|max:255',
        ]);

        $service->create($this->module, $this->name, $this->description);
        $this->reset(['name', 'description']); 
        // Mantemos o $module sem resetar para facilitar o cadastro em lote no mesmo módulo
    }

    public function toggle(FeatureService $service, string $name, bool $currentStatus)
    {
        $service->toggle($name, !$currentStatus);
    }

    public function render()
    {
        // Retornamos as features agrupadas pelo módulo para a View
        $featuresByModule = Feature::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('livewire.feature-toggle.feature-manager', [
            'featuresByModule' => $featuresByModule
        ]);
    }
}