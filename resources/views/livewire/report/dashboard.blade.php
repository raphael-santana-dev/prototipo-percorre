<div class="p-6 max-w-7xl mx-auto font-sans relative" 
     wire:init="carregarDados" 
     wire:poll.60s="carregarDados">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200">
        <div>
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2"><i class="ph-fill ph-chart-pie-slice text-purpura-500"></i> Relatórios Analíticos</h2>
            <p class="text-xs text-gray-500">Dados processados de forma assíncrona. Clique nas barras do gráfico de Status para detalhar.</p>
        </div>

        <div class="flex items-center gap-4 w-full md:w-auto">
            <select wire:model.live="filtroCiclo" class="w-full md:w-64 px-4 py-2 text-sm border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500">
                @foreach($ciclosDb as $ciclo)
                    <option value="{{ $ciclo->id }}">{{ $ciclo->nome }}</option>
                @endforeach
            </select>
            
            <button wire:click="carregarDados" class="px-4 py-2 bg-purpura-100 text-purpura-700 font-bold text-sm rounded-lg hover:bg-purpura-200 transition flex items-center gap-2">
                <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="carregarDados"></i> Atualizar
            </button>
        </div>
    </div>

    @if($carregando)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-pulse">
            <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
        </div>
    @else
        <!-- RENDERIZAÇÃO DOS 4 WIDGETS PRINCIPAIS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Vagas -->
            <livewire:chart-widget 
                chartId="grafico-vagas" 
                :config="$graficoVagas" 
                wire:key="widget-vagas-{{ $filtroCiclo }}" />

            <!-- Status (Clicável para Drill-down) -->
            <livewire:chart-widget 
                chartId="grafico-status" 
                :config="$graficoInscricoes" 
                wire:key="widget-status-{{ $filtroCiclo }}" />

            <!-- Cursos -->
            <livewire:chart-widget 
                chartId="grafico-cursos" 
                :config="$graficoCursos" 
                wire:key="widget-cursos-{{ $filtroCiclo }}" />

            <!-- Unidades -->
            <livewire:chart-widget 
                chartId="grafico-unidades" 
                :config="$graficoUnidades" 
                wire:key="widget-unidades-{{ $filtroCiclo }}" />

        </div>
    @endif

    <!-- DRILL DOWN (Resultado do Clique renderizado como um novo gráfico) -->
    @if(!empty($graficoDetalhado))
        <div class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-indigo-200 border-t-4 border-t-indigo-500 transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $tituloDetalhado }}</h3>
                <button wire:click="$set('graficoDetalhado', [])" class="text-gray-400 hover:text-red-500 bg-gray-100 hover:bg-red-50 p-1.5 rounded-lg transition"><i class="ph-bold ph-x text-xl"></i></button>
            </div>
            
            <!-- Injeta o componente ChartWidget reutilizando toda a lógica do ApexCharts -->
            <livewire:chart-widget 
                chartId="grafico-drilldown" 
                :config="$graficoDetalhado" 
                wire:key="widget-drilldown-{{ count($graficoDetalhado['series'][0]['data']) }}" />
        </div>
    @endif

</div>