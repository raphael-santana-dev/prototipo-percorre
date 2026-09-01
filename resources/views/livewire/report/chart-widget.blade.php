<div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 w-full"
     x-data="{
         chartInstance: null,

         init() {
             this.renderOrUpdate(this.$wire.config);

             this.$wire.$watch('config', (newConfig) => {
                 this.renderOrUpdate(newConfig);
             });
         },

         renderOrUpdate(config) {
             if (!config || !config.series || config.series.length === 0) return;

             // TRUQUE DE MESTRE: Cria um clone limpo do objeto para a biblioteca não mutar o Livewire
             let rawConfig = JSON.parse(JSON.stringify(config));

             if (this.chartInstance) {
                 this.chartInstance.updateOptions({
                     series: rawConfig.series,
                     labels: rawConfig.labels || [],
                     xaxis: { categories: rawConfig.labels || [] }
                 });
             } else {
                 let options = {
                     series: rawConfig.series,
                     chart: {
                         type: rawConfig.type || 'bar',
                         height: rawConfig.height || 300,
                         background: 'transparent',
                         toolbar: { show: true },
                         events: {
                             dataPointSelection: (event, chartContext, cfg) => {
                                 if (rawConfig.labels && rawConfig.labels[cfg.dataPointIndex]) {
                                     let labelClicado = rawConfig.labels[cfg.dataPointIndex];
                                     // O Livewire 3 receberá isso como parâmetros nomeados
                                     this.$dispatch('chart-click', { chartId: '{{ $chartId }}', label: labelClicado });
                                 }
                             }
                         }
                     },
                     labels: rawConfig.labels || [],
                     xaxis: { categories: rawConfig.labels || [] },
                     theme: { palette: 'palette1' }
                 };

                 this.chartInstance = new ApexCharts(this.$refs.graficoCanvas, options);
                 this.chartInstance.render();
             }
         }
     }">

    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4" x-text="$wire.config.title || ''"></h3>
    <div x-ref="graficoCanvas" wire:ignore class="w-full"></div>
</div>