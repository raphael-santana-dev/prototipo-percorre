<div class="p-6 max-w-7xl mx-auto font-sans relative text-gray-800">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ph ph-check-square-offset text-gray-500"></i> Aprovações Orçamentárias
            </h1>
            <p class="text-sm text-gray-500 mt-1">Análise, revisão e aprovação de orçamentos dos centros de custo vinculados ao seu perfil.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-amber-50 text-amber-800 px-4 py-2 rounded-lg text-sm font-medium border border-amber-200 flex items-center gap-2 shadow-2xs">
                <i class="ph-bold ph-clock"></i> {{ $totalAguardando }} Aguardando Aprovação
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-2xs border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-wrap gap-3 items-center">
            <input type="text" wire:model.live.debounce.400ms="filtroAno" placeholder="Ano" class="rounded-md border-gray-300 text-sm w-full md:w-28 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.400ms="filtroFilial" placeholder="Filial" class="rounded-md border-gray-300 text-sm w-full md:w-28 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.400ms="filtroCentroCusto" placeholder="Buscar código ou nome do Centro de Custo..." class="rounded-md border-gray-300 text-sm flex-1 focus:ring-gray-400 focus:border-gray-400">
            <select wire:model.live="filtroStatus" class="rounded-md border-gray-300 text-sm w-full md:w-64 focus:ring-gray-400 focus:border-gray-400">
                <option value="">Todos os Status</option>
                <option value="Finalizado – Aguardando Aprovação">Finalizado – Aguardando Aprovação</option>
                <option value="Aprovação – Aguardando Revisão pelo Responsável">Aprovação – Aguardando Revisão pelo Responsável</option>
                <option value="Finalizado – Aprovado">Finalizado – Aprovado</option>
                <option value="Finalizado – Integrado ao Protheus">Finalizado – Integrado ao Protheus</option>
            </select>
            <button type="button" wire:click="limparFiltros" class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-100 transition h-[38px] flex items-center gap-1.5">
                <i class="ph-bold ph-funnel-x"></i> Limpar
            </button>
        </div>

        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3 text-center">Ano</th>
                        <th class="px-4 py-3">Filial</th>
                        <th class="px-4 py-3">Cód. CC</th>
                        <th class="px-4 py-3">Centro de Custo</th>
                        <th class="px-4 py-3 text-center">Status Global</th>
                        <th class="px-4 py-3 text-right">Total Solicitado</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($registros as $orcamento)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-500">#{{ $orcamento->id }}</td>
                            <td class="px-4 py-3 font-semibold text-center">{{ $orcamento->ano }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $orcamento->filial ?: '-' }}</td>
                            <td class="px-4 py-3 font-mono font-medium text-gray-900">{{ $orcamento->ccusto ?: '-' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : ($orcamento->descricao_despesa ?: 'Sem Vínculo') }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($orcamento->status) {
                                        'Finalizado – Aguardando Aprovação', 'Finalizado' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'Aprovação – Aguardando Revisão pelo Responsável', 'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
                                        'Finalizado – Aprovado', 'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Finalizado – Integrado ao Protheus' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        default => 'bg-gray-100 text-gray-600 border-gray-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-[10px] font-semibold rounded border {{ $statusColor }} uppercase tracking-wide">
                                    {{ $orcamento->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-right text-gray-900 font-mono">
                                R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="avaliarOrcamento({{ $orcamento->id }})" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition inline-flex items-center gap-1.5" title="Analisar Orçamento">
                                    <i class="ph-bold ph-magnifying-glass"></i> Analisar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Nenhum orçamento encontrado para os filtros selecionados.
                            </td>
                        </tr>
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
</div>