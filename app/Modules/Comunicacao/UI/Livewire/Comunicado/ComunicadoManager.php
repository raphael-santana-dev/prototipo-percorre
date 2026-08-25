<?php

namespace App\Modules\Comunicacao\UI\Livewire\Comunicado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\Comunicado;

class ComunicadoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public $filtro_status = '';
    public $filtro_template = '';
    public $filtro_data_inicio = '';
    public $filtro_data_fim = '';

    public function updating($nomePropriedade) 
    { 
        if (in_array($nomePropriedade, ['filtro_status', 'filtro_template', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage(); 
        }
    }
    
    public function limparFiltros() 
    {
        $this->reset(['filtro_status', 'filtro_template', 'filtro_data_inicio', 'filtro_data_fim']);
        $this->resetPage();
    }

    public function mount()
    {
        abort_if(!feature('comunicado.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'template_id', 'label' => 'Template Utilizado', 'sortable' => true],
            ['key' => 'destinatarios', 'label' => 'Qtd. Destinatários', 'sortable' => false],
            ['key' => 'data_agendamento', 'label' => 'Agendamento / Envio', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status de Envio', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function excluir($id)
    {
        abort_if(!feature('comunicado.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.excluir'), 403);
        
        $comunicado = Comunicado::findOrFail($id);
        
        // Impede excluir se estiver no meio do envio
        if ($comunicado->status === 'enviando') {
            $this->dispatch('erro', msg: 'Não é possível excluir um comunicado que está sendo enviado.');
            return;
        }

        // Remove os anexos físicos se existirem
        if (is_array($comunicado->anexos)) {
            foreach ($comunicado->anexos as $anexo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($anexo);
            }
        }

        $comunicado->delete();
        $this->dispatch('sucesso', msg: 'Comunicado excluído com sucesso!');
    }

    public function render()
    {
        $query = Comunicado::with('template');
        
        // Filtros (O whereDate extrai o "dia" da data de agendamento ignorando a hora)
        $query->when($this->filtro_status, fn($q) => $q->where('status', $this->filtro_status))
              ->when($this->filtro_template, fn($q) => $q->where('template_id', $this->filtro_template))
              ->when($this->filtro_data_inicio, fn($q) => $q->whereDate('data_agendamento', '>=', $this->filtro_data_inicio))
              ->when($this->filtro_data_fim, fn($q) => $q->whereDate('data_agendamento', '<=', $this->filtro_data_fim));

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pega os templates para popular o filtro
        $templatesDisponiveis = \App\Modules\Comunicacao\Domain\Models\EmailTemplate::orderBy('nome')->get();

        return view('livewire.comunicacao.comunicado.comunicado-manager', [
            'registros' => $query->paginate($this->porPagina),
            'templatesDisponiveis' => $templatesDisponiveis
        ])->layout('components.layouts.app', ['title' => 'Gestão de Comunicados']);
    }
}