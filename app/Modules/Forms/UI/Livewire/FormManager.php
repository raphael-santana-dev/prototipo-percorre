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
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function abrirModal($id = null) {
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
        Formulario::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Formulário excluído.');
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