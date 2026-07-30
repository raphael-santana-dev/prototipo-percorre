<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Curso;
use App\Models\User;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Curso - Administrativo')]
class CursoDetalhes extends Component
{
    public Curso $curso;
    
    // Controle de Edição Inline
    public bool $isEditMode = false;
    
    // Campos editáveis
    public $nome;
    public $min_idade;
    public $max_idade;
    public $permite_estado_diferente;
    public $status;

    // Coleções para exibição
    public $professoresVinculados = [];

    public function mount(int $id)
    {
        abort_if(!auth()->user()->can('curso.listar'), 403);
        
        $this->carregarCurso($id);
    }

    public function carregarCurso($id)
    {
        // Carrega o curso com suas unidades, turnos e as últimas 10 inscrições
        $this->curso = Curso::with(['unidades', 'turnosVinculados', 'inscricoes' => function($q) {
            $q->latest()->take(10);
        }])->findOrFail($id);

        // Busca os usuários do grupo 'professor' que estão vinculados a este curso (Graças à nossa nova arquitetura N:M)
        $this->professoresVinculados = User::role('professor')
            ->whereHas('cursos', function($q) use ($id) {
                $q->where('cursos.id', $id);
            })->get();

        $this->preencherFormulario();
    }

    public function preencherFormulario()
    {
        $this->nome = $this->curso->nome;
        $this->min_idade = $this->curso->min_idade;
        $this->max_idade = $this->curso->max_idade;
        $this->permite_estado_diferente = (bool) $this->curso->permite_estado_diferente;
        $this->status = $this->curso->status;
    }

    public function toggleEditMode()
    {
        abort_if(!auth()->user()->can('curso.editar'), 403);
        $this->isEditMode = !$this->isEditMode;
        
        // Se cancelou a edição, restaura os dados originais
        if (!$this->isEditMode) {
            $this->preencherFormulario();
            $this->resetErrorBag();
        }
    }

    public function salvarAlteracoes()
    {
        abort_if(!auth()->user()->can('curso.editar'), 403);

        $this->validate([
            'nome' => 'required|string|min:3',
            'min_idade' => 'nullable|integer|min:0',
            'max_idade' => 'nullable|integer|gte:min_idade',
            'status' => 'required|in:Ativo,Inativo',
        ]);

        $this->curso->update([
            'nome' => $this->nome,
            'min_idade' => $this->min_idade,
            'max_idade' => $this->max_idade,
            'permite_estado_diferente' => $this->permite_estado_diferente,
            'status' => $this->status,
        ]);

        $this->isEditMode = false;
        session()->flash('success', 'Curso atualizado com sucesso!');
        
        // Recarrega os dados fresquinhos
        $this->carregarCurso($this->curso->id);
    }

    public function render()
    {
        return view('livewire.curso.curso-detalhes');
    }
}