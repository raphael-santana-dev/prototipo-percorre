<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Aprovações de Orçamentos - Financeiro')]
class OrcamentoAprovacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroStatus = 'Finalizado'; 

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;

    // --- VARIÁVEIS DA AVALIAÇÃO POR ITEM / LOTE ---
    public bool $modalAvaliacaoAberto = false;
    public bool $isAvaliacaoLote = false;
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';
    // ----------------------------------------------

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');

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
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroStatus'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial']);
        $this->filtroStatus = 'Finalizado';
        $this->resetPage();
    }

    public function abrirModalDetalhes($id)
    {
        $this->orcamentoSelecionado = Orcamento::with(['itens.natureza', 'itens.avaliacoes.usuario', 'centroCusto'])->findOrFail($id);
        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->orcamentoSelecionado = null;
    }

    // Abre modal para UMA linha
    public function abrirModalAvaliacaoItem($itemId, $statusDesejado)
    {
        $item = OrcamentoItem::find($itemId);
        
        if (in_array($item->status, ['Aprovado', 'Aprovado com ressalvas', 'Reprovado'])) {
            $this->dispatch('erro', msg: 'Este item já foi avaliado e não pode ser alterado até que o gestor o reenvie.');
            return;
        }

        $this->isAvaliacaoLote = false;
        $this->linhaParaAvaliar = $itemId;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    // Abre modal para TODAS as linhas pendentes
    public function abrirModalAvaliacaoLote($statusDesejado)
    {
        $this->isAvaliacaoLote = true;
        $this->linhaParaAvaliar = null;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    // Processa a avaliação (seja de 1 item ou de todos)
    public function confirmarAvaliacao()
    {
        $this->validate(
            ['comentarioAvaliacao' => 'required|string|min:5'], 
            ['comentarioAvaliacao.required' => 'A justificativa é obrigatória para comunicar a decisão ao gestor.']
        );

        if ($this->isAvaliacaoLote) {
            $itensPendentes = $this->orcamentoSelecionado->itens()->whereIn('status', ['Criado', 'Corrigido'])->get();
            
            if ($itensPendentes->isEmpty()) {
                $this->dispatch('erro', msg: 'Não há naturezas pendentes de avaliação neste orçamento.');
                $this->modalAvaliacaoAberto = false;
                return;
            }

            foreach ($itensPendentes as $item) {
                $item->update(['status' => $this->statusAvaliacao]);
                OrcamentoAvaliacao::create([
                    'orcamento_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'user_nome' => auth()->user()->name,
                    'status_aplicado' => $this->statusAvaliacao,
                    'comentario' => $this->comentarioAvaliacao
                ]);
            }
        } else {
            $item = OrcamentoItem::findOrFail($this->linhaParaAvaliar);
            $item->update(['status' => $this->statusAvaliacao]);
            OrcamentoAvaliacao::create([
                'orcamento_item_id' => $item->id,
                'user_id' => auth()->id(),
                'user_nome' => auth()->user()->name,
                'status_aplicado' => $this->statusAvaliacao,
                'comentario' => $this->comentarioAvaliacao
            ]);
        }

        // INTELIGÊNCIA DE STATUS GLOBAL DO ORÇAMENTO
        $orcamento = $this->orcamentoSelecionado;
        
        $temReprovado = $orcamento->itens()->whereIn('status', ['Reprovado', 'Aprovado com ressalvas'])->exists();
        $temPendente = $orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->exists();

        if ($temReprovado) {
            $orcamento->update(['status' => 'Em elaboração']);
        } elseif (!$temPendente) {
            $orcamento->update(['status' => 'Aprovado']);
        }

        $this->modalAvaliacaoAberto = false;
        $msg = $this->isAvaliacaoLote ? "Todos os itens pendentes avaliados como {$this->statusAvaliacao}." : "Linha avaliada como {$this->statusAvaliacao}.";
        $this->dispatch('sucesso', msg: $msg);
        
        // Recarrega a view
        $this->abrirModalDetalhes($orcamento->id);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true],
            ['key' => 'ccusto', 'label' => 'Centro de Custo', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status Global', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Solicitado', 'sortable' => false, 'class' => 'text-right font-bold'],
            ['key' => 'acoes', 'label' => 'Analisar', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    protected function obterQueryFiltrada()
    {
        $query = Orcamento::with('centroCusto')->whereIn('status', ['Finalizado', 'Aprovado', 'Reprovado', 'Aprovado com ressalvas']);
        
        if (!empty($this->filtroAno)) $query->where('ano', $this->filtroAno);
        if (!empty($this->filtroFilial)) $query->where('filial', 'ilike', '%' . $this->filtroFilial . '%');
        if (!empty($this->filtroStatus)) $query->where('status', $this->filtroStatus);

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