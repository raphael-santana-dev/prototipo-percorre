@props([
    'headers' => [], 
    'registros', 
    'ordenacaoCampo' => null, 
    'ordenacaoDirecao' => 'asc',
    'permiteGrid' => false,
    'modoExibicao' => 'lista'
])

<div class="w-full">
    {{-- BARRA DE FERRAMENTAS SUPERIOR (Busca, Filtros, Linhas/Pág e Modo de Exibição) --}}
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-4">
        
        {{-- Slot para os Filtros e Campo de Busca (Renderizados Juntos) --}}
        <div class="flex flex-wrap items-center gap-2 flex-1 w-full min-w-0">
            @if(isset($search))
                <div class="shrink-0 w-full sm:w-64 mr-2">
                    {{ $search }}
                </div>
            @endif
            
            {{ $filters ?? '' }}
        </div>

        {{-- Controles da Tabela --}}
        <div class="flex items-center gap-3 shrink-0">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <span class="hidden sm:inline">Linhas por página</span>
                <select wire:model.live="porPagina" class="py-1 px-2.5 text-sm border-gray-300 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 focus:ring-gray-900 focus:border-gray-900 dark:focus:ring-white h-9 cursor-pointer font-medium">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            @if($permiteGrid)
                <div class="inline-flex items-center p-1 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-9">
                    <button wire:click="alternarModoExibicao('grid')" class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md transition-all h-full {{ $modoExibicao === 'grid' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="text-base ph ph-squares-four"></i> <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button wire:click="alternarModoExibicao('lista')" class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md transition-all h-full {{ $modoExibicao === 'lista' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="text-base ph ph-list-dashes"></i> <span class="hidden sm:inline">Lista</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if($modoExibicao === 'lista')
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full w-full text-sm text-left text-gray-700 dark:text-gray-300">
                    <thead class="text-[11px] font-bold text-gray-400 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 uppercase tracking-wider">
                        <tr>
                            @foreach($headers as $header)
                                @if($header['sortable'] ?? false)
                                    <th wire:click="ordenarPor('{{ $header['key'] }}')" class="px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 group select-none transition whitespace-nowrap {{ $header['class'] ?? '' }}">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $header['label'] }}</span>
                                            @if($ordenacaoCampo === $header['key'])
                                                <i class="ph-bold ph-caret-{{ $ordenacaoDirecao === 'desc' ? 'up' : 'down' }} text-purpura-500"></i>
                                            @else
                                                <i class="opacity-0 ph-bold ph-caret-down text-gray-300 transition group-hover:opacity-100 dark:text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                @else
                                    <th class="px-4 py-3 whitespace-nowrap {{ $header['class'] ?? '' }}">
                                        {{ $header['label'] }}
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
            
            <div class="flex justify-center p-3 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
                {{ $registros->links('components.paginacao-customizada') }}
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {{ $gridSlot ?? '' }}
        </div>
        
        <div class="flex justify-center p-4 mt-4 bg-transparent">
            {{ $registros->links('components.paginacao-customizada') }}
        </div>
    @endif
</div>