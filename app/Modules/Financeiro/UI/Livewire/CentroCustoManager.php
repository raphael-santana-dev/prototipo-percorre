<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\CentroCusto;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Centros de Custo - Financeiro')]
class CentroCustoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public string $filtroCodigo = '';
    public string $filtroNome = '';
    public string $filtroDisponivel = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('financeiro.centro_custo.listar'), 403, 'O módulo de centros de custo está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.listar'), 403, 'Sem permissão.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Centros de Custo', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'codigo';
        $this->ordenacaoDirecao = 'asc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroCodigo', 'filtroNome', 'filtroDisponivel'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroCodigo', 'filtroNome', 'filtroDisponivel']);
        $this->resetPage();
    }

    public function sincronizarProtheus()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.sincronizar') && !auth()->user()->can('financeiro.centro_custo.listar'), 403, 'Sem permissão.');

        $resultado = ProtheusOrcamentoService::sincronizarCentrosCusto();
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
            ['key' => 'nome', 'label' => 'Nome', 'sortable' => true],
            ['key' => 'disponivel_orcamento', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center w-56'],
        ];
    }

    public function render()
    {
        $query = CentroCusto::query();

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo', 'ilike', '%' . trim($this->filtroCodigo) . '%');
        }
        if (!empty($this->filtroNome)) {
            $query->where('nome', 'ilike', '%' . trim($this->filtroNome) . '%');
        }
        if ($this->filtroDisponivel !== '') {
            $query->where('disponivel_orcamento', (bool) $this->filtroDisponivel);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('codigo', 'asc');
        }

        return view('livewire.financeiro.centro-custo-manager', [
            'registros' => $query->paginate($this->porPagina)
        ]);
    }
}