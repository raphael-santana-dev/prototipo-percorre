<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;

class QuickViewDrawer extends Component
{
    public string $title = 'Detalhes';
    public string $icon = 'ph-info';
    public string $subtitle = '';
    
    public array $data = []; 
    public string $maxWidth = 'md';
    public bool $allowFullscreen = false;

    #[On('load-quick-view')]
    public function loadData(array $payload)
    {
        $this->title = $payload['title'] ?? 'Detalhes';
        $this->icon = $payload['icon'] ?? 'ph-info';
        $this->data = $payload['data'] ?? [];
        $this->subtitle = $payload['subtitle'] ?? '';
        $this->maxWidth = $payload['maxWidth'] ?? 'md';
        $this->allowFullscreen = $payload['allowFullscreen'] ?? false;

        $this->dispatch('show-quick-view-drawer');
    }

    public function render()
    {
        return view('livewire.components.quick-view-drawer');
    }
}