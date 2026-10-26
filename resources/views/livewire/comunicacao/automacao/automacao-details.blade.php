<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $automacao->nome }}" 
        subtitle="Métricas de entrega, aberturas e cliques desta regra de automação"
        icon="ph ph-chart-line-up"
        badge="{{ $automacao->status ? 'Ativa' : 'Pausada' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>

            @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                <button type="button" wire:click="alternarStatusRegra" class="btn btn--secondary btn--medium {{ $automacao->status ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200' }}">
                    <i class="ph-bold {{ $automacao->status ? 'ph-pause' : 'ph-play' }}"></i>
                    {{ $automacao->status ? 'Pausar Regra' : 'Ativar Regra' }}
                </button>

                <a href="{{ route('automacoes.edit', $automacao->id) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                    <i class="ph-bold ph-pencil-simple"></i> Editar Regra
                </a>
            @endif
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
                <!-- Resumo Técnico do Gatilho e Template -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800 font-mono font-bold">
                        <i class="ph-fill ph-lightning"></i> {{ $automacao->evento_gatilho }}
                    </span>
                    <span class="text-gray-300 dark:text-gray-600">→</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purpura-50 text-purpura-700 border border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800 font-bold">
                        <i class="ph-fill ph-layout"></i> {{ $automacao->template->nome ?? 'Template Removido' }}
                    </span>
                </div>

                <!-- Filtros de Busca e Data -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-60">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar destinatário..." class="w-full pl-9 h-9 rounded-lg border-gray-200 text-xs shadow-2xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-2xs px-2.5">
                        <span class="text-[10px] text-gray-400 font-bold uppercase mr-1.5">Período</span>
                        <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                        <span class="text-gray-300 dark:text-gray-600 mx-1">até</span>
                        <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                    </div>

                    @if($filtro_busca !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                        <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 rounded-lg transition flex items-center gap-1">
                            <i class="ph-bold ph-x"></i> Limpar
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-2xl overflow-hidden mt-4">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/40">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-clock-counter-clockwise text-purpura-600 text-base"></i> Histórico de Disparos e Engajamento
            </h3>
            <span class="text-xs font-bold text-gray-400">{{ $historico->total() }} registro(s)</span>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-600 dark:text-gray-400">
                <thead class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60">
                    <tr>
                        <th class="px-6 py-3.5 font-bold">Data e Hora</th>
                        <th class="px-6 py-3.5 font-bold">Destinatário(s)</th>
                        <th class="px-6 py-3.5 font-bold">Engajamento (Leituras / Cliques)</th>
                        <th class="px-6 py-3.5 font-bold text-center">Status</th>
                        <th class="px-6 py-3.5 font-bold text-right">Auditoria</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($historico as $log)
                        <tr wire:key="log-auto-{{ $log->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="font-bold text-xs text-gray-900 dark:text-white">{{ $log->created_at->format('d/m/Y') }}</span>
                                <span class="text-gray-400 text-[11px] font-mono ml-1">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @php 
                                    $arrayDestinatarios = is_array($log->destinatarios) ? $log->destinatarios : [$log->destinatarios];
                                    $primeiroEmail = $arrayDestinatarios[0] ?? 'Desconhecido';
                                    $qtdTotal = count($arrayDestinatarios);
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-purpura-50 dark:bg-purpura-900/40 text-purpura-600 dark:text-purpura-300 font-bold text-[11px] flex items-center justify-center border border-purpura-200 dark:border-purpura-800 shrink-0">
                                        {{ strtoupper(substr($primeiroEmail, 0, 1)) }}
                                    </div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ \Illuminate\Support\Str::limit($primeiroEmail, 38) }}</span>
                                    @if($qtdTotal > 1)
                                        <span class="text-[10px] font-extrabold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-600">
                                            +{{ $qtdTotal - 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($log->aberturas_count > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                            <i class="ph-fill ph-envelope-open"></i> Aberto ({{ $log->aberturas_count }}x)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">
                                            <i class="ph ph-envelope"></i> Não aberto
                                        </span>
                                    @endif

                                    @if($log->cliques_count > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-purpura-50 text-purpura-700 border border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800">
                                            <i class="ph-fill ph-cursor-click"></i> {{ $log->cliques_count }} {{ $log->cliques_count == 1 ? 'clique' : 'cliques' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Entregue
                                </span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right">
                                <button wire:click="showQuickView({{ $log->id }})" class="px-2.5 py-1 text-xs font-bold text-gray-600 dark:text-gray-300 hover:text-purpura-600 bg-gray-50 hover:bg-purpura-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg transition inline-flex items-center gap-1" title="Ver Rastreamento de Cliques e Aberturas">
                                    <i class="ph-bold ph-info"></i> Detalhes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                                <i class="ph-duotone ph-envelope-simple-open text-4xl mb-2 block mx-auto"></i>
                                <p class="font-bold text-gray-600 dark:text-gray-300 text-sm">Nenhum disparo registrado para este filtro.</p>
                                <p class="text-xs text-gray-400 mt-0.5">Assim que o gatilho for acionado, as leituras e cliques aparecerão aqui.</p>
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