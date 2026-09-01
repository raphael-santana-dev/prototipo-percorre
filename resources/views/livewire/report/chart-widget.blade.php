<div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 w-full"
     x-data="{
         chartInstance: null,

         init() {
             // 1. Tenta renderizar assim que a tela carrega
             this.renderOrUpdate(this.$wire.config);

             // 2. Fica escutando as mudanças que vêm do PHP de forma reativa (Assíncrona)
             this.$wire.$watch('config', (newConfig) => {
                 this.renderOrUpdate(newConfig);
             });
         },

         renderOrUpdate(config) {
             // Se os dados não chegaram ainda ou estão vazios, ele aguarda sem quebrar a tela
             if (!config || !config.series || config.series.length === 0) return;

             if (this.chartInstance) {
                 // Gráfico já existe? Apenas atualiza os dados com animação (sem piscar a tela)
                 this.chartInstance.updateOptions({
                     series: config.series,
                     labels: config.labels || [],
                     xaxis: { categories: config.labels || [] }
                 });
             } else {
                 // Cria o gráfico pela primeira vez
                 let options = {
                     series: config.series,
                     chart: {
                         type: config.type || 'bar',
                         height: config.height || 300,
                         background: 'transparent',
                         toolbar: { show: true },
                         events: {
                             // Drill-down (Ação de clique)
                             dataPointSelection: (event, chartContext, cfg) => {
                                 if (config.labels && config.labels[cfg.dataPointIndex]) {
                                     let labelClicado = config.labels[cfg.dataPointIndex];
                                     this.$dispatch('chart-click', { chartId: '{{ $chartId }}', label: labelClicado });
                                 }
                             }
                         }
                     },
                     labels: config.labels || [],
                     xaxis: { categories: config.labels || [] },
                     theme: { palette: 'palette1' }
                 };

                 // Usa o $refs do Alpine (muito mais seguro que buscar por ID)
                 this.chartInstance = new ApexCharts(this.$refs.graficoCanvas, options);
                 this.chartInstance.render();
             }
         }
     }">

    <!-- O Título reativo -->
    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4" x-text="$wire.config.title || ''"></h3>

    <!-- IMPORTANTE: O wire:ignore impede que o Livewire destrua o Canvas gerado pelo JavaScript -->
    <div x-ref="graficoCanvas" wire:ignore class="w-full"></div>
</div>