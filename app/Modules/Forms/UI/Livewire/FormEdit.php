<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.app')]
#[Title('Configuração do Formulário')]
class FormEdit extends Component
{
    public $formId = null;
    
    // Dados Básicos
    public $titulo, $descricao, $status = false;
    public $data_inicio = null, $data_fim = null;
    
    // Controle de Acesso
    public $acesso_livre = true;
    public $apenas_estudantes = false;
    public $exigir_email = false;
    
    // Arrays de Permissões
    public array $roles_permitidas = [];
    public array $users_permitidos = [];
    public array $unidades_permitidas = [];
    public array $cursos_permitidos = [];
    public array $turnos_permitidas = [];

    // Controles do Explorer (Mac OS Style)
    public $activeUnidadeId = null;
    public $activeCursoId = null;

    public function mount($id = null)
    {
        abort_if(!feature('formulario.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.criar'), 403);

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
    }

    // --- MÉTODOS DO EXPLORER ---
    public function setActiveUnidade($id)
    {
        $this->activeUnidadeId = $id;
        $this->activeCursoId = null; // Reseta a terceira coluna ao trocar a primeira
    }

    public function setActiveCurso($id)
    {
        $this->activeCursoId = $id;
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
            'exigir_email' => $this->acesso_livre ? $this->exigir_email : false,
            'apenas_estudantes' => $this->acesso_livre ? false : $this->apenas_estudantes,
            
            // Grava os arrays de permissão apenas se o acesso for restrito
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

        session()->flash('sucesso', 'Formulário e Regras de Acesso configurados com sucesso!');
        return redirect()->route('formularios.index');
    }

    public function render()
    {
        return view('livewire.forms.form-edit', [
            'rolesDb' => Role::where('name', '!=', 'dev')->orderBy('name')->get(),
            'usersDb' => User::orderBy('name')->get(),
            'unidadesDb' => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb' => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}