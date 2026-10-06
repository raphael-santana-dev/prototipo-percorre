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
#[Title('Orçamentos (Protheus) - Financeiro')]
class OrcamentoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroNatureza = '';
    public $filtroStatus = '';

    public bool $modalAberto = false;
    public ?Orcamento $orcamentoSelecionado = null;
    
    // Array para os inputs do formulário web
    public array $editData = [];

    public array $breadcrumbs = [];

    // Variáveis de Simulação
    public int $mesSimulacaoAtual = 1; 
    public array $nomesMeses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];

    public function mount()
    {
        abort_if(!feature('financeiro.orcamentos.listagem'), 403, 'A visualização de Orçamentos está desativada.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.listagem'), 403, 'Acesso restrito.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Orçamentos', 'url' => route('financeiro.orcamentos')],
        ];

        $this->ordenacaoCampo = 'ano';
        $this->ordenacaoDirecao = 'desc';

        // Recupera o mês atual de simulação do banco (ex: tabela de configurações) ou da sessão.
        // Para este MVP, usaremos a sessão para manter o estado da simulação.
        $this->mesSimulacaoAtual = session('mes_simulacao_orcamento', 1);
    }

    public function avancarMesSimulacao()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Apenas administradores podem avançar o mês.');

        if ($this->mesSimulacaoAtual >= 12) {
            $this->dispatch('erro', msg: 'O ano de simulação já foi totalmente concluído (Dezembro).');
            return;
        }

        // 1. Fotografa todos os orçamentos que estão "Finalizados" para o Histórico
        $orcamentosAprovados = Orcamento::where('status', 'Finalizado')->get();

        foreach ($orcamentosAprovados as $orc) {
            \App\Models\OrcamentoHistorico::create([
                'orcamento_id' => $orc->id,
                'mes_referencia' => $this->mesSimulacaoAtual,
                'ano' => $orc->ano,
                'status_no_momento' => $orc->status,
                'valor_jan' => $orc->valor_jan,
                'valor_fev' => $orc->valor_fev,
                'valor_mar' => $orc->valor_mar,
                'valor_abr' => $orc->valor_abr,
                'valor_mai' => $orc->valor_mai,
                'valor_jun' => $orc->valor_jun,
                'valor_jul' => $orc->valor_jul,
                'valor_ago' => $orc->valor_ago,
                'valor_set' => $orc->valor_set,
                'valor_out' => $orc->valor_out,
                'valor_nov' => $orc->valor_nov,
                'valor_dez' => $orc->valor_dez,
                'valor_total_historico' => $orc->valor_total,
                'registrado_por' => auth()->id(),
            ]);
        }

        // 2. Avança o mês
        $this->mesSimulacaoAtual++;
        session(['mes_simulacao_orcamento' => $this->mesSimulacaoAtual]);

        $nomeMes = $this->nomesMeses[$this->mesSimulacaoAtual];
        $this->dispatch('sucesso', msg: "Mês avançado para {$nomeMes}! O histórico dos orçamentos aprovados foi salvo com sucesso.");
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroNatureza', 'filtroStatus'])) {
            $this->resetPage();
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

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: $resultado['mensagem']);
        } else {
            $this->dispatch('erro', msg: $resultado['mensagem']);
        }
    }

    public function abrirModalDetalhes($id)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.orcamentos.detalhes'), 403, 'Acesso restrito.');

        $this->orcamentoSelecionado = Orcamento::findOrFail($id);
        
        // Popula o array com os dados atuais do banco de dados (PostgreSQL)
        $this->editData = [
            'descricao_despesa' => $this->orcamentoSelecionado->descricao_despesa,
            'valor_jan' => $this->orcamentoSelecionado->valor_jan,
            'valor_fev' => $this->orcamentoSelecionado->valor_fev,
            'valor_mar' => $this->orcamentoSelecionado->valor_mar,
            'valor_abr' => $this->orcamentoSelecionado->valor_abr,
            'valor_mai' => $this->orcamentoSelecionado->valor_mai,
            'valor_jun' => $this->orcamentoSelecionado->valor_jun,
            'valor_jul' => $this->orcamentoSelecionado->valor_jul,
            'valor_ago' => $this->orcamentoSelecionado->valor_ago,
            'valor_set' => $this->orcamentoSelecionado->valor_set,
            'valor_out' => $this->orcamentoSelecionado->valor_out,
            'valor_nov' => $this->orcamentoSelecionado->valor_nov,
            'valor_dez' => $this->orcamentoSelecionado->valor_dez,
        ];
        
        $this->modalAberto = true;
    }

    public function salvarOrcamento()
    {
        $this->processarGravacaoStatus('Em elaboração', 'Orçamento salvo e atualizado com sucesso!');
    }

    public function finalizarOrcamento()
    {
        $this->processarGravacaoStatus('Finalizado', 'Orçamento Finalizado! Os dados foram travados para edição e os administradores serão notificados.');
        
        // TODO: Acionar o AutomacaoService para disparar e-mail aos Administradores conforme escopo
        // \App\Modules\Comunicacao\Services\AutomacaoService::disparar('orcamento.finalizado', ...);
    }

    private function processarGravacaoStatus($statusDesejado, $mensagemSucesso)
    {
        if ($this->orcamentoSelecionado->status === 'Finalizado') {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Orçamentos com status "Finalizado" não podem ser alterados.');
            return;
        }

        // Se estiver salvando apenas as edições, só muda o status se for a primeira vez
        $novoStatus = $statusDesejado;
        if ($statusDesejado === 'Em elaboração' && $this->orcamentoSelecionado->status !== 'Criado') {
            $novoStatus = $this->orcamentoSelecionado->status; // Mantém o status atual se já não for mais 'Criado'
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
        
        // TODO: Filtro por Permissão Global ou por Centro de Custo conforme escopo do projeto
        // if (!auth()->user()->hasRole('admin|dev')) {
        //     $query->whereIn('ccusto', auth()->user()->centros_de_custo->pluck('codigo'));
        // }

        if (!empty($this->filtroAno)) $query->where('ano', $this->filtroAno);
        if (!empty($this->filtroFilial)) $query->where('filial', 'ilike', '%' . $this->filtroFilial . '%');
        if (!empty($this->filtroNatureza)) $query->where('natureza', 'ilike', '%' . $this->filtroNatureza . '%');
        if (!empty($this->filtroStatus)) $query->where('status', $this->filtroStatus);

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
            ['label' => 'Orçamentos Listados', 'value' => $query->count(), 'color_text' => 'text-blue-600 dark:text-blue-400', 'color_bg' => 'bg-blue-100 dark:bg-blue-900/30'],
            ['label' => 'Previsão Total (Filtro)', 'value' => 'R$ ' . number_format($totalAnualGeral, 2, ',', '.'), 'color_text' => 'text-emerald-600 dark:text-emerald-400', 'color_bg' => 'bg-emerald-100 dark:bg-emerald-900/30'],
        ];

        return view('livewire.financeiro.orcamento-manager', [
            'registros' => $orcamentos,
            'metricas' => $metricas
        ]);
    }
}