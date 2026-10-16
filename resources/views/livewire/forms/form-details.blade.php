<div class="font-sans flex flex-col transition-all duration-300 w-full"
     x-data="{ 
        tipoVisao: @entangle('tipoVisao'),
        get isTabela() { return this.tipoVisao === 'tabela'; }
     }"
     x-init="
        $watch('isTabela', val => {
            document.body.classList.toggle('modo-expandido', val);
            document.body.classList.toggle('navbar-oculta', val);
        });
        
        // Aplica o estilo ao iniciar se já estiver na aba tabela
        if(isTabela) {
            document.body.classList.toggle('modo-expandido', true);
            document.body.classList.toggle('navbar-oculta', true);
        }
     "
     :class="isTabela ? 'h-screen border-none rounded-none bg-gray-50 dark:bg-gray-900' : 'p-6 max-w-7xl mx-auto relative'">

    <style>
        body.modo-expandido main > div > div.max-w-7xl { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
        body.navbar-oculta .js-sidebar { display: none !important; }

        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #9ca3af; }
    </style>

    <template x-if="!isTabela">
        <div class="w-full">
            <x-page-header 
                title="{{ $formulario->titulo }}" 
                icon="ph ph-list-dashes"
                badge="{{ $formulario->status ? 'Recebendo Respostas' : 'Fechado' }}">
                
                <x-slot name="actions">
                    <a href="{{ route('formularios.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2 mr-2">
                        <i class="ph-bold ph-arrow-left"></i> Voltar
                    </a>

                    <div x-data="{ openExport: false }" class="relative inline-block text-left mr-2">
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

                    <a href="{{ route('formularios.planilha', $formulario->id) }}" wire:navigate class="flex items-center gap-2 px-4 py-2 text-green-700 bg-green-50 hover:bg-green-100 transition rounded-lg font-bold shadow-sm text-sm dark:bg-green-900/30 dark:text-green-400 dark:border-green-800 border border-green-200 mr-2">
                        <i class="ph-bold ph-table text-lg"></i> Ver em Planilha
                    </a>

                    <a href="{{ route('formularios.publico', ['id' => $formulario->id, 'slug' => $formulario->slug]) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition rounded-lg font-bold shadow-sm text-sm dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800 border border-indigo-200">
                        <i class="ph-bold ph-link text-lg"></i> Link Público
                    </a>
                    
                    @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                        <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formulario->id]) }}" class="flex items-center gap-2 ml-2 px-4 py-2 text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition rounded-lg font-bold text-sm">
                            <i class="ph-bold ph-pencil-simple text-lg"></i> Editar Estrutura
                        </a>
                    @endif
                </x-slot>
            </x-page-header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-2xl">
                        <i class="ph-fill ph-users"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Total de Respostas</p>
                        <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalRespostas }}</h3>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-2xl">
                        <i class="ph-fill ph-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Data de Criação</p>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $formulario->created_at->format('d/m/Y') }}</h3>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 text-2xl">
                        <i class="ph-fill ph-share-network"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Código Único (Slug)</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-gray-300 mt-1 truncate max-w-[200px]" title="{{ $formulario->slug }}">{{ $formulario->slug }}</p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- BARRA FIXA MODO TABELA / TELA INTEIRA -->
    <template x-if="isTabela">
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center justify-between shrink-0 shadow-sm z-20">
            <div class="flex items-center gap-3">
                <button wire:click="$set('tipoVisao', 'resumo')" class="p-1.5 text-gray-500 hover:text-purpura-600 hover:bg-purpura-50 rounded transition dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700" title="Voltar para Resumo">
                    <i class="ph-bold ph-arrow-left text-lg"></i>
                </button>
                <div class="flex flex-col">
                    <h1 class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-xs md:max-w-md"><i class="ph-fill ph-table text-purpura-600 mr-1"></i> Planilha: {{ $formulario->titulo }}</h1>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ $respostas->total() }} respostas registadas</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative w-48 hidden sm:block">
                    <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                        <i class="ph ph-magnifying-glass text-gray-400"></i>
                    </div>
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="Filtrar protocolo..." class="pl-7 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                </div>

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
                
                <a href="{{ route('formularios.planilha', $formulario->id) }}" wire:navigate class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-purpura-700 bg-purpura-50 border border-purpura-200 rounded hover:bg-purpura-100 transition shadow-sm" title="Aceder à Planilha com Filtros Avançados">
                    <i class="ph-bold ph-funnel text-base"></i> Filtros
                </a>
            </div>
        </div>
    </template>

    <template x-if="!isTabela">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4 px-2">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 text-lg">Respostas Recebidas</h3>
            
            <div class="flex bg-gray-200/80 dark:bg-gray-800/80 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                <button wire:click="$set('tipoVisao', 'resumo')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors bg-white dark:bg-gray-700 text-purpura-700 dark:text-purpura-400 shadow-sm">
                    <i class="ph-bold ph-list-dashes text-lg"></i> Resumida
                </button>
                <button wire:click="$set('tipoVisao', 'tabela')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <i class="ph-bold ph-table text-lg"></i> Planilha (Perguntas)
                </button>
            </div>
        </div>
    </template>

    <!-- LISTAGEM (RESUMO OU TABELA) -->
    <div :class="isTabela ? 'flex-1 overflow-auto bg-gray-50 dark:bg-gray-900 relative custom-scrollbar' : 'w-full'">
        @if($tipoVisao === 'resumo')
            <x-table
                :headers="$this->headers"
                :registros="$respostas"
                :ordenacaoCampo="$ordenacaoCampo"
                :ordenacaoDirecao="$ordenacaoDirecao"
                :permiteGrid="false">

                @forelse($respostas as $resp)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded">#{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-300">
                            {{ $resp->created_at->format('d/m/Y \à\s H:i') }}
                        </td>
                        
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                <i class="ph-fill ph-check-circle"></i> Respondido até Etapa {{ $resp->etapa_parada }}
                            </span>
                        </td>

                        <td class="px-4 py-2.5 whitespace-nowrap text-right sticky right-0 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50 transition-colors shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] z-10">
                            @if(feature('formulario.respostas') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.respostas')))
                                <a href="{{ route('formularios.respostas.show', $resp->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold text-purpura-600 bg-purpura-50 rounded-lg hover:bg-purpura-100 transition dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50">
                                    <i class="ph-bold ph-file-text text-lg"></i> Ler Completo
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-800 mb-4 border border-gray-200 dark:border-gray-700">
                                <i class="ph ph-tray text-3xl text-gray-400"></i>
                            </div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">Caixa de entrada vazia</p>
                            <p class="text-sm">Compartilhe o link do formulário para começar a receber respostas.</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        @else
            <!-- MODO PLANILHA (TELA INTEIRA) -->
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-[11px] md:text-xs">
                <thead class="bg-gray-200 dark:bg-gray-800 sticky top-0 z-10 shadow-sm border-b-2 border-gray-300 dark:border-gray-700">
                    <tr>
                        <th wire:click="ordenarPor('id')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                            Resposta
                            @if($this->ordenacaoCampo === 'id') <i class="ph-bold ph-caret-{{ $this->ordenacaoDirecao === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                        </th>
                        <th wire:click="ordenarPor('created_at')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                            Data do Envio
                            @if($this->ordenacaoCampo === 'created_at') <i class="ph-bold ph-caret-{{ $this->ordenacaoDirecao === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                        </th>
                        
                        @foreach($campos as $campo)
                            <th wire:click="ordenarPor('respostas->{{ $campo->name }}')" class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap max-w-[200px] truncate border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800" title="{{ $campo->label }}">
                                {{ Str::limit($campo->label, 30) }}
                                @if($this->ordenacaoCampo === 'respostas->' . $campo->name) <i class="ph-bold ph-caret-{{ $this->ordenacaoDirecao === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
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
                                    $valStr = is_array($val) ? implode(' | ', array_filter($val)) : (string) $val;
                                    $valStr = empty(trim($valStr)) ? '-' : $valStr;
                                @endphp
                                <td class="px-3 py-1.5 text-gray-700 dark:text-gray-400 max-w-[250px] truncate border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100" title="{{ !str_starts_with($valStr, 'data:image/') ? $valStr : 'Assinatura Registada' }}">
                                    @if(str_starts_with($valStr, 'data:image/'))
                                        <a href="{{ $valStr }}" target="_blank" class="inline-flex items-center gap-1.5 px-2 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 border border-indigo-200 rounded text-[10px] font-bold transition-colors whitespace-nowrap dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-400">
                                            <i class="ph-bold ph-signature text-sm"></i> Ver Assinatura
                                        </a>
                                    @elseif(preg_match('/\/(uploads|documentos|links)\//', $valStr))
                                        <div class="flex flex-col gap-1">
                                            @foreach(explode(' | ', $valStr) as $caminhoArquivo)
                                                @continue(empty(trim($caminhoArquivo)))
                                                <a href="{{ asset('storage/' . trim($caminhoArquivo)) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 border border-blue-200 rounded text-[10px] font-bold transition-colors whitespace-nowrap dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400">
                                                    <i class="ph-bold ph-link text-sm"></i> Abrir Ficheiro
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        {{ $valStr }}
                                    @endif
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
        @endif
    </div>

    @if($respostas->hasPages())
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-2 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] {{ $isTabela ? 'z-20' : 'rounded-b-lg' }}">
            {{ $respostas->links() }}
        </div>
    @endif
</div>