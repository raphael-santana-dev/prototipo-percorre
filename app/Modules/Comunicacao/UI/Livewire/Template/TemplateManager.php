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
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function excluir($id)
    {
        abort_if(!feature('template.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.excluir'), 403);
        
        try {
            EmailTemplate::findOrFail($id)->delete();
            $this->dispatch('sucesso', msg: 'Template removido com sucesso!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Caso exista uma chave estrangeira segurando (ex: em uso num comunicado)
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