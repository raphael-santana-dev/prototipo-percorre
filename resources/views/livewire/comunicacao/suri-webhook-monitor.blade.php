<div class="w-full relative" wire:poll.10s>
    <x-page-header 
        title="Monitor do Webhook Suri" 
        subtitle="Acompanhamento em tempo real dos eventos disparados pelo chatbot"
        icon="ph ph-plugs-connected"
        badge="Integrações"
        :breadcrumbs="$breadcrumbs">
        
        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-magnifying-glass text-purpura-500"></i> Buscar ID
                    </label>
                    <input type="text" wire:model.live.debounce.400ms="filtroBusca" placeholder="Pesquisar por ID do evento..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-tag text-purpura-500"></i> Tipo de Evento
                    </label>
                    <select wire:model.live="filtroTipo" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos os Eventos</option>
                        @foreach($tiposDisponiveis as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-activity text-purpura-500"></i> Status
                    </label>
                    <select wire:model.live="filtroStatus" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos</option>
                        <option value="pending">Pendente (Na Fila)</option>
                        <option value="processed">Processado com Sucesso</option>
                        <option value="failed">Falha de Processamento</option>
                    </select>
                </div>

                @if($filtroBusca !== '' || $filtroStatus !== '' || $filtroTipo !== '')
                    <div class="md:col-span-1 flex items-end">
                        <button wire:click="limparFiltros" class="w-full h-[38px] text-sm font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-1 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700" title="Limpar Filtros">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    <div class="mb-4 flex items-center gap-2 px-1">
        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Live: Atualizando automaticamente</span>
    </div>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        
        @forelse($registros as $evento)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $evento->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                        @if($evento->event_type === 'new-contact') <i class="ph-fill ph-user-plus text-blue-500 text-lg"></i>
                        @elseif($evento->event_type === 'message-received') <i class="ph-fill ph-download-simple text-emerald-500 text-lg"></i>
                        @elseif($evento->event_type === 'message-sent') <i class="ph-fill ph-upload-simple text-purpura-500 text-lg"></i>
                        @elseif($evento->event_type === 'change-queue') <i class="ph-fill ph-arrows-left-right text-amber-500 text-lg"></i>
                        @elseif($evento->event_type === 'finish-attendance') <i class="ph-fill ph-check-circle text-indigo-500 text-lg"></i>
                        @elseif($evento->event_type === 'message-send-error') <i class="ph-fill ph-warning-circle text-red-500 text-lg"></i>
                        @else <i class="ph-fill ph-bell-ringing text-gray-400 text-lg"></i> @endif
                        
                        {{ $evento->event_type }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-xs font-mono text-gray-500 dark:text-gray-400">
                    {{ \Illuminate\Support\Str::limit($evento->event_id, 35) }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200">
                        {{ $evento->event_timestamp ? $evento->event_timestamp->format('d/m/Y') : '-' }}
                    </div>
                    <div class="text-[10px] text-gray-500 font-mono">
                        {{ $evento->event_timestamp ? $evento->event_timestamp->format('H:i:s.u') : '-' }}
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    @if($evento->status === 'pending')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider rounded border bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
                            <i class="ph-bold ph-spinner animate-spin"></i> Pendente
                        </span>
                    @elseif($evento->status === 'processed')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider rounded border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">
                            <i class="ph-bold ph-check"></i> Processado
                        </span>
                    @elseif($evento->status === 'failed')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider rounded border bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800" title="{{ $evento->error_message }}">
                            <i class="ph-bold ph-warning"></i> Falha
                        </span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if($evento->status === 'failed')
                            <button wire:click="reprocessar({{ $evento->id }})" class="p-1.5 text-amber-600 hover:text-white transition-colors rounded-lg hover:bg-amber-500 border border-transparent hover:border-amber-600 shadow-sm" title="Reprocessar Evento">
                                <i class="text-lg ph-bold ph-arrows-clockwise"></i>
                            </button>
                        @endif
                        
                        <button wire:click="showQuickView({{ $evento->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700" title="Ver JSON Payload">
                            <i class="text-lg ph-bold ph-file-code"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center">
                        <i class="ph-fill ph-plugs text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="font-bold text-gray-600 dark:text-gray-300 text-base">A aguardar eventos do webhook.</p>
                        <p class="text-xs mt-1">Os dados enviados pelo Chatbot aparecerão aqui em tempo real.</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot"></x-slot>
    </x-table>
</div>