<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Traits\ComPadraoListagem;

class Listagem extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';

    public function getHeadersProperty()
    {
        return [
            ['key' => 'ano', 'label' => 'Ano / Ciclo', 'sortable' => true],
            ['key' => 'data_inicio', 'label' => 'Período', 'sortable' => true],
            ['key' => 'fases_count', 'label' => 'Fases', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function updatingBusca() { $this->resetPage(); }

    public function excluir($id)
    {
        $periodo = PeriodoAvaliacao::withCount('fases')->findOrFail($id);
        
        if ($periodo->status === '1') {
            $this->dispatch('erro', msg: 'Não é possível excluir um período que está aberto.');
            return;
        }

        $periodo->delete();
        $this->dispatch('sucesso', msg: 'Período movido para a lixeira.');
    }

    public function render()
    {
        $query = PeriodoAvaliacao::withCount('fases')
            ->where('ano', 'ilike', '%' . $this->busca . '%');

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('ano', 'desc')->orderBy('ciclo', 'desc');
        }

        return view('livewire.gestao-educacional.periodo-avaliacao.listagem', [
            'registros' => $query->paginate($this->porPagina),
        ])->layout('components.layouts.app', ['title' => 'Períodos de Avaliação']);
    }
}