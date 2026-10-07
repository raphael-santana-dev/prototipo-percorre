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
    public $filtroStatus = 'Finalizado'; // Mostra apenas os que aguardam aprovação por defeito

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;

    // --- VARIÁVEIS DA AVALIAÇÃO POR ITEM ---
    public bool $modalAvaliacaoAberto = false;
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';
    // ---------------------------------------

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
        // Traz as linhas, naturezas associadas e logs da diretoria associados a cada item
        $this->orcamentoSelecionado = Orcamento::with(['itens.natureza', 'itens.avaliacoes.usuario', 'centroCusto'])->findOrFail($id);
        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->orcamentoSelecionado = null;
    }

    // --- MÉTODOS DE AVALIAÇÃO INDIVIDUAL (POR ITEM) ---
    public function abrirModalAvaliacaoItem($itemId, $statusDesejado)
    {
        $this->linhaParaAvaliar = $itemId;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacaoItem()
    {
        $this->validate(
            ['comentarioAvaliacao' => 'required|string|min:5'], 
            ['comentarioAvaliacao.required' => 'A justificativa é obrigatória para comunicar a decisão ao gestor.']
        );

        $item = OrcamentoItem::findOrFail($this->linhaParaAvaliar);
        
        // 1. Atualiza o status específico da Natureza (Linha)
        $item->update(['status' => $this->statusAvaliacao]);

        // 2. Regista a auditoria de avaliação vinculada à linha e não ao cabeçalho
        OrcamentoAvaliacao::create([
            'orcamento_item_id' => $item->id,
            'user_id' => auth()->id(),
            'user_nome' => auth()->user()->name,
            'status_aplicado' => $this->statusAvaliacao,
            'comentario' => $this->comentarioAvaliacao
        ]);

        // 3. Se a diretoria reprovar ou colocar ressalvas num único item, 
        // força o orçamento global daquele Centro de Custo a voltar para "Em elaboração"
        if (in_array($this->statusAvaliacao, ['Reprovado', 'Aprovado com ressalvas'])) {
            $item->orcamento->update(['status' => 'Em elaboração']);
        }

        $this->modalAvaliacaoAberto = false;
        $this->dispatch('sucesso', msg: "Linha avaliada como {$this->statusAvaliacao}. O Gestor será notificado.");
        
        // Atualiza a view do modal mantendo-o aberto para o Diretor continuar a avaliar as restantes naturezas
        $this->abrirModalDetalhes($item->orcamento_id);
    }
    // --------------------------------------------------

    public function getHeadersProperty()
    {
        // A listagem agora reflete o Centro de Custo Mestre (O Agrupador)
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