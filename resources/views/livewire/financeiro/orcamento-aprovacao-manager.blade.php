<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Aprovações Orçamentárias" icon="ph ph-check-square-offset" badge="Diretoria" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-bold border border-purple-200 flex items-center gap-2 shadow-sm">
                <i class="ph-fill ph-clock"></i> {{ $totalAguardando }} Aguardando Avaliação
            </span>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mt-6">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                    <option value="Finalizado">Aguardando Avaliação</option>
                    <option value="Aprovado">Aprovados</option>
                    <option value="Aprovado com ressalvas">Aprovados (Ressalvas)</option>
                    <option value="Reprovado">Reprovados</option>
                    <option value="">Todos Avaliados</option>
                </select>
                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white"><i class="ph-bold ph-funnel-x"></i> Limpar</button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <x-table :headers="$this->headers" :registros="$registros" :ordenacaoCampo="$ordenacaoCampo" :ordenacaoDirecao="$ordenacaoDirecao" :permiteGrid="$permiteGrid" :modoExibicao="$modoExibicao">
                @forelse($registros as $orcamento)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                        <td class="px-4 py-4 t-label-12 text-gray-500">#{{ $orcamento->id }}</td>
                        <td class="px-4 py-4 t-body-14-semibold text-center">{{ $orcamento->ano }}</td>
                        <td class="px-4 py-4 t-label-12-semibold">{{ $orcamento->filial ?: '-' }}</td>
                        <td class="px-4 py-4">
                            <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}</span>
                            <span class="text-[10px] text-purpura-600 font-mono block max-w-[200px]">C.C. {{ $orcamento->ccusto ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @php
                                $statusColor = match($orcamento->status) {
                                    'Finalizado' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'Aprovado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Aprovado com ressalvas' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Reprovado' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase border {{ $statusColor }} whitespace-nowrap">{{ $orcamento->status === 'Finalizado' ? 'Aguardando' : $orcamento->status }}</span>
                        </td>
                        <td class="px-4 py-4 t-body-14-semibold text-right text-gray-900 dark:text-white">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right">
                            <button wire:click="avaliarOrcamento({{ $orcamento->id }})" class="p-2 text-white bg-gray-900 hover:bg-black rounded-lg shadow-sm transition flex items-center gap-2 ml-auto" title="Analisar Orçamento"><i class="text-base ph-bold ph-magnifying-glass"></i> Analisar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Nenhum orçamento pendente encontrado.</td></tr>
                @endforelse
            </x-table>
        </div>
    </div>
</div>