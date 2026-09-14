<div class="fixed inset-0 z-[100] bg-gray-50 dark:bg-gray-900 flex flex-col w-screen h-screen overflow-hidden">
    
    <!-- Cabeçalho / Toolbar Fixa -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('formularios.show', $formulario->id) }}" wire:navigate class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700">
                <i class="ph-bold ph-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white truncate max-w-md">{{ $formulario->titulo }}</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">Visualização em Planilha ({{ $respostas->total() }} registros)</p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Barra de Busca -->
            <div class="relative w-full md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ph ph-magnifying-glass text-gray-400"></i>
                </div>
                <input wire:model.live.debounce.500ms="search" type="text" placeholder="Buscar respostas..." class="pl-10 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm">
            </div>

            <!-- Dropdown de Exportação -->
            <div x-data="{ openExport: false }" class="relative inline-block text-left">
                <button @click="openExport = !openExport" @click.away="openExport = false" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-export"></i> Exportar <i class="ph ph-caret-down"></i>
                </button>
                <div x-show="openExport" x-cloak class="absolute right-0 w-48 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg z-50 dark:bg-gray-800 dark:border-gray-700">
                    <div class="py-1">
                        <button wire:click="solicitarExportacao('xlsx')" class="flex items-center w-full px-4 py-2 text-sm text-green-700 hover:bg-green-50 font-bold text-left gap-2 dark:text-green-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-xls text-lg"></i> Formato Excel (.xlsx)
                        </button>
                        <button wire:click="solicitarExportacao('csv')" class="flex items-center w-full px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 font-bold text-left gap-2 dark:text-blue-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-csv text-lg"></i> Formato CSV (.csv)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela / Planilha -->
    <div class="flex-1 overflow-auto bg-white dark:bg-gray-900 relative">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10 shadow-sm">
                <tr>
                    <th wire:click="sortBy('id')" class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 whitespace-nowrap">
                        Protocolo
                        @if($sortField === 'id') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                    </th>
                    <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 whitespace-nowrap">
                        Data do Envio
                        @if($sortField === 'created_at') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                    </th>
                    
                    @foreach($campos as $campo)
                        <th wire:click="sortBy('respostas->{{ $campo->name }}')" class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 whitespace-nowrap max-w-xs truncate" title="{{ $campo->label }}">
                            {{ Str::limit($campo->label, 30) }}
                            @if($sortField === 'respostas->' . $campo->name) <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($respostas as $resp)
                    @php
                        $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-4 py-2 font-mono text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            #{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-300 whitespace-nowrap">
                            {{ $resp->created_at->format('d/m/Y H:i') }}
                        </td>
                        
                        @foreach($campos as $campo)
                            @php
                                $val = $respostasSalvas[$campo->name] ?? null;
                                $valStr = is_array($val) ? implode(' | ', $val) : (string) $val;
                                $valStr = empty(trim($valStr)) ? '-' : $valStr;
                            @endphp
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $valStr }}">
                                {{ $valStr }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($campos) + 2 }}" class="px-4 py-8 text-center text-gray-500">
                            Nenhuma resposta encontrada para esta busca.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação Rodapé Fixa -->
    @if($respostas->hasPages())
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-3 shrink-0">
            {{ $respostas->links() }}
        </div>
    @endif
</div>