<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Conteudo;

#[Layout('components.layouts.public')]
class ConteudoPublico extends Component
{
    public Conteudo $conteudo;
    public array $slides = [];

    public function mount($slug)
    {
        // SEGURANÇA MÁXIMA: Os escopos publicados() e autorizado() validam a data, 
        // o status e os guards da sessão num único comando direto na base de dados.
        $this->conteudo = Conteudo::with(['categoria', 'autor'])
            ->publicados()
            ->autorizado()
            ->where('slug', $slug)
            ->firstOrFail();

        // Extrair os slides (caso seja formato Carrossel ou Story)
        $opcoes = is_string($this->conteudo->opcoes_visuais) 
            ? json_decode($this->conteudo->opcoes_visuais, true) 
            : ($this->conteudo->opcoes_visuais ?? []);
            
        $this->slides = $opcoes['slides'] ?? [];
    }

    public function render()
    {
        // Define dinamicamente o título da página na aba do navegador
        return view('livewire.conteudo.conteudo-publico')
            ->title($this->conteudo->titulo . ' - Portal Editorial');
    }
}