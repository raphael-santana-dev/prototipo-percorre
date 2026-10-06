<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Orçamentos do Protheus"
        icon="ph ph-wallet"
        badge="Módulo Financeiro"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <button wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="btn btn--primary btn--small">
                <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i> 
                <span wire:loading.remove wire:target="sincronizarProtheus">Sincronizar API</span>
                <span wire:loading wire:target="sincronizarProtheus">Buscando...</span>
            </button>
        </x-slot>
    </x-page-header>

    @if(isset($metricas))
        <!-- BARRA DO SIMULADOR E HISTÓRICO -->
        <div class="mb-6 bg-gradient-to-r from-gray-900 to-purpura-900 rounded-xl p-5 shadow-lg border border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/20">
                    <i class="ph-bold ph-calendar-forward text-2xl text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black tracking-wide">Mês Vigente (Simulação): <span class="text-yellow-400">{{ $nomesMeses[$mesSimulacaoAtual] }}</span></h3>
                    <p class="text-xs text-gray-300 mt-0.5">Os meses anteriores a {{ $nomesMeses[$mesSimulacaoAtual] }} serão congelados e o histórico será atualizado ao avançar.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-sm font-bold transition flex items-center gap-2" onclick="confirm('Abrir modal de histórico não implementado visualmente')">
                    <i class="ph-bold ph-clock-counter-clockwise"></i> Ver Histórico Salvo
                </button>

                @if(auth()->user()->hasRole('dev|admin'))
                    <button wire:click="avancarMesSimulacao" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 rounded-lg text-sm font-black transition shadow-md flex items-center gap-2" onclick="confirm('Tem certeza? Isso irá congelar os orçamentos finalizados atuais para o mês de {{ $nomesMeses[$mesSimulacaoAtual] }} e avançar o sistema para o próximo mês.') || event.stopImmediatePropagation()">
                        Avançar Mês <i class="ph-bold ph-arrow-right"></i>
                    </button>
                @endif
            </div>
        </div>

        <div class="mb-6">
            <x-summary-cards :metricas="$metricas" />
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano (Ex: 2026)" class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar Natureza..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="Criado">Criado (Importado)</option>
                    <option value="Em elaboração">Em Elaboração</option>
                    <option value="Finalizado">Finalizados</option>
                </select>

                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white">
                    <i class="ph-bold ph-funnel-x"></i> Limpar
                </button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
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
                            <td class="px-4 py-3 t-label-12 text-gray-500 dark:text-gray-400">#{{ $orcamento->id }}</td>
                            <td class="px-4 py-3 t-body-14-semibold text-center text-gray-900 dark:text-white">{{ $orcamento->ano }}</td>
                            <td class="px-4 py-3 t-label-12-semibold text-gray-700 dark:text-gray-300">{{ $orcamento->filial ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="tag tag--small tag--filled tag--neutral uppercase border-0">
                                    {{ $orcamento->natureza ?: 'S/ NATUREZA' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $orcamento->ccusto ?: '-' }}</td>
                            
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($orcamento->status) {
                                        'Em elaboração' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'Finalizado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider border {{ $statusColor }}">
                                    {{ $orcamento->status ?? 'Criado' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 t-body-14-semibold text-right text-pistache-700 dark:text-pistache-400">
                                R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(feature('financeiro.orcamentos.detalhes') && (auth()->user()->hasRole('dev') || auth()->user()->can('financeiro.orcamentos.detalhes')))
                                    <button wire:click="abrirModalDetalhes({{ $orcamento->id }})" class="p-2 text-gray-400 hover:text-purpura-600 bg-white border border-gray-200 rounded-lg shadow-sm transition" title="Ver / Editar Valores">
                                        <i class="text-base ph-bold ph-pencil-simple"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <i class="ph-fill ph-wallet text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                Nenhum orçamento encontrado com os filtros atuais.<br>Clique em "Atualizar via API" para puxar do Protheus.
                            </td>
                        </tr>
                    @endforelse

                </x-table>
            </div>
        </div>
    </div>

    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            
            <div class="bg-white dark:bg-gray-900 w-full h-full max-w-7xl max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-800 overflow-hidden" 
                 x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-chart-line-up text-purpura-500 text-2xl"></i> Plataforma de Orçamento Web
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Análise e inserção da distribuição mensal (Moeda: {{ $orcamentoSelecionado->moeda }} | C. Moeda: {{ $orcamentoSelecionado->cmoeda ?: '-' }})
                        </p>
                    </div>
                    <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition shadow-sm">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar">
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Filial / Ano</span>
                            <span class="text-base font-black text-gray-900 dark:text-white">{{ $orcamentoSelecionado->filial ?: 'N/A' }} / {{ $orcamentoSelecionado->ano ?: 'N/A' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Natureza</span>
                            <span class="text-sm font-mono font-bold text-purpura-600 dark:text-purpura-400">{{ $orcamentoSelecionado->natureza ?: 'S/ NATUREZA' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Centro de Custo</span>
                            <span class="text-sm font-mono font-bold text-gray-900 dark:text-gray-200">{{ $orcamentoSelecionado->ccusto ?: 'S/ CCUSTO' }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status Atual</span>
                            <span class="text-sm font-bold {{ $orcamentoSelecionado->status === 'Finalizado' ? 'text-emerald-600' : 'text-blue-600' }}">{{ $orcamentoSelecionado->status ?: 'Criado' }}</span>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm">
                            <span class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-500 uppercase tracking-wider mb-1">Total Previsto Anual</span>
                            <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Descrição da Despesa (Área)</label>
                        <textarea wire:model="editData.descricao_despesa" rows="2" class="w-full rounded-xl border-gray-200 shadow-sm text-sm focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white" placeholder="Detalhe o motivo ou contexto deste orçamento..." @if($orcamentoSelecionado->status === 'Finalizado') disabled @endif></textarea>
                    </div>

                    <h3 class="font-extrabold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">Valores Previstos (Mensal)</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @php
                            $mesesConfig = [
                                'valor_jan' => '01 / Janeiro', 'valor_fev' => '02 / Fevereiro', 'valor_mar' => '03 / Março',
                                'valor_abr' => '04 / Abril', 'valor_mai' => '05 / Maio', 'valor_jun' => '06 / Junho',
                                'valor_jul' => '07 / Julho', 'valor_ago' => '08 / Agosto', 'valor_set' => '09 / Setembro',
                                'valor_out' => '10 / Outubro', 'valor_nov' => '11 / Novembro', 'valor_dez' => '12 / Dezembro',
                            ];
                        @endphp

                        @foreach($mesesConfig as $campoKey => $mesNome)
                            <div class="flex flex-col p-3 rounded-xl border {{ ($editData[$campoKey] ?? 0) > 0 ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm' : 'bg-gray-50/50 dark:bg-gray-900/30 border-dashed border-gray-200 dark:border-gray-800' }} focus-within:border-purpura-500 focus-within:shadow-md transition">
                                <label class="text-[10px] font-bold uppercase text-gray-500 mb-1">{{ $mesNome }}</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-0.5 text-sm text-gray-400 font-bold">R$</span>
                                    <input type="number" step="0.01" wire:model="editData.{{ $campoKey }}" 
       class="w-full text-base font-black text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0 pl-8 p-0 disabled:opacity-50"
       @if($orcamentoSelecionado->status === 'Finalizado' || $loop->iteration < $mesSimulacaoAtual) disabled title="Mês congelado pelo simulador ou orçamento finalizado" @endif>
                                </div>
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

                <div class="p-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <span class="text-xs text-gray-500 font-medium">Ao salvar, o status mudará automaticamente para "Em elaboração".</span>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <button wire:click="fecharModal" class="btn btn--secondary btn--medium w-full md:w-auto">Cancelar</button>
                        
                        @if($orcamentoSelecionado->status !== 'Finalizado')
                            <button wire:click="salvarOrcamento" class="btn btn--primary btn--medium w-full md:w-auto bg-gray-900 border-none hover:bg-black">
                                Salvar Rascunho
                            </button>
                            <button wire:click="finalizarOrcamento" class="btn btn--primary btn--medium w-full md:w-auto bg-emerald-600 border-none hover:bg-emerald-700" onclick="confirm('Tem certeza? Orçamentos finalizados não poderão ser alterados.') || event.stopImmediatePropagation()">
                                <i class="ph-bold ph-check"></i> Finalizar Orçamento
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>