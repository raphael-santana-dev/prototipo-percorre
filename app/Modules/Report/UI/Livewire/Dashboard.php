<?php

namespace App\Modules\Report\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Models\Ciclo;
use App\Models\Inscricao;
use App\Models\OfertaVaga;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app')]
#[Title('Dashboard Estratégico - Relatórios')]
class Dashboard extends Component
{
    public $filtroCiclo = '';
    public $ciclosDb = [];
    public bool $carregando = true;

    // Estrutura dos 4 gráficos principais
    public array $graficoVagas = [];
    public array $graficoInscricoes = [];
    public array $graficoCursos = [];
    public array $graficoUnidades = [];
    
    // Estrutura do gráfico de detalhamento (Drill-down)
    public array $graficoDetalhado = [];
    public string $tituloDetalhado = '';

    public function mount()
    {
        $this->ciclosDb = Ciclo::orderBy('id', 'desc')->get();
        if ($this->ciclosDb->count() > 0) {
            $this->filtroCiclo = $this->ciclosDb->first()->id;
        }
        
        $this->inicializarEstruturaGraficos();
    }

    private function inicializarEstruturaGraficos()
    {
        $this->graficoVagas = ['title' => 'Ocupação de Vagas', 'type' => 'donut', 'height' => 300, 'series' => [], 'labels' => []];
        $this->graficoInscricoes = ['title' => 'Status das Inscrições', 'type' => 'bar', 'height' => 300, 'series' => [], 'labels' => []];
        $this->graficoCursos = ['title' => 'Inscrições por Curso', 'type' => 'area', 'height' => 300, 'series' => [], 'labels' => []];
        $this->graficoUnidades = ['title' => 'Distribuição por Unidade', 'type' => 'pie', 'height' => 300, 'series' => [], 'labels' => []];
    }

    public function updatingFiltroCiclo()
    {
        $this->carregando = true;
        $this->graficoDetalhado = []; // Fecha o drill-down ao mudar de ciclo
    }

    public function carregarDados()
    {
        if (!$this->filtroCiclo) return;

        // 1. DADOS DE VAGAS
        $totalVagas = OfertaVaga::where('ciclo_id', $this->filtroCiclo)->sum('vagas');
        $vagasPreenchidas = Inscricao::where('ciclo_id', $this->filtroCiclo)
            ->whereHas('statusInscricao', fn($q) => $q->whereIn('nome', ['Aprovado', 'Selecionado']))
            ->count();
        
        $this->graficoVagas['series'] = [$vagasPreenchidas, max(0, $totalVagas - $vagasPreenchidas)];
        $this->graficoVagas['labels'] = ['Vagas Preenchidas', 'Vagas Abertas'];

        // 2. STATUS DAS INSCRIÇÕES
        $statusData = Inscricao::select('status_inscricao_id', DB::raw('count(*) as total'))
            ->with('statusInscricao:id,nome')
            ->where('ciclo_id', $this->filtroCiclo)
            ->groupBy('status_inscricao_id')
            ->get();

        $this->graficoInscricoes['labels'] = $statusData->pluck('statusInscricao.nome')->map(fn($v) => $v ?? 'Pendente')->toArray();
        $this->graficoInscricoes['series'] = [['name' => 'Inscritos', 'data' => $statusData->pluck('total')->toArray()]];

        // 3. INSCRIÇÕES POR CURSO
        $cursosData = Inscricao::select('curso_id', DB::raw('count(*) as total'))
            ->with('curso:id,nome')
            ->where('ciclo_id', $this->filtroCiclo)
            ->groupBy('curso_id')
            ->orderByDesc('total')
            ->get();

        $this->graficoCursos['labels'] = $cursosData->pluck('curso.nome')->map(fn($v) => $v ?? 'Sem Curso')->toArray();
        $this->graficoCursos['series'] = [['name' => 'Inscritos', 'data' => $cursosData->pluck('total')->toArray()]];

        // 4. INSCRIÇÕES POR UNIDADE
        $unidadesData = Inscricao::select('unidade_id', DB::raw('count(*) as total'))
            ->with('unidade:id,nome')
            ->where('ciclo_id', $this->filtroCiclo)
            ->groupBy('unidade_id')
            ->get();

        $this->graficoUnidades['labels'] = $unidadesData->pluck('unidade.nome')->map(fn($v) => $v ?? 'Sem Unidade')->toArray();
        $this->graficoUnidades['series'] = $unidadesData->pluck('total')->toArray();

        $this->carregando = false;
    }

    #[On('chart-click')]
    public function processarCliqueGrafico($dados)
    {
        $chartId = $dados['chartId'];
        $labelClicado = $dados['label'];

        // Se clicou no gráfico de Status, renderiza um gráfico detalhando quais Cursos têm esse status
        if ($chartId === 'grafico-status') {
            $this->tituloDetalhado = "Detalhamento: Inscrições '{$labelClicado}' por Curso";
            
            $detalhes = Inscricao::select('curso_id', DB::raw('count(*) as total'))
                ->with('curso:id,nome')
                ->where('ciclo_id', $this->filtroCiclo)
                ->whereHas('statusInscricao', fn($q) => $q->where('nome', $labelClicado))
                ->groupBy('curso_id')
                ->orderByDesc('total')
                ->get();

            $labels = $detalhes->pluck('curso.nome')->map(fn($v) => $v ?? 'Sem Curso')->toArray();
            $valores = $detalhes->pluck('total')->toArray();

            // Alimenta o 5º gráfico dinâmico que vai aparecer na tela
            $this->graficoDetalhado = [
                'title' => "Distribuição do status '{$labelClicado}'",
                'type' => 'bar',
                'height' => 350,
                'series' => [['name' => 'Quantidade', 'data' => $valores]],
                'labels' => $labels
            ];
        }
    }

    public function render()
    {
        return view('livewire.report.dashboard');
    }
}