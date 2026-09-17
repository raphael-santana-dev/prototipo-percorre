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

        // OTIMIZAÇÃO: Cache de 30 minutos (1800 segundos). Isso impede que a página derreta 
        // o banco de dados se 50 administradores derem F5 ao mesmo tempo.
        $cacheKey = 'dashboard_gerencial_ciclo_' . $this->filtroCiclo;

        $dadosProcessados = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () {
            
            // 1. DELEGAÇÃO PARA O BANCO (GROUP BY NATIVO) em vez de iterar foreach no PHP
            $statusContagem = Inscricao::where('ciclo_id', $this->filtroCiclo)
                ->join('status_inscricoes', 'inscricoes.status_inscricao_id', '=', 'status_inscricoes.id')
                ->groupBy('status_inscricoes.nome')
                ->pluck(DB::raw('count(inscricoes.id) as total'), 'status_inscricoes.nome')->toArray();

            $cursoContagem = Inscricao::where('ciclo_id', $this->filtroCiclo)->whereNotNull('curso_id')
                ->join('cursos', 'inscricoes.curso_id', '=', 'cursos.id')
                ->groupBy('cursos.nome')
                ->pluck(DB::raw('count(inscricoes.id) as total'), 'cursos.nome')->toArray();

            $unidadeContagem = Inscricao::where('ciclo_id', $this->filtroCiclo)->whereNotNull('unidade_id')
                ->join('unidades', 'inscricoes.unidade_id', '=', 'unidades.id')
                ->groupBy('unidades.nome')
                ->pluck(DB::raw('count(inscricoes.id) as total'), 'unidades.nome')->toArray();

            $inscricoesPorDia = Inscricao::where('ciclo_id', $this->filtroCiclo)
                ->selectRaw('DATE(created_at) as data_criacao, count(id) as total')
                ->groupBy('data_criacao')
                ->pluck('total', 'data_criacao')
                ->toArray();

            $pcdContagemDb = Inscricao::where('ciclo_id', $this->filtroCiclo)
                ->groupBy('possui_deficiencia')
                ->pluck(DB::raw('count(id) as total'), 'possui_deficiencia')->toArray();

            // 2. Extração limpa para campos dinâmicos e idades (apenas os dados necessários)
            $inscricoesDinamicas = Inscricao::select('dados_dinamicos', 'data_nascimento')
                ->where('ciclo_id', $this->filtroCiclo)->get();
                
            $idadesContagem = ['Menor de 18' => 0, '18 a 24' => 0, '25 a 34' => 0, '35 a 45' => 0, 'Acima de 45' => 0];
            $pcdContagem = ['Sim' => 0, 'Não' => 0];
            $vagasPreenchidas = 0;

            foreach (['Aprovado', 'Selecionado'] as $s) {
                if (isset($statusContagem[$s])) $vagasPreenchidas += $statusContagem[$s];
            }

            foreach ($pcdContagemDb as $k => $v) {
                $isPcd = in_array(strtolower(trim($k)), ['sim', 's', '1', 'true']) ? 'Sim' : 'Não';
                $pcdContagem[$isPcd] += $v;
            }

            $camposFormulario = CampoFormulario::where('ciclo_id', $this->filtroCiclo)
                ->whereIn('tipo', ['select', 'radio'])->get();

            $contadoresDinamicos = [];
            foreach ($camposFormulario as $campo) {
                $contadoresDinamicos[$campo->name] = ['label' => $campo->label, 'opcoes' => []];
            }

            foreach ($inscricoesDinamicas as $insc) {
                if ($insc->data_nascimento) {
                    $idade = Carbon::parse($insc->data_nascimento)->age;
                    if ($idade < 18) $idadesContagem['Menor de 18']++;
                    elseif ($idade <= 24) $idadesContagem['18 a 24']++;
                    elseif ($idade <= 34) $idadesContagem['25 a 34']++;
                    elseif ($idade <= 45) $idadesContagem['35 a 45']++;
                    else $idadesContagem['Acima de 45']++;
                }

                $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
                foreach ($camposFormulario as $campo) {
                    $valorResposta = trim((string) ($dinamicos[$campo->name] ?? ''));
                    $valorResposta = empty($valorResposta) ? 'Não Informado' : $valorResposta;
                    $contadoresDinamicos[$campo->name]['opcoes'][$valorResposta] = ($contadoresDinamicos[$campo->name]['opcoes'][$valorResposta] ?? 0) + 1;
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

            $graficosDinamicos = [];
            foreach ($contadoresDinamicos as $name => $dados) {
                arsort($dados['opcoes']); 
                $tipoGrafico = count($dados['opcoes']) > 5 ? 'bar' : 'donut';
                $graficosDinamicos[] = [
                    'id' => 'grafico-dinamico-' . $name,
                    'config' => [
                        'title' => Str::limit($dados['label'], 45), 'type' => $tipoGrafico, 'height' => 350,
                        'labels' => array_keys($dados['opcoes']),
                        'series' => $tipoGrafico === 'bar' ? [['name' => 'Qtd', 'data' => array_values($dados['opcoes'])]] : array_values($dados['opcoes'])
                    ]
                ];
            }

            return [
                'totalVagas' => OfertaVaga::where('ciclo_id', $this->filtroCiclo)->sum('vagas'),
                'vagasPreenchidas' => $vagasPreenchidas,
                'labelsDias' => $labelsDias, 'dadosDias' => $dadosDias,
                'statusContagem' => $statusContagem,
                'cursoContagem' => $cursoContagem,
                'unidadeContagem' => $unidadeContagem,
                'idadesContagem' => $idadesContagem,
                'pcdContagem' => $pcdContagem,
                'graficosDinamicos' => $graficosDinamicos
            ];
        });

        // 3. Alocação dos dados do cache para as variáveis públicas do Livewire
        $this->graficoInscricoesDia = ['title' => 'Inscrições diárias', 'type' => 'area', 'height' => 350, 'labels' => $dadosProcessados['labelsDias'], 'series' => [['name' => 'Novas Inscrições', 'data' => $dadosProcessados['dadosDias']]]];
        $this->graficoVagas = ['title' => 'Vagas disponíveis', 'type' => 'donut', 'height' => 350, 'labels' => ['Vagas Preenchidas', 'Vagas Abertas'], 'series' => [$dadosProcessados['vagasPreenchidas'], max(0, $dadosProcessados['totalVagas'] - $dadosProcessados['vagasPreenchidas'])]];
        $this->graficoInscricoes = ['title' => 'Status', 'type' => 'bar', 'height' => 350, 'labels' => array_keys($dadosProcessados['statusContagem']), 'series' => [['name' => 'Inscritos', 'data' => array_values($dadosProcessados['statusContagem'])]]];
        $this->graficoCursos = ['title' => 'Cursos', 'type' => 'area', 'height' => 350, 'labels' => array_keys($dadosProcessados['cursoContagem']), 'series' => [['name' => 'Inscritos', 'data' => array_values($dadosProcessados['cursoContagem'])]]];
        $this->graficoUnidades = ['title' => 'Unidades', 'type' => 'donut', 'height' => 350, 'labels' => array_keys($dadosProcessados['unidadeContagem']), 'series' => array_values($dadosProcessados['unidadeContagem'])];
        $this->graficoIdades = ['title' => 'Faixa Etária', 'type' => 'pie', 'height' => 350, 'labels' => array_keys($dadosProcessados['idadesContagem']), 'series' => array_values($dadosProcessados['idadesContagem'])];
        $this->graficoPCD = ['title' => 'Pessoas com Deficiência (PCD)', 'type' => 'donut', 'height' => 350, 'labels' => array_keys($dadosProcessados['pcdContagem']), 'series' => array_values($dadosProcessados['pcdContagem'])];
        $this->graficosDinamicos = $dadosProcessados['graficosDinamicos'];

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