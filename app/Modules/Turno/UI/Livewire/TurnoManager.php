<?php

namespace App\Modules\Turno\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Turno\Application\Services\TurnoService;
use Illuminate\Support\Str;

use App\Modules\Turno\Domain\Models\Turno;

use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Turnos - Administrativo')]
class TurnoManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;

    public bool $showModal = false;
    public bool $isEditMode = false;
    
    public ?int $turnoId = null;
    public string $nome = '';
    public string $horario_inicio = '';
    public string $horario_fim = '';

    public $modelClass = Turno::class;

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('turno.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.listar'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function openModal()
    {
        abort_if(!feature('turno.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.criar'), 403);
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function save(TurnoService $service)
    {
        if ($this->isEditMode) {
            abort_if(!feature('turno.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.editar'), 403);
        } else {
            abort_if(!feature('turno.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.criar'), 403);
        }

        $this->validate([
            'nome' => 'required|string|max:255|unique:turnos,nome' . ($this->turnoId ? ',' . $this->turnoId : ''),
            'horario_inicio' => 'required|date_format:H:i',
            'horario_fim' => 'required|date_format:H:i|after:horario_inicio',
        ]);

        $dados = [
            'nome' => $this->nome,
            'slug' => Str::slug($this->nome),
            'horario_inicio' => $this->horario_inicio,
            'horario_fim' => $this->horario_fim,
        ];

        if ($this->isEditMode) {
            $service->atualizarTurno($this->turnoId, $dados);
            $this->dispatch('sucesso', msg: 'Turno atualizado com sucesso!');
        } else {
            $service->criarTurno($dados);
            $this->dispatch('sucesso', msg: 'Turno criado com sucesso!');
        }

        $this->closeModal();
    }

    public function edit(TurnoService $service, int $id)
    {
        abort_if(!feature('turno.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.editar'), 403);
        
        $turno = $service->buscarPorId($id);
        
        $this->turnoId = $turno->id;
        $this->nome = $turno->nome;
        // Formata os campos de tempo para o input html H:i
        $this->horario_inicio = \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i');
        $this->horario_fim = \Carbon\Carbon::parse($turno->horario_fim)->format('H:i');
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(TurnoService $service, int $id)
    {
        abort_if(!feature('turno.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.excluir'), 403);
        
        $service->deletarTurno($id);
        $this->dispatch('sucesso', msg: 'Turno excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->turnoId = null;
        $this->nome = '';
        $this->horario_inicio = '';
        $this->horario_fim = '';
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome / Período', 'sortable' => true],
            ['key' => 'horario_inicio', 'label' => 'Início', 'sortable' => true],
            ['key' => 'horario_fim', 'label' => 'Fim', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render(TurnoService $service)
    {
        $query = Turno::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('nome', 'asc');
        }

        $turnos = $query->paginate($this->porPagina);

        return view('livewire.turno.turno-manager', [
            'registros' => $turnos,
        ]);
    }

    public function toggleStatus($id)
    {
        abort_if(!feature('turno.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('turno.editar'), 403);
        $this->traitToggleStatus($id);
    }
}