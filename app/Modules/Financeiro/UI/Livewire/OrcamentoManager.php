<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Orçamentos - Financeiro')]
class OrcamentoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroNatureza = '';
    public $filtroStatus = '';

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;
    
    public array $editData = [];
    public array $breadcrumbs = [];

    // Variáveis do Simulador
    public int $anoSimulacao = 2026;
    public int $mesSimulacaoAtual = 1; 
    public array $nomesMeses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];

    public function mount()
    {
        abort_if(!feature('financeiro.orcamentos.listagem'), 403, 'Acesso desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.listagem'), 403);

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Meus Orçamentos', 'url' => route('financeiro.orcamentos')],
        ];

        $this->ordenacaoCampo = 'ano';
        $this->ordenacaoDirecao = 'desc';
        
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
        $this->mesSimulacaoAtual = session('mes_simulacao_orcamento', date('n'));
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus'])) {
            $this->resetPage();
        }
        if ($nomePropriedade === 'mesSimulacaoAtual' || $nomePropriedade === 'anoSimulacao') {
            session(['mes_simulacao_orcamento' => $this->mesSimulacaoAtual, 'ano_simulacao_orcamento' => $this->anoSimulacao]);
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus']);
        $this->resetPage();
    }

    public function sincronizarProtheus()
    {
        $resultado = ProtheusOrcamentoService::sincronizar();
        if ($resultado['sucesso']) $this->dispatch('sucesso', msg: $resultado['mensagem']);
        else $this->dispatch('erro', msg: $resultado['mensagem']);
    }

    public function abrirModalDetalhes($id)
    {
        $this->orcamentoSelecionado = Orcamento::with('avaliacoes')->findOrFail($id);
        
        $this->editData = [
            'descricao_despesa' => $this->orcamentoSelecionado->descricao_despesa,
            'valor_jan' => $this->orcamentoSelecionado->valor_jan, 'valor_fev' => $this->orcamentoSelecionado->valor_fev,
            'valor_mar' => $this->orcamentoSelecionado->valor_mar, 'valor_abr' => $this->orcamentoSelecionado->valor_abr,
            'valor_mai' => $this->orcamentoSelecionado->valor_mai, 'valor_jun' => $this->orcamentoSelecionado->valor_jun,
            'valor_jul' => $this->orcamentoSelecionado->valor_jul, 'valor_ago' => $this->orcamentoSelecionado->valor_ago,
            'valor_set' => $this->orcamentoSelecionado->valor_set, 'valor_out' => $this->orcamentoSelecionado->valor_out,
            'valor_nov' => $this->orcamentoSelecionado->valor_nov, 'valor_dez' => $this->orcamentoSelecionado->valor_dez,
        ];
        
        $this->modalAberto = true;
    }

    public function salvarOrcamento()
    {
        $this->processarGravacao('Em elaboração', 'Orçamento salvo como rascunho com sucesso!');
    }

    public function finalizarOrcamento()
    {
        $this->processarGravacao('Finalizado', 'Orçamento submetido para aprovação!');
    }

    private function processarGravacao($statusDesejado, $mensagemSucesso)
    {
        if (in_array($this->orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas'])) {
            $this->dispatch('erro', msg: 'Orçamentos já aprovados não podem ser alterados.');
            return;
        }

        $novoStatus = $statusDesejado;
        if ($statusDesejado === 'Em elaboração' && !in_array($this->orcamentoSelecionado->status, ['Criado', 'Reprovado'])) {
            $novoStatus = $this->orcamentoSelecionado->status; 
        }

        $dadosUpdate = [
            'descricao_despesa' => $this->editData['descricao_despesa'] ?? null,
            'status' => $novoStatus,
        ];

        $meses = ['valor_jan', 'valor_fev', 'valor_mar', 'valor_abr', 'valor_mai', 'valor_jun', 'valor_jul', 'valor_ago', 'valor_set', 'valor_out', 'valor_nov', 'valor_dez'];
        foreach($meses as $mes) {
            $dadosUpdate[$mes] = empty($this->editData[$mes]) ? 0 : (float) $this->editData[$mes];
        }

        $this->orcamentoSelecionado->update($dadosUpdate);
        $this->dispatch('sucesso', msg: $mensagemSucesso);
        $this->fecharModal();
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->orcamentoSelecionado = null;
    }

    public function avancarAnoSimulacao()
    {
        $this->anoSimulacao++;
        $this->mesSimulacaoAtual = 1; // Reseta o mês para Janeiro
        session(['ano_simulacao_orcamento' => $this->anoSimulacao, 'mes_simulacao_orcamento' => 1]);
        $this->dispatch('sucesso', msg: "Ano avançado para {$this->anoSimulacao}! O ciclo mensal foi reiniciado.");
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
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    protected function obterQueryFiltrada()
    {
        $query = Orcamento::query();
        
        if (!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('financeiro.orcamentos.global')) {
            if (method_exists(auth()->user(), 'centros_de_custo')) {
                $query->whereIn('ccusto', auth()->user()->centros_de_custo->pluck('codigo')->toArray());
            } else {
                $query->where('ccusto', '00000000'); 
            }
        }

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

        $orcamentos = $query->paginate($this->porPagina);
        $totalAnualGeral = (clone $query)->get()->sum('valor_total');
        
        $metricas = [
            ['label' => 'Itens Listados (Seu CCusto)', 'value' => $query->count(), 'color_text' => 'text-blue-600', 'color_bg' => 'bg-blue-100'],
            ['label' => 'Previsão Total (Filtro)', 'value' => 'R$ ' . number_format($totalAnualGeral, 2, ',', '.'), 'color_text' => 'text-emerald-600', 'color_bg' => 'bg-emerald-100'],
        ];

        return view('livewire.financeiro.orcamento-manager', [
            'registros' => $orcamentos,
            'metricas' => $metricas
        ]);
    }
}