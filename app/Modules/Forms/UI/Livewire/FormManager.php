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
use App\Models\User;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.app')]
#[Title('Gerenciamento de Formulários')]
class FormManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public $formId = null;
    
    public $titulo, $descricao, $status = false;
    
    public $data_inicio = null;
    public $data_fim = null;
    public $acesso_livre = true;
    public $apenas_estudantes = false;
    public $exigir_email = false;
    
    // Arrays de restrição
    public $roles_permitidas = [];
    public $users_permitidos = [];
    public $unidades_permitidas = [];
    public $cursos_permitidos = [];
    public $turnos_permitidas = [];

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
        $this->reset(['formId', 'titulo', 'descricao', 'status', 'data_inicio', 'data_fim', 'acesso_livre', 'apenas_estudantes', 'exigir_email', 'roles_permitidas', 'users_permitidos', 'unidades_permitidas', 'cursos_permitidos', 'turnos_permitidas']);
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
            $this->exigir_email = $form->exigir_email;
            
            $this->roles_permitidas = is_array($form->roles_permitidas) ? $form->roles_permitidas : [];
            $this->users_permitidos = is_array($form->users_permitidos) ? $form->users_permitidos : [];
            $this->unidades_permitidas = is_array($form->unidades_permitidas) ? $form->unidades_permitidas : [];
            $this->cursos_permitidos = is_array($form->cursos_permitidos) ? $form->cursos_permitidos : [];
            $this->turnos_permitidas = is_array($form->turnos_permitidas) ? $form->turnos_permitidas : [];
        }

        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
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
            'exigir_email' => $this->acesso_livre ? $this->exigir_email : false, // E-mail obriga apenas em forms livres (logados já têm e-mail)
            'apenas_estudantes' => $this->acesso_livre ? false : $this->apenas_estudantes,
            'roles_permitidas' => $this->acesso_livre ? null : $this->roles_permitidas,
            'users_permitidos' => $this->acesso_livre ? null : $this->users_permitidos,
            'unidades_permitidas' => $this->acesso_livre ? null : $this->unidades_permitidas,
            'cursos_permitidos' => $this->acesso_livre ? null : $this->cursos_permitidos,
            'turnos_permitidas' => $this->acesso_livre ? null : $this->turnos_permitidas,
        ];

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

        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        else $query->orderBy('id', 'desc');

        return view('livewire.forms.form-manager', [
            'registros' => $query->paginate($this->porPagina),
            'rolesDb' => Role::where('name', '!=', 'dev')->orderBy('name')->get(),
            'usersDb' => User::orderBy('name')->get(),
            'unidadesDb' => Unidade::orderBy('nome')->get(),
            'cursosDb' => Curso::orderBy('nome')->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}