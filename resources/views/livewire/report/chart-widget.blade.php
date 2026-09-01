<div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 w-full"
     x-data="{
         chartId: '{{ $chartId }}',
         config: @entangle('config').live,
         chartInstance: null,
         
         init() {
             // Fica observando o Backend. Se a configuração mudar (via botão atualizar ou auto-load), atualiza o gráfico
             this.$watch('config', (val) => {
                 if (val && val.series && val.series.length > 0) {
                     this.initOrUpdateChart();
                 }
             });
             
             // Renderização inicial
             if (this.config && this.config.series && this.config.series.length > 0) {
                 this.initOrUpdateChart();
             }
         },
         
         initOrUpdateChart() {
             if (this.chartInstance) {
                 // Se o gráfico já existe, injeta apenas os novos dados sem piscar a tela
                 this.chartInstance.updateOptions({
                     series: this.config.series,
                     labels: this.config.labels || [],
                     xaxis: { categories: this.config.labels || [] }
                 });
             } else {
                 // Cria o gráfico pela primeira vez
                 let options = {
                     series: this.config.series,
                     chart: {
                         type: this.config.type,
                         height: this.config.height || 350,
                         background: 'transparent',
                         toolbar: { show: true },
                         events: {
                             dataPointSelection: (event, chartContext, cfg) => {
                                 if (this.config.labels && this.config.labels[cfg.dataPointIndex]) {
                                     let labelClicado = this.config.labels[cfg.dataPointIndex];
                                     this.$dispatch('chart-click', { chartId: this.chartId, label: labelClicado });
                                 }
                             }
                         }
                     },
                     labels: this.config.labels || [],
                     xaxis: { categories: this.config.labels || [] },
                     theme: { palette: 'palette1' }
                 };
                 
                 this.chartInstance = new ApexCharts(document.getElementById(this.chartId), options);
                 this.chartInstance.render();
             }
         }
     }">
    
    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4" x-text="config.title || ''"></h3>
    
    <!-- O ApexCharts usará o ID da div para desenhar o Canvas dinamicamente -->
    <div :id="chartId" class="w-full"></div>
</div>