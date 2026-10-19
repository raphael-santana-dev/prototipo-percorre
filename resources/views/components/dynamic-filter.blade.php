@props([
    'schema' => [],
    'filtrosAtivos' => [],
    'mode' => 'sidebar', // 'sidebar' ou 'drawer'
    'aberto' => true,
])

@php
    $secoes = collect($schema)->groupBy(fn($item) => $item['section'] ?? 'Filtros Principais');
    $totalAtivos = collect($filtrosAtivos)->filter(fn($v) => !empty($v))->count();
@endphp

@if($mode === 'sidebar')
    <aside x-show="$wire.painelFiltrosAberto"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 -translate-x-4"
           x-transition:enter-end="opacity-100 translate-x-0"
           class="w-full lg:w-72 shrink-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xs flex flex-col overflow-hidden self-start sticky top-4 max-h-[calc(100vh-110px)]">
        
        {{-- Cabeçalho da Sidebar de Filtros --}}
        <div class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/50 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <i class="ph-fill ph-funnel text-purpura-600 text-base"></i>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-gray-200">Filtros</h3>
                @if($totalAtivos > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-purpura-100 text-purpura-700 dark:bg-purpura-900/50 dark:text-purpura-300">
                        {{ $totalAtivos }}
                    </span>
                @endif
            </div>

            @if($totalAtivos > 0)
                <button type="button" wire:click="limparTodosFiltrosAvancados" class="text-[11px] font-bold text-red-500 hover:underline">
                    Limpar tudo
                </button>
            @endif
        </div>

        {{-- Lista de Filtros Acordeão --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($secoes as $nomeSecao => $itensSecao)
                @if($secoes->count() > 1)
                    <div class="px-4 py-2 bg-gray-100/70 dark:bg-gray-800/60 text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">
                        {{ $nomeSecao }}
                    </div>
                @endif

                @foreach($itensSecao as $filtro)
                    @php
                        $chave = $filtro['key'];
                        $tipo = $filtro['type'] ?? 'multiselect';
                        $opcoes = $filtro['options'] ?? [];
                        $selecionados = $filtrosAtivos[$chave] ?? [];
                        if (!is_array($selecionados)) $selecionados = array_filter([$selecionados]);
                        $qtdMarcados = count(array_filter($selecionados, fn($v) => $v !== '' && $v !== null));
                    @endphp

                    <div x-data="{ openGroup: {{ ($qtdMarcados > 0 || !empty($filtro['default_open'])) ? 'true' : 'false' }}, searchOpt: '', novoTermo: '' }" class="p-3.5">
                        <button type="button" @click="openGroup = !openGroup" class="w-full flex items-center justify-between text-left group">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-purpura-600 transition flex items-center gap-2 truncate">
                                @if(!empty($filtro['icon']))
                                    <i class="ph-bold {{ $filtro['icon'] }} text-gray-400 group-hover:text-purpura-500"></i>
                                @endif
                                <span class="truncate">{{ $filtro['label'] }}</span>
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if($qtdMarcados > 0)
                                    <span class="px-1.5 py-0.2 text-[10px] font-extrabold rounded bg-purpura-50 text-purpura-700 border border-purpura-200">
                                        {{ $qtdMarcados }}
                                    </span>
                                @endif
                                <i class="ph-bold ph-caret-down text-xs text-gray-400 transition-transform duration-200" :class="openGroup ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <div x-show="openGroup" x-collapse x-cloak class="mt-3 space-y-2">
                            @if($tipo === 'multiselect')
                                @if(count($opcoes) > 5)
                                    <div class="relative mb-2">
                                        <i class="ph ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                        <input type="text" x-model="searchOpt" placeholder="Filtrar opções..." class="w-full pl-7 pr-2 py-1 text-xs rounded-md border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-purpura-500 focus:border-purpura-500">
                                    </div>
                                @endif

                                <div class="max-h-44 overflow-y-auto custom-scrollbar space-y-1 pr-1">
                                    @forelse($opcoes as $valId => $valNome)
                                        @php $isChecked = in_array((string) $valId, array_map('strval', $selecionados), true); @endphp
                                        <label x-show="!searchOpt || '{{ strtolower(addslashes($valNome)) }}'.includes(searchOpt.toLowerCase())"
                                               class="flex items-center gap-2 p-1.5 rounded-md cursor-pointer transition {{ $isChecked ? 'bg-purpura-50/70 dark:bg-purpura-900/30 text-purpura-900 dark:text-purpura-200 font-bold' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                                            <input type="checkbox"
                                                   wire:click="toggleFiltroValor('{{ $chave }}', '{{ addslashes((string) $valId) }}')"
                                                   @if($isChecked) checked @endif
                                                   class="w-3.5 h-3.5 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                            <span class="text-xs truncate" title="{{ $valNome }}">{{ $valNome }}</span>
                                        </label>
                                    @empty
                                        <span class="text-[11px] text-gray-400 italic block py-1">Sem opções disponíveis</span>
                                    @endforelse
                                </div>

                            @elseif($tipo === 'date_range')
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">De</label>
                                        <input type="date" wire:model.live="filtrosAvancados.{{ $chave }}.inicio" class="w-full text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Até</label>
                                        <input type="date" wire:model.live="filtrosAvancados.{{ $chave }}.fim" class="w-full text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5">
                                    </div>
                                </div>

                            @elseif($tipo === 'text_multi')
                                <div class="flex gap-1.5">
                                    <input type="text"
                                           x-model="novoTermo"
                                           @keydown.enter.prevent="if(novoTermo.trim() !== '') { $wire.adicionarTermoFiltro('{{ $chave }}', novoTermo); novoTermo = ''; }"
                                           placeholder="Digite e pressione Enter..."
                                           class="w-full text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2.5">
                                    <button type="button"
                                            @click="if(novoTermo.trim() !== '') { $wire.adicionarTermoFiltro('{{ $chave }}', novoTermo); novoTermo = ''; }"
                                            class="px-2.5 py-1.5 bg-purpura-600 hover:bg-purpura-700 text-white rounded-md text-xs font-bold shrink-0">
                                        +
                                    </button>
                                </div>
                                @if(!empty($selecionados))
                                    <div class="flex flex-wrap gap-1 pt-1">
                                        @foreach($selecionados as $termo)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-[11px] font-bold text-gray-700 dark:text-gray-300">
                                                {{ $termo }}
                                                <button type="button" wire:click="removerFiltroItem('{{ $chave }}', '{{ addslashes($termo) }}')" class="text-gray-400 hover:text-red-500">
                                                    <i class="ph-bold ph-x text-[10px]"></i>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </aside>

@elseif($mode === 'drawer')
    {{-- MODO NAVIGATION DRAWER (PARA O KANBAN CRM) --}}
    <div class="relative z-[150]" role="dialog" aria-modal="true" x-show="$wire.drawerFiltrosAberto" x-cloak>
        <div x-show="$wire.drawerFiltrosAberto" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="$wire.set('drawerFiltrosAberto', false)"></div>

        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 overflow-hidden">
                <div class="fixed inset-y-0 left-0 flex max-w-full pr-10 pointer-events-auto">
                    <div x-show="$wire.drawerFiltrosAberto"
                         x-transition:enter="transform transition ease-in-out duration-300"
                         x-transition:enter-start="-translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-300"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="-translate-x-full"
                         class="w-screen max-w-sm bg-white dark:bg-gray-900 shadow-2xl flex flex-col h-full border-r border-gray-200 dark:border-gray-800">
                        
                        <div class="px-5 py-4 bg-gray-900 text-white flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-2">
                                <i class="ph-fill ph-funnel text-purpura-400 text-lg"></i>
                                <h2 class="text-sm font-bold">Filtros do Fluxo (CRM)</h2>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($totalAtivos > 0)
                                    <button type="button" wire:click="limparTodosFiltrosAvancados" class="text-xs font-bold text-red-400 hover:underline">
                                        Limpar tudo
                                    </button>
                                @endif
                                <button type="button" @click="$wire.set('drawerFiltrosAberto', false)" class="text-gray-400 hover:text-white p-1">
                                    <i class="ph-bold ph-x text-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($secoes as $nomeSecao => $itensSecao)
                                @if($secoes->count() > 1)
                                    <div class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                        {{ $nomeSecao }}
                                    </div>
                                @endif

                                @foreach($itensSecao as $filtro)
                                    @php
                                        $chave = $filtro['key'];
                                        $tipo = $filtro['type'] ?? 'multiselect';
                                        $opcoes = $filtro['options'] ?? [];
                                        $selecionados = $filtrosAtivos[$chave] ?? [];
                                        if (!is_array($selecionados)) $selecionados = array_filter([$selecionados]);
                                        $qtdMarcados = count(array_filter($selecionados, fn($v) => $v !== '' && $v !== null));
                                    @endphp

                                    <div x-data="{ openGroup: {{ ($qtdMarcados > 0 || !empty($filtro['default_open'])) ? 'true' : 'false' }}, searchOpt: '', novoTermo: '' }" class="p-4">
                                        <button type="button" @click="openGroup = !openGroup" class="w-full flex items-center justify-between text-left">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                                @if(!empty($filtro['icon'])) <i class="ph-bold {{ $filtro['icon'] }} text-purpura-500"></i> @endif
                                                {{ $filtro['label'] }}
                                            </span>
                                            <div class="flex items-center gap-1.5">
                                                @if($qtdMarcados > 0)
                                                    <span class="px-1.5 py-0.2 text-[10px] font-extrabold rounded bg-purpura-50 text-purpura-700 border border-purpura-200">{{ $qtdMarcados }}</span>
                                                @endif
                                                <i class="ph-bold ph-caret-down text-xs text-gray-400 transition-transform" :class="openGroup ? 'rotate-180' : ''"></i>
                                            </div>
                                        </button>

                                        <div x-show="openGroup" x-collapse x-cloak class="mt-3 space-y-2">
                                            @if($tipo === 'multiselect')
                                                @if(count($opcoes) > 5)
                                                    <input type="text" x-model="searchOpt" placeholder="Buscar opção..." class="w-full px-2.5 py-1 text-xs rounded-md border-gray-200 dark:border-gray-700 dark:bg-gray-800 mb-2">
                                                @endif
                                                <div class="max-h-48 overflow-y-auto custom-scrollbar space-y-1">
                                                    @foreach($opcoes as $valId => $valNome)
                                                        @php $isChecked = in_array((string) $valId, array_map('strval', $selecionados), true); @endphp
                                                        <label x-show="!searchOpt || '{{ strtolower(addslashes($valNome)) }}'.includes(searchOpt.toLowerCase())"
                                                               class="flex items-center gap-2 p-1.5 rounded-md cursor-pointer {{ $isChecked ? 'bg-purpura-50 dark:bg-purpura-900/30 font-bold text-purpura-900 dark:text-purpura-200' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                                                            <input type="checkbox"
                                                                   wire:click="toggleFiltroValor('{{ $chave }}', '{{ addslashes((string) $valId) }}')"
                                                                   @if($isChecked) checked @endif
                                                                   class="w-4 h-4 rounded text-purpura-600 border-gray-300">
                                                            <span class="text-xs truncate">{{ $valNome }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif($tipo === 'date_range')
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">De</label>
                                                        <input type="date" wire:model.live="filtrosAvancados.{{ $chave }}.inicio" class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Até</label>
                                                        <input type="date" wire:model.live="filtrosAvancados.{{ $chave }}.fim" class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800">
                                                    </div>
                                                </div>
                                            @elseif($tipo === 'text_multi')
                                                <div class="flex gap-1.5">
                                                    <input type="text"
                                                           x-model="novoTermo"
                                                           @keydown.enter.prevent="if(novoTermo.trim() !== '') { $wire.adicionarTermoFiltro('{{ $chave }}', novoTermo); novoTermo = ''; }"
                                                           placeholder="Digite e Enter..."
                                                           class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800 py-1.5 px-2.5">
                                                    <button type="button"
                                                            @click="if(novoTermo.trim() !== '') { $wire.adicionarTermoFiltro('{{ $chave }}', novoTermo); novoTermo = ''; }"
                                                            class="px-2.5 py-1.5 bg-purpura-600 text-white rounded-md text-xs font-bold">+</button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>

                        <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 shrink-0">
                            <button type="button" @click="$wire.set('drawerFiltrosAberto', false)" class="w-full py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs rounded-lg shadow-xs">
                                Ver Resultados
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif