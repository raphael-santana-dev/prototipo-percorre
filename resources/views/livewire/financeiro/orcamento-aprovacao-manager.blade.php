<div class="p-6 max-w-7xl mx-auto font-sans relative text-gray-800">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ph ph-check-square-offset text-gray-500"></i> Aprovações Orçamentárias
            </h1>
            <p class="text-sm text-gray-500 mt-1">Análise, correção e aprovação de orçamentos da Diretoria.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 flex items-center gap-2 shadow-sm">
                <i class="ph-bold ph-clock"></i> {{ $totalAguardando }} Aguardando Avaliação
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-wrap gap-3 items-center">
            <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Ano" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-32 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Filial" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-32 focus:ring-gray-400 focus:border-gray-400">
            <input type="text" wire:model.live.debounce.500ms="filtroCentroCusto" placeholder="Centro de Custo..." class="rounded-md border-gray-300 text-sm shadow-sm flex-1 focus:ring-gray-400 focus:border-gray-400">
            <select wire:model.live="filtroStatus" class="rounded-md border-gray-300 text-sm shadow-sm w-full md:w-48 focus:ring-gray-400 focus:border-gray-400">
                <option value="Finalizado">Aguardando Avaliação</option>
                <option value="Aprovado">Aprovados</option>
                <option value="Aprovado com ressalvas">Aprovados (Ressalvas)</option>
                <option value="Reprovado">Reprovados</option>
                <option value="">Todos Avaliados</option>
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
                        <th class="px-4 py-3">Centro de Custo</th>
                        <th class="px-4 py-3">Cód. CC</th>
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
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}</td>
                            <td class="px-4 py-3 font-mono font-medium">{{ $orcamento->ccusto ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($orcamento->status) {
                                        'Finalizado' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Aprovado com ressalvas' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                        'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-gray-100 text-gray-600 border-gray-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-[10px] font-semibold rounded-md border {{ $statusColor }} uppercase tracking-wide">{{ $orcamento->status === 'Finalizado' ? 'Aguardando' : $orcamento->status }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-right text-gray-900">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="avaliarOrcamento({{ $orcamento->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-md transition" title="Analisar Orçamento"><i class="text-lg ph ph-magnifying-glass"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum orçamento pendente encontrado.</td></tr>
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