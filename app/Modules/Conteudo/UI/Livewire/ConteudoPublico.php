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
    public $noticiasFundo = [];

    public function mount($slug)
    {
        $this->conteudo = Conteudo::with(['categoria', 'autor'])
            ->publicados()->autorizado()->where('slug', $slug)->firstOrFail();

        $opcoes = is_string($this->conteudo->opcoes_visuais) 
            ? json_decode($this->conteudo->opcoes_visuais, true) 
            : ($this->conteudo->opcoes_visuais ?? []);
            
        $this->slides = $opcoes['slides'] ?? [];

        // Captura o grid para o fundo borrado se for Story
        if ($this->conteudo->tipo === 'story') {
            $this->noticiasFundo = Conteudo::publicados()->autorizado()->where('tipo', 'padrao')->latest()->take(6)->get();
        }
    }

    public function render()
    {
        // Define dinamicamente o título da página na aba do navegador
        return view('livewire.conteudo.conteudo-publico')
            ->title($this->conteudo->titulo . ' - Portal Editorial');
    }
}