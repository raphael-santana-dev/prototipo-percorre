<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <x-page-header 
        title="{{ $automacao->nome }}" 
        subtitle="Métricas de entrega, aberturas e cliques desta regra de automação"
        icon="ph ph-chart-line-up"
        badge="{{ $automacao->status ? 'Ativa' : 'Pausada' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-arrow-left"></i> Voltar
            </a>

            @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                <button type="button" wire:click="alternarStatusRegra" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    <i class="ph {{ $automacao->status ? 'ph-pause' : 'ph-play' }}"></i>
                    {{ $automacao->status ? 'Pausar Regra' : 'Ativar Regra' }}
                </button>

                <a href="{{ route('automacoes.edit', $automacao->id) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-pencil-simple"></i> Editar Regra
                </a>
            @endif
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <code class="px-2.5 py-1 rounded bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 font-mono">
                        {{ $automacao->evento_gatilho }}
                    </code>
                    <span class="text-gray-400">→</span>
                    <span class="px-2.5 py-1 rounded bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 font-medium">
                        {{ $automacao->template->nome ?? 'Template Removido' }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-60">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar destinatário..." class="w-full pl-9 h-9 rounded-lg border-gray-200 text-xs focus:ring-1 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5">
                        <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                        <span class="text-gray-300 dark:text-gray-600 mx-1">até</span>
                        <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                    </div>

                    @if($filtro_busca !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                        <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 rounded-lg transition">
                            Limpar
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mt-4">
        <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                Histórico de Disparos e Engajamento
            </h3>
            <span class="text-xs text-gray-400">{{ $historico->total() }} registro(s)</span>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-600 dark:text-gray-400">
                <thead class="text-[11px] text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-5 py-3 font-medium">Data e Hora</th>
                        <th class="px-5 py-3 font-medium">Destinatário(s)</th>
                        <th class="px-5 py-3 font-medium">Engajamento</th>
                        <th class="px-5 py-3 font-medium text-center">Status</th>
                        <th class="px-5 py-3 font-medium text-right">Auditoria</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($historico as $log)
                        <tr wire:key="log-auto-{{ $log->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="font-medium text-xs text-gray-900 dark:text-white">{{ $log->created_at->format('d/m/Y') }}</span>
                                <span class="text-gray-400 text-[11px] font-mono ml-1">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @php 
                                    $arrayDestinatarios = is_array($log->destinatarios) ? $log->destinatarios : [$log->destinatarios];
                                    $primeiroEmail = $arrayDestinatarios[0] ?? 'Desconhecido';
                                    $qtdTotal = count($arrayDestinatarios);
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-900 dark:text-gray-200">{{ \Illuminate\Support\Str::limit($primeiroEmail, 42) }}</span>
                                    @if($qtdTotal > 1)
                                        <span class="text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-600">
                                            +{{ $qtdTotal - 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($log->aberturas_count > 0)
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                            Aberto ({{ $log->aberturas_count }}x)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                                            Não aberto
                                        </span>
                                    @endif

                                    @if($log->cliques_count > 0)
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800">
                                            {{ $log->cliques_count }} {{ $log->cliques_count == 1 ? 'clique' : 'cliques' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-center">
                                <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                    Entregue
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <button wire:click="showQuickView({{ $log->id }})" class="px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded transition">
                                    Detalhes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-400 text-xs">
                                Nenhum disparo registrado para este filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($historico->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-center">
                {{ $historico->links('components.paginacao-customizada') }}
            </div>
        @endif
    </div>
</div>