<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class TemplateManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public $filtro_busca = '';

    public function updating($nomePropriedade) { if ($nomePropriedade === 'filtro_busca') $this->resetPage(); }
    
    public function limparFiltros() {
        $this->reset(['filtro_busca']);
        $this->resetPage();
    }

    public function mount()
    {
        abort_if(!feature('template.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome do Template', 'sortable' => true],
            ['key' => 'assunto', 'label' => 'Assunto do E-mail', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function preVisualizar($id)
    {
        $template = EmailTemplate::findOrFail($id);

        $conteudoHtml = $template->corpo ?? $template->conteudo ?? $template->html ?? '<p class="text-gray-500 italic">Sem conteúdo disponível.</p>';

        $html = '<div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 mt-2 shadow-inner">';
        $html .= '  <div class="mb-5 pb-4 border-b border-gray-200 dark:border-gray-700">';
        $html .= '      <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Assunto do E-mail:</span>';
        $html .= '      <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">' . $template->assunto . '</span>';
        $html .= '  </div>';
        $html .= '  <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300">';
        $html .=        $conteudoHtml;
        $html .= '  </div>';
        $html .= '</div>';

        $this->dispatch('load-quick-view', [
            'title' => 'Pré-visualização do Template',
            'subtitle' => $template->nome,
            'icon' => 'ph-envelope-open',
            'maxWidth' => '2xl', 
            'data' => [
                'Layout da Mensagem' => $html
            ]
        ]);
    }

    public function excluir($id)
    {
        abort_if(!feature('template.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.excluir'), 403);
        
        try {
            EmailTemplate::findOrFail($id)->delete();
            $this->dispatch('sucesso', msg: 'Template removido com sucesso!');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->dispatch('erro', msg: 'Não é possível excluir este template pois ele já está em uso.');
        }
    }

    public function render()
    {
        $query = EmailTemplate::query();
        $query->when($this->filtro_busca, function($q) {
            $q->where('nome', 'ilike', '%' . $this->filtro_busca . '%')
            ->orWhere('assunto', 'ilike', '%' . $this->filtro_busca . '%');
        });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.comunicacao.template.template-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Templates de E-mail']);
    }
}