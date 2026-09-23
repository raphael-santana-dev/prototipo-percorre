<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;

#[Layout('components.layouts.public')]
#[Title('Portal de Notícias - Instituto Percorre')]
class PortalNoticias extends Component
{
    use WithPagination;

    public $categoriaFiltro = '';
    public $termoBusca = '';

    public function updating($prop)
    {
        if (in_array($prop, ['categoriaFiltro', 'termoBusca'])) {
            $this->resetPage();
        }
    }

    public function setCategoria($id)
    {
        $this->categoriaFiltro = $this->categoriaFiltro === (string)$id ? '' : $id;
        $this->resetPage();
    }

    public function render()
    {
        $destaques = collect();
        $stories = collect();
        $carrosseis = collect();

        // Se não houver filtros aplicados, carregamos as vitrines exclusivas
        if (empty($this->categoriaFiltro) && empty($this->termoBusca)) {
            $destaques = Conteudo::with('categoria')->publicados()->autorizado()
                ->where('is_destaque', true)->orderBy('ordem_destaque', 'asc')->get();

            $stories = Conteudo::with('categoria')->publicados()->autorizado()
                ->where('tipo', 'story')->where('is_destaque', false)
                ->orderBy('data_inicio', 'desc')->orderBy('created_at', 'desc')->get();

            $carrosseis = Conteudo::with('categoria')->publicados()->autorizado()
                ->where('tipo', 'carrossel')->where('is_destaque', false)
                ->orderBy('data_inicio', 'desc')->orderBy('created_at', 'desc')->get();
        }

        // CARREGA A GRELHA PADRÃO DE NOTÍCIAS
        $query = Conteudo::with('categoria')->publicados()->autorizado();

        if (empty($this->categoriaFiltro) && empty($this->termoBusca)) {
            $query->where('is_destaque', false)->where('tipo', 'padrao');
        }

        if (!empty($this->categoriaFiltro)) $query->where('categoria_id', $this->categoriaFiltro);
        if (!empty($this->termoBusca)) $query->where('titulo', 'ilike', '%' . $this->termoBusca . '%');

        $noticias = $query->orderBy('data_inicio', 'desc')->orderBy('created_at', 'desc')->paginate(12);
        $categoriasDb = ConteudoCategoria::where('is_active', true)->orderBy('nome')->get();

        return view('livewire.conteudo.portal-noticias', [
            'destaques' => $destaques,
            'stories' => $stories,
            'carrosseis' => $carrosseis,
            'noticias' => $noticias,
            'categoriasDb' => $categoriasDb,
        ]);
    }
}