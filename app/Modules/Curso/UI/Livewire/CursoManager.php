<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Curso\Application\Services\CursoService;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use App\Models\Curso;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Cursos - Administrativo')]
class CursoManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

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

    public $modelClass = Curso::class;
    public array $breadcrumbs = [];

    public function mount() 
    {
        abort_if(!auth()->user()->can('curso.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
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

    public function showQuickView(CursoService $service, int $id)
    {
        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);

        $idadeMin = $curso->min_idade ? $curso->min_idade . ' anos' : 'Livre';
        $idadeMax = $curso->max_idade ? $curso->max_idade . ' anos' : 'Sem limite';
        $restricao = $curso->permite_estado_diferente ? 'Aceita alunos de outros Estados' : 'Apenas residentes do Estado local';

        $this->dispatch('load-quick-view', [
            'title' => $curso->nome,
            'subtitle' => 'Status: ' . $curso->status,
            'icon' => 'ph-graduation-cap',
            'data' => [
                'Slug / URL' => $curso->slug,
                'Regras de Idade' => $idadeMin . ' até ' . $idadeMax,
                'Restrição Geográfica' => $restricao,
                'Disponibilidade' => $curso->unidades->count() . ' unidades | ' . $curso->turnosVinculados->count() . ' turnos',
                'Mais Detalhes' => '<a href="'.route('cursos.show', $curso->id).'" class="font-bold text-purpura-600 hover:underline">Ver Página Completa</a>'
            ]
        ]);
    }

    public function getHeadersProperty() {
        return [
            ['key' => 'id', 'label' => 'Nome do Curso', 'sortable' => true],
            ['key' => 'min_idade', 'label' => 'Regras de Idade', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render(CursoService $service) 
    {
        // 1. Iniciamos o construtor de consultas
        $query = \App\Models\Curso::query();

        // 2. Aplicamos a ordenação dinâmica da Trait 'ComPadraoListagem'
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc'); // Ordenação padrão
        }

        // 3. Paginamos os resultados direto no banco de dados
        $cursos = $query->paginate($this->porPagina);

        return view('livewire.curso.curso-manager', [
            'registros' => $cursos,
            'unidadesDisponiveis' => \App\Modules\Unidade\Domain\Models\Unidade::where('status', 'Ativa')->orderBy('nome')->get(),
            'turnosDisponiveis' => \App\Modules\Turno\Domain\Models\Turno::orderBy('id')->get()
        ]);
    }
}