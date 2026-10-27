<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Naturezas Financeiras - Financeiro')]
class NaturezaManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public string $filtroCodigo = '';
    public string $filtroDescricao = '';
    public string $filtroDisponivel = '';
    public string $filtroTipo = '';

    public array $breadcrumbs = [];

    public array $tipos = [
        'DESPESA' => 'Despesa',
        'RECEITA' => 'Receita',
    ];

    public function mount()
    {
        abort_if(!feature('financeiro.natureza.listar'), 403, 'O módulo de naturezas financeiras está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.listar'), 403, 'Sem permissão.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Naturezas Financeiras', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'codigo';
        $this->ordenacaoDirecao = 'asc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroCodigo', 'filtroDescricao', 'filtroDisponivel', 'filtroTipo'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroCodigo', 'filtroDescricao', 'filtroDisponivel', 'filtroTipo']);
        $this->resetPage();
    }

    public function sincronizarProtheus()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.sincronizar') && !auth()->user()->can('financeiro.natureza.listar'), 403, 'Sem permissão.');

        $resultado = ProtheusOrcamentoService::sincronizarNaturezas();
        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: $resultado['mensagem']);
        } else {
            $this->dispatch('erro', msg: $resultado['mensagem']);
        }
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'class' => 'w-20'],
            ['key' => 'codigo', 'label' => 'Código', 'sortable' => true, 'class' => 'w-36'],
            ['key' => 'descricao', 'label' => 'Nome / Descrição', 'sortable' => true],
            ['key' => 'categoria', 'label' => 'Categoria', 'sortable' => true],
            ['key' => 'tipo', 'label' => 'Tipo', 'sortable' => true, 'class' => 'w-28 text-center'],
            ['key' => 'disponivel_orcamento', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center w-56'],
        ];
    }

    public function render()
    {
        $query = Natureza::query();

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo', 'ilike', '%' . trim($this->filtroCodigo) . '%');
        }
        if (!empty($this->filtroDescricao)) {
            $query->where('descricao', 'ilike', '%' . trim($this->filtroDescricao) . '%');
        }
        if (!empty($this->filtroTipo)) {
            $query->where('tipo', 'ilike', '%' . trim($this->filtroTipo) . '%');
        }
        if ($this->filtroDisponivel !== '') {
            $query->where('disponivel_orcamento', (bool) $this->filtroDisponivel);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('codigo', 'asc');
        }

        return view('livewire.financeiro.natureza-manager', [
            'registros' => $query->paginate($this->porPagina),
            'tipos'     => $this->tipos,
        ]);
    }
}