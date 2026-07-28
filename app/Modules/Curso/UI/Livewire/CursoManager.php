<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Curso\Application\Services\CursoService;
use Illuminate\Support\Str;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Cursos - Administrativo')]
class CursoManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $isEditMode = false;
    
    public ?int $cursoId = null;
    public string $nome = '';
    public string $status = 'Ativo';
    public ?int $min_idade = null;
    public ?int $max_idade = null;
    public bool $permite_estado_diferente = false;

    public array $unidadesSelecionadas = [];
    public array $turnosSelecionados = [];

    public function mount() 
    { 
        // Supondo que você use esse padrão de permissão
        abort_if(!auth()->user()->can('curso.listar'), 403); 
    }

    public function openModal() 
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function save(CursoService $service) 
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'status' => 'required|in:Ativo,Inativo',
            'min_idade' => 'nullable|integer|min:0',
            'max_idade' => 'nullable|integer|gt:min_idade',
            'permite_estado_diferente' => 'boolean',
        ]);

        $dados = [
            'nome' => $this->nome,
            'slug' => \Illuminate\Support\Str::slug($this->nome),
            'status' => $this->status,
            'min_idade' => $this->min_idade,
            'max_idade' => $this->max_idade,
            'permite_estado_diferente' => $this->permite_estado_diferente,
        ];

        if ($this->isEditMode) {
            $service->atualizarCurso($this->cursoId, $dados);
            $cursoId = $this->cursoId;
        } else {
            $cursoCriado = $service->criarCurso($dados);
            $cursoId = $cursoCriado->id;
        }

        // Sincroniza Unidades e Turnos via DDD
        $service->sincronizarRelacionamentos($cursoId, $this->unidadesSelecionadas, $this->turnosSelecionados);

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Curso atualizado!' : 'Curso cadastrado!');
    }

    // 3. Atualizando o Edit
    public function edit(CursoService $service, int $id) 
    {
        $this->resetInputFields();
        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);
        
        $this->cursoId = $curso->id;
        $this->nome = $curso->nome;
        $this->status = $curso->status;
        $this->min_idade = $curso->min_idade;
        $this->max_idade = $curso->max_idade;
        $this->permite_estado_diferente = $curso->permite_estado_diferente;
        
        // Povoando os Arrays com os IDs
        $this->unidadesSelecionadas = $curso->unidades->pluck('id')->toArray();
        $this->turnosSelecionados = $curso->turnosVinculados->pluck('id')->toArray();
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(CursoService $service, int $id)
    {
        $service->deletarCurso($id);
        session()->flash('success', 'Curso movido para a lixeira.');
    }

    private function resetInputFields() 
    {
        $this->reset(['cursoId', 'nome', 'min_idade', 'max_idade', 'isEditMode', 'unidadesSelecionadas', 'turnosSelecionados']);
        $this->status = 'Ativo';
        $this->permite_estado_diferente = false;
        $this->resetErrorBag();
    }

    public function render(CursoService $service) 
    {
        return view('livewire.curso.curso-manager', [
            'cursos' => $service->listarTodos(),
            'unidadesDisponiveis' => \App\Models\Unidade::where('status', 'Ativa')->orderBy('nome')->get(),
            'turnosDisponiveis' => \App\Models\Turno::orderBy('id')->get() // Assumindo que a Model Turno existe
        ]);
    }
}