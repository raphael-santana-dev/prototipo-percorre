<?php

namespace App\Modules\Report\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Models\Ciclo;
use App\Models\Inscricao;
use App\Models\OfertaVaga;
use App\Models\CampoFormulario;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Dashboard Estratégico - Relatórios')]
class Dashboard extends Component
{
    public $filtroCiclo = '';
    public $ciclosDb = [];
    public bool $carregando = true;

    // Gráficos Nativos e Estruturais
    public array $graficoVagas = [];
    public array $graficoInscricoes = [];
    public array $graficoCursos = [];
    public array $graficoUnidades = [];
    public array $graficoIdades = [];
    public array $graficoPCD = [];

    // Array que guardará todos os gráficos gerados dinamicamente a partir do Form Builder
    public array $graficosDinamicos = [];

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
        $this->graficoIdades = $this->graficoPCD = $vazio;
        $this->graficosDinamicos = [];
    }

    public function updatingFiltroCiclo()
    {
        $this->carregando = true;
        $this->graficoDetalhado = []; 
    }

    public function carregarDados()
    {
        if (!$this->filtroCiclo) return;

        // 1. Busca os campos do formulário deste ciclo que não são texto livre
        $camposFormulario = CampoFormulario::where('ciclo_id', $this->filtroCiclo)
            ->whereIn('tipo', ['select', 'radio'])
            ->get();

        // 2. Extrai TODA a base de Inscrições do Ciclo (Alta Performance)
        $inscricoes = Inscricao::select('id', 'status_inscricao_id', 'curso_id', 'unidade_id', 'data_nascimento', 'possui_deficiencia', 'dados_dinamicos')
            ->with(['statusInscricao:id,nome', 'curso:id,nome', 'unidade:id,nome'])
            ->where('ciclo_id', $this->filtroCiclo)
            ->get();

        // 3. Inicializadores de Contagem
        $totalVagas = OfertaVaga::where('ciclo_id', $this->filtroCiclo)->sum('vagas');
        $statusContagem = []; $cursoContagem = []; $unidadeContagem = [];
        $idadesContagem = ['Menor de 18' => 0, '18 a 24' => 0, '25 a 34' => 0, '35 a 45' => 0, 'Acima de 45' => 0];
        $pcdContagem = ['Sim' => 0, 'Não' => 0];
        $vagasPreenchidas = 0;
        
        // Inicializa contadores para os campos dinâmicos do formulário
        $contadoresDinamicos = [];
        foreach ($camposFormulario as $campo) {
            $contadoresDinamicos[$campo->name] = [
                'label' => $campo->label,
                'opcoes' => []
            ];
        }

        // 4. Processamento em Memória RAM
        foreach ($inscricoes as $insc) {
            // Nativos: Status, Curso e Unidade
            $status = $insc->statusInscricao->nome ?? 'Pendente';
            $statusContagem[$status] = ($statusContagem[$status] ?? 0) + 1;
            if (in_array($status, ['Aprovado', 'Selecionado'])) $vagasPreenchidas++;

            $curso = $insc->curso->nome ?? 'Sem Curso';
            $cursoContagem[$curso] = ($cursoContagem[$curso] ?? 0) + 1;
            
            $unidade = $insc->unidade->nome ?? 'Sem Unidade';
            $unidadeContagem[$unidade] = ($unidadeContagem[$unidade] ?? 0) + 1;

            // Nativos: Faixa Etária
            if ($insc->data_nascimento) {
                $idade = Carbon::parse($insc->data_nascimento)->age;
                if ($idade < 18) $idadesContagem['Menor de 18']++;
                elseif ($idade <= 24) $idadesContagem['18 a 24']++;
                elseif ($idade <= 34) $idadesContagem['25 a 34']++;
                elseif ($idade <= 45) $idadesContagem['35 a 45']++;
                else $idadesContagem['Acima de 45']++;
            }

            // Nativos: PCD
            $isPcd = in_array(strtolower(trim($insc->possui_deficiencia)), ['sim', 's', '1', 'true']) ? 'Sim' : 'Não';
            $pcdContagem[$isPcd]++;

            // Lendo o JSON Dinâmico do Form Builder
            $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
            
            foreach ($camposFormulario as $campo) {
                $valorResposta = trim((string) ($dinamicos[$campo->name] ?? ''));
                if (empty($valorResposta)) {
                    $valorResposta = 'Não Informado';
                }
                
                $contadoresDinamicos[$campo->name]['opcoes'][$valorResposta] = 
                    ($contadoresDinamicos[$campo->name]['opcoes'][$valorResposta] ?? 0) + 1;
            }
        }

        arsort($cursoContagem);

        // --- Montagem Nativos ---
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

        // --- Montagem Inteligente dos Gráficos do Form Builder ---
        $this->graficosDinamicos = [];
        foreach ($contadoresDinamicos as $name => $dados) {
            arsort($dados['opcoes']); // Ordena do maior para o menor
            
            // Inteligência visual: Se tiver muitas opções (Ex: Escolas, Profissões), usa barra. Se for poucas (Sim/Não, Gênero), usa Donut.
            $tipoGrafico = count($dados['opcoes']) > 5 ? 'bar' : 'donut';

            $this->graficosDinamicos[] = [
                'id' => 'grafico-dinamico-' . $name,
                'config' => [
                    'title' => Str::limit($dados['label'], 45), // Limita o título para não quebrar a tela
                    'type' => $tipoGrafico,
                    'height' => 300,
                    'labels' => array_keys($dados['opcoes']),
                    'series' => $tipoGrafico === 'bar' 
                        ? [['name' => 'Qtd', 'data' => array_values($dados['opcoes'])]] 
                        : array_values($dados['opcoes'])
                ]
            ];
        }

        $this->carregando = false;
    }

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