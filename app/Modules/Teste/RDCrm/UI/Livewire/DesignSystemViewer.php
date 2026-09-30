<?php

namespace App\Modules\Teste\RDCrm\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Design System - Visualizador')]
class DesignSystemViewer extends Component
{
    public array $breadcrumbs = [];

    public function mount()
    {
        // Breadcrumbs seguindo o padrão do seu projeto
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Teste (RDCrm)', 'url' => '#'],
            ['label' => 'Design System', 'url' => '#'],
        ];
    }

    public function render()
    {
        // Renderiza a view que deve ser criada na pasta de resources correspondente
        return view('livewire.teste.rd-crm.design-system-viewer');
    }
}