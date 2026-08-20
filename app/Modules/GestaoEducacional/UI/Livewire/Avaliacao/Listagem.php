<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Avaliacao;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Traits\ComPadraoListagem; // Usando a trait padrão do seu projeto

class Listagem extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';

    public function getHeadersProperty()
    {
        return [
            ['key' => 'student', 'label' => 'Estudante', 'sortable' => false],
            ['key' => 'turma', 'label' => 'Turma / Ciclo', 'sortable' => false],
            ['key' => 'periodo', 'label' => 'Ano de Referência', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function render()
    {
        // 1. Subquery inteligente: Pega apenas 1 registro (a Fase 1) para representar a Matriz inteira
        $queryIdsUnicos = AlunoAvaliacao::selectRaw('MIN(id)')
            ->whereNull('deleted_at')
            ->groupBy('periodo_id', 'turma_id', 'student_id');

        $query = AlunoAvaliacao::with(['student', 'turma', 'periodo'])
            ->whereIn('id', $queryIdsUnicos);

        // 2. Isolamento de Perfil (Multiguard & Roles)
        $isStudent = auth()->guard('student')->check() || (auth()->check() && auth()->user()->hasRole('aluno|estudante'));
        $isProfessor = auth()->check() && auth()->user()->hasRole('professor');

        if ($isStudent) {
            // Aluno só vê as próprias avaliações
            $studentId = auth()->guard('student')->check() ? auth()->guard('student')->id() : auth()->id();
            $query->where('student_id', $studentId);
        } elseif ($isProfessor) {
            // Professor só vê alunos das turmas dele
            $turmasDoProfessor = DB::table('professor_turma')
                ->where('user_id', auth()->id())
                ->pluck('turma_id');
            $query->whereIn('turma_id', $turmasDoProfessor);
        }

        // 3. Filtro de Busca Textual
        if (!empty($this->busca)) {
            $query->where(function($q) {
                $q->whereHas('student', function($sq) {
                    $sq->where('name', 'ilike', '%' . $this->busca . '%')
                       ->orWhere('cpf', 'like', '%' . $this->busca . '%');
                })->orWhereHas('turma', function($sq) {
                    $sq->where('nome', 'ilike', '%' . $this->busca . '%');
                });
            });
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.gestao-educacional.avaliacao.listagem', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Minhas Avaliações']);
    }
}