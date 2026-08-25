<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Formulários - Administrativo')]
class FormManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public $formId = null;
    public $titulo = '';
    public $descricao = '';
    public $status = true;

    public $modelClass = Formulario::class;
    public array $breadcrumbs = [];

    public function mount() {
        abort_if(!feature('formulario.listar'), 403, 'Módulo de formulários desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.listar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function abrirModal($id = null) {
        if ($id) {
            abort_if(!feature('formulario.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.editar'), 403);
        } else {
            abort_if(!feature('formulario.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.criar'), 403);
        }

        $this->resetValidation();
        $this->reset(['formId', 'titulo', 'descricao']);
        if ($id) {
            $form = Formulario::findOrFail($id);
            $this->formId = $form->id;
            $this->titulo = $form->titulo;
            $this->descricao = $form->descricao;
            $this->status = $form->status;
        }
        $this->modalAberto = true;
    }

    public function salvar() {
        if ($this->formId) {
            abort_if(!feature('formulario.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.editar'), 403);
        } else {
            abort_if(!feature('formulario.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.criar'), 403);
        }

        $this->validate(['titulo' => 'required|min:3']);

        Formulario::updateOrCreate(
            ['id' => $this->formId],
            [
                'titulo' => $this->titulo,
                'slug' => Str::slug($this->titulo) . '-' . time(),
                'descricao' => $this->descricao,
                'status' => $this->status
            ]
        );

        $this->modalAberto = false;
        $this->dispatch('sucesso', msg: 'Formulário salvo com sucesso!');
    }

    public function excluir($id) {
        abort_if(!feature('formulario.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.excluir'), 403);

        Formulario::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Formulário excluído.');
    }

    public function toggleStatus($id) {
        abort_if(!feature('formulario.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.editar'), 403);
        $this->traitToggleStatus($id);
    }

    public function getHeadersProperty() {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'titulo', 'label' => 'Título do Formulário', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render() {
        $query = Formulario::query()->withCount('respostas');
        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        else $query->orderBy('id', 'desc');

        return view('livewire.forms.form-manager', ['registros' => $query->paginate($this->porPagina)]);
    }
}