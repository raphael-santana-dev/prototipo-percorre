<div class="w-full relative space-y-6 font-sans"
     wire:init="carregarDados"
     wire:poll.60s="carregarDados">

    {{-- CABEÇALHO E FILTROS --}}
    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Dashboard de Comunicação & Automação
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Monitoramento de disparos, aberturas, cliques em links internos, WhatsApp Suri e esteiras em andamento.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Atalhos de Período --}}
            <div class="inline-flex items-center bg-gray-100 dark:bg-gray-900 p-0.5 rounded-md border border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="aplicarPeriodoRapido('hoje')" class="px-2.5 py-1 rounded text-xs font-medium transition {{ $periodoRapido === 'hoje' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Hoje
                </button>
                <button type="button" wire:click="aplicarPeriodoRapido('semana')" class="px-2.5 py-1 rounded text-xs font-medium transition {{ $periodoRapido === 'semana' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    7 Dias
                </button>
                <button type="button" wire:click="aplicarPeriodoRapido('mes')" class="px-2.5 py-1 rounded text-xs font-medium transition {{ $periodoRapido === 'mes' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    30 Dias
                </button>
            </div>

            {{-- Intervalo Entre Datas --}}
            <div class="flex items-center h-9 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md px-2">
                <span class="text-[11px] text-gray-400 mr-1">De</span>
                <input type="date" wire:model.live="dataInicio" class="border-0 bg-transparent text-xs text-gray-700 dark:text-gray-200 p-1 focus:ring-0">
                <span class="text-[11px] text-gray-400 mx-1">até</span>
                <input type="date" wire:model.live="dataFim" class="border-0 bg-transparent text-xs text-gray-700 dark:text-gray-200 p-1 focus:ring-0">
            </div>

            {{-- Filtro de Canal --}}
            <select wire:model.live="filtroCanal" class="h-9 text-xs font-medium border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md py-0 pl-3 pr-7">
                <option value="todos">Todos os canais</option>
                <option value="email">Apenas E-mail</option>
                <option value="whatsapp">Apenas WhatsApp (Suri)</option>
            </select>

            {{-- Botão Atualizar --}}
            <button type="button"
                    wire:click="atualizarManualmente"
                    wire:loading.attr="disabled"
                    class="h-9 px-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 font-medium text-xs rounded-md transition inline-flex items-center gap-1.5 disabled:opacity-60">
                <i class="ph ph-arrows-clockwise inline-block" wire:loading.class="animate-spin" wire:target="atualizarManualmente"></i>
                <span wire:loading.remove wire:target="atualizarManualmente">Atualizar</span>
                <span wire:loading wire:target="atualizarManualmente">Atualizando...</span>
            </button>
        </div>
    </div>

    {{-- CARDS DE RESUMO (KPIs) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Card 1: Total Disparado --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total disparado no período</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ number_format($kpis['total_disparos'], 0, ',', '.') }}
            </div>
            <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span><strong class="text-gray-700 dark:text-gray-200">{{ $kpis['emails_enviados'] }}</strong> e-mails</span>
                <span>·</span>
                <span><strong class="text-gray-700 dark:text-gray-200">{{ $kpis['whatsapp_enviados'] }}</strong> WhatsApp</span>
            </div>
        </div>

        {{-- Card 2: E-mails Abertos --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">E-mails abertos (taxa)</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $kpis['emails_abertos_unicos'] }}
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">({{ $kpis['taxa_abertura'] }}%)</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                De <strong class="text-gray-700 dark:text-gray-200">{{ $kpis['emails_enviados'] }}</strong> enviados · <strong class="text-gray-700 dark:text-gray-200">{{ $kpis['emails_aberturas_total'] }}</strong> leituras totais
            </p>
        </div>

        {{-- Card 3: Links Internos e Cliques --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Links internos e cliques</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $kpis['links_cliques_total'] }}
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">cliques</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <strong class="text-gray-700 dark:text-gray-200">{{ $kpis['emails_com_links'] }}</strong> c/ links · <strong class="text-gray-700 dark:text-gray-200">{{ $kpis['emails_clicados_unicos'] }}</strong> clicados ({{ $kpis['taxa_cliques_sobre_links'] }}%)
            </p>
        </div>

        {{-- Card 4: Esteiras e Automações Ativas --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Esteiras e regras ativas</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $kpis['esteiras_ativas'] }}
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">esteiras</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <strong class="text-gray-700 dark:text-gray-200">{{ $kpis['automacoes_ativas'] }}</strong> gatilho(s) automático(s) ativo(s)
            </p>
        </div>
    </div>

    @if($carregando)
        <div class="flex flex-col gap-4 animate-pulse">
            @for ($i = 0; $i < 3; $i++)
                <div class="h-72 bg-gray-200 dark:bg-gray-700 rounded-lg w-full"></div>
            @endfor
        </div>
    @else
        {{-- DRILL-DOWN INTERATIVO --}}
        @if(!empty($graficoDetalhado))
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-300 dark:border-gray-600">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $tituloDetalhado }}</h3>
                    <button type="button" wire:click="$set('graficoDetalhado', [])" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1 rounded transition" aria-label="Fechar detalhamento">
                        <i class="ph ph-x text-base"></i>
                    </button>
                </div>
                <livewire:chart-widget chartId="grafico-com-drilldown" :config="$graficoDetalhado" wire:key="com-drilldown-{{ md5(json_encode($graficoDetalhado)) }}" />
            </div>
        @endif

        {{-- SEÇÃO 1: EVOLUÇÃO NO INTERVALO DE DATAS --}}
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                1. Volume de disparos e aberturas ({{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }})
            </h3>
            <div class="grid grid-cols-1 gap-4">
                <livewire:chart-widget
                    chartId="grafico-disparos-dia"
                    :config="$graficoDisparosDia"
                    wire:key="w-disp-dia-{{ $dataInicio }}-{{ $dataFim }}-{{ $filtroCanal }}" />
            </div>
        </div>

        {{-- SEÇÃO 2: RELAÇÃO DE E-MAILS ENVIADOS, ABERTOS, COM LINKS E CLICADOS --}}
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                2. Relação de e-mails: enviados, abertos, com links internos e cliques
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
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
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                3. Desempenho por template e canal (E-mail e WhatsApp Suri)
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
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

        {{-- SEÇÃO 4: ESTEIRAS DE AUTOMAÇÃO E RANKING DE LINKS CLICADOS --}}
        <div class="space-y-4">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                4. Esteiras de automação e links internos mais clicados
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-7">
                    <livewire:chart-widget
                        chartId="grafico-esteiras"
                        :config="$graficoEsteiras"
                        wire:key="w-esteiras-{{ $dataInicio }}-{{ $dataFim }}" />
                </div>

                {{-- Ranking dos Links Internos Clicados --}}
                <div class="lg:col-span-5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 flex flex-col">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
                            Links internos mais clicados
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $kpis['links_cliques_total'] }} clique(s)</span>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[260px] overflow-y-auto custom-scrollbar pr-1">
                        @forelse($topLinksClicados as $lk)
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $lk['label'] }}</p>
                                    <p class="text-[11px] font-mono text-gray-400 truncate" title="{{ $lk['url_destino'] }}">{{ $lk['url_destino'] }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shrink-0">
                                    {{ $lk['total_cliques'] }}x
                                </span>
                            </div>
                        @empty
                            <div class="py-10 text-center text-xs text-gray-400">
                                Nenhum clique em link interno registrado no período selecionado.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Painel Detalhado das Esteiras de Automação --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2 bg-gray-50 dark:bg-gray-900">
                    <div>
                        <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
                            Status das esteiras de disparo
                        </h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            Acompanhe em qual etapa cada régua de comunicação está operando.
                        </p>
                    </div>
                    <a href="{{ route('esteiras.index') }}" class="text-xs font-medium text-purpura-600 dark:text-purpura-400 hover:underline">
                        Gerenciar esteiras →
                    </a>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($esteirasEmAndamento as $est)
                        <div class="p-4 sm:px-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <div class="space-y-1.5 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('esteiras.show', $est['id']) }}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-purpura-600">
                                        {{ $est['nome'] }}
                                    </a>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium border {{ $est['progresso']['cor'] }}">
                                        {{ $est['progresso']['label'] }}
                                    </span>
                                    @if(!$est['status'])
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            Pausada
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Público: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $est['contexto'] }}</span> · {{ $est['progresso']['detalhe'] }}
                                </p>

                                {{-- Cadência de Etapas --}}
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach($est['etapas'] as $etItem)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                            <span class="font-mono text-gray-400">{{ $etItem['ordem'] }}.</span>
                                            <span class="font-medium">{{ $etItem['nome'] }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">({{ $etItem['enviados'] }})</span>
                                        </span>
                                        @if(!$loop->last)
                                            <span class="text-xs text-gray-300 dark:text-gray-600">→</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center justify-between lg:justify-end gap-4 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100 dark:border-gray-700">
                                <div class="text-left lg:text-right text-xs">
                                    <span class="block font-semibold text-gray-900 dark:text-white">{{ $est['enviados'] }} enviado(s)</span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">{{ $est['poupados'] }} poupado(s)</span>
                                </div>
                                <a href="{{ route('esteiras.show', $est['id']) }}" class="px-3 py-1.5 rounded-md bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 text-xs font-medium transition">
                                    Ver detalhes
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