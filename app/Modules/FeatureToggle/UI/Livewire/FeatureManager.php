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
    public string $name = '';
    public string $description = '';

    public function mount()
    {
        // Trava de segurança absoluta: Apenas a role 'dev' (minúsculo) acessa esta tela
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    public function addFeature(FeatureService $service)
    {
        $this->validate([
            'name' => 'required|string|min:3|unique:features,name',
            'description' => 'required|string|max:255',
        ]);

        $service->create($this->name, $this->description);
        $this->reset(['name', 'description']);
    }

    public function toggle(FeatureService $service, string $name, bool $currentStatus)
    {
        // Inverte o status atual e salva
        $service->toggle($name, !$currentStatus);
    }

    public function render()
    {
        return view('livewire.feature-toggle.feature-manager', [
            'features' => Feature::orderBy('name')->get()
        ]);
    }
}