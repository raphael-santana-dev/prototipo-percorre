<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header title="Orçamentos Financeiros" icon="ph ph-wallet" badge="Gestão" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <button wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="btn btn--primary btn--small">
                <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i> 
                <span wire:loading.remove wire:target="sincronizarProtheus">Importar Protheus</span>
                <span wire:loading wire:target="sincronizarProtheus">Buscando...</span>
            </button>
        </x-slot>
    </x-page-header>

    @if(isset($metricas))
        <div class="mb-6 bg-gradient-to-r from-gray-900 to-purpura-900 rounded-xl p-5 shadow-lg border border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm shrink-0">
                    <i class="ph ph-calendar text-2xl text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="text-lg tracking-wide">Simulador Orçamentos</h3>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-gray-800 text-white text-sm rounded-lg py-2 px-4">Ano: {{ $anoSimulacao }}</div>
                <button wire:click="avancarAnoSimulacao" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 rounded-lg text-sm transition shadow-md flex items-center gap-2 whitespace-nowrap" onclick="confirm('Esta ação clonará a Base Prevista de TODOS os orçamentos para o próximo ano. Continuar?') || event.stopImmediatePropagation()">
                    Avançar <i class="ph ph-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="mb-6"><x-summary-cards :metricas="$metricas" /></div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroCentroCusto" placeholder="Buscar Centro de Custo" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="Criado">Criado (Importado)</option>
                    <option value="Em elaboração">Em Elaboração</option>
                    <option value="Finalizado">Finalizado (Aguardando)</option>
                    <option value="Aprovado">Aprovado</option>
                    <option value="Aprovado com ressalvas">Aprovado com Ressalvas</option>
                    <option value="Reprovado">Reprovado</option>
                </select>
                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white"><i class="ph-bold ph-funnel-x"></i> Limpar Filtros</button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <x-table :headers="$this->headers" :registros="$registros" :ordenacaoCampo="$ordenacaoCampo" :ordenacaoDirecao="$ordenacaoDirecao" :permiteGrid="$permiteGrid" :modoExibicao="$modoExibicao">
                @forelse($registros as $orcamento)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                        <td class="px-4 py-1.5 t-label-12 text-gray-500">#{{ $orcamento->id }}</td>
                        <td class="px-4 py-1.5 t-body-14-semibold text-center">{{ $orcamento->ano }}</td>
                        <td class="px-4 py-1.5 t-label-12-semibold">{{ $orcamento->filial ?: '-' }}</td>
                        <td class="px-4 py-1.5 text-xs font-mono font-bold text-purpura-600">{{ $orcamento->ccusto ?: '-' }}</td>
                        <td class="px-4 py-1.5 text-sm font-bold text-gray-900 dark:text-white">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}</td>
                        <td class="px-4 py-1.5 text-center">
                            @php
                                $statusColor = match($orcamento->status) {
                                    'Em elaboração' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Finalizado' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'Aprovado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Aprovado com ressalvas' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Reprovado' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase border {{ $statusColor }} whitespace-nowrap">{{ $orcamento->status ?? 'Criado' }}</span>
                        </td>
                        <td class="px-4 py-1.5 text-base font-bold text-right text-gray-900 dark:text-white tracking-tight">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-1.5 text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                @if(in_array($orcamento->status, ['Aprovado', 'Aprovado com ressalvas']))
                                    <button wire:click="verJsonConsolidado({{ $orcamento->id }})" 
                                            class="p-1.5 text-emerald-600 transition-colors rounded hover:text-emerald-800 hover:bg-emerald-50 dark:hover:bg-gray-700" 
                                            title="Ver JSON Consolidado (Tabela Intermediária Protheus)">
                                        <i class="text-lg ph-bold ph-brackets-curly"></i>
                                    </button>
                                @endif

                                <button wire:click="editarOrcamento({{ $orcamento->id }})" 
                                        class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" 
                                        title="Abrir Planilha de Edição">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">Nenhum orçamento encontrado.</td></tr>
                @endforelse
            </x-table>
        </div>
    </div>

    {{-- MODAL DE VISUALIZAÇÃO DO JSON CONSOLIDADO (TABELA INTERMEDIÁRIA PROTHEUS) --}}
    @if($modalJsonAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4"
             x-data="{ copiado: false }">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[85vh]">
                
                <!-- Cabeçalho do Modal -->
                <div class="px-6 py-4 bg-gray-900 text-white flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider flex items-center gap-2">
                            <i class="ph-bold ph-brackets-curly text-emerald-400 text-lg"></i>
                            Payload Consolidado - Tabela Intermediária Protheus
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $tituloOrcamentoJson }}</p>
                    </div>
                    <button wire:click="fecharModalJson" class="text-gray-400 hover:text-white p-1.5 rounded-lg transition">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Corpo com o JSON Formatado -->
                <div class="p-4 bg-gray-950 text-emerald-400 font-mono text-xs overflow-y-auto flex-1 custom-scrollbar">
                    <pre class="whitespace-pre-wrap break-words leading-relaxed">{{ $jsonConsolidado }}</pre>
                </div>

                <!-- Rodapé do Modal -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-gray-500">
                        Registro gravado na coluna <code class="font-mono font-bold text-gray-700 dark:text-gray-300">orcamentos.dados_consolidados</code>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="navigator.clipboard.writeText(@js($jsonConsolidado)); copiado = true; setTimeout(() => copiado = false, 2000)"
                                class="px-3 py-1.5 text-xs font-bold rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition flex items-center gap-1.5 shadow-sm">
                            <i class="ph-bold" :class="copiado ? 'ph-check text-emerald-600' : 'ph-copy'"></i>
                            <span x-text="copiado ? 'Copiado!' : 'Copiar JSON'"></span>
                        </button>
                        <button type="button" wire:click="fecharModalJson" class="px-4 py-1.5 text-xs font-bold text-white bg-gray-900 hover:bg-black rounded-md transition shadow-sm">
                            Fechar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>