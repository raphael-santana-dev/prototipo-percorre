<div class="fixed inset-0 z-[100] bg-gray-50 dark:bg-gray-900 flex flex-col w-screen h-screen overflow-hidden">
    
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center justify-between shrink-0 shadow-sm z-20">
        <div class="flex items-center gap-3">
            <a href="{{ route('formularios.show', $formulario->id) }}" wire:navigate class="p-1.5 text-gray-500 hover:text-purpura-600 hover:bg-purpura-50 rounded transition dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700" title="Voltar para Detalhes">
                <i class="ph-bold ph-arrow-left text-lg"></i>
            </a>
            <div class="flex flex-col">
                <h1 class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-xs md:max-w-md"><i class="ph-fill ph-table text-green-600 mr-1"></i> {{ $formulario->titulo }}</h1>
                <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ $respostas->total() }} respostas filtradas</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($search || $data_inicio || $data_fim || $filtro_curso || $filtro_unidade || $filtro_turno)
                <button wire:click="limparFiltros" class="hidden md:flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 rounded hover:bg-gray-200 transition dark:bg-gray-700 dark:text-gray-300">
                    <i class="ph-bold ph-x"></i> Limpar Filtros
                </button>
            @endif
            
            <div x-data="{ openExport: false }" class="relative inline-block text-left">
                <button @click="openExport = !openExport" @click.away="openExport = false" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white transition-colors bg-green-600 rounded shadow-sm hover:bg-green-700">
                    <i class="text-base ph-bold ph-export"></i> Exportar <i class="ph-bold ph-caret-down ml-1 text-[10px]"></i>
                </button>
                <div x-show="openExport" x-cloak class="absolute right-0 w-40 mt-1 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded shadow-lg z-50 dark:bg-gray-800 dark:border-gray-700">
                    <div class="py-1">
                        <button wire:click="solicitarExportacao('xlsx')" class="flex items-center w-full px-3 py-2 text-xs font-bold text-green-700 hover:bg-green-50 text-left gap-2 dark:text-green-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-xls text-base"></i> Excel (.xlsx)
                        </button>
                        <button wire:click="solicitarExportacao('csv')" class="flex items-center w-full px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50 text-left gap-2 dark:text-blue-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-csv text-base"></i> CSV (.csv)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-2 flex flex-wrap gap-2 shrink-0 z-10 items-center justify-between md:justify-start">
        <div class="w-full md:w-56 relative">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                <i class="ph ph-magnifying-glass text-gray-400"></i>
            </div>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Nome, CPF ou E-mail..." class="pl-8 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
        </div>
        
        <div class="w-full md:w-32 flex items-center gap-1">
            <span class="text-[10px] font-bold text-gray-400 uppercase hidden md:block">De</span>
            <input wire:model.live="data_inicio" type="date" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm" title="Data Inicial">
        </div>
        
        <div class="w-full md:w-32 flex items-center gap-1">
            <span class="text-[10px] font-bold text-gray-400 uppercase hidden md:block">Até</span>
            <input wire:model.live="data_fim" type="date" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm" title="Data Final">
        </div>

        <div class="w-full md:w-40">
            <select wire:model.live="filtro_unidade" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                <option value="">Todas as Unidades</option>
                @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ Str::limit($u->nome, 20) }}</option> @endforeach
            </select>
        </div>

        <div class="w-full md:w-40">
            <select wire:model.live="filtro_curso" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                <option value="">Todos os Cursos</option>
                @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ Str::limit($c->nome, 20) }}</option> @endforeach
            </select>
        </div>

        <div class="w-full md:w-32">
            <select wire:model.live="filtro_turno" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                <option value="">Todos Turnos</option>
                @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
            </select>
        </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-900 relative custom-scrollbar">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-[11px] md:text-xs">
            <thead class="bg-gray-200 dark:bg-gray-800 sticky top-0 z-10 shadow-sm border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th wire:click="sortBy('id')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                        Resposta
                        @if($sortField === 'id') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                    </th>
                    <th wire:click="sortBy('created_at')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                        Data do Envio
                        @if($sortField === 'created_at') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                    </th>
                    
                    @foreach($campos as $campo)
                        <th wire:click="sortBy('respostas->{{ $campo->name }}')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap max-w-[200px] truncate border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800" title="{{ $campo->label }}">
                            {{ Str::limit($campo->label, 30) }}
                            @if($sortField === 'respostas->' . $campo->name) <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @forelse($respostas as $resp)
                    @php
                        $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);
                    @endphp
                    <tr class="hover:bg-blue-50 dark:hover:bg-gray-800/80 transition group">
                        <td class="px-3 py-1.5 font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100">
                            #{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300 whitespace-nowrap border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100">
                            {{ $resp->created_at->format('d/m/Y H:i') }}
                        </td>
                        
                        @foreach($campos as $campo)
                            @php
                                $val = $respostasSalvas[$campo->name] ?? null;
                                $valStr = is_array($val) ? implode(' | ', $val) : (string) $val;
                                $valStr = empty(trim($valStr)) ? '-' : $valStr;
                            @endphp
                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-400 max-w-[250px] truncate border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100" title="{{ $valStr }}">
                                {{ $valStr }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($campos) + 2 }}" class="px-4 py-8 text-center text-gray-500">
                            <i class="ph-fill ph-magnifying-glass text-3xl mb-2 text-gray-300"></i><br>
                            Nenhuma resposta atende aos filtros atuais.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($respostas->hasPages())
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-2 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            {{ $respostas->links() }}
        </div>
    @endif
</div>