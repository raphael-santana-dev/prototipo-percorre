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
    public bool $permite_estado_diferente = false;

    public array $unidadesSelecionadas = [];
    public array $turnosSelecionados = [];

    public $modelClass = Curso::class;
    public array $breadcrumbs = [];

    public function mount() 
    {
        abort_if(!feature('curso.listar'), 403, 'O módulo de cursos está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.listar'), 403, 'Você não tem permissão para listar cursos.');

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function openModal() 
    {
        abort_if(!feature('curso.criar'), 403, 'A criação de novos cursos está desativada no momento.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.criar'), 403, 'Você não tem permissão para criar cursos.');
        
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function save(CursoService $service) 
    {
        if ($this->isEditMode) {
            abort_if(!feature('curso.editar'), 403, 'A edição de cursos está desativada no momento.');
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.editar'), 403, 'Você não tem permissão para editar cursos.');
        } else {
            abort_if(!feature('curso.criar'), 403, 'A criação de novos cursos está desativada no momento.');
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.criar'), 403, 'Você não tem permissão para criar cursos.');
        }

        $this->validate([
            'nome' => 'required|string|max:255',
            'status' => 'required',
            'permite_estado_diferente' => 'boolean',
        ]);

        $dados = [
            'nome' => $this->nome,
            'slug' => \Illuminate\Support\Str::slug($this->nome),
            'status' => $this->status,
            'permite_estado_diferente' => $this->permite_estado_diferente,
        ];

        if ($this->isEditMode) {
            $service->atualizarCurso($this->cursoId, $dados);
            $cursoId = $this->cursoId;
        } else {
            $cursoCriado = $service->criarCurso($dados);
            $cursoId = $cursoCriado->id;
        }

        $service->sincronizarRelacionamentos($cursoId, $this->unidadesSelecionadas, $this->turnosSelecionados);

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Curso atualizado!' : 'Curso cadastrado!');
    }

    public function edit(CursoService $service, int $id) 
    {
        abort_if(!feature('curso.editar'), 403, 'A edição de cursos está desativada no momento.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.editar'), 403, 'Você não tem permissão para editar cursos.');

        $this->resetInputFields();
        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);
        
        $this->cursoId = $curso->id;
        $this->nome = $curso->nome;
        $this->status = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo']) ? 'Ativo' : 'Inativo';
        $this->permite_estado_diferente = $curso->permite_estado_diferente;
        $this->unidadesSelecionadas = $curso->unidades->pluck('id')->toArray();
        $this->turnosSelecionados = $curso->turnosVinculados->pluck('id')->toArray();
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(CursoService $service, int $id)
    {
        abort_if(!feature('curso.excluir'), 403, 'A exclusão de cursos está desativada no momento.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.excluir'), 403, 'Você não tem permissão para excluir cursos.');
        
        $service->deletarCurso($id);
        $this->dispatch('sucesso', msg: 'Curso movido para a lixeira.');
    }

    private function resetInputFields() 
    {
        $this->reset(['cursoId', 'nome', 'isEditMode', 'unidadesSelecionadas', 'turnosSelecionados']);
        $this->status = 'Ativo';
        $this->permite_estado_diferente = false;
        $this->resetErrorBag();
    }

    public function showQuickView(CursoService $service, int $id)
    {
        abort_if(!feature('curso.visualizar'), 403, 'A visualização de detalhes está desativada.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('curso.visualizar'), 403, 'Você não tem permissão para visualizar o cursos.');

        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);

        $restricao = $curso->permite_estado_diferente ? 'Aceita alunos de outros Estados' : 'Apenas residentes do Estado local';

        $this->dispatch('load-quick-view', [
            'title' => $curso->nome,
            'subtitle' => 'Status: ' . $curso->status,
            'icon' => 'ph-graduation-cap',
            'data' => [
                'Slug / URL' => $curso->slug,
                'Restrição Geográfica' => $restricao,
                'Disponibilidade' => $curso->unidades->count() . ' unidades | ' . $curso->turnosVinculados->count() . ' turnos',
                'Mais Detalhes' => '<a href="'.route('cursos.show', $curso->id).'" class="font-bold text-purpura-600 hover:underline">Ver Página Completa</a>'
            ]
        ]);
    }

    public function getHeadersProperty() {
        return [
            ['key' => 'id', 'label' => 'Nome do Curso', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render(CursoService $service) 
    {
        $query = \App\Models\Curso::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $cursos = $query->paginate($this->porPagina);

        return view('livewire.curso.curso-manager', [
            'registros' => $cursos,
            'unidadesDisponiveis' => \App\Modules\Unidade\Domain\Models\Unidade::where('status', 'Ativa')->orderBy('nome')->get(),
            'turnosDisponiveis' => \App\Modules\Turno\Domain\Models\Turno::orderBy('id')->get()
        ]);
    }
}