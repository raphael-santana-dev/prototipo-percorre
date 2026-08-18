<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class AutomacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome da Regra', 'sortable' => true],
            ['key' => 'evento_gatilho', 'label' => 'Gatilho (Quando...)', 'sortable' => true],
            ['key' => 'template_id', 'label' => 'Template Enviado', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    // Função de Alternância de Status (Toggle)
    public function toggleStatus($id)
    {
        $automacao = Automacao::findOrFail($id);
        $automacao->status = !$automacao->status;
        $automacao->save();
        
        $this->dispatch('sucesso', msg: $automacao->status ? 'Automação Ativada!' : 'Automação Desativada!');
    }

    public function excluir($id)
    {
        Automacao::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Regra de automação excluída.');
    }

    public function render()
    {
        $query = Automacao::with('template');
        $query->orderBy($this->ordenacaoCampo ?: 'id', $this->ordenacaoDirecao);

        return view('livewire.comunicacao.automacao.automacao-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Gestão de Automações']);
    }
}