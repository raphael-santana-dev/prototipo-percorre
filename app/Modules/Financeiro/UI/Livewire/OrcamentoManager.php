<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Orçamentos - Financeiro')]
class OrcamentoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroStatus = '';

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;
    
    public string $justificativaGeral = '';
    public array $itensOrcamento = [];
    public array $itensRemovidos = [];

    public array $breadcrumbs = [];
    public int $anoSimulacao = 2026;

    public function mount()
    {
        abort_if(!feature('financeiro.orcamentos.listagem'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.listagem'), 403);

        $this->breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Financeiro', 'url' => '#'], ['label' => 'Orçamentos', 'url' => route('financeiro.orcamentos')]];
        $this->ordenacaoCampo = 'ano'; $this->ordenacaoDirecao = 'desc';
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroStatus'])) $this->resetPage();
        if ($nomePropriedade === 'anoSimulacao') session(['ano_simulacao_orcamento' => $this->anoSimulacao]);
    }

    public function limparFiltros() { $this->reset(['filtroAno', 'filtroFilial', 'filtroStatus']); $this->resetPage(); }

    public function sincronizarProtheus() {
        $resultado = ProtheusOrcamentoService::sincronizar();
        if ($resultado['sucesso']) $this->dispatch('sucesso', msg: $resultado['mensagem']); else $this->dispatch('erro', msg: $resultado['mensagem']);
    }

    public function abrirModalDetalhes($id)
    {
        $this->orcamentoSelecionado = Orcamento::with(['avaliacoes', 'itens.natureza', 'centroCusto'])->findOrFail($id);
        $this->justificativaGeral = $this->orcamentoSelecionado->descricao_despesa ?? '';
        $this->itensOrcamento = []; $this->itensRemovidos = [];

        foreach ($this->orcamentoSelecionado->itens as $item) {
            $this->itensOrcamento[] = [
                'id' => $item->id, 'natureza_codigo' => $item->natureza_codigo, 'descricao' => $item->descricao,
                'previsto_jan' => $item->previsto_jan, 'valor_jan' => $item->valor_jan,
                'previsto_fev' => $item->previsto_fev, 'valor_fev' => $item->valor_fev,
                'previsto_mar' => $item->previsto_mar, 'valor_mar' => $item->valor_mar,
                'previsto_abr' => $item->previsto_abr, 'valor_abr' => $item->valor_abr,
                'previsto_mai' => $item->previsto_mai, 'valor_mai' => $item->valor_mai,
                'previsto_jun' => $item->previsto_jun, 'valor_jun' => $item->valor_jun,
                'previsto_jul' => $item->previsto_jul, 'valor_jul' => $item->valor_jul,
                'previsto_ago' => $item->previsto_ago, 'valor_ago' => $item->valor_ago,
                'previsto_set' => $item->previsto_set, 'valor_set' => $item->valor_set,
                'previsto_out' => $item->previsto_out, 'valor_out' => $item->valor_out,
                'previsto_nov' => $item->previsto_nov, 'valor_nov' => $item->valor_nov,
                'previsto_dez' => $item->previsto_dez, 'valor_dez' => $item->valor_dez,
            ];
        }

        if (empty($this->itensOrcamento)) $this->adicionarItem();
        $this->modalAberto = true;
    }

    public function adicionarItem() {
        $this->itensOrcamento[] = [
            'id' => null, 'natureza_codigo' => '', 'descricao' => '',
            'previsto_jan' => 0, 'valor_jan' => 0, 'previsto_fev' => 0, 'valor_fev' => 0,
            'previsto_mar' => 0, 'valor_mar' => 0, 'previsto_abr' => 0, 'valor_abr' => 0,
            'previsto_mai' => 0, 'valor_mai' => 0, 'previsto_jun' => 0, 'valor_jun' => 0,
            'previsto_jul' => 0, 'valor_jul' => 0, 'previsto_ago' => 0, 'valor_ago' => 0,
            'previsto_set' => 0, 'valor_set' => 0, 'previsto_out' => 0, 'valor_out' => 0,
            'previsto_nov' => 0, 'valor_nov' => 0, 'previsto_dez' => 0, 'valor_dez' => 0,
        ];
    }

    public function removerItem($index) {
        if (!empty($this->itensOrcamento[$index]['id'])) $this->itensRemovidos[] = $this->itensOrcamento[$index]['id'];
        unset($this->itensOrcamento[$index]);
        $this->itensOrcamento = array_values($this->itensOrcamento); 
        if (empty($this->itensOrcamento)) $this->adicionarItem(); 
    }

    public function salvarOrcamento() { $this->processarGravacao('Em elaboração', 'Orçamento guardado como rascunho!'); }
    public function finalizarOrcamento() { $this->processarGravacao('Finalizado', 'Orçamento submetido para aprovação!'); }

    private function processarGravacao($statusDesejado, $mensagemSucesso) {
        if (in_array($this->orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas'])) {
            $this->dispatch('erro', msg: 'Orçamentos já aprovados não podem ser alterados.'); return;
        }

        foreach ($this->itensOrcamento as $item) {
            if (empty(trim($item['natureza_codigo']))) { $this->dispatch('erro', msg: 'Todas as linhas devem ter uma Natureza selecionada.'); return; }
        }

        $novoStatus = ($statusDesejado === 'Em elaboração' && !in_array($this->orcamentoSelecionado->status, ['Criado', 'Reprovado'])) ? $this->orcamentoSelecionado->status : $statusDesejado;
        $this->orcamentoSelecionado->update(['descricao_despesa' => $this->justificativaGeral, 'status' => $novoStatus]);
        if (!empty($this->itensRemovidos)) OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();

        foreach ($this->itensOrcamento as $dataItem) {
            if (empty($dataItem['id'])) {
                $naturezaDB = Natureza::where('codigo', $dataItem['natureza_codigo'])->first();
                $descricao = $naturezaDB ? $naturezaDB->descricao : trim($dataItem['descricao']);
            } else { $descricao = trim($dataItem['descricao']); }

            OrcamentoItem::updateOrCreate(
                ['id' => $dataItem['id'], 'orcamento_id' => $this->orcamentoSelecionado->id],
                [
                    'natureza_codigo' => trim($dataItem['natureza_codigo']), 'descricao' => $descricao,
                    'previsto_jan' => empty($dataItem['previsto_jan']) ? 0 : (float) $dataItem['previsto_jan'], 'valor_jan' => empty($dataItem['valor_jan']) ? 0 : (float) $dataItem['valor_jan'],
                    'previsto_fev' => empty($dataItem['previsto_fev']) ? 0 : (float) $dataItem['previsto_fev'], 'valor_fev' => empty($dataItem['valor_fev']) ? 0 : (float) $dataItem['valor_fev'],
                    'previsto_mar' => empty($dataItem['previsto_mar']) ? 0 : (float) $dataItem['previsto_mar'], 'valor_mar' => empty($dataItem['valor_mar']) ? 0 : (float) $dataItem['valor_mar'],
                    'previsto_abr' => empty($dataItem['previsto_abr']) ? 0 : (float) $dataItem['previsto_abr'], 'valor_abr' => empty($dataItem['valor_abr']) ? 0 : (float) $dataItem['valor_abr'],
                    'previsto_mai' => empty($dataItem['previsto_mai']) ? 0 : (float) $dataItem['previsto_mai'], 'valor_mai' => empty($dataItem['valor_mai']) ? 0 : (float) $dataItem['valor_mai'],
                    'previsto_jun' => empty($dataItem['previsto_jun']) ? 0 : (float) $dataItem['previsto_jun'], 'valor_jun' => empty($dataItem['valor_jun']) ? 0 : (float) $dataItem['valor_jun'],
                    'previsto_jul' => empty($dataItem['previsto_jul']) ? 0 : (float) $dataItem['previsto_jul'], 'valor_jul' => empty($dataItem['valor_jul']) ? 0 : (float) $dataItem['valor_jul'],
                    'previsto_ago' => empty($dataItem['previsto_ago']) ? 0 : (float) $dataItem['previsto_ago'], 'valor_ago' => empty($dataItem['valor_ago']) ? 0 : (float) $dataItem['valor_ago'],
                    'previsto_set' => empty($dataItem['previsto_set']) ? 0 : (float) $dataItem['previsto_set'], 'valor_set' => empty($dataItem['valor_set']) ? 0 : (float) $dataItem['valor_set'],
                    'previsto_out' => empty($dataItem['previsto_out']) ? 0 : (float) $dataItem['previsto_out'], 'valor_out' => empty($dataItem['valor_out']) ? 0 : (float) $dataItem['valor_out'],
                    'previsto_nov' => empty($dataItem['previsto_nov']) ? 0 : (float) $dataItem['previsto_nov'], 'valor_nov' => empty($dataItem['valor_nov']) ? 0 : (float) $dataItem['valor_nov'],
                    'previsto_dez' => empty($dataItem['previsto_dez']) ? 0 : (float) $dataItem['previsto_dez'], 'valor_dez' => empty($dataItem['valor_dez']) ? 0 : (float) $dataItem['valor_dez'],
                ]
            );
        }
        $this->dispatch('sucesso', msg: $mensagemSucesso); $this->fecharModal();
    }

    public function fecharModal() { $this->modalAberto = false; $this->orcamentoSelecionado = null; }

    public function avancarAnoSimulacao()
    {
        $anoAtual = $this->anoSimulacao; $proximoAno = $anoAtual + 1;
        $orcamentosAtuais = Orcamento::with('itens')->where('ano', (string) $anoAtual)->get();

        foreach ($orcamentosAtuais as $orcamento) {
            $novoOrcamento = Orcamento::firstOrCreate(
                ['chave_composta' => "{$orcamento->filial}_{$proximoAno}_{$orcamento->ccusto}"],
                ['filial' => $orcamento->filial, 'ano' => (string) $proximoAno, 'ccusto' => $orcamento->ccusto, 'moeda' => $orcamento->moeda, 'cmoeda' => $orcamento->cmoeda, 'xcat' => $orcamento->xcat, 'descricao_despesa' => $orcamento->descricao_despesa, 'status' => 'Criado']
            );

            if ($novoOrcamento->wasRecentlyCreated || $novoOrcamento->itens()->count() === 0) {
                foreach ($orcamento->itens as $item) {
                    OrcamentoItem::create([
                        'orcamento_id' => $novoOrcamento->id, 'natureza_codigo' => $item->natureza_codigo, 'descricao' => $item->descricao,
                        'previsto_jan' => $item->valor_jan, 'valor_jan' => $item->valor_jan, // Valor final de 2026 vira o Previsto de 2027
                        'previsto_fev' => $item->valor_fev, 'valor_fev' => $item->valor_fev,
                        'previsto_mar' => $item->valor_mar, 'valor_mar' => $item->valor_mar,
                        'previsto_abr' => $item->valor_abr, 'valor_abr' => $item->valor_abr,
                        'previsto_mai' => $item->valor_mai, 'valor_mai' => $item->valor_mai,
                        'previsto_jun' => $item->valor_jun, 'valor_jun' => $item->valor_jun,
                        'previsto_jul' => $item->valor_jul, 'valor_jul' => $item->valor_jul,
                        'previsto_ago' => $item->valor_ago, 'valor_ago' => $item->valor_ago,
                        'previsto_set' => $item->valor_set, 'valor_set' => $item->valor_set,
                        'previsto_out' => $item->valor_out, 'valor_out' => $item->valor_out,
                        'previsto_nov' => $item->valor_nov, 'valor_nov' => $item->valor_nov,
                        'previsto_dez' => $item->valor_dez, 'valor_dez' => $item->valor_dez,
                    ]);
                }
            }
        }
        $this->anoSimulacao = $proximoAno; session(['ano_simulacao_orcamento' => $this->anoSimulacao]);
        $this->dispatch('sucesso', msg: "Ano avançado para {$this->anoSimulacao}! Valores fechados foram convertidos na Nova Base Prevista.");
    }

    public function getHeadersProperty() {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true], ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true], ['key' => 'ccusto', 'label' => 'Centro de Custo', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Atual', 'sortable' => false, 'class' => 'text-right font-bold uppercase text-gray-400 text-[10px]'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right']
        ];
    }

    protected function obterQueryFiltrada() {
        $query = Orcamento::with('centroCusto');
        if (!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.global')) {
            if (method_exists(auth()->user(), 'centros_de_custo')) $query->whereIn('ccusto', auth()->user()->centros_de_custo->pluck('codigo')->toArray());
            else $query->where('ccusto', '00000000'); 
        }
        if (!empty($this->filtroAno)) $query->where('ano', $this->filtroAno);
        if (!empty($this->filtroFilial)) $query->where('filial', 'ilike', '%' . $this->filtroFilial . '%');
        if (!empty($this->filtroStatus)) $query->where('status', $this->filtroStatus);
        return $query;
    }

    public function render() {
        $query = $this->obterQueryFiltrada();
        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao); else $query->orderBy('id', 'desc');
        $orcamentos = $query->paginate($this->porPagina);
        $totalAnualGeral = (clone $query)->get()->sum('valor_total');
        $metricas = [
            ['label' => 'Orçamentos (Centros de Custo)', 'value' => $query->count(), 'color_text' => 'text-blue-600', 'color_bg' => 'bg-blue-100'],
            ['label' => 'Previsão Total (Filtro)', 'value' => 'R$ ' . number_format($totalAnualGeral, 2, ',', '.'), 'color_text' => 'text-emerald-600', 'color_bg' => 'bg-emerald-100'],
        ];
        $todasNaturezas = Natureza::where('disponivel_orcamento', true)->orderBy('descricao')->get();
        return view('livewire.financeiro.orcamento-manager', ['registros' => $orcamentos, 'metricas' => $metricas, 'todasNaturezas' => $todasNaturezas]);
    }
}