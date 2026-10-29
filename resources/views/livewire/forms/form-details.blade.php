<div class="font-sans relative transition-all duration-300"
     x-data="{ modoExpandido: false, navbarOculta: false }"
     x-init="
        $watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val));
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
     "
     :class="modoExpandido ? 'p-4 max-w-full' : 'p-6 max-w-7xl mx-auto'">

    <style>
        body.modo-expandido main > div > div { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; }
        body.navbar-oculta .js-topnav, body.navbar-oculta header, body.navbar-oculta .js-sidebar { display: none !important; }
    </style>

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
        subtitle="{{ $formulario->descricao ?: 'Gestão, triagem e exportação de respostas submetidas' }}"
        icon="ph ph-files"
        badge="{{ $formulario->status ? 'Recebendo Respostas' : 'Fechado' }}">
        
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('formularios.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800">
                    <i class="ph-bold ph-arrow-left"></i> Voltar
                </a>

                {{-- SELETOR DE MODO DE VISUALIZAÇÃO: LISTA PADRÃO vs MODO PLANILHA --}}
                <div class="inline-flex items-center p-1 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <button type="button"
                            wire:click="alternarVisualizacao('padrao')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md flex items-center gap-1.5 transition-all {{ $modoVisualizacao === 'padrao' ? 'bg-white dark:bg-gray-900 text-purpura-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                        <i class="ph-bold ph-list-bullets text-sm"></i> Lista Padrão
                    </button>
                    <button type="button"
                            wire:click="alternarVisualizacao('planilha')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md flex items-center gap-1.5 transition-all {{ $modoVisualizacao === 'planilha' ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                        <i class="ph-bold ph-table text-sm"></i> Modo Planilha
                    </button>
                </div>

                @if($modoVisualizacao === 'planilha')
                    <button type="button"
                            @click="modoExpandido = !modoExpandido; if (!modoExpandido) navbarOculta = false;"
                            class="btn btn--secondary btn--medium bg-white dark:bg-gray-800"
                            title="Expandir Planilha na Tela">
                        <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                        <span x-text="modoExpandido ? 'Compactar' : 'Expandir'"></span>
                    </button>
                @endif

                {{-- DROPDOWN EXPORTAR --}}
                <div x-data="{ openExport: false }" class="relative inline-block text-left">
                    <button @click="openExport = !openExport" @click.away="openExport = false" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800">
                        <i class="text-base ph-bold ph-export text-purpura-600"></i>
                        <span>Exportar {{ count($selecionadas) > 0 ? '(' . count($selecionadas) . ')' : '' }}</span>
                        <i class="ph-bold ph-caret-down text-xs"></i>
                    </button>
                    <div x-show="openExport" x-cloak class="absolute right-0 w-56 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-xl shadow-xl z-50 dark:bg-gray-800 dark:border-gray-700">
                        <div class="py-1">
                            <button wire:click="solicitarExportacao('xlsx')" @click="openExport = false" class="flex items-center w-full px-4 py-2.5 text-xs text-emerald-700 hover:bg-emerald-50 font-bold text-left gap-2 dark:text-emerald-400 dark:hover:bg-gray-700">
                                <i class="ph-fill ph-file-xls text-lg"></i>
                                <span>Planilha Excel (.xlsx) — {{ count($selecionadas) > 0 ? count($selecionadas) . ' Selecionadas' : 'Todas Filtradas' }}</span>
                            </button>
                            <button wire:click="solicitarExportacao('csv')" @click="openExport = false" class="flex items-center w-full px-4 py-2.5 text-xs text-blue-700 hover:bg-blue-50 font-bold text-left gap-2 dark:text-blue-400 dark:hover:bg-gray-700">
                                <i class="ph-fill ph-file-csv text-lg"></i>
                                <span>Arquivo CSV (.csv) — {{ count($selecionadas) > 0 ? count($selecionadas) . ' Selecionadas' : 'Todas Filtradas' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <a href="{{ route('formularios.publico', ['id' => $formulario->id, 'slug' => $formulario->slug]) }}" target="_blank" class="btn btn--secondary btn--medium bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100">
                    <i class="ph-bold ph-arrow-square-out"></i> Link Público
                </a>
                
                @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                    <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formulario->id]) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                        <i class="ph-bold ph-pencil-simple"></i> Editar Campos
                    </a>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    {{-- CARDS DE RESUMO (EXIBIDOS NA VISÃO PADRÃO) --}}
    @if($modoVisualizacao === 'padrao')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 flex items-center justify-center text-purpura-600 dark:text-purpura-400 text-xl border border-purpura-100">
                    <i class="ph-fill ph-users"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total de Respostas</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $totalRespostas }}</h3>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl border border-blue-100">
                    <i class="ph-fill ph-list-checks"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Perguntas Ativas</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ count($campos) }}</h3>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 text-xl border border-amber-100">
                    <i class="ph-fill ph-link"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Identificador (Slug)</p>
                    <p class="text-sm font-mono font-bold text-gray-900 dark:text-gray-200 mt-0.5 truncate" title="{{ $formulario->slug }}">/f/{{ $formulario->slug }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- BARRA DE BUSCA, FILTROS DINÂMICOS E SELEÇÃO EM LOTE --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs mb-5 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="w-full sm:w-80 relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por protocolo, nome, CPF ou conteúdo..." class="pl-9 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-purpura-500 focus:border-purpura-500 h-9 shadow-2xs">
            </div>

            <div class="flex items-center gap-2">
                <label class="inline-flex items-center gap-2 px-3 h-9 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 transition">
                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                    <span>Selecionar Página</span>
                </label>

                @if(count($selecionadas) > 0)
                    <button type="button" wire:click="desmarcarTodas" class="inline-flex items-center gap-1 px-3 h-9 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <i class="ph-bold ph-x-circle"></i> Limpar Seleção ({{ count($selecionadas) }})
                    </button>
                @endif
            </div>
        </div>

        {{-- CHIPS DE FILTROS DINÂMICOS --}}
        <div wire:ignore.self x-data="{
            visible: [],
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
            
            <div class="flex items-center h-9 px-2.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-2xs shrink-0 gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Data Envio</span>
                <div class="flex items-center gap-1">
                    <input wire:model.live="data_inicio" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                    <span class="text-gray-400 text-xs">até</span>
                    <input wire:model.live="data_fim" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                </div>
                @if($data_inicio || $data_fim)
                    <button type="button" @click="$wire.set('data_inicio', ''); $wire.set('data_fim', '');" class="text-indigo-400 hover:text-indigo-600"><i class="ph-bold ph-x text-xs"></i></button>
                @endif
            </div>

            @foreach($this->camposFiltraveis as $campo)
                @php
                    $opcoesCampo = [];
                    if (!empty($campo->opcoes)) {
                        $dec = is_string($campo->opcoes) ? json_decode($campo->opcoes, true) : $campo->opcoes;
                        $opcoesCampo = is_array($dec) ? $dec : explode(',', $campo->opcoes);
                    }
                    $isDataHora = in_array($campo->subtipo, ['date', 'datetime-local']);
                    $isSelecao  = in_array($campo->tipo, ['select', 'radio', 'check']);
                @endphp

                <div x-show="visible.includes('{{ $campo->name }}')" x-cloak class="flex items-center h-9 px-2.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-2xs shrink-0 gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 truncate max-w-[130px]" title="{{ strip_tags($campo->label) }}">{{ strip_tags($campo->label) }}</span>
                    
                    @if($isDataHora)
                        <div class="flex items-center gap-1">
                            <input wire:model.live="filtrosDinamicos.{{ $campo->name }}.de" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                            <span class="text-gray-400 text-xs">até</span>
                            <input wire:model.live="filtrosDinamicos.{{ $campo->name }}.ate" type="date" class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200">
                        </div>
                    @elseif($isSelecao)
                        <select wire:model.live="filtrosDinamicos.{{ $campo->name }}" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Todas</option>
                            @foreach($opcoesCampo as $op)
                                <option value="{{ trim($op) }}">{{ trim($op) }}</option>
                            @endforeach
                        </select>
                    @else
                        <input wire:model.live.debounce.400ms="filtrosDinamicos.{{ $campo->name }}" type="text" placeholder="Filtrar..." class="border-none bg-transparent text-xs p-0 focus:ring-0 text-gray-700 dark:text-gray-200 w-28">
                    @endif

                    <button type="button" @click="remove('{{ $campo->name }}')" class="text-indigo-400 hover:text-indigo-600"><i class="ph-bold ph-x text-xs"></i></button>
                </div>
            @endforeach

            <div x-data="{ openMenu: false }" class="relative ml-1 shrink-0">
                <button type="button" @click="openMenu = !openMenu" class="text-xs font-bold text-purpura-600 hover:text-purpura-800 dark:text-purpura-400 flex items-center gap-1 h-9 px-2">
                    <i class="ph-bold ph-plus"></i> Filtrar por Pergunta
                </button>
                <div x-show="openMenu" @click.away="openMenu = false" x-cloak class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-50 py-2 max-h-60 overflow-y-auto custom-scrollbar">
                    @foreach($this->camposFiltraveis as $campo)
                        <button type="button" 
                                @click="add('{{ $campo->name }}'); openMenu = false;" 
                                x-show="!visible.includes('{{ $campo->name }}')"
                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-purpura-50 dark:hover:bg-gray-700 flex items-center justify-between">
                            <span class="truncate pr-2">{{ strip_tags($campo->label) }}</span>
                            <span class="text-[9px] uppercase font-mono bg-gray-100 dark:bg-gray-700 text-gray-500 px-1 rounded">{{ $campo->tipo }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @if($search || $data_inicio || $data_fim || !empty(array_filter($filtrosDinamicos)))
                <button type="button" wire:click="limparFiltros" @click="visible = []" class="text-xs font-bold text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-auto transition">
                    <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                </button>
            @endif
        </div>

        {{-- BARRA ESCURA QUANDO HÁ ITENS SELECIONADOS --}}
        @if(count($selecionadas) > 0)
            <div class="bg-gray-900 text-white px-4 py-2.5 rounded-lg flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs font-bold">{{ count($selecionadas) }} resposta(s) selecionada(s)</span>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="solicitarExportacao('xlsx')" class="px-3 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition flex items-center gap-1.5">
                        <i class="ph-bold ph-file-xls"></i> Exportar Selecionadas (.XLSX)
                    </button>
                    <button type="button" wire:click="solicitarExportacao('csv')" class="px-3 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-md transition flex items-center gap-1.5">
                        <i class="ph-bold ph-file-csv"></i> Exportar Selecionadas (.CSV)
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- TABELA DE RESPOSTAS (ALTERNA ENTRE LISTA PADRÃO E MODO PLANILHA) --}}
    <div class="custom-scrollbar overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs">
        <x-table
            :headers="$this->headers"
            :registros="$respostas"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="false">

            @forelse($respostas as $resp)
                @php
                    $respostasSalvas = is_string($resp->respostas) ? (json_decode($resp->respostas, true) ?? []) : ($resp->respostas ?? []);
                    $nomeIdentificado = $this->extrairNomeDeRespostas($campos, $respostasSalvas);
                    $cpfIdentificado  = $this->extrairCpfDeRespostas($campos, $respostasSalvas);
                    $emailColetado    = $respostasSalvas['_email_coletado'] ?? null;

                    // Coleta até 3 campos preenchidos para o resumo na Lista Padrão
                    $resumoPares = [];
                    foreach ($campos as $cItem) {
                        if (count($resumoPares) >= 3) break;
                        $vItem = $respostasSalvas[$cItem->name] ?? null;
                        if ($vItem === null || $vItem === '' || $vItem === []) continue;
                        if (is_string($vItem) && str_starts_with($vItem, 'data:image/')) {
                            $resumoPares[] = strip_tags($cItem->label) . ': [Assinatura Digital]';
                            continue;
                        }
                        $strVal = is_array($vItem) ? implode(', ', array_filter($vItem)) : (string) $vItem;
                        if (trim($strVal) !== '' && trim($strVal) !== trim((string) $nomeIdentificado)) {
                            $resumoPares[] = Str::limit(strip_tags($cItem->label), 22) . ': ' . Str::limit($strVal, 35);
                        }
                    }
                @endphp

                <tr wire:key="resp-row-{{ $resp->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ in_array((string) $resp->id, $selecionadas) ? 'bg-purpura-50/40 dark:bg-purpura-900/20' : '' }}">
                    
                    {{-- 1. CHECKBOX --}}
                    <td class="px-4 py-3 whitespace-nowrap text-center w-10">
                        <input type="checkbox"
                               wire:model.live="selecionadas"
                               value="{{ $resp->id }}"
                               class="rounded border-gray-300 text-purpura-600 focus:ring-purpura-500 cursor-pointer">
                    </td>

                    {{-- 2. PROTOCOLO --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="{{ route('formularios.respostas.show', $resp->id) }}" class="px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 hover:bg-purpura-50 text-gray-700 hover:text-purpura-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded transition">
                            #{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>

                    @if($modoVisualizacao === 'padrao')
                        {{-- 3. PARTICIPANTE / IDENTIFICAÇÃO --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-purpura-50 border border-purpura-200 text-purpura-700 font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($nomeIdentificado ?: 'R', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('formularios.respostas.show', $resp->id) }}" class="text-sm font-bold text-gray-900 hover:text-purpura-600 dark:text-white truncate block max-w-[240px]">
                                        {{ $nomeIdentificado ?: 'Resposta #' . str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate block max-w-[240px]">
                                        {{ $cpfIdentificado ? "CPF: {$cpfIdentificado}" : ($emailColetado ?: 'Preenchimento concluído') }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- 4. RESUMO DO PREENCHIMENTO --}}
                        <td class="px-4 py-3">
                            @if(!empty($resumoPares))
                                <div class="flex flex-wrap gap-1.5 max-w-xl">
                                    @foreach($resumoPares as $parTexto)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 truncate max-w-[260px]">
                                            {{ $parTexto }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Ver detalhes completos da resposta</span>
                            @endif
                        </td>

                        {{-- 5. DATA DO ENVIO --}}
                        <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $resp->created_at->format('d/m/Y \à\s H:i') }}
                        </td>
                    @else
                        {{-- MODO PLANILHA: DATA + TODAS AS COLUNAS DINÂMICAS --}}
                        <td class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $resp->created_at->format('d/m/Y H:i') }}
                        </td>

                        @foreach($campos as $campo)
                            @php
                                $val = $respostasSalvas[$campo->name] ?? null;
                                $valStr = is_array($val) ? implode(' | ', array_filter($val)) : (string) $val;
                                $valStr = empty(trim($valStr)) ? '-' : $valStr;
                            @endphp
                            <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300 max-w-[240px] truncate" title="{{ !str_starts_with($valStr, 'data:image/') ? $valStr : 'Assinatura Registrada' }}">
                                @if(str_starts_with($valStr, 'data:image/'))
                                    <button type="button"
                                            onclick="window.abrirAssinaturaBase64(@js($valStr))"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded text-[10px] font-bold">
                                        <i class="ph-bold ph-signature"></i> Assinatura
                                    </button>
                                @elseif(preg_match('/\/(uploads|documentos|links)\//', $valStr))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(explode(' | ', $valStr) as $caminhoArquivo)
                                            @continue(empty(trim($caminhoArquivo)))
                                            <a href="{{ asset('storage/' . trim($caminhoArquivo)) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded text-[10px] font-bold">
                                                <i class="ph-bold ph-paperclip"></i> Anexo
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $valStr }}
                                @endif
                            </td>
                        @endforeach
                    @endif

                    {{-- COLUNA DE AÇÕES (FICHA PDF + DETALHES COMPLETOS) --}}
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button type="button"
                                    wire:click="baixarFichaRespostaPdf({{ $resp->id }})"
                                    wire:loading.attr="disabled"
                                    title="Baixar Ficha de Respostas (PDF)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition cursor-pointer">
                                <i class="ph-bold ph-file-pdf text-base"></i>
                            </button>

                            <a href="{{ route('formularios.respostas.show', $resp->id) }}"
                               title="Visualizar Detalhes da Resposta"
                               class="inline-flex items-center justify-center w-8 h-8 text-purpura-700 bg-purpura-50 border border-purpura-200 rounded-lg hover:bg-purpura-100 transition">
                                <i class="ph-bold ph-eye text-base"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $modoVisualizacao === 'padrao' ? 6 : (count($campos) + 4) }}" class="px-6 py-12 text-center text-gray-500">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-50 dark:bg-gray-800 mb-3 border border-gray-200 dark:border-gray-700">
                            <i class="ph ph-tray text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-base font-bold text-gray-900 dark:text-white mb-1">Nenhuma resposta encontrada</p>
                        <p class="text-xs">Ajuste os filtros de busca ou compartilhe o link público do formulário.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>