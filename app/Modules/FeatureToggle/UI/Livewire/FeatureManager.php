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
    public string $action = ''; // Substituído $name por $action
    public string $description = '';

    public function mount() { abort_if(!auth()->user()->hasRole('dev'), 403); }

    public function addFeature(FeatureService $service)
    {
        $this->module = strtolower(trim($this->module));
        $this->action = strtolower(trim($this->action));
        $fullName = $this->module . '.' . $this->action;

        $this->validate([
            'module' => 'required|string|min:2',
            'action' => 'required|string|min:2',
            'description' => 'required|string|max:255',
        ]);

        if (Feature::where('name', $fullName)->exists()) {
            $this->addError('action', 'Esta feature já está cadastrada neste módulo.');
            return;
        }

        $service->create($this->module, $fullName, $this->description);
        $this->reset(['action', 'description']); 
    }

    public function toggle(FeatureService $service, string $name, bool $currentStatus) {
        $service->toggle($name, !$currentStatus);
    }

    public function render() {
        return view('livewire.feature-toggle.feature-manager', [
            'featuresByModule' => Feature::orderBy('module')->orderBy('name')->get()->groupBy('module')
        ]);
    }
}