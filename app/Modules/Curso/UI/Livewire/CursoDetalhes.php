<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Curso;
use App\Models\User;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Curso - Administrativo')]
class CursoDetalhes extends Component
{
    // Armazenamos APENAS o ID de forma pública. Isso mata o Erro 419 para sempre.
    public int $cursoId;
    
    public bool $showModal = false;
    
    // Campos do Formulário
    public $nome;
    public $min_idade;
    public $max_idade;
    public $permite_estado_diferente;
    public $status;

    public array $unidadesSelecionadas = [];
    public array $turnosSelecionados = [];

    public function mount(int $id)
    {
        abort_if(!auth()->user()->can('curso.listar'), 403);
        $this->cursoId = $id; // Guarda apenas o número inteiro
    }

    // ==========================================
    // PROPRIEDADES COMPUTADAS (Segurança e Performance)
    // ==========================================
    
    #[Computed]
    public function curso()
    {
        // O Livewire busca no banco toda vez, sem precisar trafegar pela rede
        return Curso::with(['unidades', 'turnosVinculados'])->findOrFail($this->cursoId);
    }
    
    #[Computed]
    public function professoresVinculados()
    {
        return User::role('professor')
            ->whereHas('cursos', function($q) {
                $q->where('cursos.id', $this->cursoId);
            })->get();
    }

    #[Computed]
    public function inscricoesRecentes()
    {
        return $this->curso->inscricoes()->latest()->take(10)->get();
    }

    #[Computed]
    public function todasUnidades()
    {
        return Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get();
    }

    #[Computed]
    public function todosTurnos()
    {
        return Turno::orderBy('nome')->get();
    }

    // ==========================================
    // MÉTODOS DO MODAL
    // ==========================================

    public function openModal()
    {
        abort_if(!auth()->user()->can('curso.editar'), 403);
        
        $curso = $this->curso; // Acessa o método Computed

        $this->nome = $curso->nome;
        $this->min_idade = $curso->min_idade;
        $this->max_idade = $curso->max_idade;
        $this->permite_estado_diferente = (bool) $curso->permite_estado_diferente;
        $this->status = $curso->status;

        $this->unidadesSelecionadas = $curso->unidades->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->turnosSelecionados = $curso->turnosVinculados->pluck('id')->map(fn($id) => (string) $id)->toArray();

        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function salvarAlteracoes()
    {
        abort_if(!auth()->user()->can('curso.editar'), 403);

        $this->validate([
            'nome' => 'required|string|min:3',
            'min_idade' => 'nullable|integer|min:0',
            'max_idade' => 'nullable|integer|gte:min_idade',
            'status' => 'required|in:Ativo,Inativo',
            'unidadesSelecionadas' => 'nullable|array',
            'turnosSelecionados' => 'nullable|array',
        ]);

        $curso = $this->curso; // Acessa o método Computed

        $curso->update([
            'nome' => $this->nome,
            'min_idade' => $this->min_idade,
            'max_idade' => $this->max_idade,
            'permite_estado_diferente' => $this->permite_estado_diferente,
            'status' => $this->status,
        ]);

        $curso->unidades()->sync($this->unidadesSelecionadas);
        $curso->turnosVinculados()->sync($this->turnosSelecionados);

        unset($this->curso); // Limpa o cache computado para forçar a atualização visual

        $this->showModal = false;
        session()->flash('success', 'Curso e vínculos atualizados com sucesso!');
    }

    public function render()
    {
        return view('livewire.curso.curso-detalhes');
    }
}