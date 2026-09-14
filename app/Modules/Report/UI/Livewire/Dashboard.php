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

    public array $graficoInscricoesDia = [];
    public array $graficoVagas = [];
    public array $graficoInscricoes = [];
    public array $graficoCursos = [];
    public array $graficoUnidades = [];
    public array $graficoIdades = [];
    public array $graficoPCD = [];

    public array $graficosDinamicos = [];

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
        $vazio = ['title' => 'Carregando...', 'type' => 'bar', 'height' => 350, 'series' => [], 'labels' => []];
        $this->graficoInscricoesDia = $this->graficoVagas = $this->graficoInscricoes = $this->graficoCursos = $this->graficoUnidades = $vazio;
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

        $camposFormulario = CampoFormulario::where('ciclo_id', $this->filtroCiclo)
            ->whereIn('tipo', ['select', 'radio'])
            ->get();

        $inscricoes = Inscricao::select('id', 'status_inscricao_id', 'curso_id', 'unidade_id', 'data_nascimento', 'possui_deficiencia', 'dados_dinamicos', 'created_at')
            ->with(['statusInscricao:id,nome', 'curso:id,nome', 'unidade:id,nome'])
            ->where('ciclo_id', $this->filtroCiclo)
            ->get();

        $totalVagas = OfertaVaga::where('ciclo_id', $this->filtroCiclo)->sum('vagas');
        $inscricoesPorDia = [];
        $statusContagem = []; $cursoContagem = []; $unidadeContagem = [];
        $idadesContagem = ['Menor de 18' => 0, '18 a 24' => 0, '25 a 34' => 0, '35 a 45' => 0, 'Acima de 45' => 0];
        $pcdContagem = ['Sim' => 0, 'Não' => 0];
        $vagasPreenchidas = 0;
        
        $contadoresDinamicos = [];
        foreach ($camposFormulario as $campo) {
            $contadoresDinamicos[$campo->name] = [
                'label' => $campo->label,
                'opcoes' => []
            ];
        }

        foreach ($inscricoes as $insc) {
            if ($insc->created_at) {
                $dataStr = $insc->created_at->format('Y-m-d');
                $inscricoesPorDia[$dataStr] = ($inscricoesPorDia[$dataStr] ?? 0) + 1;
            }

            $status = $insc->statusInscricao->nome ?? 'Pendente';
            $statusContagem[$status] = ($statusContagem[$status] ?? 0) + 1;
            if (in_array($status, ['Aprovado', 'Selecionado'])) $vagasPreenchidas++;

            $curso = $insc->curso->nome ?? 'Sem Curso';
            $cursoContagem[$curso] = ($cursoContagem[$curso] ?? 0) + 1;
            
            $unidade = $insc->unidade->nome ?? 'Sem Unidade';
            $unidadeContagem[$unidade] = ($unidadeContagem[$unidade] ?? 0) + 1;

            if ($insc->data_nascimento) {
                $idade = Carbon::parse($insc->data_nascimento)->age;
                if ($idade < 18) $idadesContagem['Menor de 18']++;
                elseif ($idade <= 24) $idadesContagem['18 a 24']++;
                elseif ($idade <= 34) $idadesContagem['25 a 34']++;
                elseif ($idade <= 45) $idadesContagem['35 a 45']++;
                else $idadesContagem['Acima de 45']++;
            }

            $isPcd = in_array(strtolower(trim($insc->possui_deficiencia)), ['sim', 's', '1', 'true']) ? 'Sim' : 'Não';
            $pcdContagem[$isPcd]++;

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
        ksort($inscricoesPorDia);

        $labelsDias = [];
        $dadosDias = [];
        foreach ($inscricoesPorDia as $data => $qtd) {
            $labelsDias[] = Carbon::parse($data)->format('d/m/Y');
            $dadosDias[] = $qtd;
        }

        $this->graficoInscricoesDia = [
            'title' => 'Inscrições diárias', 'type' => 'area', 'height' => 350,
            'labels' => $labelsDias,
            'series' => [['name' => 'Novas Inscrições', 'data' => $dadosDias]]
        ];
        $this->graficoVagas = [
            'title' => 'Vagas disponíveis', 'type' => 'donut', 'height' => 350,
            'labels' => ['Vagas Preenchidas', 'Vagas Abertas'],
            'series' => [$vagasPreenchidas, max(0, $totalVagas - $vagasPreenchidas)]
        ];
        $this->graficoInscricoes = [
            'title' => 'Status', 'type' => 'bar', 'height' => 350,
            'labels' => array_keys($statusContagem),
            'series' => [['name' => 'Inscritos', 'data' => array_values($statusContagem)]]
        ];
        $this->graficoCursos = [
            'title' => 'Cursos', 'type' => 'area', 'height' => 350,
            'labels' => array_keys($cursoContagem),
            'series' => [['name' => 'Inscritos', 'data' => array_values($cursoContagem)]]
        ];
        $this->graficoUnidades = [
            'title' => 'Unidades', 'type' => 'donut', 'height' => 350,
            'labels' => array_keys($unidadeContagem),
            'series' => array_values($unidadeContagem)
        ];
        $this->graficoIdades = [
            'title' => 'Faixa Etária', 'type' => 'pie', 'height' => 350,
            'labels' => array_keys($idadesContagem),
            'series' => array_values($idadesContagem)
        ];
        $this->graficoPCD = [
            'title' => 'Pessoas com Deficiência (PCD)', 'type' => 'donut', 'height' => 350,
            'labels' => array_keys($pcdContagem),
            'series' => array_values($pcdContagem)
        ];

        $this->graficosDinamicos = [];
        foreach ($contadoresDinamicos as $name => $dados) {
            arsort($dados['opcoes']); 
            
            $tipoGrafico = count($dados['opcoes']) > 5 ? 'bar' : 'donut';

            $this->graficosDinamicos[] = [
                'id' => 'grafico-dinamico-' . $name,
                'config' => [
                    'title' => Str::limit($dados['label'], 45),
                    'type' => $tipoGrafico,
                    'height' => 350,
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
                'title' => "Status '{$label}'",
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