<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Modules\Financeiro\Domain\Models\Orcamento;

#[Layout('components.layouts.app')]
#[Title('Aprovações de Orçamentos - Financeiro')]
class OrcamentoAprovacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroAno = '';
    public $filtroFilial = '';
    public $filtroStatus = 'Finalizado – Aguardando Aprovação'; 
    public $filtroCentroCusto = '';

    public array $breadcrumbs = [];

    public function mount()
    {
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
        if (in_array($nomePropriedade, ['filtroAno', 'filtroFilial', 'filtroStatus', 'filtroCentroCusto'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroAno', 'filtroFilial', 'filtroCentroCusto']);
        $this->filtroStatus = '';
        $this->resetPage();
    }

    public function avaliarOrcamento($id)
    {
        return redirect()->route('financeiro.aprovacoes.editor', $id);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'ano', 'label' => 'Ano', 'sortable' => true, 'class' => 'text-center w-20'],
            ['key' => 'filial', 'label' => 'Filial', 'sortable' => true, 'class' => 'w-24'],
            ['key' => 'ccusto', 'label' => 'Cód. C.C.', 'sortable' => true, 'class' => 'w-28'],
            ['key' => 'ccusto_nome', 'label' => 'Centro de Custo', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status Global', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'valor_total', 'label' => 'Total Solicitado', 'sortable' => false, 'class' => 'text-right'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    protected function aplicarEscopoCentrosCustoUsuario($query)
    {
        $codigosPermitidos = auth()->user()->obterCodigosCcAprovacao();

        // Se retornar null, o usuário é 'dev' ou tem Permissão Global de Centros de Custo (vê todos)
        if ($codigosPermitidos !== null) {
            $query->whereIn('ccusto', !empty($codigosPermitidos) ? $codigosPermitidos : ['00000000']);
        }

        return $query;
    }

    protected function obterQueryFiltrada()
    {
        $query = Orcamento::with(['centroCusto', 'itens'])
            ->whereIn('status', [
                'Finalizado – Aguardando Aprovação',
                'Finalizado - Aguardando Aprovação',
                'Aprovação – Aguardando Revisão pelo Responsável',
                'Aprovação - Aguardando Revisão pelo Responsável',
                'Finalizado – Aprovado',
                'Finalizado - Aprovado',
                'Finalizado – Integrado ao Protheus',
                'Finalizado - Integrado ao Protheus',
                'Finalizado',
                'Aprovado',
                'Reprovado',
                'Aprovado com ressalvas'
            ]);

        $this->aplicarEscopoCentrosCustoUsuario($query);
        
        if (!empty($this->filtroAno)) {
            $query->where('ano', trim($this->filtroAno));
        }
        if (!empty($this->filtroFilial)) {
            $query->where('filial', 'ilike', '%' . trim($this->filtroFilial) . '%');
        }
        if (!empty($this->filtroStatus)) {
            if (in_array($this->filtroStatus, ['Finalizado – Aguardando Aprovação', 'Finalizado - Aguardando Aprovação'])) {
                $query->whereIn('status', ['Finalizado – Aguardando Aprovação', 'Finalizado - Aguardando Aprovação', 'Finalizado']);
            } else {
                $query->where('status', $this->filtroStatus);
            }
        }
        if (!empty($this->filtroCentroCusto)) {
            $termoCC = trim($this->filtroCentroCusto);
            $query->where(function ($q) use ($termoCC) {
                $q->where('ccusto', 'ilike', '%' . $termoCC . '%')
                  ->orWhereHas('centroCusto', fn($sub) => $sub->where('nome', 'ilike', '%' . $termoCC . '%'));
            });
        }

        return $query;
    }

    public function render()
    {
        $query = $this->obterQueryFiltrada();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $queryAguardando = Orcamento::whereIn('status', ['Finalizado - Aguardando Aprovação', 'Finalizado - Aguardando Aprovação', 'Finalizado']);
        $this->aplicarEscopoCentrosCustoUsuario($queryAguardando);

        return view('livewire.financeiro.orcamento-aprovacao-manager', [
            'registros'       => $query->paginate($this->porPagina),
            'totalAguardando' => $queryAguardando->count(),
        ]);
    }
}