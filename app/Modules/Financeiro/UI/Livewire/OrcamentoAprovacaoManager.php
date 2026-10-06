<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Aprovações de Orçamentos - Financeiro')]
class OrcamentoAprovacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroNatureza = '';
    public $filtroStatus = 'Finalizado'; // Por padrão, mostra apenas os aguardando aprovação

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;

    public bool $modalAvaliacaoAberto = false;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Aprovações', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'updated_at';
        $this->ordenacaoDirecao = 'desc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial', 'filtroNatureza']);
        $this->filtroStatus = 'Finalizado';
        $this->resetPage();
    }

    public function abrirModalDetalhes($id)
    {
        $this->orcamentoSelecionado = Orcamento::with('avaliacoes')->findOrFail($id);
        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->orcamentoSelecionado = null;
    }

    public function abrirModalAvaliacao($status)
    {
        $this->statusAvaliacao = $status;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacao()
    {
        $this->validate(
            ['comentarioAvaliacao' => 'required|string|min:5'], 
            ['comentarioAvaliacao.required' => 'A justificativa é obrigatória para aprovar ou reprovar.']
        );

        $this->orcamentoSelecionado->update(['status' => $this->statusAvaliacao]);

        OrcamentoAvaliacao::create([
            'orcamento_id' => $this->orcamentoSelecionado->id,
            'user_id' => auth()->id(),
            'user_nome' => auth()->user()->name,
            'status_aplicado' => $this->statusAvaliacao,
            'comentario' => $this->comentarioAvaliacao
        ]);

        $this->modalAvaliacaoAberto = false;
        $this->orcamentoSelecionado->refresh();
        $this->dispatch('sucesso', msg: "Orçamento avaliado como {$this->statusAvaliacao} com sucesso!");
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true],
            ['key' => 'natureza', 'label' => 'Natureza', 'sortable' => true],
            ['key' => 'ccusto', 'label' => 'C. Custo', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Previsto', 'sortable' => false, 'class' => 'text-right font-bold'],
            ['key' => 'acoes', 'label' => 'Avaliar', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    protected function obterQueryFiltrada()
    {
        // Pega orçamentos que já foram enviados para a aprovação ou que já foram avaliados
        $query = Orcamento::whereIn('status', ['Finalizado', 'Aprovado', 'Reprovado', 'Aprovado com ressalvas']);
        
        if (!empty($this->filtroAno)) $query->where('ano', $this->filtroAno);
        if (!empty($this->filtroFilial)) $query->where('filial', 'ilike', '%' . $this->filtroFilial . '%');
        if (!empty($this->filtroNatureza)) $query->where('natureza', 'ilike', '%' . $this->filtroNatureza . '%');
        if (!empty($this->filtroStatus)) $query->where('status', $this->filtroStatus);

        return $query;
    }

    public function render()
    {
        $query = $this->obterQueryFiltrada();

        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        else $query->orderBy('id', 'desc');

        return view('livewire.financeiro.orcamento-aprovacao-manager', [
            'registros' => $query->paginate($this->porPagina),
            'totalAguardando' => Orcamento::where('status', 'Finalizado')->count()
        ]);
    }
}