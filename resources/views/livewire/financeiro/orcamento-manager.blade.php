<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <!-- CABEÇALHO DA PÁGINA -->
    <x-page-header 
        title="Orçamentos do Protheus"
        icon="ph ph-wallet"
        badge="Módulo Financeiro"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <!-- Botão de Sincronização com feedback visual de "Carregando" -->
            <button wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="px-5 py-2.5 text-xs font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition flex items-center gap-2">
                <i class="ph-bold ph-arrows-clockwise text-base" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i> 
                <span wire:loading.remove wire:target="sincronizarProtheus">Atualizar via API</span>
                <span wire:loading wire:target="sincronizarProtheus">Sincronizando...</span>
            </button>
        </x-slot>
    </x-page-header>

    <!-- CARDS DE MÉTRICAS -->
    @if(isset($metricas))
        <div class="mb-6">
            <x-summary-cards :metricas="$metricas" />
        </div>
    @endif

    <!-- CONTEÚDO PRINCIPAL (Filtros colados com a Tabela) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <!-- BARRA DE FILTROS -->
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar por Ano (Ex: 2026)" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar por Filial..." class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar por Natureza..." class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                
                <button wire:click="limparFiltros" class="w-full flex items-center justify-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold rounded-lg shadow-sm transition dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 text-xs py-2">
                    <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                </button>
            </div>
        </div>

        <!-- TABELA DE DADOS -->
        <div class="relative z-0 bg-white dark:bg-gray-900">
            
            <!-- CSS Mágico para retirar a borda dupla do componente de tabela genérico -->
            <style>
                .tabela-orcamento .bg-white.border.rounded-xl { border: none !important; border-radius: 0 !important; box-shadow: none !important; }
                .dark .tabela-orcamento .dark\:bg-gray-800.dark\:border-gray-700 { border: none !important; background: transparent !important; }
                .tabela-orcamento .custom-scrollbar table thead tr th { border-top: 1px solid #f3f4f6; }
                .dark .tabela-orcamento .custom-scrollbar table thead tr th { border-top-color: #374151; }
            </style>

            <div class="tabela-orcamento">
                <x-table 
                    :headers="$this->headers" 
                    :registros="$registros" 
                    :ordenacaoCampo="$ordenacaoCampo" 
                    :ordenacaoDirecao="$ordenacaoDirecao" 
                    :permiteGrid="$permiteGrid" 
                    :modoExibicao="$modoExibicao">

                    @forelse($registros as $orcamento)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                            <td class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400">#{{ $orcamento->id }}</td>
                            <td class="px-4 py-3 text-sm font-black text-center text-gray-900 dark:text-white">{{ $orcamento->ano }}</td>
                            <td class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-300">{{ $orcamento->filial ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider bg-gray-100 text-gray-600 border border-gray-200 rounded dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                    {{ $orcamento->natureza ?: 'S/ NATUREZA' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $orcamento->ccusto ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm font-black text-right text-emerald-600 dark:text-emerald-400">
                                R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="abrirModalDetalhes({{ $orcamento->id }})" class="p-2 text-gray-400 hover:text-purpura-600 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-purpura-400 rounded-lg shadow-sm transition" title="Ver Distribuição Mensal">
                                    <i class="text-base ph-bold ph-arrows-out-simple"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <i class="ph-fill ph-wallet text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                Nenhum orçamento encontrado no banco de dados local.<br>Clique em "Atualizar via API" para buscar do Protheus.
                            </td>
                        </tr>
                    @endforelse

                </x-table>
            </div>
        </div>
    </div>

    <!-- ==============================================
         MODAL EM TELA CHEIA (DETALHES DO ORÇAMENTO)
    =============================================== -->
    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            
            <!-- Janela do Modal (Quase tela inteira) -->
            <div class="bg-white dark:bg-gray-900 w-full h-full max-w-7xl max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-800 overflow-hidden" 
                 x-data @keydown.escape.window="$wire.fecharModal()">
                
                <!-- Cabeçalho do Modal -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-chart-line-up text-purpura-500 text-2xl"></i> Detalhamento Orçamentário
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Análise de distribuição mensal (Moeda: {{ $orcamentoSelecionado->moeda }} | C. Moeda: {{ $orcamentoSelecionado->cmoeda ?: '-' }})
                        </p>
                    </div>
                    <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition shadow-sm">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Corpo do Modal (Com rolagem interna) -->
                <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar">
                    
                    <!-- Cards Superiores: Resumo da Linha -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Filial</span>
                            <span class="text-lg font-black text-gray-900 dark:text-white">{{ $orcamentoSelecionado->filial ?: 'N/A' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Ano Base</span>
                            <span class="text-lg font-black text-gray-900 dark:text-white">{{ $orcamentoSelecionado->ano ?: 'N/A' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Natureza</span>
                            <span class="text-base font-mono font-bold text-purpura-600 dark:text-purpura-400">{{ $orcamentoSelecionado->natureza ?: 'S/ NATUREZA' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Centro de Custo</span>
                            <span class="text-base font-mono font-bold text-gray-900 dark:text-gray-200">{{ $orcamentoSelecionado->ccusto ?: 'S/ CCUSTO' }}</span>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm">
                            <span class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-500 uppercase tracking-wider mb-1">Total Previsto Anual</span>
                            <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <h3 class="font-extrabold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">Distribuição Financeira Mensal</h3>
                    
                    <!-- Grid dos 12 Meses -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @php
                            $meses = [
                                '01 / Janeiro' => $orcamentoSelecionado->valor_jan,
                                '02 / Fevereiro' => $orcamentoSelecionado->valor_fev,
                                '03 / Março' => $orcamentoSelecionado->valor_mar,
                                '04 / Abril' => $orcamentoSelecionado->valor_abr,
                                '05 / Maio' => $orcamentoSelecionado->valor_mai,
                                '06 / Junho' => $orcamentoSelecionado->valor_jun,
                                '07 / Julho' => $orcamentoSelecionado->valor_jul,
                                '08 / Agosto' => $orcamentoSelecionado->valor_ago,
                                '09 / Setembro' => $orcamentoSelecionado->valor_set,
                                '10 / Outubro' => $orcamentoSelecionado->valor_out,
                                '11 / Novembro' => $orcamentoSelecionado->valor_nov,
                                '12 / Dezembro' => $orcamentoSelecionado->valor_dez,
                            ];
                        @endphp

                        @foreach($meses as $mes => $valor)
                            <div class="flex flex-col p-4 rounded-xl border {{ $valor > 0 ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm hover:border-purpura-300 dark:hover:border-purpura-600 transition' : 'bg-gray-50/50 dark:bg-gray-900/30 border-dashed border-gray-200 dark:border-gray-800 opacity-60' }}">
                                <span class="text-[10px] font-bold uppercase text-gray-500 mb-1">{{ $mes }}</span>
                                <span class="text-base font-black {{ $valor > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">
                                    R$ {{ number_format($valor, 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @if($orcamentoSelecionado->xcat)
                        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <span class="block text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Metadados de Categoria (XCAT)</span>
                            <span class="text-sm font-mono text-gray-800 dark:text-gray-200 break-all">{{ $orcamentoSelecionado->xcat }}</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif

</div>