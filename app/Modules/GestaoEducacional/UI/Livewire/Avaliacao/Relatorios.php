<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\Avaliacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Models\Turma;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacaoItem;
use App\Traits\ComPadraoListagem;

class Relatorios extends Component
{
    use WithPagination, ComPadraoListagem;

    public $busca = '';
    public $periodoFiltro = '';
    public $turmaFiltro = '';

    public function mount()
    {
        abort_if(!feature('relatorio.acessar'), 403, 'Relatórios desativados.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('relatorio.acessar'), 403);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'student', 'label' => 'Estudante', 'sortable' => false],
            ['key' => 'turma', 'label' => 'Turma / Ciclo', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'media_parcial', 'label' => 'M. Parcial', 'sortable' => false, 'class' => 'text-center w-24'],
            ['key' => 'media_final', 'label' => 'M. Final', 'sortable' => false, 'class' => 'text-center w-24'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function updatingBusca() { $this->resetPage(); }
    public function updatingPeriodoFiltro() { $this->resetPage(); }
    public function updatingTurmaFiltro() { $this->resetPage(); }

    private function getQueryBase()
    {
        $queryIdsUnicos = AlunoAvaliacao::selectRaw('MAX(id)')
            ->whereNull('deleted_at')
            ->groupBy('student_id', 'turma_id', 'periodo_id');

        $query = AlunoAvaliacao::with(['student', 'turma', 'periodo'])
            ->whereIn('id', $queryIdsUnicos);

        if ($this->periodoFiltro) {
            $query->where('periodo_id', $this->periodoFiltro);
        }

        if ($this->turmaFiltro) {
            $query->where('turma_id', $this->turmaFiltro);
        }

        if ($this->busca) {
            $query->whereHas('student', function($q) {
                $q->where('name', 'ilike', '%' . $this->busca . '%')
                  ->orWhere('cpf', 'like', '%' . $this->busca . '%');
            });
        }

        return $query;
    }

    public function exportarCSV()
    {
        abort_if(!feature('relatorio.exportar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('relatorio.exportar'), 403);
        
        $registros = $this->getQueryBase()->get();
        $csvFileName = 'relatorio_avaliacoes_' . date('Ymd_His') . '.csv';

        $studentIds = $registros->pluck('student_id')->unique();
        $todosItens = AlunoAvaliacaoItem::join('aluno_avaliacoes', 'aluno_avaliacoes.id', '=', 'aluno_avaliacao_itens.aluno_avaliacao_id')
            ->whereIn('aluno_avaliacoes.student_id', $studentIds)
            ->select('aluno_avaliacao_itens.nivel_nps', 'aluno_avaliacoes.fase', 'aluno_avaliacoes.student_id', 'aluno_avaliacoes.periodo_id')
            ->get();

        $callback = function() use($registros, $todosItens) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 
            
            fputcsv($file, ['ID_ALUNO', 'NOME_ALUNO', 'TURMA', 'ANO_PERIODO', 'CICLO', 'MEDIA_PARCIAL', 'MEDIA_FINAL'], ';');

            foreach ($registros as $reg) {
                $itensAluno = $todosItens->where('student_id', $reg->student_id)->where('periodo_id', $reg->periodo_id);
                $np = $itensAluno->whereIn('fase', ['1', '2'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));
                $nf = $itensAluno->whereIn('fase', ['3'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));
                
                fputcsv($file, [
                    $reg->student_id,
                    $reg->student->name ?? 'N/A',
                    $reg->turma->nome ?? 'N/A',
                    $reg->periodo->ano ?? 'N/A',
                    $reg->periodo->ciclo ?? 'N/A',
                    $np->count() > 0 ? round($np->avg(), 1) : '',
                    $nf->count() > 0 ? round($nf->avg(), 1) : ''
                ], ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $csvFileName, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $query = $this->getQueryBase();
        
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $paginacao = $query->paginate($this->porPagina);

        $totalAlunos = $paginacao->total();
        $mediaGeralParcial = '-';
        $mediaGeralFinal = '-';

        if ($totalAlunos > 0) {
            $studentIds = $paginacao->pluck('student_id')->unique();
            $periodoIds = $paginacao->pluck('periodo_id')->unique();
            $turmaIds = $paginacao->pluck('turma_id')->unique();

            $todosItens = AlunoAvaliacaoItem::join('aluno_avaliacoes', 'aluno_avaliacoes.id', '=', 'aluno_avaliacao_itens.aluno_avaliacao_id')
                ->whereIn('aluno_avaliacoes.student_id', $studentIds)
                ->whereIn('aluno_avaliacoes.periodo_id', $periodoIds)
                ->select('aluno_avaliacao_itens.nivel_nps', 'aluno_avaliacoes.fase', 'aluno_avaliacoes.student_id', 'aluno_avaliacoes.periodo_id')
                ->get();
                
            $todasFases = AlunoAvaliacao::whereIn('periodo_id', $periodoIds)
                ->whereIn('student_id', $studentIds)
                ->whereIn('turma_id', $turmaIds)
                ->get();

            $notasParciais = $todosItens->whereIn('fase', ['1', '2'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));
            $notasFinais = $todosItens->whereIn('fase', ['3'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));

            $mediaGeralParcial = $notasParciais->count() > 0 ? round($notasParciais->avg(), 1) : '-';
            $mediaGeralFinal = $notasFinais->count() > 0 ? round($notasFinais->avg(), 1) : '-';

            foreach ($paginacao as $reg) {
                $itensDesteAluno = $todosItens->where('student_id', $reg->student_id)->where('periodo_id', $reg->periodo_id);
                $np = $itensDesteAluno->whereIn('fase', ['1', '2'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));
                $nf = $itensDesteAluno->whereIn('fase', ['3'])->pluck('nivel_nps')->filter(fn($v) => is_numeric($v));
                
                $reg->mediaParcial = $np->count() > 0 ? round($np->avg(), 1) : '-';
                $reg->mediaFinal = $nf->count() > 0 ? round($nf->avg(), 1) : '-';
                
                $fasesDoAluno = $todasFases->where('periodo_id', $reg->periodo_id)
                                           ->where('student_id', $reg->student_id)
                                           ->where('turma_id', $reg->turma_id);
                $totalFases = $fasesDoAluno->count();
                $concluidas = $fasesDoAluno->where('status', '2')->count();
                $reg->isFinalizado = ($totalFases > 0 && $concluidas === $totalFases);
            }
        }

        $metricas = [
            [
                'label' => 'Alunos Avaliados',
                'value' => $totalAlunos,
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'icon' => '<i class="ph-fill ph-users text-2xl text-blue-500 dark:text-blue-400"></i>'
            ],
            [
                'label' => 'Média Geral (Parcial)',
                'value' => $mediaGeralParcial,
                'color_text' => 'text-indigo-600 dark:text-indigo-400',
                'color_bg' => 'bg-indigo-100 dark:bg-indigo-900/30',
                'icon' => '<i class="ph-fill ph-chart-line-up text-2xl text-indigo-500 dark:text-indigo-400"></i>'
            ],
            [
                'label' => 'Média Geral (Final)',
                'value' => $mediaGeralFinal,
                'color_text' => 'text-purpura-600 dark:text-purpura-400',
                'color_bg' => 'bg-purpura-100 dark:bg-purpura-900/30',
                'icon' => '<i class="ph-fill ph-medal text-2xl text-purpura-500 dark:text-purpura-400"></i>'
            ]
        ];

        return view('livewire.gestao-educacional.avaliacao.relatorios', [
            'registros' => $paginacao,
            'metricas' => $metricas,
            'periodosDisponiveis' => PeriodoAvaliacao::orderBy('ano', 'desc')->orderBy('ciclo', 'desc')->get(),
            'turmasDisponiveis' => Turma::orderBy('nome', 'asc')->get()
        ])->layout('components.layouts.app', ['title' => 'Relatórios de Avaliação']);
    }
}