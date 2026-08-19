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
        $this->permiteGrid = false;
    }

    public function previewTemplate($templateId)
    {
        $template = \App\Modules\Comunicacao\Domain\Models\EmailTemplate::find($templateId);
        
        if ($template) {
            $this->dispatch('load-quick-view', [
                'title' => 'Pré-visualização do Template',
                'subtitle' => 'Layout que será disparado por esta automação',
                'icon' => 'ph-layout',
                'data' => [
                    'Nome do Template' => $template->nome,
                    'Assunto do E-mail' => '<div class="font-bold text-gray-900">'.$template->assunto.'</div>',
                    'Corpo da Mensagem' => '<div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 mt-2 max-h-96 overflow-y-auto">'.($template->conteudo ?? '<i>Sem conteúdo configurado.</i>').'</div>'
                ]
            ]);
        } else {
            $this->dispatch('erro', msg: 'Template não encontrado ou excluído.');
        }
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