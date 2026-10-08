<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;

#[Layout('components.layouts.app')]
#[Title('Aprovações de Orçamentos - Financeiro')]
class OrcamentoAprovacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroStatus = 'Finalizado'; 
    public $filtroCentroCusto = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        // abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Financeiro', 'url' => '#'],
            ['label' => 'Aprovações', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'updated_at';
        $this->ordenacaoDirecao = 'desc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroStatus', 'filtroCentroCusto'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial', 'filtroCentroCusto']);
        $this->filtroStatus = 'Finalizado';
        $this->resetPage();
    }

    // Redireciona para o Editor Excel
    public function avaliarOrcamento($id)
    {
        return redirect()->route('financeiro.aprovacoes.editor', $id);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true],
            ['key' => 'ccusto', 'label' => 'Centro de Custo', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status Global', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Solicitado', 'sortable' => false, 'class' => 'text-right font-bold uppercase text-gray-400 text-[10px]'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    protected function obterQueryFiltrada()
    {
        $query = Orcamento::with('centroCusto')->whereIn('status', ['Finalizado', 'Aprovado', 'Reprovado', 'Aprovado com ressalvas']);
        
        if (!empty($this->filtroAno)) $query->where('ano', $this->filtroAno);
        if (!empty($this->filtroFilial)) $query->where('filial', 'ilike', '%' . $this->filtroFilial . '%');
        if (!empty($this->filtroStatus)) $query->where('status', $this->filtroStatus);
        if (!empty($this->filtroCentroCusto)) $query->where('ccusto', 'ilike', '%' . $this->filtroCentroCusto . '%');

        return $query;
    }

    public function render()
    {
        $query = $this->obterQueryFiltrada();

        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        else $query->orderBy('updated_at', 'desc');

        return view('livewire.financeiro.orcamento-aprovacao-manager', [
            'registros' => $query->paginate($this->porPagina),
            'totalAguardando' => Orcamento::where('status', 'Finalizado')->count()
        ]);
    }
}