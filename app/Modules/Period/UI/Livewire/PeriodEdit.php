<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Models\OfertaVaga;

#[Layout('components.layouts.app')]
#[Title('Editar Ciclo - Administrativo')]
class PeriodEdit extends Component
{
    public $cicloId, $nome, $ano, $semestre, $data_inicio, $data_fim, $status;
    
    // Arrays de Relacionamentos do Ciclo
    public array $unidadesSelecionadas = []; 
    public array $cursosSelecionados = []; 
    public array $turnosSelecionados = []; 
    public array $statusSelecionados = [];
    public array $ofertasVagas = [];

    // Controles do Explorer (macOS Style)
    public $activeUnidadeId = null;
    public $activeCursoId = null;

    public function mount($id)
    {
        $ciclo = Ciclo::with(['unidades', 'cursos', 'turnos', 'statusPipeline'])->findOrFail($id);
        
        $this->cicloId = $ciclo->id;
        $this->nome = $ciclo->nome;
        $this->ano = $ciclo->ano;
        $this->semestre = $ciclo->semestre;
        $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
        $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
        $this->status = $ciclo->status;
        
        // Povoa as marcações salvas
        $this->unidadesSelecionadas = $ciclo->unidades->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->cursosSelecionados = $ciclo->cursos->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->turnosSelecionados = $ciclo->turnos->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->statusSelecionados = $ciclo->statusPipeline->pluck('id')->map(fn($v) => (string)$v)->toArray();

        $ofertas = OfertaVaga::where('ciclo_id', $id)->get();
        foreach ($ofertas as $oferta) {
            $this->ofertasVagas[] = [
                'unidade_id' => $oferta->unidade_id,
                'curso_id' => $oferta->curso_id,
                'turno_id' => $oferta->turno_id,
                'vagas' => $oferta->vagas,
            ];
        }
    }

    // --- MÉTODOS DO EXPLORER ---
    public function setActiveUnidade($id)
    {
        $this->activeUnidadeId = $id;
        $this->activeCursoId = null; // Reseta a terceira coluna
    }

    public function setActiveCurso($id)
    {
        $this->activeCursoId = $id;
    }

    // --- MÉTODOS DE VAGAS ---
    public function updatedOfertasVagas($value, $name)
    {
        $parts = explode('.', $name);
        if (count($parts) === 2) {
            $index = $parts[0];
            $campo = $parts[1];

            if ($campo === 'unidade_id') {
                $this->ofertasVagas[$index]['curso_id'] = '';
                $this->ofertasVagas[$index]['turno_id'] = '';
            } elseif ($campo === 'curso_id') {
                $this->ofertasVagas[$index]['turno_id'] = '';
            }
        }
    }

    public function toggleTodosStatus()
    {
        $todos = StatusInscricao::pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->statusSelecionados = (count($this->statusSelecionados) === count($todos)) ? [] : $todos;
    }

    public function addOferta()
    {
        $this->ofertasVagas[] = ['unidade_id' => '', 'curso_id' => '', 'turno_id' => '', 'vagas' => 0];
    }

    public function removeOferta($index)
    {
        unset($this->ofertasVagas[$index]);
        $this->ofertasVagas = array_values($this->ofertasVagas);
    }

    // --- SALVAMENTO ---
    public function salvar()
    {
        $this->validate([
            'nome' => 'nullable|string|max:255',
            'ano' => 'required|integer',
            'semestre' => 'required|integer',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date',
        ]);

        if ($this->status) {
            Ciclo::where('id', '!=', $this->cicloId)->update(['status' => false]);
        }

        $cicloSalvo = Ciclo::findOrFail($this->cicloId);
        $cicloSalvo->update([
            'nome' => empty(trim($this->nome)) ? "{$this->ano} - {$this->semestre}º Semestre" : $this->nome,
            'ano' => $this->ano,
            'semestre' => $this->semestre,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
            'status' => $this->status,
        ]);

        // Sincroniza todas as tabelas pivô
        $cicloSalvo->unidades()->sync($this->unidadesSelecionadas);
        $cicloSalvo->cursos()->sync($this->cursosSelecionados);
        $cicloSalvo->turnos()->sync($this->turnosSelecionados);

        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) {
            $syncStatus[$statusId] = ['ordem' => $index + 1];
        }
        $cicloSalvo->statusPipeline()->sync($syncStatus);

        OfertaVaga::where('ciclo_id', $cicloSalvo->id)->delete();
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['curso_id']) && !empty($oferta['unidade_id']) && !empty($oferta['turno_id'])) {
                OfertaVaga::create([
                    'ciclo_id' => $cicloSalvo->id,
                    'unidade_id' => $oferta['unidade_id'],
                    'curso_id' => $oferta['curso_id'],
                    'turno_id' => $oferta['turno_id'],
                    'vagas' => (int) ($oferta['vagas'] ?? 0),
                ]);
            }
        }

        session()->flash('sucesso', 'Ciclo e grade de vagas atualizados com sucesso!');
        return redirect()->route('ciclos.index');
    }

    public function render()
    {
        return view('livewire.period.period-edit', [
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb' => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'statusDisponiveis' => StatusInscricao::orderBy('nome')->get(),
        ]);
    }
}