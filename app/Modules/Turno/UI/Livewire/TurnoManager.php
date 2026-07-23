<?php

namespace App\Modules\Turno\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Turno\Application\Services\TurnoService;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Turnos - Administrativo')]
class TurnoManager extends Component
{
    public bool $showModal = false;
    public bool $isEditMode = false;
    
    public ?int $turnoId = null;
    public string $nome = '';
    public string $horario_inicio = '';
    public string $horario_fim = '';

    public function mount()
    {
        // Proteção de Rota - Exige a permissão base para acessar a tela
        abort_if(!auth()->user()->can('turno.listar'), 403, 'Acesso restrito.');
    }

    public function openModal()
    {
        abort_if(!auth()->user()->can('turno.criar'), 403);
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
            abort_if(!auth()->user()->can('turno.editar'), 403);
        } else {
            abort_if(!auth()->user()->can('turno.criar'), 403);
        }

        $this->validate([
            'nome' => 'required|string|max:255|unique:turnos,nome' . ($this->turnoId ? ',' . $this->turnoId : ''),
            'horario_inicio' => 'required|date_format:H:i',
            'horario_fim' => 'required|date_format:H:i|after:horario_inicio',
        ]);

        $dados = [
            'nome' => $this->nome,
            'horario_inicio' => $this->horario_inicio,
            'horario_fim' => $this->horario_fim,
        ];

        if ($this->isEditMode) {
            $service->atualizarTurno($this->turnoId, $dados);
            session()->flash('success', 'Turno atualizado com sucesso!');
        } else {
            $service->criarTurno($dados);
            session()->flash('success', 'Turno criado com sucesso!');
        }

        $this->closeModal();
    }

    public function edit(TurnoService $service, int $id)
    {
        abort_if(!auth()->user()->can('turno.editar'), 403);
        
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
        abort_if(!auth()->user()->can('turno.excluir'), 403);
        
        $service->deletarTurno($id);
        session()->flash('success', 'Turno excluído com sucesso!');
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

    public function render(TurnoService $service)
    {
        return view('livewire.turno.turno-manager', [
            'turnos' => $service->listarTodos()
        ]);
    }
}