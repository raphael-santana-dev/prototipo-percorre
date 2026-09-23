<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;

#[Layout('components.layouts.app')]
#[Title('Gerir Conteúdos - Administrativo')]
class ConteudoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    // Filtros
    public $filtro_busca = '';
    public $filtro_categoria = '';
    public $filtro_status = '';
    public $filtro_tipo = '';
    public $filtro_publico = '';

    // Modal de Destaque
    public $modalDestaqueAberto = false;
    public $conteudoDestaqueId = null;
    public $ordemDestaque = 1;

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso Restrito');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Gestão de Conteúdo', 'url' => '#'],
            ['label' => 'Publicações', 'url' => '#'],
        ];
        $this->permiteGrid = false;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_categoria', 'filtro_status', 'filtro_tipo', 'filtro_publico'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_categoria', 'filtro_status', 'filtro_tipo', 'filtro_publico']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'imagem', 'label' => 'Capa', 'sortable' => false, 'class' => 'w-16 text-center'],
            ['key' => 'titulo', 'label' => 'Conteúdo / Categoria', 'sortable' => true],
            ['key' => 'tipo', 'label' => 'Formato & Público', 'sortable' => false],
            ['key' => 'periodo', 'label' => 'Período', 'sortable' => false],
            ['key' => 'is_destaque', 'label' => 'Destaque', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-36'],
        ];
    }

    public function toggleStatus($id)
    {
        $conteudo = Conteudo::findOrFail($id);
        $conteudo->is_active = !$conteudo->is_active;
        $conteudo->save();
        $this->dispatch('sucesso', msg: 'Status do conteúdo alterado!');
    }

    public function excluir($id)
    {
        $conteudo = Conteudo::findOrFail($id);
        $conteudo->delete();
        $this->dispatch('sucesso', msg: 'Conteúdo movido para a lixeira!');
    }

    // ==== LÓGICA DE DESTAQUES ====

    public function abrirModalDestaque($id)
    {
        $conteudo = Conteudo::findOrFail($id);
        $this->conteudoDestaqueId = $conteudo->id;
        $this->ordemDestaque = $conteudo->ordem_destaque ?? 1;
        $this->modalDestaqueAberto = true;
    }

    public function salvarDestaque()
    {
        $this->validate([
            'ordemDestaque' => 'required|integer|min:1|max:5',
        ]);

        $conteudo = Conteudo::findOrFail($this->conteudoDestaqueId);

        // Validação: Máximo de 5 destaques
        $totalDestaques = Conteudo::where('is_destaque', true)
                                  ->where('id', '!=', $conteudo->id)
                                  ->count();

        if (!$conteudo->is_destaque && $totalDestaques >= 5) {
            $this->dispatch('erro', msg: 'Já existem 5 conteúdos em destaque. Remova um destaque antes de adicionar este.');
            return;
        }

        // Se já existe algum com esta ordem, empurra-o (lógica opcional de reordenação) ou apenas sobrepõe
        // Para garantir consistência simples, atualizamos este.
        Conteudo::where('is_destaque', true)
                ->where('ordem_destaque', $this->ordemDestaque)
                ->where('id', '!=', $conteudo->id)
                ->update(['ordem_destaque' => null, 'is_destaque' => false]); // Remove do destaque quem ocupava a posição

        $conteudo->update([
            'is_destaque' => true,
            'ordem_destaque' => $this->ordemDestaque
        ]);

        $this->modalDestaqueAberto = false;
        $this->dispatch('sucesso', msg: 'Destaque configurado com sucesso!');
    }

    public function removerDestaque($id)
    {
        Conteudo::where('id', $id)->update([
            'is_destaque' => false,
            'ordem_destaque' => null
        ]);
        $this->dispatch('sucesso', msg: 'Conteúdo removido dos destaques!');
    }

    public function render()
    {
        $query = Conteudo::with(['categoria', 'autor']);

        if (!empty($this->filtro_busca)) {
            $query->where('titulo', 'ilike', '%' . $this->filtro_busca . '%');
        }

        if (!empty($this->filtro_categoria)) {
            $query->where('categoria_id', $this->filtro_categoria);
        }

        if ($this->filtro_status !== '') {
            $query->where('is_active', $this->filtro_status);
        }

        if (!empty($this->filtro_tipo)) {
            $query->where('tipo', $this->filtro_tipo);
        }

        if (!empty($this->filtro_publico)) {
            // Usa procura num array JSON de forma segura no Postgres
            $query->whereJsonContains('publico_alvo', $this->filtro_publico);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return view('livewire.conteudo.conteudo-manager', [
            'registros' => $query->paginate($this->porPagina ?? 15),
            'categoriasDb' => ConteudoCategoria::where('is_active', true)->orderBy('nome')->get()
        ]);
    }
}