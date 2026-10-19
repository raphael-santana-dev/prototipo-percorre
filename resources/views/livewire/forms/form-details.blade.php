<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <script>
        if (typeof window.abrirAssinaturaBase64 !== 'function') {
            window.abrirAssinaturaBase64 = function(dataUrl) {
                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const blobUrl = URL.createObjectURL(blob);
                        window.open(blobUrl, '_blank');
                    });
            };
        }
    </script>
    
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
                    <i class="text-lg ph ph-export"></i>
                    <span>Exportar {{ count($selecionadas) > 0 ? '(' . count($selecionadas) . ')' : '' }}</span>
                    <i class="ph ph-caret-down"></i>
                </button>
                <div x-show="openExport" x-cloak class="absolute right-0 w-52 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg z-50 dark:bg-gray-800 dark:border-gray-700">
                    <div class="py-1">
                        <button wire:click="solicitarExportacao('xlsx')" @click="openExport = false" class="flex items-center w-full px-4 py-2 text-sm text-green-700 hover:bg-green-50 font-bold text-left gap-2 dark:text-green-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-xls text-lg"></i>
                            <span>Excel (.xlsx) {{ count($selecionadas) > 0 ? 'Selecionados' : 'Todos' }}</span>
                        </button>
                        <button wire:click="solicitarExportacao('csv')" @click="openExport = false" class="flex items-center w-full px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 font-bold text-left gap-2 dark:text-blue-400 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-csv text-lg"></i>
                            <span>CSV (.csv) {{ count($selecionadas) > 0 ? 'Selecionados' : 'Todos' }}</span>
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

    {{-- CARDS DE RESUMO --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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

    {{-- BARRA DE FILTROS DINÂMICOS (ESTILO PERIOD-MANAGER) --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-6 space-y-4">
        
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                {{-- Campo de Busca Geral --}}
                <div class="w-full sm:w-72 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ph ph-magnifying-glass text-gray-400"></i>
                    </div>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Pesquisar protocolo ou respostas..." class="pl-9 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 py-2 shadow-sm">
                </div>
            </div>

            {{-- Controlo de Seleção em Lote --}}
            <div class="flex items-center gap-2">
                <label class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 transition">
                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                    <span>Selecionar Página</span>
                </label>

                @if(count($selecionadas) > 0)
                    <button type="button" wire:click="desmarcarTodas" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition dark:bg-gray-700 dark:text-gray-300">
                        <i class="ph-bold ph-x-circle"></i> Desmarcar ({{ count($selecionadas) }})
                    </button>
                @endif
            </div>
        </div>

        {{-- CHIPS DE FILTROS DINÂMICOS DOS CAMPOS DO FORMULÁRIO --}}
        <div wire:ignore.self x-data="{
            visible: [],
            allFields: @js($this->camposFiltraveis->pluck('name')->toArray()),
            init() {
                @foreach($this->camposFiltraveis as $f)
                    @if(isset($filtrosDinamicos[$f->name]) && $filtrosDinamicos[$f->name] !== '' && $filtrosDinamicos[$f->name] !== null)
                        if(!this.visible.includes('{{ $f->name }}')) this.visible.push('{{ $f->name }}');
                    @endif
                @endforeach
            },
            add(f) { if(!this.visible.includes(f)) this.visible.push(f); },
            remove(f) {
                $wire.set('filtrosDinamicos.' + f, '');
                this.visible = this.visible.filter(i => i !== f);
            }
        }" class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
            
            {{-- Chip Fixo: Período de Envio (Data) --}}
            <div class="flex items-center h-9 px-2 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0 gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Data Envio</span>
                <div class="flex items-center gap-1">
                    <input wire:model.live="data_inicio" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200" title="De">
                    <span class="text-gray-400 text-xs">até</span>
                    <input wire:model.live="data_fim" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200" title="Até">
                </div>
                @if($data_inicio || $data_fim)
                    <button type="button" @click="$wire.set('data_inicio', ''); $wire.set('data_fim', '');" class="text-indigo-400 hover:text-indigo-600 transition"><i class="ph-bold ph-x text-sm"></i></button>
                @endif
            </div>

            {{-- Chips Dinâmicos Gerados a Partir dos Campos do Formulário --}}
            @foreach($this->camposFiltraveis as $campo)
                @php
                    $cfgCampo = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                    $opcoesCampo = [];
                    if (!empty($campo->opcoes)) {
                        $dec = is_string($campo->opcoes) ? json_decode($campo->opcoes, true) : $campo->opcoes;
                        $opcoesCampo = is_array($dec) ? $dec : explode(',', $campo->opcoes);
                    }
                    $isDataHora = in_array($campo->subtipo, ['date', 'datetime-local']);
                    $isSelecao = in_array($campo->tipo, ['select', 'radio', 'check']);
                @endphp

                <div x-show="visible.includes('{{ $campo->name }}')" x-cloak class="flex items-center h-9 px-2 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0 gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 truncate max-w-[130px]" title="{{ $campo->label }}">{{ $campo->label }}</span>
                    
                    @if($isDataHora)
                        <div class="flex items-center gap-1">
                            <input wire:model.live="filtrosDinamicos.{{ $campo->name }}.de" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                            <span class="text-gray-400 text-xs">até</span>
                            <input wire:model.live="filtrosDinamicos.{{ $campo->name }}.ate" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                        </div>
                    @elseif($isSelecao)
                        <select wire:model.live="filtrosDinamicos.{{ $campo->name }}" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Todas as opções</option>
                            @foreach($opcoesCampo as $op)
                                <option value="{{ trim($op) }}">{{ trim($op) }}</option>
                            @endforeach
                        </select>
                    @else
                        <input wire:model.live.debounce.400ms="filtrosDinamicos.{{ $campo->name }}" type="text" placeholder="Filtrar..." class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200 w-28">
                    @endif

                    <button type="button" @click="remove('{{ $campo->name }}')" class="text-indigo-400 hover:text-indigo-600 transition"><i class="ph-bold ph-x text-sm"></i></button>
                </div>
            @endforeach

            {{-- Botão Adicionar Filtro Dinâmico --}}
            <div x-data="{ openMenu: false }" class="relative ml-1 shrink-0">
                <button type="button" @click="openMenu = !openMenu" class="text-xs font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 transition-colors h-9 px-2 focus:outline-none">
                    <i class="ph-bold ph-plus"></i> Adicionar Filtro
                </button>
                
                <div x-show="openMenu" @click.away="openMenu = false" x-cloak class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-50 py-2 max-h-60 overflow-y-auto custom-scrollbar">
                    <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700 mb-1">Campos do Formulário</div>
                    @foreach($this->camposFiltraveis as $campo)
                        <button type="button" 
                                @click="add('{{ $campo->name }}'); openMenu = false;" 
                                x-show="!visible.includes('{{ $campo->name }}')"
                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-purpura-50 dark:hover:bg-gray-700 flex items-center justify-between">
                            <span class="truncate pr-2">{{ $campo->label }}</span>
                            <span class="text-[9px] uppercase font-mono bg-gray-100 dark:bg-gray-700 text-gray-500 px-1 rounded">{{ $campo->tipo }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @if($search || $data_inicio || $data_fim || !empty(array_filter($filtrosDinamicos)))
                <button type="button" wire:click="limparFiltros" @click="visible = []" class="text-xs font-medium text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-2 transition">
                    Limpar Todos
                </button>
            @endif
        </div>
    </div>

    {{-- TABELA DE RESPOSTAS COM CHECKBOX NA 1ª COLUNA E AÇÕES NA 2ª COLUNA --}}
    <div class="custom-scrollbar overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <x-table
            :headers="$this->headers"
            :registros="$respostas"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="false">

            @forelse($respostas as $resp)
                @php
                    $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);
                @endphp
                
                <tr wire:key="resp-row-{{ $resp->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group {{ in_array((string) $resp->id, $selecionadas) ? 'bg-purpura-50/40 dark:bg-purpura-900/20' : '' }}">
                    
                    {{-- 1ª COLUNA: CHECKBOX DE SELEÇÃO --}}
                    <td class="px-4 py-2.5 whitespace-nowrap text-center w-10">
                        <input type="checkbox"
                               wire:model.live="selecionadas"
                               value="{{ $resp->id }}"
                               class="rounded border-gray-300 text-purpura-600 focus:ring-purpura-500 cursor-pointer">
                    </td>

                    {{-- 2ª COLUNA: AÇÕES (APENAS O ÍCONE DE OLHO) --}}
                    <td class="px-4 py-2.5 whitespace-nowrap text-center w-16">
                        @if(feature('formulario.respostas') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.respostas')))
                            <a href="{{ route('formularios.respostas.show', $resp->id) }}"
                               title="Ler Resposta Completa"
                               class="inline-flex items-center justify-center w-8 h-8 text-purpura-600 bg-purpura-50 rounded-lg hover:bg-purpura-100 hover:text-purpura-700 transition dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50">
                                <i class="ph-bold ph-eye text-base"></i>
                            </a>
                        @endif
                    </td>

                    {{-- 3ª COLUNA: ID --}}
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded">
                            #{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                    </td>

                    {{-- 4ª COLUNA: DATA DO ENVIO --}}
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-300">
                        {{ $resp->created_at->format('d/m/Y \à\s H:i') }}
                    </td>
                    
                    {{-- DEMAIS COLUNAS: DADOS DAS RESPOSTAS --}}
                    @foreach($campos as $campo)
                        @php
                            $val = $respostasSalvas[$campo->name] ?? null;
                            $valStr = is_array($val) ? implode(' | ', array_filter($val)) : (string) $val;
                            $valStr = empty(trim($valStr)) ? '-' : $valStr;
                        @endphp
                        <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 max-w-[250px] truncate" title="{{ !str_starts_with($valStr, 'data:image/') ? $valStr : 'Assinatura Registada' }}">
                            @if(str_starts_with($valStr, 'data:image/'))
                                <button type="button"
                                        onclick="window.abrirAssinaturaBase64(@js($valStr))"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 border border-indigo-200 rounded text-[10px] font-bold transition-colors whitespace-nowrap dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-400 cursor-pointer">
                                    <i class="ph-bold ph-signature text-sm"></i> Ver Assinatura
                                </button>
                            @elseif(preg_match('/\/(uploads|documentos|links)\//', $valStr))
                                <div class="flex flex-wrap gap-1">
                                    @foreach(explode(' | ', $valStr) as $caminhoArquivo)
                                        @continue(empty(trim($caminhoArquivo)))
                                        <a href="{{ asset('storage/' . trim($caminhoArquivo)) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 border border-blue-200 rounded text-[10px] font-bold transition-colors whitespace-nowrap dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400">
                                            <i class="ph-bold ph-link text-sm"></i> Abrir Arquivo
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
                    <td colspan="{{ count($campos) + 4 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-800 mb-4 border border-gray-200 dark:border-gray-700">
                            <i class="ph ph-tray text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">Nenhuma resposta encontrada</p>
                        <p class="text-sm">Ajuste os filtros de busca ou compartilhe o link do formulário para receber respostas.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #9ca3af; }
    </style>
</div>