<?php

namespace App\Modules\Comunicacao\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLogEvento;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraDisparo;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Dashboard de Comunicação')]
class ComunicacaoDashboard extends Component
{
    public string $periodoRapido = 'semana'; // 'hoje', 'semana', 'mes', 'trimestre', 'custom'
    public string $dataInicio = '';
    public string $dataFim = '';
    public string $filtroCanal = 'todos'; // 'todos', 'email', 'whatsapp'

    public bool $carregando = true;

    // Resumo de KPIs do Topo
    public array $kpis = [
        'total_disparos'          => 0,
        'emails_enviados'         => 0,
        'emails_abertos_unicos'   => 0,
        'emails_aberturas_total'  => 0,
        'taxa_abertura'           => 0,
        'emails_com_links'        => 0,
        'emails_clicados_unicos'  => 0,
        'links_cliques_total'     => 0,
        'taxa_cliques_sobre_links'=> 0,
        'whatsapp_enviados'       => 0,
        'whatsapp_entregues_lidos'=> 0,
        'esteiras_ativas'         => 0,
        'automacoes_ativas'       => 0,
    ];

    // Configurações dos Gráficos (Compatíveis com <livewire:chart-widget>)
    public array $graficoDisparosDia = [];
    public array $graficoFunilEmail = [];
    public array $graficoRelacaoLinks = [];
    public array $graficoCanais = [];
    public array $graficoTopTemplates = [];
    public array $graficoEsteiras = [];

    // Drill-down ao clicar em um gráfico
    public array $graficoDetalhado = [];
    public string $tituloDetalhado = '';

    // Listas Auxiliares
    public array $esteirasEmAndamento = [];
    public array $topLinksClicados = [];

    public function mount(): void
    {
        // Inicialmente na semana (últimos 7 dias incluindo hoje)
        $this->aplicarPeriodoRapido('semana', false);
        $this->inicializarEstruturaGraficos();
    }

    private function inicializarEstruturaGraficos(): void
    {
        $vazio = ['title' => 'Carregando...', 'type' => 'bar', 'height' => 340, 'series' => [], 'labels' => []];
        $this->graficoDisparosDia  = $vazio;
        $this->graficoFunilEmail   = $vazio;
        $this->graficoRelacaoLinks = $vazio;
        $this->graficoCanais       = $vazio;
        $this->graficoTopTemplates = $vazio;
        $this->graficoEsteiras     = $vazio;
    }

    public function updatedPeriodoRapido(string $valor): void
    {
        if ($valor !== 'custom') {
            $this->aplicarPeriodoRapido($valor, true);
        }
    }

    public function updatedDataInicio(): void
    {
        $this->periodoRapido = 'custom';
        $this->atualizarManualmente();
    }

    public function updatedDataFim(): void
    {
        $this->periodoRapido = 'custom';
        $this->atualizarManualmente();
    }

    public function updatedFiltroCanal(): void
    {
        $this->atualizarManualmente();
    }

    public function aplicarPeriodoRapido(string $periodo, bool $recarregar = true): void
    {
        $this->periodoRapido = $periodo;
        $hoje = now();

        match ($periodo) {
            'hoje'      => [
                $this->dataInicio = $hoje->format('Y-m-d'),
                $this->dataFim    = $hoje->format('Y-m-d'),
            ],
            'semana'    => [
                $this->dataInicio = $hoje->copy()->subDays(6)->format('Y-m-d'),
                $this->dataFim    = $hoje->format('Y-m-d'),
            ],
            'mes'       => [
                $this->dataInicio = $hoje->copy()->subDays(29)->format('Y-m-d'),
                $this->dataFim    = $hoje->format('Y-m-d'),
            ],
            'trimestre' => [
                $this->dataInicio = $hoje->copy()->subDays(89)->format('Y-m-d'),
                $this->dataFim    = $hoje->format('Y-m-d'),
            ],
            default     => null,
        };

        if ($recarregar) {
            $this->atualizarManualmente();
        }
    }

    private function getCacheKey(): string
    {
        return "dashboard_comunicacao_{$this->dataInicio}_{$this->dataFim}_{$this->filtroCanal}";
    }

    public function atualizarManualmente(): void
    {
        $this->carregando = true;
        $this->graficoDetalhado = [];
        Cache::forget($this->getCacheKey());
        $this->carregarDados();
    }

    public function carregarDados(): void
    {
        $inicio = Carbon::parse($this->dataInicio ?: now()->subDays(6))->startOfDay();
        $fim    = Carbon::parse($this->dataFim ?: now())->endOfDay();

        if ($inicio->gt($fim)) {
            [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        $dados = Cache::remember($this->getCacheKey(), 300, function () use ($inicio, $fim) {
            return $this->processarMetricasComunicacao($inicio, $fim);
        });

        $this->kpis                = $dados['kpis'];
        $this->graficoDisparosDia  = $dados['graficoDisparosDia'];
        $this->graficoFunilEmail   = $dados['graficoFunilEmail'];
        $this->graficoRelacaoLinks = $dados['graficoRelacaoLinks'];
        $this->graficoCanais       = $dados['graficoCanais'];
        $this->graficoTopTemplates = $dados['graficoTopTemplates'];
        $this->graficoEsteiras     = $dados['graficoEsteiras'];
        $this->esteirasEmAndamento = $dados['esteirasEmAndamento'];
        $this->topLinksClicados    = $dados['topLinksClicados'];

        $this->carregando = false;
    }

    private function processarMetricasComunicacao(Carbon $inicio, Carbon $fim): array
    {
        $temColunaCanalLog = Schema::hasColumn('comunicacao_logs', 'canal');

        // 1. Prepara a série temporal de dias do intervalo
        $periodoDias = CarbonPeriod::create($inicio->copy()->startOfDay(), '1 day', $fim->copy()->startOfDay());
        $mapaDias = [];
        foreach ($periodoDias as $dt) {
            $chaveDia = $dt->format('Y-m-d');
            $mapaDias[$chaveDia] = [
                'label'    => $dt->format('d/m'),
                'email'    => 0,
                'whatsapp' => 0,
                'abertos'  => 0,
                'cliques'  => 0,
            ];
        }

        // 2. Consulta Comunicados no intervalo (com Template e Logs vinculados)
        $comunicados = Comunicado::with(['template', 'logs'])
            ->whereBetween('created_at', [$inicio, $fim])
            ->get();

        $emailsEnviados        = 0;
        $emailsAbertosUnicos   = 0;
        $emailsAberturasTotal  = 0;
        $emailsComLinks        = 0;
        $emailsClicadosUnicos  = 0;
        $linksCliquesTotal     = 0;
        $whatsappEnviados      = 0;
        $whatsappEntreguesLidos= 0;
        $errosTotal            = 0;

        $contagemPorTemplate = [];

        foreach ($comunicados as $com) {
            $diaKey = $com->created_at->format('Y-m-d');
            $tpl    = $com->template;
            $logs   = $com->logs;

            // Se o Comunicado possui logs detalhados em comunicacao_logs, analisa por canal
            if ($logs->isNotEmpty()) {
                foreach ($logs as $log) {
                    $isWpp = ($temColunaCanalLog && $log->canal === 'whatsapp')
                        || str_contains(strtolower((string) $log->template_nome), 'whatsapp')
                        || str_starts_with(strtolower((string) $log->assunto), 'whatsapp:');

                    if ($this->filtroCanal === 'email' && $isWpp) continue;
                    if ($this->filtroCanal === 'whatsapp' && !$isWpp) continue;

                    if ($log->status === 'erro') {
                        $errosTotal++;
                    }

                    $nomeTpl = $tpl->nome ?? ($log->template_nome ?: 'Template #' . $com->template_id);
                    $contagemPorTemplate[$nomeTpl] = ($contagemPorTemplate[$nomeTpl] ?? 0) + 1;

                    if ($isWpp) {
                        $whatsappEnviados++;
                        if (in_array($log->status, ['entregue', 'lido'], true) || (int) $log->aberturas_count > 0) {
                            $whatsappEntreguesLidos++;
                        }
                        if (isset($mapaDias[$diaKey])) {
                            $mapaDias[$diaKey]['whatsapp']++;
                        }
                    } else {
                        $emailsEnviados++;
                        if (isset($mapaDias[$diaKey])) {
                            $mapaDias[$diaKey]['email']++;
                        }

                        // Verifica aberturas (consolida entre Comunicado e ComunicacaoLog)
                        $qtdAberturas = max((int) $com->aberturas_count, (int) $log->aberturas_count);
                        if ($qtdAberturas === 0 && ($com->aberto_em !== null || $log->aberto_em !== null)) {
                            $qtdAberturas = 1;
                        }

                        // Verifica cliques
                        $qtdCliques = max((int) $com->cliques_count, (int) $log->cliques_count);
                        if ($qtdCliques === 0 && ($com->clicado_em !== null || $log->clicado_em !== null)) {
                            $qtdCliques = 1;
                        }

                        // Verifica se este e-mail possui links internos ou botões no corpo/template
                        $possuiLinksInternos = $this->verificarSePossuiLinkInterno($log->corpo, $tpl, $qtdCliques);

                        if ($possuiLinksInternos) {
                            $emailsComLinks++;
                        }

                        if ($qtdAberturas > 0) {
                            $emailsAbertosUnicos++;
                            $emailsAberturasTotal += $qtdAberturas;
                            if (isset($mapaDias[$diaKey])) {
                                $mapaDias[$diaKey]['abertos']++;
                            }
                        }

                        if ($qtdCliques > 0) {
                            $emailsClicadosUnicos++;
                            $linksCliquesTotal += $qtdCliques;
                            if (isset($mapaDias[$diaKey])) {
                                $mapaDias[$diaKey]['cliques'] += $qtdCliques;
                            }
                        }
                    }
                }
            } else {
                // Caso o registro exista apenas na tabela `comunicados` (como no exemplo do dump)
                $canalTpl = $tpl->canal ?? 'email';
                $isWpp = ($canalTpl === 'whatsapp');

                if ($this->filtroCanal === 'email' && $isWpp) continue;
                if ($this->filtroCanal === 'whatsapp' && !$isWpp) continue;

                if ($com->status === 'erro') {
                    $errosTotal++;
                }

                $nomeTpl = $tpl->nome ?? ('Template #' . ($com->template_id ?: 'Avulso'));
                $contagemPorTemplate[$nomeTpl] = ($contagemPorTemplate[$nomeTpl] ?? 0) + 1;

                if ($isWpp) {
                    $whatsappEnviados++;
                    if (isset($mapaDias[$diaKey])) {
                        $mapaDias[$diaKey]['whatsapp']++;
                    }
                } else {
                    $emailsEnviados++;
                    if (isset($mapaDias[$diaKey])) {
                        $mapaDias[$diaKey]['email']++;
                    }

                    $qtdAberturas = (int) $com->aberturas_count;
                    if ($qtdAberturas === 0 && $com->aberto_em !== null) {
                        $qtdAberturas = 1;
                    }

                    $qtdCliques = (int) $com->cliques_count;
                    if ($qtdCliques === 0 && $com->clicado_em !== null) {
                        $qtdCliques = 1;
                    }

                    $possuiLinksInternos = $this->verificarSePossuiLinkInterno($tpl?->corpo, $tpl, $qtdCliques);
                    if ($possuiLinksInternos) {
                        $emailsComLinks++;
                    }

                    if ($qtdAberturas > 0) {
                        $emailsAbertosUnicos++;
                        $emailsAberturasTotal += $qtdAberturas;
                        if (isset($mapaDias[$diaKey])) {
                            $mapaDias[$diaKey]['abertos']++;
                        }
                    }

                    if ($qtdCliques > 0) {
                        $emailsClicadosUnicos++;
                        $linksCliquesTotal += $qtdCliques;
                        if (isset($mapaDias[$diaKey])) {
                            $mapaDias[$diaKey]['cliques'] += $qtdCliques;
                        }
                    }
                }
            }
        }

        $totalDisparos = $emailsEnviados + $whatsappEnviados;
        $taxaAbertura  = $emailsEnviados > 0 ? round(($emailsAbertosUnicos / $emailsEnviados) * 100, 1) : 0;
        $taxaCliquesSobreLinks = $emailsComLinks > 0 ? round(($emailsClicadosUnicos / $emailsComLinks) * 100, 1) : 0;

        // 3. Top Links Internos Clicados no Período (tabela comunicacao_log_eventos)
        $topLinksClicados = [];
        if (Schema::hasTable('comunicacao_log_eventos')) {
            $topLinksClicados = ComunicacaoLogEvento::where('tipo', 'clique')
                ->whereBetween('created_at', [$inicio, $fim])
                ->select(
                    DB::raw("COALESCE(NULLIF(texto_link, ''), 'Link / Botão') as label"),
                    'url_destino',
                    DB::raw('COUNT(*) as total_cliques')
                )
                ->groupBy('label', 'url_destino')
                ->orderByDesc('total_cliques')
                ->limit(8)
                ->get()
                ->toArray();
        }

        // 4. Esteiras de Automação em Andamento e Desempenho
        $esteirasAtivasList = [];
        $labelsEsteiras     = [];
        $serieEsteiraEnv    = [];
        $serieEsteiraPoup   = [];

        if (Schema::hasTable('comunicacao_esteiras')) {
            $esteirasModels = EsteiraComunicacao::with(['etapas.template', 'ciclo', 'formulario'])
                ->orderByDesc('status')
                ->orderByDesc('id')
                ->get();

            foreach ($esteirasModels as $est) {
                $progresso = EsteiraComunicacaoService::obterResumoProgressoEsteira($est);

                $enviadosEst = EsteiraDisparo::where('esteira_id', $est->id)
                    ->where('status', 'enviado')
                    ->count();

                $poupadosEst = EsteiraDisparo::where('esteira_id', $est->id)
                    ->where('status', '!=', 'enviado')
                    ->count();

                $labelsEsteiras[]   = Str::limit($est->nome, 28);
                $serieEsteiraEnv[]  = $enviadosEst;
                $serieEsteiraPoup[] = $poupadosEst;

                $etapasResumo = [];
                foreach ($est->etapas as $et) {
                    $qtdEtapa = EsteiraDisparo::where('esteira_id', $est->id)
                        ->where('etapa_id', $et->id)
                        ->where('status', 'enviado')
                        ->count();

                    $etapasResumo[] = [
                        'ordem'    => $et->ordem,
                        'nome'     => $et->nome,
                        'canal'    => strtoupper($et->canal_disparo ?? ($et->template->canal ?? 'email')),
                        'enviados' => $qtdEtapa,
                    ];
                }

                $esteirasAtivasList[] = [
                    'id'          => $est->id,
                    'nome'        => $est->nome,
                    'status'      => (bool) $est->status,
                    'publico'     => $est->publico_alvo,
                    'contexto'    => $est->ciclo->nome ?? ($est->formulario->titulo ?? ucfirst($est->publico_alvo)),
                    'progresso'   => $progresso,
                    'enviados'    => $enviadosEst,
                    'poupados'    => $poupadosEst,
                    'etapas'      => $etapasResumo,
                ];
            }
        }

        arsort($contagemPorTemplate);
        $topTemplatesSlice = array_slice($contagemPorTemplate, 0, 8, true);

        $emailsComLinkSemClique = max(0, $emailsComLinks - $emailsClicadosUnicos);
        $emailsSemLinks         = max(0, $emailsEnviados - $emailsComLinks);

        return [
            'kpis' => [
                'total_disparos'           => $totalDisparos,
                'emails_enviados'          => $emailsEnviados,
                'emails_abertos_unicos'    => $emailsAbertosUnicos,
                'emails_aberturas_total'   => $emailsAberturasTotal,
                'taxa_abertura'            => $taxaAbertura,
                'emails_com_links'         => $emailsComLinks,
                'emails_clicados_unicos'   => $emailsClicadosUnicos,
                'links_cliques_total'      => $linksCliquesTotal,
                'taxa_cliques_sobre_links' => $taxaCliquesSobreLinks,
                'whatsapp_enviados'        => $whatsappEnviados,
                'whatsapp_entregues_lidos' => $whatsappEntreguesLidos,
                'esteiras_ativas'          => collect($esteirasAtivasList)->where('status', true)->count(),
                'automacoes_ativas'        => Schema::hasTable('automacoes') ? Automacao::where('status', true)->count() : 0,
            ],

            'graficoDisparosDia' => [
                'title'  => 'Evolução Diária de Disparos e Aberturas no Período',
                'type'   => 'area',
                'height' => 340,
                'labels' => array_column($mapaDias, 'label'),
                'series' => [
                    ['name' => 'E-mails Enviados', 'data' => array_column($mapaDias, 'email')],
                    ['name' => 'WhatsApp (Suri)', 'data' => array_column($mapaDias, 'whatsapp')],
                    ['name' => 'E-mails Abertos', 'data' => array_column($mapaDias, 'abertos')],
                    ['name' => 'Cliques em Links', 'data' => array_column($mapaDias, 'cliques')],
                ],
            ],

            'graficoFunilEmail' => [
                'title'  => 'Funil de Engajamento de E-mails (Enviados → Links → Abertos → Cliques)',
                'type'   => 'bar',
                'height' => 340,
                'labels' => [
                    '1. E-mails Enviados',
                    '2. C/ Links Internos',
                    '3. E-mails Abertos',
                    '4. Total de Aberturas',
                    '5. E-mails c/ Cliques',
                    '6. Total Links Clicados',
                ],
                'series' => [
                    [
                        'name' => 'Quantidade',
                        'data' => [
                            $emailsEnviados,
                            $emailsComLinks,
                            $emailsAbertosUnicos,
                            $emailsAberturasTotal,
                            $emailsClicadosUnicos,
                            $linksCliquesTotal,
                        ],
                    ],
                ],
            ],

            'graficoRelacaoLinks' => [
                'title'  => 'Relação de E-mails com Links Internos vs. Cliques',
                'type'   => 'donut',
                'height' => 340,
                'labels' => [
                    'Com Links e Clicados',
                    'Com Links (Sem Clique)',
                    'Sem Links Internos',
                ],
                'series' => [
                    $emailsClicadosUnicos,
                    $emailsComLinkSemClique,
                    $emailsSemLinks,
                ],
            ],

            'graficoCanais' => [
                'title'  => 'Distribuição por Canal e Status',
                'type'   => 'donut',
                'height' => 340,
                'labels' => ['E-mails Enviados', 'WhatsApp (Suri)', 'Falhas / Erros'],
                'series' => [$emailsEnviados, $whatsappEnviados, $errosTotal],
            ],

            'graficoTopTemplates' => [
                'title'  => 'Top Templates Mais Disparados (Clique na barra para detalhar)',
                'type'   => 'bar',
                'height' => 340,
                'labels' => !empty($topTemplatesSlice) ? array_keys($topTemplatesSlice) : ['Sem disparos no período'],
                'series' => [
                    [
                        'name' => 'Disparos',
                        'data' => !empty($topTemplatesSlice) ? array_values($topTemplatesSlice) : [0],
                    ],
                ],
            ],

            'graficoEsteiras' => [
                'title'  => 'Esteiras de Automação: Disparos Enviados vs. Poupados (Anti-Spam/Saída)',
                'type'   => 'bar',
                'height' => 340,
                'labels' => !empty($labelsEsteiras) ? $labelsEsteiras : ['Nenhuma esteira cadastrada'],
                'series' => [
                    ['name' => 'Disparos Enviados', 'data' => !empty($serieEsteiraEnv) ? $serieEsteiraEnv : [0]],
                    ['name' => 'Poupados (Condição/Saída)', 'data' => !empty($serieEsteiraPoup) ? $serieEsteiraPoup : [0]],
                ],
            ],

            'esteirasEmAndamento' => $esteirasAtivasList,
            'topLinksClicados'    => $topLinksClicados,
        ];
    }

    /**
     * Verifica se o e-mail possui links internos/botões clicáveis (<a href="...">, botões do template ou variáveis de link).
     */
    private function verificarSePossuiLinkInterno(?string $corpoHtml, $template = null, int $cliquesRegistrados = 0): bool
    {
        if ($cliquesRegistrados > 0) {
            return true;
        }

        if ($template) {
            $botoes = is_array($template->botoes)
                ? $template->botoes
                : (json_decode($template->botoes ?? '[]', true) ?: []);
            if (!empty($botoes)) {
                return true;
            }

            $corpoTpl = (string) ($template->corpo ?? '');
            if (
                str_contains($corpoTpl, '{{link_') ||
                preg_match('/<a\s+[^>]*href=["\'](?!#|mailto:|tel:)[^"\']+["\']/i', $corpoTpl)
            ) {
                return true;
            }
        }

        if (!empty($corpoHtml)) {
            if (
                str_contains($corpoHtml, '/email/t/c/') ||
                preg_match('/<a\s+[^>]*href=["\'](?!#|mailto:|tel:)[^"\']+["\']/i', $corpoHtml)
            ) {
                return true;
            }
        }

        return false;
    }

    #[On('chart-click')]
    public function processarCliqueGrafico($chartId, $label): void
    {
        if ($chartId === 'grafico-top-templates' && $label !== 'Sem disparos no período') {
            $inicio = Carbon::parse($this->dataInicio)->startOfDay();
            $fim    = Carbon::parse($this->dataFim)->endOfDay();

            $comunicadosTpl = Comunicado::with('template')
                ->whereBetween('created_at', [$inicio, $fim])
                ->whereHas('template', fn($q) => $q->where('nome', $label))
                ->get();

            $enviados = $comunicadosTpl->count();
            $abertos  = $comunicadosTpl->filter(fn($c) => (int) $c->aberturas_count > 0 || $c->aberto_em !== null)->count();
            $clicados = $comunicadosTpl->filter(fn($c) => (int) $c->cliques_count > 0 || $c->clicado_em !== null)->count();
            $somaCliques = (int) $comunicadosTpl->sum('cliques_count');

            $this->tituloDetalhado = "Detalhamento de Engajamento do Template: '{$label}'";
            $this->graficoDetalhado = [
                'title'  => "Métricas do Template: {$label}",
                'type'   => 'bar',
                'height' => 320,
                'labels' => ['Total Disparado', 'Abertos (Únicos)', 'Com Cliques (Únicos)', 'Total de Cliques'],
                'series' => [
                    ['name' => $label, 'data' => [$enviados, $abertos, $clicados, $somaCliques]],
                ],
            ];
        }
    }

    public function render()
    {
        return view('livewire.comunicacao.comunicacao-dashboard');
    }
}