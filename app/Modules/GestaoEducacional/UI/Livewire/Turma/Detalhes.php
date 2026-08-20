<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Turma;

use Livewire\Component;
use App\Models\Turma;
use Illuminate\Support\Facades\DB;

class Detalhes extends Component
{
    public $turmaId;
    public $nome, $ano, $ciclo_id, $curso_id, $unidade_id, $turno_id, $status = true;
    
    // Arrays para preencher os Selects
    public $ciclos = [], $cursos = [], $unidades = [], $turnos = [];
    
    // Coleções para exibir Professores e Alunos na aba de visualização
    public $professores = [];
    public $matriculas = [];

    public function mount($id = null)
    {
        // Carrega dependências estruturais do banco de dados direto das tabelas base
        $this->ciclos = DB::table('ciclos')->select('id', 'nome', 'ano', 'semestre')->orderBy('ano', 'desc')->get();
        $this->cursos = DB::table('cursos')->select('id', 'nome')->where('status', 'Ativo')->orderBy('nome')->get();
        $this->unidades = DB::table('unidades')->select('id', 'nome')->where('status', 'Ativa')->orderBy('nome')->get();
        $this->turnos = DB::table('turnos')->select('id', 'nome', 'horario_inicio')->where('status', true)->get();

        if ($id) {
            $turma = Turma::with(['professores', 'matriculas.student'])->findOrFail($id);
            
            $this->turmaId = $turma->id;
            $this->nome = $turma->nome;
            $this->ano = $turma->ano;
            $this->ciclo_id = $turma->ciclo_id;
            $this->curso_id = $turma->curso_id;
            $this->unidade_id = $turma->unidade_id;
            $this->turno_id = $turma->turno_id;
            $this->status = (bool) $turma->status;

            $this->professores = $turma->professores;
            $this->matriculas = $turma->matriculas;
        } else {
            $this->ano = date('Y');
        }
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'ano' => 'required|digits:4',
            'ciclo_id' => 'required|exists:ciclos,id',
            'curso_id' => 'required|exists:cursos,id',
            'unidade_id' => 'required|exists:unidades,id',
            'turno_id' => 'required|exists:turnos,id',
            'status' => 'boolean',
        ]);

        Turma::updateOrCreate(
            ['id' => $this->turmaId],
            [
                'nome' => $this->nome,
                'ano' => $this->ano,
                'ciclo_id' => $this->ciclo_id,
                'curso_id' => $this->curso_id,
                'unidade_id' => $this->unidade_id,
                'turno_id' => $this->turno_id,
                'status' => $this->status,
            ]
        );

        session()->flash('sucesso', 'Turma salva com sucesso!');
        return redirect()->route('turmas.index');
    }

    public function render()
    {
        return view('livewire.gestao-educacional.turma.detalhes')
            ->layout('components.layouts.app', ['title' => 'Detalhes da Turma']);
    }
}