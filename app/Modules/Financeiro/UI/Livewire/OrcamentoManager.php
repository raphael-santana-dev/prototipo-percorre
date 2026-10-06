<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;
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
    
    // Array para edição
    public array $editData = [];

    // Variáveis para Avaliação (Aprovação/Reprovação)
    public bool $modalAvaliacaoAberto = false;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    public array $breadcrumbs = [];

    // Variáveis do Simulador
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
            ['label' => 'Orçamentos', 'url' => route('financeiro.orcamentos')],
        ];

        $this->ordenacaoCampo = 'ano';
        $this->ordenacaoDirecao = 'desc';
        $this->mesSimulacaoAtual = session('mes_simulacao_orcamento', 1);
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
        // Se já está aprovado, não pode mais ser mexido pelo gestor comum
        if (in_array($this->orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas'])) {
            $this->dispatch('erro', msg: 'Orçamentos já aprovados não podem ser alterados.');
            return;
        }

        $novoStatus = $statusDesejado;
        if ($statusDesejado === 'Em elaboração' && $this->orcamentoSelecionado->status !== 'Criado' && $this->orcamentoSelecionado->status !== 'Reprovado') {
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

    // --- MÉTODOS DE AVALIAÇÃO (DIRETORIA/ADMIN) ---
    public function abrirModalAvaliacao($status)
    {
        $this->statusAvaliacao = $status;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacao()
    {
        $this->validate(['comentarioAvaliacao' => 'required|string|min:5'], ['comentarioAvaliacao.required' => 'A justificativa é obrigatória.']);

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
        $this->dispatch('sucesso', msg: "Orçamento avaliado como {$this->statusAvaliacao}!");
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->orcamentoSelecionado = null;
    }

    public function avancarMesSimulacao()
    {
        // Força apenas os orçamentos não aprovados/finalizados a virarem Finalizados.
        Orcamento::whereIn('status', ['Criado', 'Em elaboração'])->update(['status' => 'Finalizado']);
        
        // Em um ambiente real, aqui faríamos a fotografia.
        $this->mesSimulacaoAtual++;
        session(['mes_simulacao_orcamento' => $this->mesSimulacaoAtual]);
        $this->dispatch('sucesso', msg: "Mês avançado para {$this->nomesMeses[$this->mesSimulacaoAtual]}!");
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
        
        // CONTROLE VISUAL POR CENTRO DE CUSTO
        // Se o usuário não for master, filtra para ele ver apenas os CCustos dele
        // Exemplo: se houver relacionamento $user->centros_de_custo
        if (!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('financeiro.orcamentos.global')) {
            // Pegamos o array de códigos de centro de custo do usuário atual
            if (method_exists(auth()->user(), 'centros_de_custo')) {
                $query->whereIn('ccusto', auth()->user()->centros_de_custo->pluck('codigo')->toArray());
            } else {
                // Fallback de segurança se o relacionamento não existir
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