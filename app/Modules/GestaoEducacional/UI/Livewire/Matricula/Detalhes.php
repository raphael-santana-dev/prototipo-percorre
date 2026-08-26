<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Matricula;

use Livewire\Component;
use App\Models\Matricula;
use App\Models\Turma;
use App\Modules\Student\Domain\Models\Student;
use Illuminate\Support\Facades\DB;

class Detalhes extends Component
{
    public $matriculaId;
    public $numero_matricula, $student_id, $curso_id, $unidade_id, $turno_id, $status = 'ativa';
    
    public array $turmas_selecionadas = [];

    // Coleções para os Selects
    public $estudantes = [];
    public $cursos = [];
    public $unidades = [];
    public $turnos = [];
    public $turmasDisponiveis = [];

    public function mount($id = null)
    {
        if ($id) {
            abort_if(!feature('matricula.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.editar'), 403);
        } else {
            abort_if(!feature('matricula.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.criar'), 403);
        }

        $this->estudantes = Student::select('id', 'name', 'cpf')->orderBy('name')->get();
        $this->cursos = DB::table('cursos')->select('id', 'nome')->where('status', 'Ativo')->orderBy('nome')->get();
        $this->unidades = DB::table('unidades')->select('id', 'nome')->where('status', 'Ativa')->orderBy('nome')->get();
        $this->turnos = DB::table('turnos')->select('id', 'nome')->where('status', true)->get();
        $this->turmasDisponiveis = Turma::select('id', 'nome', 'ano')->where('status', true)->orderBy('ano', 'desc')->get();

        if ($id) {
            $matricula = Matricula::with('turmas')->findOrFail($id);
            $this->matriculaId = $matricula->id;
            $this->numero_matricula = $matricula->numero_matricula;
            $this->student_id = $matricula->student_id;
            $this->curso_id = $matricula->curso_id;
            $this->unidade_id = $matricula->unidade_id;
            $this->turno_id = $matricula->turno_id;
            $this->status = $matricula->status;
            
            // Puxa as turmas que o aluno já está matriculado
            $this->turmas_selecionadas = $matricula->turmas->pluck('id')->toArray();
        } else {
            // Gera um número automático (RA) para facilitar
            $this->numero_matricula = 'RA' . now()->format('Y') . rand(1000, 9999);
        }
    }

    public function salvar()
    {
        if ($this->matriculaId) {
            abort_if(!feature('matricula.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.editar'), 403);
        } else {
            abort_if(!feature('matricula.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.criar'), 403);
        }
        
        $this->validate([
            'numero_matricula' => 'required|string|unique:matriculas,numero_matricula,' . $this->matriculaId,
            'student_id' => 'required|exists:students,id',
            'curso_id' => 'required|exists:cursos,id',
            'unidade_id' => 'required|exists:unidades,id',
            'turno_id' => 'required|exists:turnos,id',
            'status' => 'required|string',
            'turmas_selecionadas' => 'array'
        ]);

        $matricula = Matricula::updateOrCreate(
            ['id' => $this->matriculaId],
            [
                'numero_matricula' => $this->numero_matricula,
                'student_id' => $this->student_id,
                'curso_id' => $this->curso_id,
                'unidade_id' => $this->unidade_id,
                'turno_id' => $this->turno_id,
                'status' => strtolower($this->status),
            ]
        );

        // Sincroniza a tabela Pivot (matricula_turma)
        $matricula->turmas()->sync($this->turmas_selecionadas);

        session()->flash('sucesso', 'Matrícula salva com sucesso!');
        return redirect()->route('matriculas.index');
    }

    public function render()
    {
        return view('livewire.gestao-educacional.matricula.detalhes')
            ->layout('components.layouts.app', ['title' => 'Detalhes da Matrícula']);
    }
}