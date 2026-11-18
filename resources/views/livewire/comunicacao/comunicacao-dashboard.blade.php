<div class="w-full relative"
     wire:init="carregarDados"
     wire:poll.60s="carregarDados">

    {{-- CABEÇALHO E FILTROS DE DATA / CANAL --}}
    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl  border border-gray-200 dark:border-gray-700 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 border border-purpura-200 dark:border-purpura-800 flex items-center justify-center text-purpura-600">
                    <i class="ph-fill ph-chart-line-up text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white">Dashboard de Comunicação & Automação</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Monitoramento de disparos, aberturas, cliques em links internos, WhatsApp Suri e esteiras em andamento
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Atalhos de Período --}}
            <div class="inline-flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="aplicarPeriodoRapido('hoje')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $periodoRapido === 'hoje' ? 'bg-white dark:bg-gray-800 text-purpura-600 ' : 'text-gray-600 dark:text-gray-400' }}">
                    Hoje
                </button>
                <button type="button" wire:click="aplicarPeriodoRapido('semana')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $periodoRapido === 'semana' ? 'bg-white dark:bg-gray-800 text-purpura-600 ' : 'text-gray-600 dark:text-gray-400' }}">
                    7 Dias (Semana)
                </button>
                <button type="button" wire:click="aplicarPeriodoRapido('mes')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $periodoRapido === 'mes' ? 'bg-white dark:bg-gray-800 text-purpura-600 ' : 'text-gray-600 dark:text-gray-400' }}">
                    30 Dias
                </button>
            </div>

            {{-- Intervalo Entre Datas --}}
            <div class="flex items-center h-9 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 ">
                <span class="text-[10px] font-bold uppercase text-gray-400 mr-1.5">De</span>
                <input type="date" wire:model.live="dataInicio" class="border-0 bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 p-1 focus:ring-0">
                <span class="text-[10px] font-bold uppercase text-gray-400 mx-1.5">até</span>
                <input type="date" wire:model.live="dataFim" class="border-0 bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 p-1 focus:ring-0">
            </div>

            {{-- Filtro de Canal --}}
            <select wire:model.live="filtroCanal" class="h-9 text-xs font-bold border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl  py-0 pl-3 pr-7">
                <option value="todos">Todos os Canais</option>
                <option value="email">Apenas E-mail</option>
                <option value="whatsapp">Apenas WhatsApp (Suri)</option>
            </select>

            {{-- Botão Atualizar --}}
            <button wire:click="atualizarManualmente"
                    wire:loading.attr="disabled"
                    class="h-9 px-3.5 bg-purpura-100 text-purpura-700 font-bold text-xs rounded-xl hover:bg-purpura-200 transition flex items-center gap-1.5 disabled:opacity-60">
                <i class="ph-bold ph-arrows-clockwise inline-block" wire:loading.class="animate-spin" wire:target="atualizarManualmente"></i>
                <span wire:loading.remove wire:target="atualizarManualmente">Atualizar</span>
                <span wire:loading wire:target="atualizarManualmente">Atualizando...</span>
            </button>
        </div>
    </div>

    {{-- CARDS DE RESUMO (KPIs) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
        {{-- Card 1: Total Disparado --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700  flex items-start justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Total Disparado no Período</span>
                <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($kpis['total_disparos'], 0, ',', '.') }}</div>
                <div class="flex items-center gap-2 mt-2 text-xs font-bold">
                    <span class="text-blue-600"><i class="ph-fill ph-envelope-simple"></i> {{ $kpis['emails_enviados'] }} E-mails</span>
                    <span class="text-gray-300">•</span>
                    <span class="text-emerald-600"><i class="ph-fill ph-whatsapp-logo"></i> {{ $kpis['whatsapp_enviados'] }} WPP</span>
                </div>
            </div>
            <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center shrink-0">
                <i class="ph-fill ph-paper-plane-tilt text-xl"></i>
            </span>
        </div>

        {{-- Card 2: E-mails Enviados vs. Abertos --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700  flex items-start justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">E-mails Abertos (Taxa)</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">
                    {{ $kpis['emails_abertos_unicos'] }}
                    <span class="text-sm font-bold text-gray-500">({{ $kpis['taxa_abertura'] }}%)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    De <b>{{ $kpis['emails_enviados'] }}</b> enviados • <b>{{ $kpis['emails_aberturas_total'] }}</b> leituras totais
                </p>
            </div>
            <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="ph-fill ph-envelope-open text-xl"></i>
            </span>
        </div>

        {{-- Card 3: Links Internos e Cliques --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700  flex items-start justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">E-mails c/ Links & Cliques</span>
                <div class="text-2xl font-black text-purpura-600 mt-1">
                    {{ $kpis['links_cliques_total'] }} <span class="text-xs font-bold uppercase text-gray-500">cliques</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <b>{{ $kpis['emails_com_links'] }}</b> c/ links • <b>{{ $kpis['emails_clicados_unicos'] }}</b> clicados ({{ $kpis['taxa_cliques_sobre_links'] }}%)
                </p>
            </div>
            <span class="w-10 h-10 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 flex items-center justify-center shrink-0">
                <i class="ph-fill ph-cursor-click text-xl"></i>
            </span>
        </div>

        {{-- Card 4: Esteiras e Automações Ativas --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700  flex items-start justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Esteiras & Regras Ativas</span>
                <div class="text-2xl font-black text-amber-600 mt-1">
                    {{ $kpis['esteiras_ativas'] }} <span class="text-xs font-bold uppercase text-gray-500">esteiras</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <b>{{ $kpis['automacoes_ativas'] }}</b> gatilho(s) automático(s) ativos
                </p>
            </div>
            <span class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center shrink-0">
                <i class="ph-fill ph-git-merge text-xl"></i>
            </span>
        </div>
    </div>

    @if($carregando)
        <div class="flex flex-col gap-6 animate-pulse">
            @for ($i = 0; $i < 3; $i++)
                <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl w-full"></div>
            @endfor
        </div>
    @else
        {{-- DRILL-DOWN INTERATIVO AO CLICAR EM UM GRÁFICO --}}
        @if(!empty($graficoDetalhado))
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl  border border-indigo-200 border-t-4 border-t-indigo-500 transition-all">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">{{ $tituloDetalhado }}</h3>
                    <button wire:click="$set('graficoDetalhado', [])" class="text-gray-400 hover:text-red-500 bg-gray-100 hover:bg-red-50 p-1.5 rounded-lg transition">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>
                <livewire:chart-widget chartId="grafico-com-drilldown" :config="$graficoDetalhado" wire:key="com-drilldown-{{ md5(json_encode($graficoDetalhado)) }}" />
            </div>
        @endif

        {{-- SEÇÃO 1: EVOLUÇÃO NO INTERVALO DE DATAS --}}
        <div class="mt-6">
            <h3 class="font-bold text-gray-600 dark:text-gray-300 mb-6 uppercase tracking-widest text-xs flex items-center gap-1.5">
                <i class="ph-bold ph-trend-up text-purpura-500"></i> 1. Volume de Disparos e Aberturas no Período ({{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }})
            </h3>
            <div class="grid grid-cols-1 gap-6">
                <livewire:chart-widget
                    chartId="grafico-disparos-dia"
                    :config="$graficoDisparosDia"
                    wire:key="w-disp-dia-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
            </div>
        </div>

        {{-- SEÇÃO 2: RELAÇÃO DE E-MAILS ENVIADOS, ABERTOS, COM LINKS INTERNOS E CLICADOS --}}
        <div class="mt-6">
            <h3 class="font-bold text-gray-600 dark:text-gray-300 mb-6 uppercase tracking-widest text-xs flex items-center gap-1.5">
                <i class="ph-bold ph-envelope-open text-purpura-500"></i> 2. Relação de E-mails: Enviados, Abertos, Com Links Internos e Cliques
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7">
                    <livewire:chart-widget
                        chartId="grafico-funil-email"
                        :config="$graficoFunilEmail"
                        wire:key="w-funil-email-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
                </div>
                <div class="lg:col-span-5">
                    <livewire:chart-widget
                        chartId="grafico-relacao-links"
                        :config="$graficoRelacaoLinks"
                        wire:key="w-rel-links-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
                </div>
            </div>
        </div>

        {{-- SEÇÃO 3: TEMPLATES MAIS DISPARADOS E DISTRIBUIÇÃO POR CANAL --}}
        <div class="mt-6">
            <h3 class="font-bold text-gray-600 dark:text-gray-300 mb-6 uppercase tracking-widest text-xs flex items-center gap-1.5">
                <i class="ph-bold ph-layout text-purpura-500"></i> 3. Desempenho por Template e Canal (E-mail & WhatsApp Suri)
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7">
                    <livewire:chart-widget
                        chartId="grafico-top-templates"
                        :config="$graficoTopTemplates"
                        wire:key="w-top-tpl-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
                </div>
                <div class="lg:col-span-5">
                    <livewire:chart-widget
                        chartId="grafico-canais"
                        :config="$graficoCanais"
                        wire:key="w-canais-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
                </div>
            </div>
        </div>

        {{-- SEÇÃO 4: ESTEIRAS DE AUTOMAÇÃO EM ANDAMENTO + RANKING DE LINKS CLICADOS --}}
        <div class="mt-6">
            <h3 class="font-bold text-gray-600 dark:text-gray-300 mb-6 uppercase tracking-widest text-xs flex items-center gap-1.5">
                <i class="ph-bold ph-git-merge text-purpura-500"></i> 4. Esteiras de Automação em Andamento e Links Internos Mais Clicados
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7">
                    <livewire:chart-widget
                        chartId="grafico-esteiras"
                        :config="$graficoEsteiras"
                        wire:key="w-esteiras-{{ $dataInicio }}-{{ $dataFim }}" />
                </div>

                {{-- Ranking dos Links Internos Clicados --}}
                <div class="lg:col-span-5 bg-white dark:bg-gray-800 rounded-xl  border border-gray-200 dark:border-gray-700 p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-3">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-1.5">
                                <i class="ph-fill ph-cursor-click text-purpura-600"></i> Links Internos Mais Clicados
                            </h4>
                            <span class="text-[11px] font-bold text-purpura-600">{{ $kpis['links_cliques_total'] }} clique(s)</span>
                        </div>

                        <div class="space-y-2.5 max-h-[260px] overflow-y-auto custom-scrollbar pr-1">
                            @forelse($topLinksClicados as $lk)
                                <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/80 dark:border-gray-700 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $lk['label'] }}</p>
                                        <p class="text-[10px] font-mono text-gray-400 truncate" title="{{ $lk['url_destino'] }}">{{ $lk['url_destino'] }}</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-purpura-100 text-purpura-800 shrink-0">
                                        {{ $lk['total_cliques'] }}x
                                    </span>
                                </div>
                            @empty
                                <div class="py-12 text-center text-xs text-gray-400">
                                    Nenhum clique em link interno registrado no período selecionado.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Painel Detalhado das Esteiras de Automação em Andamento --}}
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl  border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/60 dark:bg-gray-900/40">
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-git-merge text-purpura-600 text-base mb-2"></i> Status das Esteiras de Disparo (Em Andamento / Finalizadas)
                        </h4>
                        <p class="text-[11px] text-gray-500 mt-0.5">Acompanhe em qual etapa cada régua de comunicação está operando neste momento.</p>
                    </div>
                    <a href="{{ route('esteiras.index') }}" class="text-xs font-bold text-purpura-600 hover:underline flex items-center gap-1">
                        Gerenciar Esteiras <i class="ph-bold ph-arrow-right"></i>
                    </a>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($esteirasEmAndamento as $est)
                        <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('esteiras.show', $est['id']) }}" class="text-sm font-extrabold text-gray-900 dark:text-white hover:text-purpura-600">
                                        {{ $est['nome'] }}
                                    </a>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $est['progresso']['cor'] }}">
                                        <i class="ph-bold {{ $est['progresso']['icone'] }}"></i>
                                        {{ $est['progresso']['label'] }}
                                    </span>
                                    @if(!$est['status'])
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500">Pausada</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">
                                    Público: <b class="text-gray-700 dark:text-gray-300">{{ $est['contexto'] }}</b> • {{ $est['progresso']['detalhe'] }}
                                </p>

                                {{-- Cadência de Etapas --}}
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach($est['etapas'] as $etItem)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                            <span class="w-4 h-4 rounded-full bg-purpura-600 text-white text-[9px] flex items-center justify-center font-black">{{ $etItem['ordem'] }}</span>
                                            <span>{{ $etItem['nome'] }}</span>
                                            <span class="text-purpura-600 font-extrabold">({{ $etItem['enviados'] }})</span>
                                        </span>
                                        @if(!$loop->last)
                                            <i class="ph-bold ph-arrow-right text-[10px] text-gray-400"></i>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <span class="block text-xs font-extrabold text-blue-600">{{ $est['enviados'] }} enviados</span>
                                    <span class="block text-[10px] font-semibold text-emerald-600">{{ $est['poupados'] }} poupados</span>
                                </div>
                                <a href="{{ route('esteiras.show', $est['id']) }}" class="px-3 py-2 rounded-xl bg-gray-100 hover:bg-purpura-50 text-gray-700 hover:text-purpura-700 text-xs font-bold transition">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-gray-400">
                            Nenhuma esteira de comunicação cadastrada até o momento.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>