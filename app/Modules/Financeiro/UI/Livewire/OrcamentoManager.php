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
    public $filtroStatus = '';
    public $filtroCentroCusto = '';

    public array $breadcrumbs = [];

    // Controle do Modal de JSON Consolidado (Tabela Intermediária)
    public bool $modalJsonAberto = false;
    public string $jsonConsolidado = '';
    public string $tituloOrcamentoJson = '';

    public function mount()
    {
        abort_if(
            !auth()->user()->hasRole('dev')
            && !auth()->user()->can('financeiro.orcamentos.editar')
            && !auth()->user()->can('financeiro.orcamentos.listar'),
            403,
            'Sem permissão para acessar orçamentos.'
        );

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')], 
            ['label' => 'Financeiro', 'url' => '#'], 
            ['label' => 'Orçamentos', 'url' => route('financeiro.orcamentos')]
        ];

        $this->filtroAno = date('Y');
        $this->ordenacaoCampo = 'ano'; 
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
        $this->reset(['filtroFilial', 'filtroStatus', 'filtroCentroCusto']);
        $this->filtroAno = date('Y');
        $this->resetPage(); 
    }

    public function integrarAoProtheus(int $id): void
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.editar') && !auth()->user()->can('financeiro.orcamentos.integrar'), 403, 'Sem permissão.');

        $orcamento = Orcamento::with(['centroCusto', 'itens.natureza'])->findOrFail($id);

        if (!in_array($orcamento->status, ['Finalizado – Aprovado', 'Finalizado - Aprovado', 'Aprovado'], true)) {
            $this->dispatch('erro', msg: 'O orçamento precisa estar com status "Finalizado – Aprovado" para ser integrado ao Protheus.');
            return;
        }

        app(ProtheusOrcamentoService::class)->enviarParaProtheusSimulado($orcamento);
        $orcamento->update(['status' => 'Finalizado – Integrado ao Protheus']);

        \App\Modules\Financeiro\Services\OrcamentoNotificacaoService::notificarIntegracaoProtheus($orcamento, auth()->user());

        $this->dispatch('sucesso', msg: "Orçamento #{$orcamento->id} finalizado e preparado para integração com a Tabela Intermediária do Protheus!");
    }

    public function sincronizarCadastrosProtheus()
    {
        $resultado = ProtheusOrcamentoService::sincronizarCadastrosBase();
        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: $resultado['mensagem']);
        } else {
            $this->dispatch('erro', msg: $resultado['mensagem']);
        }
    }

    public function sincronizarProtheus()
    {
        $resultado = ProtheusOrcamentoService::sincronizar();
        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: $resultado['mensagem']); 
        } else {
            $this->dispatch('erro', msg: $resultado['mensagem']);
        }
    }

    public function editarOrcamento($id)
    {
        return redirect()->route('financeiro.orcamentos.editor', $id);
    }

    public function verJsonConsolidado($id)
    {
        $orcamento = Orcamento::with(['centroCusto', 'itens.natureza'])->findOrFail($id);

        if (!in_array($orcamento->status, ['Finalizado – Aprovado', 'Finalizado - Aprovado', 'Finalizado – Integrado ao Protheus', 'Finalizado - Integrado ao Protheus', 'Aprovado', 'Aprovado com ressalvas'])) {
            $this->dispatch('erro', msg: 'Os dados consolidados só ficam disponíveis após a aprovação do orçamento.');
            return;
        }

        $dados = $orcamento->dados_consolidados;
        if (empty($dados)) {
            $dados = app(ProtheusOrcamentoService::class)->enviarParaProtheusSimulado($orcamento);
        }

        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'S/ Vínculo';
        $this->tituloOrcamentoJson = "Orçamento #{$orcamento->id} - C.C. {$orcamento->ccusto} ({$ccNome}) - Ano {$orcamento->ano}";
        $this->jsonConsolidado = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->modalJsonAberto = true;
    }

    public function fecharModalJson()
    {
        $this->modalJsonAberto = false;
        $this->jsonConsolidado = '';
        $this->tituloOrcamentoJson = '';
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true], 
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true], 
            ['key' => 'ccusto', 'label' => 'Cód. C.C.', 'sortable' => true],
            ['key' => 'ccusto_nome', 'label' => 'Centro de Custo', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status Global', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Atual', 'sortable' => false, 'class' => 'text-right font-bold uppercase text-gray-400 text-[10px]'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right']
        ];
    }

    protected function obterQueryFiltrada()
    {
        $query = Orcamento::with('centroCusto');

        // Obtém os Centros de Custo que o usuário pode EDITAR (retorna null se for dev ou tiver Permissão Global de Edição)
        $ccPermitidosEdicao = auth()->user()->obterCodigosCcEdicao();
        if ($ccPermitidosEdicao !== null) {
            $query->whereIn('ccusto', !empty($ccPermitidosEdicao) ? $ccPermitidosEdicao : ['00000000']);
        }

        if (!empty($this->filtroAno)) {
            $query->where('ano', trim($this->filtroAno));
        }
        if (!empty($this->filtroFilial)) {
            $query->where('filial', 'ilike', '%' . trim($this->filtroFilial) . '%');
        }
        if (!empty($this->filtroCentroCusto)) {
            $termoCC = trim($this->filtroCentroCusto);
            $query->where(function ($q) use ($termoCC) {
                $q->where('ccusto', 'ilike', '%' . $termoCC . '%')
                  ->orWhereHas('centroCusto', fn($sub) => $sub->where('nome', 'ilike', '%' . $termoCC . '%'));
            });
        }
        if (!empty($this->filtroStatus)) {
            $query->where('status', $this->filtroStatus);
        }

        return $query;
    }

    public function render()
    {
        $query = $this->obterQueryFiltrada();
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $orcamentos = $query->paginate($this->porPagina);
        $totalAnualGeral = (clone $query)->get()->sum('valor_total');
        $metricas = [
            ['label' => 'Orçamentos (' . ($this->filtroAno ?: 'Todos') . ')', 'value' => $query->count(), 'color_text' => 'text-blue-600', 'color_bg' => 'bg-blue-100'],
            ['label' => 'Previsão Total (Filtro)', 'value' => 'R$ ' . number_format($totalAnualGeral, 2, ',', '.'), 'color_text' => 'text-emerald-600', 'color_bg' => 'bg-emerald-100'],
        ];

        return view('livewire.financeiro.orcamento-manager', [
            'registros' => $orcamentos,
            'metricas'  => $metricas,
        ]);
    }
}