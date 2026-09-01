<div class="p-6 max-w-7xl mx-auto font-sans relative" 
     wire:init="carregarDados" 
     wire:poll.60s="carregarDados">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200">
        <div>
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2"><i class="ph-fill ph-chart-pie-slice text-purpura-500"></i> Relatórios Analíticos</h2>
            <p class="text-xs text-gray-500">Dados demográficos dinâmicos e funil de acompanhamento em tempo real.</p>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-pulse">
            @for ($i = 0; $i < 8; $i++)
                <div class="h-80 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            @endfor
        </div>
    @else
        <!-- RESULTADO DRILL DOWN -->
        @if(!empty($graficoDetalhado))
            <div class="mb-8 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-indigo-200 border-t-4 border-t-indigo-500 transition-all">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $tituloDetalhado }}</h3>
                    <button wire:click="$set('graficoDetalhado', [])" class="text-gray-400 hover:text-red-500 bg-gray-100 hover:bg-red-50 p-1.5 rounded-lg transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                <livewire:chart-widget chartId="grafico-drilldown" :config="$graficoDetalhado" wire:key="widget-drilldown-{{ rand() }}" />
            </div>
        @endif

        <h3 class="font-bold text-gray-600 mb-4 uppercase tracking-widest text-xs"><i class="ph-bold ph-funnel text-purpura-500"></i> Funil e Ocupação</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <livewire:chart-widget chartId="grafico-vagas" :config="$graficoVagas" wire:key="widget-vagas-{{ $filtroCiclo }}" />
            <livewire:chart-widget chartId="grafico-status" :config="$graficoInscricoes" wire:key="widget-status-{{ $filtroCiclo }}" />
        </div>

        <h3 class="font-bold text-gray-600 mb-4 uppercase tracking-widest text-xs"><i class="ph-bold ph-map-pin text-purpura-500"></i> Distribuição Operacional</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <livewire:chart-widget chartId="grafico-cursos" :config="$graficoCursos" wire:key="widget-cursos-{{ $filtroCiclo }}" />
            <livewire:chart-widget chartId="grafico-unidades" :config="$graficoUnidades" wire:key="widget-unidades-{{ $filtroCiclo }}" />
        </div>

        <h3 class="font-bold text-gray-600 mb-4 uppercase tracking-widest text-xs"><i class="ph-bold ph-users-three text-purpura-500"></i> Perfil e Demografia (JSON Builders)</h3>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <livewire:chart-widget chartId="grafico-idades" :config="$graficoIdades" wire:key="widget-idades-{{ $filtroCiclo }}" />
            <livewire:chart-widget chartId="grafico-genero" :config="$graficoGenero" wire:key="widget-genero-{{ $filtroCiclo }}" />
            <livewire:chart-widget chartId="grafico-raca" :config="$graficoRaca" wire:key="widget-raca-{{ $filtroCiclo }}" />
            <livewire:chart-widget chartId="grafico-pcd" :config="$graficoPCD" wire:key="widget-pcd-{{ $filtroCiclo }}" />
        </div>
    @endif
</div>