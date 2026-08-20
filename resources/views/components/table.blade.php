@props([
    'headers' => [], 
    'registros', 
    'ordenacaoCampo' => null, 
    'ordenacaoDirecao' => 'asc',
    'permiteGrid' => false,
    'modoExibicao' => 'lista'
])

<div>
    @if (count($registros) > 10 || $permiteGrid)
        <!-- REMOVIDO o bg-white, shadow e bordas. Deixamos apenas flexbox e margem inferior -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            
            <div>
                @if (count($registros) > 10)
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Mostrar</span>
                    <select wire:model.live="porPagina" class="py-1.5 text-sm border-gray-200 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-purpura-500 shadow-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400">registros</span>
                </div>
                @endif
            </div>

            @if($permiteGrid)
                <!-- A "pílula" cinza que engloba os botões -->
                <div class="inline-flex items-center p-1 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    
                    <button wire:click="alternarModoExibicao('grid')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $modoExibicao === 'grid' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="text-base ph ph-squares-four"></i> Grid
                    </button>
                    
                    <button wire:click="alternarModoExibicao('lista')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $modoExibicao === 'lista' ? 'bg-white text-gray-900 shadow-sm border border-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="text-base ph ph-list-dashes"></i> Lista
                    </button>
                    
                </div>
            @endif
        </div>
    @endif

    @if($modoExibicao === 'lista')
        <div class="overflow-hidden transition-colors duration-300 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
            
            {{-- ADICIONADA A CLASSE custom-scrollbar --}}
            <div class="overflow-x-auto custom-scrollbar">
                
                {{-- A MÁGICA DO MOBILE ESTÁ AQUI: min-w-full --}}
                <table class="min-w-full w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50/50 dark:text-gray-400 dark:bg-gray-900/50 dark:border-gray-700">
                        <tr>
                            @foreach($headers as $header)
                                @if($header['sortable'] ?? false)
                                    {{-- ADICIONADO whitespace-nowrap --}}
                                    <th wire:click="ordenarPor('{{ $header['key'] }}')" class="px-4 py-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 group select-none transition whitespace-nowrap {{ $header['class'] ?? '' }}">
                                        <div class="flex items-center gap-1">
                                            <span>{{ $header['label'] }}</span>
                                            @if($ordenacaoCampo === $header['key'])
                                                <i class="ph ph-caret-{{ $ordenacaoDirecao === 'desc' ? 'up' : 'down' }} text-purpura-500"></i>
                                            @else
                                                <i class="opacity-0 ph ph-caret-down text-gray-300 transition group-hover:opacity-100 dark:text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                @else
                                    {{-- ADICIONADO whitespace-nowrap --}}
                                    <th class="px-4 py-3 whitespace-nowrap {{ $header['class'] ?? '' }}">
                                        {{ $header['label'] }}
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
            
            <div class="flex justify-center p-4 bg-white border-t border-gray-100 dark:border-gray-700 dark:bg-gray-800">
                {{ $registros->links('components.paginacao-customizada') }}
            </div>
        </div>
    @else
        {{-- ÁREA DO GRID --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {{ $gridSlot ?? '' }}
        </div>
        
        <div class="flex justify-center p-4 mt-4 bg-transparent">
            {{ $registros->links('components.paginacao-customizada') }}
        </div>
    @endif

    {{-- ESTILOS DA SCROLLBAR (Embutidos com segurança no Blade pai) --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
        .dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #6b7280; }
    </style>
</div>