<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Orçamentos - Financeiro')]
class OrcamentoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroStatus = '';

    public array $breadcrumbs = [];
    public int $anoSimulacao = 2026;

    public function mount()
    {
        abort_if(!feature('financeiro.orcamentos.listagem'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.listagem'), 403);

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')], 
            ['label' => 'Financeiro', 'url' => '#'], 
            ['label' => 'Orçamentos', 'url' => route('financeiro.orcamentos')]
        ];
        $this->ordenacaoCampo = 'ano'; 
        $this->ordenacaoDirecao = 'desc';
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroStatus'])) $this->resetPage();
        if ($nomePropriedade === 'anoSimulacao') session(['ano_simulacao_orcamento' => $this->anoSimulacao]);
    }

    public function limparFiltros() { 
        $this->reset(['filtroAno', 'filtroFilial', 'filtroStatus']); 
        $this->resetPage(); 
    }

    public function sincronizarProtheus() {
        $resultado = ProtheusOrcamentoService::sincronizar();
        if ($resultado['sucesso']) $this->dispatch('sucesso', msg: $resultado['mensagem']); 
        else $this->dispatch('erro', msg: $resultado['mensagem']);
    }

    // NOVA FUNÇÃO: Redireciona para a tela dedicada do Excel View
    public function editarOrcamento($id)
    {
        return redirect()->route('financeiro.orcamentos.editor', $id);
    }

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
                        'orcamento_id' => $novoOrcamento->id, 'natureza_codigo' => $item->natureza_codigo, 'descricao' => $item->descricao, 'status' => 'Criado',
                        'previsto_jan' => $item->valor_jan, 'valor_jan' => $item->valor_jan, 
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
            ['key' => 'id', 'label' => 'ID', 'sortable' => true], 
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true], 
            ['key' => 'ccusto', 'label' => 'Centro de Custo', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status Global', 'sortable' => true, 'class' => 'text-center'],
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
        return view('livewire.financeiro.orcamento-manager', ['registros' => $orcamentos, 'metricas' => $metricas]);
    }
}