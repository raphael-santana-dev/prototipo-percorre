<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Detalhes da Automação" 
        icon="ph ph-chart-line-up"
        badge="{{ $automacao->status ? 'Ativa' : 'Inativa' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
            <x-slot name="actions">
                <a href="{{ route('automacoes.edit', $automacao->id) }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 font-bold text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="ph ph-pencil-simple text-lg"></i> Editar Regra
                </a>
            </x-slot>
        @endif

        <!-- Filtros do Histórico -->
        <x-slot name="filters">
            <div class="flex gap-2 items-center flex-wrap">
                <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar e-mail do destinatário..." class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-white">

                <div class="flex items-center bg-white border border-gray-300 rounded-md shadow-sm px-2 overflow-hidden focus-within:ring-1 focus-within:ring-purpura-500 focus-within:border-purpura-500 dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[10px] text-gray-500 font-bold uppercase mr-1">De</span>
                    <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-sm p-1.5 focus:ring-0 text-gray-700 bg-transparent cursor-pointer dark:text-gray-300">
                    <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-1"></div>
                    <span class="text-[10px] text-gray-500 font-bold uppercase mr-1">Até</span>
                    <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-sm p-1.5 focus:ring-0 text-gray-700 bg-transparent cursor-pointer dark:text-gray-300">
                </div>

                @if($filtro_busca !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    <!-- Tabela de Histórico -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center dark:bg-gray-900/50 dark:border-gray-700">
            <h3 class="font-bold text-gray-800 flex items-center gap-2 dark:text-white">
                <i class="ph-fill ph-clock-counter-clockwise text-gray-400"></i> Últimos Disparos (Log)
            </h3>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 whitespace-nowrap">Data / Hora</th>
                        <th class="px-6 py-3 whitespace-nowrap">Destinatário(s)</th>
                        <th class="px-6 py-3 whitespace-nowrap text-center">Status</th>
                        <th class="px-6 py-3 whitespace-nowrap text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($historico as $log)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-700">
                            <td class="px-6 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-gray-200">
                                {{ $log->created_at->format('d/m/Y') }} <span class="text-gray-400 ml-1">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @php 
                                    $arrayDestinatarios = is_array($log->destinatarios) ? $log->destinatarios : [$log->destinatarios];
                                    $primeiroEmail = $arrayDestinatarios[0] ?? 'Desconhecido';
                                    $qtdTotal = count($arrayDestinatarios);
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($primeiroEmail, 35) }}</span>
                                    @if($qtdTotal > 1)
                                        <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" title="Existem mais destinatários neste disparo">
                                            +{{ $qtdTotal - 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase tracking-wider border border-green-200 bg-green-50 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
                                    <i class="ph-bold ph-check mr-1"></i> Entregue
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Detalhes do Disparo">
                                    <i class="text-xl ph ph-info"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                    <i class="ph ph-envelope-simple-open text-2xl text-gray-400"></i>
                                </div>
                                <p class="font-bold text-gray-600 dark:text-gray-400">Nenhum histórico encontrado.</p>
                                <p class="text-xs mt-1">Quando essa automação for acionada, os registros aparecerão aqui.</p>
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