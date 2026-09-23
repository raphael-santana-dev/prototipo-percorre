<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Conteudo;
use Illuminate\Support\Facades\Session;

#[Layout('components.layouts.public')]
class ConteudoPublico extends Component
{
    public Conteudo $conteudo;
    public array $slides = [];

    public function mount($slug)
    {
        $this->conteudo = Conteudo::with(['categoria', 'autor'])
            ->publicados()
            ->autorizado()
            ->where('slug', $slug)
            ->firstOrFail();

        // MOTOR DE VISUALIZAÇÃO: Contabiliza apenas se não estiver na sessão
        $sessionKey = 'viewed_conteudo_' . $this->conteudo->id;
        if (!Session::has($sessionKey)) {
            $this->conteudo->increment('visualizacoes');
            Session::put($sessionKey, true);
        }

        $opcoes = is_string($this->conteudo->opcoes_visuais) 
            ? json_decode($this->conteudo->opcoes_visuais, true) 
            : ($this->conteudo->opcoes_visuais ?? []);
            
        $this->slides = $opcoes['slides'] ?? [];
    }

    public function render()
    {
        // Movido para dentro do render (resolve o erro de count() e tipagem)
        $noticiasFundo = collect();
        if ($this->conteudo->tipo === 'story') {
            $noticiasFundo = Conteudo::publicados()->autorizado()->where('tipo', 'padrao')->latest()->take(6)->get();
        }

        $topLidas = Conteudo::with('categoria')
            ->publicados()
            ->autorizado()
            ->where('id', '!=', $this->conteudo->id)
            ->where('tipo', 'padrao') 
            ->orderBy('visualizacoes', 'desc')
            ->orderBy('data_inicio', 'desc')
            ->take(5)
            ->get();

        return view('livewire.conteudo.conteudo-publico', [
            'noticiasFundo' => $noticiasFundo,
            'topLidas' => $topLidas
        ])->title($this->conteudo->titulo . ' - Portal Editorial');
    }
}