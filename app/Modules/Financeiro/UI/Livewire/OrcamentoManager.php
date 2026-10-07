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
    public $filtroNatureza = '';
    public $filtroCentroCusto = '';
    public $filtroStatus = '';

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;
    
    public string $justificativaGeral = '';
    public array $itensOrcamento = [];
    public array $itensRemovidos = [];

    public array $breadcrumbs = [];

    // --- VARIÁVEL DO SIMULADOR (APENAS ANO) ---
    public int $anoSimulacao = 2026;

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
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus', 'filtroCentroCusto'])) {
            $this->resetPage();
        }
        if ($nomePropriedade === 'anoSimulacao') {
            session(['ano_simulacao_orcamento' => $this->anoSimulacao]);
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus', 'filtroCentroCusto']);
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
        $this->orcamentoSelecionado = Orcamento::with(['avaliacoes', 'itens'])->findOrFail($id);
        
        $this->justificativaGeral = $this->orcamentoSelecionado->descricao_despesa ?? '';
        $this->itensOrcamento = [];
        $this->itensRemovidos = [];

        foreach ($this->orcamentoSelecionado->itens as $item) {
            $this->itensOrcamento[] = [
                'id' => $item->id,
                'descricao' => $item->descricao,
                'valor_jan' => $item->valor_jan, 'valor_fev' => $item->valor_fev,
                'valor_mar' => $item->valor_mar, 'valor_abr' => $item->valor_abr,
                'valor_mai' => $item->valor_mai, 'valor_jun' => $item->valor_jun,
                'valor_jul' => $item->valor_jul, 'valor_ago' => $item->valor_ago,
                'valor_set' => $item->valor_set, 'valor_out' => $item->valor_out,
                'valor_nov' => $item->valor_nov, 'valor_dez' => $item->valor_dez,
            ];
        }

        if (empty($this->itensOrcamento)) {
            $this->adicionarItem();
        }
        
        $this->modalAberto = true;
    }

    public function adicionarItem()
    {
        $this->itensOrcamento[] = [
            'id' => null,
            'descricao' => '',
            'valor_jan' => 0, 'valor_fev' => 0, 'valor_mar' => 0,
            'valor_abr' => 0, 'valor_mai' => 0, 'valor_jun' => 0,
            'valor_jul' => 0, 'valor_ago' => 0, 'valor_set' => 0,
            'valor_out' => 0, 'valor_nov' => 0, 'valor_dez' => 0,
        ];
    }

    public function removerItem($index)
    {
        if (!empty($this->itensOrcamento[$index]['id'])) {
            $this->itensRemovidos[] = $this->itensOrcamento[$index]['id'];
        }
        
        unset($this->itensOrcamento[$index]);
        $this->itensOrcamento = array_values($this->itensOrcamento); 
        
        if (empty($this->itensOrcamento)) {
            $this->adicionarItem(); 
        }
    }

    public function salvarOrcamento()
    {
        $this->processarGravacao('Em elaboração', 'Orçamento guardado como rascunho com sucesso!');
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

        foreach ($this->itensOrcamento as $item) {
            if (empty(trim($item['descricao']))) {
                $this->dispatch('erro', msg: 'Ação Bloqueada: Todos os itens do orçamento devem ter uma descrição.');
                return;
            }
        }

        $novoStatus = $statusDesejado;
        if ($statusDesejado === 'Em elaboração' && !in_array($this->orcamentoSelecionado->status, ['Criado', 'Reprovado'])) {
            $novoStatus = $this->orcamentoSelecionado->status; 
        }

        $this->orcamentoSelecionado->update([
            'descricao_despesa' => $this->justificativaGeral,
            'status' => $novoStatus,
        ]);

        if (!empty($this->itensRemovidos)) {
            OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();
        }

        foreach ($this->itensOrcamento as $dataItem) {
            OrcamentoItem::updateOrCreate(
                ['id' => $dataItem['id'], 'orcamento_id' => $this->orcamentoSelecionado->id],
                [
                    'descricao' => trim($dataItem['descricao']),
                    'valor_jan' => empty($dataItem['valor_jan']) ? 0 : (float) $dataItem['valor_jan'],
                    'valor_fev' => empty($dataItem['valor_fev']) ? 0 : (float) $dataItem['valor_fev'],
                    'valor_mar' => empty($dataItem['valor_mar']) ? 0 : (float) $dataItem['valor_mar'],
                    'valor_abr' => empty($dataItem['valor_abr']) ? 0 : (float) $dataItem['valor_abr'],
                    'valor_mai' => empty($dataItem['valor_mai']) ? 0 : (float) $dataItem['valor_mai'],
                    'valor_jun' => empty($dataItem['valor_jun']) ? 0 : (float) $dataItem['valor_jun'],
                    'valor_jul' => empty($dataItem['valor_jul']) ? 0 : (float) $dataItem['valor_jul'],
                    'valor_ago' => empty($dataItem['valor_ago']) ? 0 : (float) $dataItem['valor_ago'],
                    'valor_set' => empty($dataItem['valor_set']) ? 0 : (float) $dataItem['valor_set'],
                    'valor_out' => empty($dataItem['valor_out']) ? 0 : (float) $dataItem['valor_out'],
                    'valor_nov' => empty($dataItem['valor_nov']) ? 0 : (float) $dataItem['valor_nov'],
                    'valor_dez' => empty($dataItem['valor_dez']) ? 0 : (float) $dataItem['valor_dez'],
                ]
            );
        }

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
        $anoAtual = $this->anoSimulacao;
        $proximoAno = $anoAtual + 1;

        $orcamentosAtuais = Orcamento::with('itens')->where('ano', (string) $anoAtual)->get();

        foreach ($orcamentosAtuais as $orcamento) {
            $novaChaveComposta = "{$orcamento->filial}_{$proximoAno}_{$orcamento->natureza}_{$orcamento->ccusto}";

            $novoOrcamento = Orcamento::firstOrCreate(
                [
                    'chave_composta' => $novaChaveComposta,
                ],
                [
                    'filial' => $orcamento->filial,
                    'ano' => (string) $proximoAno,
                    'natureza' => $orcamento->natureza,
                    'ccusto' => $orcamento->ccusto,
                    'moeda' => $orcamento->moeda,
                    'cmoeda' => $orcamento->cmoeda,
                    'xcat' => $orcamento->xcat,
                    'descricao_despesa' => $orcamento->descricao_despesa,
                    'status' => 'Criado', 
                    'valor_jan' => 0, 'valor_fev' => 0, 'valor_mar' => 0,
                    'valor_abr' => 0, 'valor_mai' => 0, 'valor_jun' => 0,
                    'valor_jul' => 0, 'valor_ago' => 0, 'valor_set' => 0,
                    'valor_out' => 0, 'valor_nov' => 0, 'valor_dez' => 0,
                ]
            );

            if ($novoOrcamento->wasRecentlyCreated || $novoOrcamento->itens()->count() === 0) {
                foreach ($orcamento->itens as $item) {
                    OrcamentoItem::create([
                        'orcamento_id' => $novoOrcamento->id,
                        'descricao' => $item->descricao,
                        'valor_jan' => $item->valor_jan, 'valor_fev' => $item->valor_fev,
                        'valor_mar' => $item->valor_mar, 'valor_abr' => $item->valor_abr,
                        'valor_mai' => $item->valor_mai, 'valor_jun' => $item->valor_jun,
                        'valor_jul' => $item->valor_jul, 'valor_ago' => $item->valor_ago,
                        'valor_set' => $item->valor_set, 'valor_out' => $item->valor_out,
                        'valor_nov' => $item->valor_nov, 'valor_dez' => $item->valor_dez,
                    ]);
                }
            }
        }

        $this->anoSimulacao = $proximoAno;
        session(['ano_simulacao_orcamento' => $this->anoSimulacao]);
        
        $this->dispatch('sucesso', msg: "Ano avançado para {$this->anoSimulacao}! Todos os orçamentos e itens foram duplicados para o novo ciclo.");
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
        if (!empty($this->filtroCentroCusto)) $query->where('ccusto', 'ilike', '%' . $this->filtroCentroCusto . '%');

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