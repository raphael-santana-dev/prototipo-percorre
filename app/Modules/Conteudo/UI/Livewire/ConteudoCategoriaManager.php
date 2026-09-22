<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Models\ConteudoCategoria;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Categorias de Conteúdo - Administrativo')]
class ConteudoCategoriaManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $modalAberto = false;
    public $isEditMode = false;
    public $categoriaId = null;
    public $nome = '';

    public $filtro_busca = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso Restrito');
        
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Gestão de Conteúdo', 'url' => '#'],
            ['label' => 'Categorias', 'url' => '#'],
        ];
        $this->permiteGrid = false;
    }

    public function updatingFiltroBusca()
    {
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome da Categoria', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function abrirModal()
    {
        $this->resetValidation();
        $this->reset(['categoriaId', 'nome', 'isEditMode']);
        $this->modalAberto = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $categoria = ConteudoCategoria::findOrFail($id);
        
        $this->categoriaId = $categoria->id;
        $this->nome = $categoria->nome;
        
        $this->isEditMode = true;
        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|string|max:255|unique:conteudo_categorias,nome,' . $this->categoriaId,
        ]);

        ConteudoCategoria::updateOrCreate(
            ['id' => $this->categoriaId],
            [
                'nome' => $this->nome,
                'slug' => Str::slug($this->nome),
            ]
        );

        $this->modalAberto = false;
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!');
    }

    public function excluir($id)
    {
        $categoria = ConteudoCategoria::findOrFail($id);
        
        if ($categoria->conteudos()->count() > 0) {
            $this->dispatch('erro', msg: 'Ação negada: Esta categoria possui conteúdos vinculados a ela.');
            return;
        }

        $categoria->delete();
        $this->dispatch('sucesso', msg: 'Categoria excluída permanentemente!');
    }

    public function toggleStatus($id)
    {
        $categoria = ConteudoCategoria::findOrFail($id);
        $categoria->is_active = !$categoria->is_active;
        $categoria->save();
        
        $this->dispatch('sucesso', msg: 'Status alterado com sucesso!');
    }

    public function render()
    {
        $query = ConteudoCategoria::withCount('conteudos');

        if (!empty($this->filtro_busca)) {
            $query->where('nome', 'ilike', '%' . $this->filtro_busca . '%');
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('nome', 'asc');
        }

        return view('livewire.conteudo.categoria-manager', [
            'registros' => $query->paginate($this->porPagina ?? 15)
        ]);
    }
}