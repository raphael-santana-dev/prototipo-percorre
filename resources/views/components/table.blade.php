@props([
    'headers' => [], 
    'registros', 
    'ordenacaoCampo' => null, 
    'ordenacaoDirecao' => 'asc'
])

<div>
    {{-- BARRA DE CONTROLOS SUPERIOR (Registos por página) --}}
    <div class="flex flex-wrap justify-between items-center mb-4 bg-white dark:bg-gray-800 p-3 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Mostrar</span>
            <select wire:model.live="porPagina" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm py-1.5 focus:ring-brand-purple">
                <option value="5">5</option>    
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">registos</span>
        </div>
    </div>

    {{-- A TABELA EM SI --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                
                {{-- CABEÇALHO DINÂMICO --}}
                <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        @foreach($headers as $header)
                            @if($header['sortable'] ?? false)
                                <th wire:click="ordenarPor('{{ $header['key'] }}')" class="px-6 py-4 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 group select-none transition {{ $header['class'] ?? '' }}">
                                    <div class="flex items-center gap-1">
                                        <span>{{ $header['label'] }}</span>
                                        @if($ordenacaoCampo === $header['key'])
                                            <svg class="w-4 h-4 text-brand-purple {{ $ordenacaoDirecao === 'desc' ? 'transform rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                        @endif
                                    </div>
                                </th>
                            @else
                                <th class="px-6 py-4 {{ $header['class'] ?? '' }}">
                                    {{ $header['label'] }}
                                </th>
                            @endif
                        @endforeach
                    </tr>
                </thead>

                {{-- CORPO DA TABELA (Injetado pela View específica) --}}
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    {{ $slot }}
                </tbody>

            </table>
        </div>

        {{-- PAGINAÇÃO DINÂMICA --}}
        <div class="p-6 bg-white dark:bg-gray-800 flex justify-center">
            {{ $registros->links('components.paginacao-customizada') }}
        </div>
    </div>
</div>