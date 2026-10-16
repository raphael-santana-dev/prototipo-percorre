<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Gerenciar Permissões: {{ strtoupper($roleName) }}" 
        icon="ph ph-shield-check"
        badge="Acessos do Grupo"
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="actions">
            <a href="{{ route('roles.index') }}" class="btn btn--secondary btn--medium bg-white">
                <i class="text-lg ph ph-arrow-left"></i> Voltar para Grupos
            </a>
        </x-slot>
    </x-page-header>

    <div class="bg-blue-50 border border-blue-100 dark:bg-blue-900/30 dark:border-blue-800 p-4 rounded-xl text-sm text-blue-800 dark:text-blue-300 font-medium mb-6 flex items-start gap-3 shadow-sm">
        <i class="ph-fill ph-info text-xl text-blue-500 mt-0.5"></i>
        <p class="leading-snug">As alterações feitas nos interruptores (Toggles) abaixo são <strong>guardadas automaticamente em tempo real</strong>.</p>
    </div>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        {{-- BUSCA E FILTROS --}}
        <x-slot name="search">
            <div class="relative w-full">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.500ms="filtro_keyword" placeholder="Buscar permissão por nome ou descrição..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition-colors dark:bg-gray-800 dark:border-gray-700">
            </div>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col gap-4">
                <div wire:ignore.self x-data="{
                    visible: [],
                    allFilters: ['modulo'],
                    init() {
                        if ($wire.filtro_module && !this.visible.includes('modulo')) this.visible.push('modulo');
                    },
                    add(f) { if(!this.visible.includes(f)) this.visible.push(f); },
                    remove(f) {
                        if(f === 'modulo') $wire.set('filtro_module', '');
                        this.visible = this.visible.filter(i => i !== f);
                    },
                    get canAddMore() { return this.visible.length < this.allFilters.length; }
                }" class="flex flex-wrap items-center gap-2">
                    
                    <div x-show="visible.includes('modulo')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Módulo</span>
                        <select wire:model.live="filtro_module" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Todos</option>
                            @foreach($modulosDisponiveis as $modulo) <option value="{{ $modulo }}">{{ ucfirst($modulo) }}</option> @endforeach
                        </select>
                        <button @click="remove('modulo')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                    </div>

                    <div x-show="canAddMore" x-data="{ open: false }" class="relative ml-1 shrink-0" x-cloak>
                        <button @click="open = !open" class="text-sm font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors h-9 px-2 focus:outline-none">
                            + Adicionar Filtro
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute left-0 top-full mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 py-2">
                            <button x-show="!visible.includes('modulo')" @click="add('modulo'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-squares-four text-gray-400 text-lg"></i> Filtrar Módulo</button>
                        </div>
                    </div>

                    <button x-show="$wire.filtro_module || $wire.filtro_keyword" x-cloak wire:click="limparFiltros" @click="visible = []" class="text-sm font-medium text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-1 transition">
                        Limpar Todos
                    </button>
                </div>
            </div>
        </x-slot>

        @php
            $grouped = [];
            foreach($permissionsByModule as $module => $permissions) {
                foreach($permissions as $perm) {
                    $parts = explode('.', $perm->name);
                    $action = array_pop($parts); 
                    
                    $prefix = strtolower($module) . '.';
                    $subStr = str_starts_with($perm->name, $prefix) ? substr($perm->name, strlen($prefix)) : $perm->name;
                    $subParts = explode('.', $subStr);
                    array_pop($subParts); 
                    
                    $subName = count($subParts) > 0 ? implode(' ', $subParts) : 'Geral';
                    
                    $perm->action_label = $action;
                    $grouped[$module][$subName][] = $perm;
                }
            }
        @endphp

        @forelse($grouped as $module => $submodules)
            @foreach($submodules as $subName => $perms)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                    
                    <td class="px-4 py-3 text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider align-middle">
                        {{ $module ?: 'GERAL' }}
                    </td>
                    
                    <td class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-300 capitalize align-middle">
                        {{ $subName }}
                    </td>
                    
                    <td class="px-4 py-3 text-[10px] text-gray-500 dark:text-gray-400 max-w-[200px] truncate align-middle" title="{{ $perms[0]->description }}">
                        {{ $perms[0]->description }}
                    </td>
                    
                    <td class="px-4 py-2 align-middle">
                        <div class="flex items-center justify-end gap-4 h-full">
                            @foreach($perms as $p)
                                <div class="flex flex-col items-center justify-center gap-1 w-[72px]" title="{{ $p->description }}">
                                    <span class="text-[9px] font-bold text-gray-500 capitalize tracking-wider">{{ $p->action_label }}</span>
                                    
                                    <!-- TOGGLE COM AUTO-SAVE EM TEMPO REAL -->
                                    <label class="relative inline-flex items-center cursor-pointer my-1">
                                        <input type="checkbox" wire:model.live="selectedPermissions" value="{{ $p->name }}" class="sr-only peer">
                                        <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="4" class="py-16 text-center text-gray-500 bg-gray-50/30 dark:bg-gray-900/30">
                    <i class="ph-fill ph-shield-warning text-4xl text-gray-300 mb-3"></i>
                    <p class="font-bold text-gray-700 dark:text-gray-400">Nenhuma permissão encontrada para o filtro.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>