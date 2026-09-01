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
use Carbon\Carbon;

#[Layout('components.layouts.app')]
#[Title('Dashboard Estratégico - Relatórios')]
class Dashboard extends Component
{
    public $filtroCiclo = '';
    public $ciclosDb = [];
    public bool $carregando = true;

    // Gráficos Atuais
    public array $graficoVagas = [];
    public array $graficoInscricoes = [];
    public array $graficoCursos = [];
    public array $graficoUnidades = [];
    
    // Novos Gráficos (Demográficos e Dinâmicos)
    public array $graficoIdades = [];
    public array $graficoPCD = [];
    public array $graficoGenero = [];
    public array $graficoRaca = [];

    // Drill-down
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
        $vazio = ['title' => 'Carregando...', 'type' => 'bar', 'height' => 300, 'series' => [], 'labels' => []];
        $this->graficoVagas = $this->graficoInscricoes = $this->graficoCursos = $this->graficoUnidades = $vazio;
        $this->graficoIdades = $this->graficoPCD = $this->graficoGenero = $this->graficoRaca = $vazio;
    }

    public function updatingFiltroCiclo()
    {
        $this->carregando = true;
        $this->graficoDetalhado = []; 
    }

    public function carregarDados()
    {
        if (!$this->filtroCiclo) return;

        // 1. Coleta Vagas Globais
        $totalVagas = OfertaVaga::where('ciclo_id', $this->filtroCiclo)->sum('vagas');

        // 2. Extrai TODA a base de Inscrições do Ciclo de uma vez só (Alta Performance)
        $inscricoes = Inscricao::select('id', 'status_inscricao_id', 'curso_id', 'unidade_id', 'data_nascimento', 'possui_deficiencia', 'dados_dinamicos')
            ->with(['statusInscricao:id,nome', 'curso:id,nome', 'unidade:id,nome'])
            ->where('ciclo_id', $this->filtroCiclo)
            ->get();

        // Inicializadores de Contagem
        $statusContagem = []; $cursoContagem = []; $unidadeContagem = [];
        $idadesContagem = ['Menor de 18' => 0, '18 a 24' => 0, '25 a 34' => 0, '35 a 45' => 0, 'Acima de 45' => 0];
        $pcdContagem = ['Sim' => 0, 'Não' => 0];
        $generoContagem = []; $racaContagem = [];
        $vagasPreenchidas = 0;

        // Loop Único em Memória
        foreach ($inscricoes as $insc) {
            // Status e Ocupação
            $status = $insc->statusInscricao->nome ?? 'Pendente';
            $statusContagem[$status] = ($statusContagem[$status] ?? 0) + 1;
            if (in_array($status, ['Aprovado', 'Selecionado'])) $vagasPreenchidas++;

            // Curso e Unidade
            $curso = $insc->curso->nome ?? 'Sem Curso';
            $cursoContagem[$curso] = ($cursoContagem[$curso] ?? 0) + 1;
            
            $unidade = $insc->unidade->nome ?? 'Sem Unidade';
            $unidadeContagem[$unidade] = ($unidadeContagem[$unidade] ?? 0) + 1;

            // Idades Matemáticas
            if ($insc->data_nascimento) {
                $idade = Carbon::parse($insc->data_nascimento)->age;
                if ($idade < 18) $idadesContagem['Menor de 18']++;
                elseif ($idade <= 24) $idadesContagem['18 a 24']++;
                elseif ($idade <= 34) $idadesContagem['25 a 34']++;
                elseif ($idade <= 45) $idadesContagem['35 a 45']++;
                else $idadesContagem['Acima de 45']++;
            }

            // Campos Nativos Simples
            $isPcd = in_array(strtolower(trim($insc->possui_deficiencia)), ['sim', 's', '1', 'true']) ? 'Sim' : 'Não';
            $pcdContagem[$isPcd]++;

            // Lendo o Form Builder (JSON) Dinamicamente
            $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
            
            $genero = $dinamicos['genero'] ?? 'Não Informado';
            $generoContagem[$genero] = ($generoContagem[$genero] ?? 0) + 1;

            $raca = $dinamicos['cor_raca'] ?? 'Não Informada';
            $racaContagem[$raca] = ($racaContagem[$raca] ?? 0) + 1;
        }

        // Ordenações para ficar mais bonito na tela
        arsort($cursoContagem);
        arsort($generoContagem);
        arsort($racaContagem);

        // --- Montagem dos Objetos para o Alpine ---

        $this->graficoVagas = [
            'title' => 'Ocupação de Vagas Geração', 'type' => 'donut', 'height' => 300,
            'labels' => ['Vagas Preenchidas', 'Vagas Abertas'],
            'series' => [$vagasPreenchidas, max(0, $totalVagas - $vagasPreenchidas)]
        ];

        $this->graficoInscricoes = [
            'title' => 'Status do Funil', 'type' => 'bar', 'height' => 300,
            'labels' => array_keys($statusContagem),
            'series' => [['name' => 'Inscritos', 'data' => array_values($statusContagem)]]
        ];

        $this->graficoCursos = [
            'title' => 'Procura por Curso', 'type' => 'area', 'height' => 300,
            'labels' => array_keys($cursoContagem),
            'series' => [['name' => 'Inscritos', 'data' => array_values($cursoContagem)]]
        ];

        $this->graficoUnidades = [
            'title' => 'Candidatos por Unidade', 'type' => 'donut', 'height' => 300,
            'labels' => array_keys($unidadeContagem),
            'series' => array_values($unidadeContagem)
        ];

        $this->graficoIdades = [
            'title' => 'Faixa Etária', 'type' => 'pie', 'height' => 300,
            'labels' => array_keys($idadesContagem),
            'series' => array_values($idadesContagem)
        ];

        $this->graficoPCD = [
            'title' => 'Pessoas com Deficiência (PCD)', 'type' => 'donut', 'height' => 300,
            'labels' => array_keys($pcdContagem),
            'series' => array_values($pcdContagem)
        ];

        $this->graficoGenero = [
            'title' => 'Identidade de Gênero', 'type' => 'bar', 'height' => 300,
            'labels' => array_keys($generoContagem),
            'series' => [['name' => 'Qtd', 'data' => array_values($generoContagem)]]
        ];

        $this->graficoRaca = [
            'title' => 'Cor / Raça Autodeclarada', 'type' => 'bar', 'height' => 300,
            'labels' => array_keys($racaContagem),
            'series' => [['name' => 'Qtd', 'data' => array_values($racaContagem)]]
        ];

        $this->carregando = false;
    }

    // CORREÇÃO: Recebendo os parâmetros mapeados de forma limpa
    #[On('chart-click')]
    public function processarCliqueGrafico($chartId, $label)
    {
        if ($chartId === 'grafico-status') {
            $this->tituloDetalhado = "Detalhamento: Inscrições '{$label}' por Curso";
            
            $detalhes = Inscricao::select('curso_id', DB::raw('count(*) as total'))
                ->with('curso:id,nome')
                ->where('ciclo_id', $this->filtroCiclo)
                ->whereHas('statusInscricao', fn($q) => $q->where('nome', $label))
                ->groupBy('curso_id')
                ->orderByDesc('total')
                ->get();

            $labels = $detalhes->pluck('curso.nome')->map(fn($v) => $v ?? 'Sem Curso')->toArray();
            $valores = $detalhes->pluck('total')->toArray();

            $this->graficoDetalhado = [
                'title' => "Distribuição do status '{$label}'",
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