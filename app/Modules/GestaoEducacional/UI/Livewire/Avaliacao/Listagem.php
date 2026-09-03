<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Avaliacao;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Traits\ComPadraoListagem; 

class Listagem extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';

    public function mount()
    {
        abort_if(!feature('avaliacao.listar'), 403, 'Sistema de avaliações desativado temporariamente.');
    }
    
    public function getHeadersProperty()
    {
        return [
            ['key' => 'student', 'label' => 'Estudante', 'sortable' => false],
            ['key' => 'turma', 'label' => 'Turma / Ciclo', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status da Matriz', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function render()
    {
        $queryIdsUnicos = AlunoAvaliacao::selectRaw('MIN(id)')
            ->whereNull('deleted_at')
            ->groupBy('periodo_id', 'turma_id', 'student_id');

        $query = AlunoAvaliacao::with(['student', 'turma', 'periodo'])
            ->whereIn('id', $queryIdsUnicos);

        $isStudent = auth()->guard('student')->check();
        $isProfessor = auth()->guard('web')->check() && auth()->guard('web')->user()->hasRole('professor');

        if ($isStudent) {
            $query->where('student_id', auth()->guard('student')->id());
        } elseif ($isProfessor) {
            $turmasDoProfessor = DB::table('professor_turma')
                ->where('user_id', auth()->guard('web')->id())
                ->pluck('turma_id');
            $query->whereIn('turma_id', $turmasDoProfessor);
        }

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

        $paginacao = $query->paginate($this->porPagina);

        if ($paginacao->count() > 0) {
            $periodoIds = $paginacao->pluck('periodo_id')->unique();
            $studentIds = $paginacao->pluck('student_id')->unique();
            $turmaIds = $paginacao->pluck('turma_id')->unique();

            $todasFases = AlunoAvaliacao::whereIn('periodo_id', $periodoIds)
                ->whereIn('student_id', $studentIds)
                ->whereIn('turma_id', $turmaIds)
                ->get();

            foreach($paginacao as $reg) {
                $fasesDoAluno = $todasFases->where('periodo_id', $reg->periodo_id)
                                           ->where('student_id', $reg->student_id)
                                           ->where('turma_id', $reg->turma_id);
                $totalFases = $fasesDoAluno->count();
                $concluidas = $fasesDoAluno->where('status', '2')->count();
                
                $reg->isFinalizado = ($totalFases > 0 && $concluidas === $totalFases);
                $reg->progressoTexto = "{$concluidas}/{$totalFases}";
            }
        }

        $layout = $isStudent ? 'components.layouts.student-app' : 'components.layouts.app';

        return view('livewire.gestao-educacional.avaliacao.listagem', [
            'registros' => $paginacao
        ])->layout($layout, ['title' => 'Minhas Avaliações']);
    }
}