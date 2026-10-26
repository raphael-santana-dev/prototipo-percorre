<div class="p-6 max-w-7xl mx-auto font-sans relative text-gray-800">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ph ph-wallet text-gray-500"></i> Orçamentos Financeiros
            </h1>
            <p class="text-sm text-gray-500 mt-1">Gestão, simulação e envio de orçamentos por centro de custo.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
                <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i> 
                <span wire:loading.remove wire:target="sincronizarProtheus">Importar Protheus</span>
                <span wire:loading wire:target="sincronizarProtheus">Buscando...</span>
            </button>
        </div>
    </div>

    @if(isset($metricas))
        <!-- Painel de Simulação Flat -->
        <div class="mb-6 bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0 border border-gray-200">
                    <i class="ph ph-calendar text-xl text-gray-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Simulador de Base Orçamentária</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Avance a base de todos os orçamentos para o próximo ano.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-gray-50 text-gray-700 border border-gray-200 text-sm font-semibold rounded-md py-1.5 px-3">
                    Ano Base: {{ $anoSimulacao }}
                </div>
                <button wire:click="avancarAnoSimulacao" class="px-4 py-1.5 bg-gray-900 hover:bg-black text-white rounded-md text-sm font-medium transition shadow-sm flex items-center gap-2" onclick="confirm('Esta ação clonará a Base Prevista de TODOS os orçamentos para o próximo ano. Continuar?') || event.stopImmediatePropagation()">
                    Avançar Ano <i class="ph ph-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="mb-6"><x-summary-cards :metricas="$metricas" /></div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-wrap gap-3 items-center">
            <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Ano" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-32 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Filial" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-32 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.500ms="filtroCentroCusto" placeholder="Centro de Custo..." class="rounded-md border-gray-300 text-sm shadow-sm flex-1 focus:ring-gray-400 focus:border-gray-400">
            <select wire:model.live="filtroStatus" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-48 focus:ring-gray-400 focus:border-gray-400">
                <option value="">Todos os Status</option>
                <option value="Criado">Criado (Importado)</option>
                <option value="Em elaboração">Em Elaboração</option>
                <option value="Finalizado">Finalizado (Aguardando)</option>
                <option value="Aprovado">Aprovado</option>
                <option value="Aprovado com ressalvas">Aprovado com Ressalvas</option>
                <option value="Reprovado">Reprovado</option>
            </select>
            <button wire:click="limparFiltros" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-100 transition shadow-sm"><i class="ph-bold ph-funnel-x"></i> Limpar</button>
        </div>

        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3 text-center">Ano</th>
                        <th class="px-4 py-3">Filial</th>
                        <th class="px-4 py-3">Código CC</th>
                        <th class="px-4 py-3">Centro de Custo</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Total Atual</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($registros as $orcamento)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-500">#{{ $orcamento->id }}</td>
                            <td class="px-4 py-3 font-semibold text-center">{{ $orcamento->ano }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $orcamento->filial ?: '-' }}</td>
                            <td class="px-4 py-3 font-mono font-medium">{{ $orcamento->ccusto ?: '-' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($orcamento->status) {
                                        'Em elaboração' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Finalizado' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Aprovado com ressalvas' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                        'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-gray-100 text-gray-600 border-gray-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-[10px] font-semibold rounded-md border {{ $statusColor }} uppercase tracking-wide">{{ $orcamento->status ?? 'Criado' }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-right text-gray-900">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    @if(in_array($orcamento->status, ['Aprovado', 'Aprovado com ressalvas']))
                                        <button wire:click="verJsonConsolidado({{ $orcamento->id }})" class="p-1.5 text-gray-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-md transition" title="Ver JSON Consolidado (Protheus)">
                                            <i class="text-lg ph-bold ph-brackets-curly"></i>
                                        </button>
                                    @endif
                                    <button wire:click="editarOrcamento({{ $orcamento->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-md transition" title="Abrir Planilha de Edição">
                                        <i class="text-lg ph ph-pencil-simple"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum orçamento encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-white">
                {{ $registros->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL JSON CONSOLIDADO --}}
    @if($modalJsonAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/50 px-4" x-data="{ copiado: false }">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full border border-gray-200 overflow-hidden flex flex-col max-h-[85vh]">
                <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <i class="ph-bold ph-brackets-curly text-emerald-600 text-lg"></i> Payload Consolidado
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $tituloOrcamentoJson }}</p>
                    </div>
                    <button wire:click="fecharModalJson" class="text-gray-400 hover:text-gray-700 p-1.5 rounded-md transition"><i class="ph-bold ph-x text-lg"></i></button>
                </div>

                <div class="p-4 bg-gray-50 text-gray-800 font-mono text-xs overflow-y-auto flex-1 border-b border-gray-200 custom-scrollbar">
                    <pre class="whitespace-pre-wrap break-words leading-relaxed">{{ $jsonConsolidado }}</pre>
                </div>

                <div class="px-5 py-3 bg-white flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-gray-500">Coluna: <code class="font-mono font-medium text-gray-700">orcamentos.dados_consolidados</code></span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="navigator.clipboard.writeText(@js($jsonConsolidado)); copiado = true; setTimeout(() => copiado = false, 2000)" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5">
                            <i class="ph-bold" :class="copiado ? 'ph-check text-emerald-600' : 'ph-copy'"></i>
                            <span x-text="copiado ? 'Copiado!' : 'Copiar JSON'"></span>
                        </button>
                        <button type="button" wire:click="fecharModalJson" class="px-4 py-1.5 text-xs font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-md transition">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>