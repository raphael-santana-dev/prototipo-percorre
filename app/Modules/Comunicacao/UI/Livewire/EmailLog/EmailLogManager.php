<?php

namespace App\Modules\Comunicacao\UI\Livewire\EmailLog;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;

class EmailLogManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    
    public $modalPreviewAberto = false;
    public $modalErroAberto = false;
    public $modalMetricasAberto = false;
    public $logSelecionado = null;

    public $filtro_status = '';
    public $filtro_origem = '';
    public $filtro_engajamento = '';

    public function mount()
    {
        //abort_if(!feature('email_log.listar'), 403, 'Módulo desativado.');
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('email_log.listar'), 403);
        
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_status', 'filtro_origem', 'filtro_engajamento'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_status', 'filtro_origem', 'filtro_engajamento']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'destinatario', 'label' => 'Destinatário', 'sortable' => true],
            ['key' => 'assunto', 'label' => 'Assunto / Origem', 'sortable' => false],
            ['key' => 'data_agendamento', 'label' => 'Agendado / Enviado', 'sortable' => true],
            ['key' => 'aberturas_count', 'label' => 'Engajamento (Abertura / Cliques)', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-28'],
        ];
    }

    public function verPreview($id)
    {
        $this->logSelecionado = ComunicacaoLog::findOrFail($id);
        $this->modalPreviewAberto = true;
    }

    public function verMetricas($id)
    {
        $this->logSelecionado = ComunicacaoLog::with('eventos')->findOrFail($id);
        $this->modalMetricasAberto = true;
    }

    public function verErro($id)
    {
        $this->logSelecionado = ComunicacaoLog::findOrFail($id);
        $this->modalErroAberto = true;
    }

    public function render()
    {
        $query = ComunicacaoLog::query();

        if (!empty($this->filtro_status)) {
            $query->where('status', $this->filtro_status);
        }
        if (!empty($this->filtro_origem)) {
            $query->whereIn('origem', $this->filtro_origem === 'automacao' ? ['automacao', 'automação'] : [$this->filtro_origem]);
        }
        if ($this->filtro_engajamento === 'abertos') {
            $query->where('aberturas_count', '>', 0);
        } elseif ($this->filtro_engajamento === 'nao_abertos') {
            $query->where('aberturas_count', 0);
        } elseif ($this->filtro_engajamento === 'clicados') {
            $query->where('cliques_count', '>', 0);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $totalEnviados = ComunicacaoLog::where('status', 'enviado')->count();
        $totalAbertos  = ComunicacaoLog::where('status', 'enviado')->where('aberturas_count', '>', 0)->count();
        $totalClicados = ComunicacaoLog::where('status', 'enviado')->where('cliques_count', '>', 0)->count();

        $taxaAbertura = $totalEnviados > 0 ? round(($totalAbertos / $totalEnviados) * 100, 1) : 0;
        $taxaCliques  = $totalEnviados > 0 ? round(($totalClicados / $totalEnviados) * 100, 1) : 0;

        return view('livewire.comunicacao.email-log.email-log-manager', [
            'registros'     => $query->paginate($this->porPagina),
            'totalEnviados' => $totalEnviados,
            'totalAbertos'  => $totalAbertos,
            'totalClicados' => $totalClicados,
            'taxaAbertura'  => $taxaAbertura,
            'taxaCliques'   => $taxaCliques,
        ])->layout('components.layouts.app', ['title' => 'Monitor de E-mails']);
    }
}