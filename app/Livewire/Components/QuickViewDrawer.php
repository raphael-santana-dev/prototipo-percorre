<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;

class QuickViewDrawer extends Component
{
    public string $title = 'Detalhes';
    public string $icon = 'ph-info';
    public string $subtitle = '';
    
    // O array $data receberá pares de chave/valor, ex: ['E-mail' => 'teste@teste.com', 'Status' => 'Ativo']
    public array $data = []; 

    /**
     * Ouve o evento global 'load-quick-view' de qualquer lugar do sistema
     */
    #[On('load-quick-view')]
    public function loadData(array $payload)
    {
        // Usa o null coalescing operator (??) para garantir valores padrão caso algo falhe
        $this->title = $payload['title'] ?? 'Detalhes';
        $this->icon = $payload['icon'] ?? 'ph-info';
        $this->data = $payload['data'] ?? [];
        $this->subtitle = $payload['subtitle'] ?? '';

        $this->dispatch('show-quick-view-drawer');
    }

    public function render()
    {
        return view('livewire.components.quick-view-drawer');
    }
}