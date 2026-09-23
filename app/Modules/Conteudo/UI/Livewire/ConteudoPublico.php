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
        // Busca o conteúdo ignorando as validações temporais do escopo "publicados()"
        // para podermos fazer a verificação de expiração manual abaixo.
        $conteudo = Conteudo::with(['categoria', 'autor'])
            ->autorizado()
            ->where('slug', $slug)
            ->firstOrFail();

        // MOTOR DE EXPIRAÇÃO: Se tiver data de expiração, a data já passou e o conteúdo ainda consta como ativo
        if ($conteudo->data_fim && $conteudo->data_fim->isPast() && $conteudo->is_active) {
            // Altera o status fisicamente na base de dados para inativo
            $conteudo->update(['is_active' => false]);
            // Interrompe o acesso com página 404
            abort(404);
        }

        // Se estiver inativo ou a data de início ainda estiver no futuro, bloqueia.
        if (!$conteudo->is_active || ($conteudo->data_inicio && $conteudo->data_inicio->isFuture())) {
            abort(404);
        }

        $this->conteudo = $conteudo;

        // MOTOR DE VISUALIZAÇÃO
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
        $noticiasFundo = collect();
        if ($this->conteudo->tipo === 'story') {
            $noticiasFundo = Conteudo::publicados()->autorizado()->where('tipo', 'padrao')->latest()->take(6)->get();
        }

        // Traz as Top 5 garantindo que estão publicadas e acessíveis
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
            'topLidas' => $topLidas ?? collect() // Fallback de segurança garantindo que nunca é nulo
        ])->title($this->conteudo->titulo . ' - Portal Editorial');
    }
}