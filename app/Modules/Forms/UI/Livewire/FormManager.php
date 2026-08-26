<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app')]
#[Title('Gerenciamento de Formulários')]
class FormManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public $formId = null;
    
    // Dados Básicos
    public $titulo, $descricao, $status = false;
    
    // Travas de Tempo e Acesso
    public $data_inicio = null;
    public $data_fim = null;
    public $acesso_livre = true;
    public $apenas_estudantes = false;
    public $roles_permitidas = [];

    public $modelClass = Formulario::class;
    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('formulario.listar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.listar'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function abrirModal($id = null)
    {
        // Limpa tudo antes de abrir
        $this->reset(['formId', 'titulo', 'descricao', 'status', 'data_inicio', 'data_fim', 'acesso_livre', 'apenas_estudantes', 'roles_permitidas']);
        $this->resetValidation();

        if ($id) {
            $form = Formulario::findOrFail($id);
            $this->formId = $form->id;
            $this->titulo = $form->titulo;
            $this->descricao = $form->descricao;
            $this->status = $form->status;
            $this->data_inicio = $form->data_inicio ? $form->data_inicio->format('Y-m-d\TH:i') : null;
            $this->data_fim = $form->data_fim ? $form->data_fim->format('Y-m-d\TH:i') : null;
            $this->acesso_livre = $form->acesso_livre;
            $this->apenas_estudantes = $form->apenas_estudantes;
            $this->roles_permitidas = is_array($form->roles_permitidas) ? $form->roles_permitidas : [];
        } else {
            $this->acesso_livre = true; // Formulários novos começam como públicos por padrão
        }

        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ]);

        $dados = [
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'status' => $this->status,
            'tipo' => 'geral',
            'data_inicio' => $this->data_inicio ?: null,
            'data_fim' => $this->data_fim ?: null,
            'acesso_livre' => $this->acesso_livre,
            'apenas_estudantes' => $this->acesso_livre ? false : $this->apenas_estudantes,
            'roles_permitidas' => $this->acesso_livre ? null : $this->roles_permitidas,
        ];

        // Se for novo formulário, cria a URL amigável
        if (!$this->formId) {
            $dados['slug'] = Str::slug($this->titulo) . '-' . Str::random(5);
        }

        Formulario::updateOrCreate(['id' => $this->formId], $dados);

        $this->modalAberto = false;
        $this->dispatch('sucesso', msg: 'Formulário salvo com sucesso!');
    }

    public function excluir($id)
    {
        Formulario::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Formulário excluído!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'titulo', 'label' => 'Formulário', 'sortable' => true],
            ['key' => 'acesso', 'label' => 'Regras de Acesso', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Formulario::query()->where('tipo', 'geral');

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.forms.form-manager', [
            'registros' => $query->paginate($this->porPagina),
            // Trazemos os papéis do sistema para permitir restrição caso "Acesso Livre" seja desativado
            'rolesDb' => Role::where('name', '!=', 'dev')->orderBy('name')->get() 
        ]);
    }
}