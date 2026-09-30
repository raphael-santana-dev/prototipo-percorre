@props([
    'headers' => [], 
    'registros', 
    'ordenacaoCampo' => null, 
    'ordenacaoDirecao' => 'asc',
    'permiteGrid' => false,
    'modoExibicao' => 'lista'
])

<div class="w-full">
    {{-- Controles Superiores: Linhas por página e Toggle de View --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <span>Linhas por página</span>
            <select wire:model.live="porPagina" class="py-1 px-2.5 text-sm border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700 focus:ring-gray-900 focus:border-gray-900 dark:focus:ring-white h-8 cursor-pointer font-medium">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        @if($permiteGrid)
            <div class="inline-flex items-center p-1 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button wire:click="alternarModoExibicao('grid')" class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md transition-all {{ $modoExibicao === 'grid' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                    <i class="text-base ph ph-squares-four"></i> Grid
                </button>
                <button wire:click="alternarModoExibicao('lista')" class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md transition-all {{ $modoExibicao === 'lista' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                    <i class="text-base ph ph-list-dashes"></i> Lista
                </button>
            </div>
        @endif
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