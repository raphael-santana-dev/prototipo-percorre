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
    
    // Variáveis dos Modais
    public $modalPreviewAberto = false;
    public $modalErroAberto = false;
    public $logSelecionado = null;

    // Filtros
    public $filtro_status = '';
    public $filtro_origem = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_status', 'filtro_origem'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_status', 'filtro_origem']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'destinatario', 'label' => 'Destinatário', 'sortable' => true],
            ['key' => 'assunto', 'label' => 'Assunto / Origem', 'sortable' => false],
            ['key' => 'data_agendamento', 'label' => 'Agendado / Enviado', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function verPreview($id)
    {
        $this->logSelecionado = ComunicacaoLog::findOrFail($id);
        $this->modalPreviewAberto = true;
    }

    public function verErro($id)
    {
        $this->logSelecionado = ComunicacaoLog::findOrFail($id);
        $this->modalErroAberto = true;
    }

    public function render()
    {
        $query = ComunicacaoLog::query();

        if (!empty($this->filtro_status)) $query->where('status', $this->filtro_status);
        if (!empty($this->filtro_origem)) $query->where('origem', $this->filtro_origem);

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.comunicacao.email-log.email-log-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Monitor de E-mails']);
    }
}