<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ filtroModulo: '', filtroSubmodulo: '' }">
    <x-page-header 
        title="Gerenciar Permissões: {{ strtoupper($roleName) }}" 
        icon="ph ph-shield-check"
        badge=""
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="actions">
            <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                <i class="text-lg ph ph-arrow-left"></i> Voltar para Roles
            </a>
        </x-slot>
    </x-page-header>

    <!-- BARRA DE FILTROS (Instantâneo com Alpine.js) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-squares-four text-purpura-500"></i> Módulo
            </label>
            <select x-model="filtroModulo" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Módulos</option>
                @foreach($permissionsByModule->keys()->sort() as $mod) 
                    <option value="{{ strtoupper($mod ?: 'GERAL') }}">{{ strtoupper($mod ?: 'GERAL') }}</option> 
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-magnifying-glass text-purpura-500"></i> Submódulo
            </label>
            <input type="text" x-model="filtroSubmodulo" placeholder="Buscar submódulo por palavra-chave..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
        </div>
    </div>

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <form wire:submit.prevent="save">
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-48">Módulo</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-48">Submódulo</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[200px]">Descrição</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Permissões (Ações)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50" id="tabela-permissoes">
                        @php
                            // Agrupamento inteligente para a Tabela
                            $grouped = [];
                            foreach($permissionsByModule as $module => $permissions) {
                                foreach($permissions as $perm) {
                                    $parts = explode('.', $perm->name);
                                    $action = array_pop($parts); // Última palavra é a ação
                                    
                                    // Remove o módulo do prefixo, se existir, para achar o submódulo
                                    $prefix = strtolower($module) . '.';
                                    $subStr = str_starts_with($perm->name, $prefix) ? substr($perm->name, strlen($prefix)) : $perm->name;
                                    $subParts = explode('.', $subStr);
                                    array_pop($subParts); // Remove a ação do array de submódulos
                                    
                                    $subName = count($subParts) > 0 ? implode(' ', $subParts) : 'Geral';
                                    
                                    $perm->action_label = $action;
                                    $grouped[$module][$subName][] = $perm;
                                }
                            }
                        @endphp

                        @forelse($grouped as $module => $submodules)
                            @foreach($submodules as $subName => $perms)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors permission-row"
                                    x-show="(filtroModulo === '' || filtroModulo === '{{ strtoupper($module ?: 'GERAL') }}') && (filtroSubmodulo === '' || '{{ strtolower($subName) }}'.includes(filtroSubmodulo.toLowerCase()))"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <td class="px-6 py-4 text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider">
                                        {{ $module ?: 'GERAL' }}
                                    </td>
                                    
                                    <td class="px-6 py-4 text-xs font-bold text-gray-700 dark:text-gray-300 capitalize">
                                        {{ $subName }}
                                    </td>
                                    
                                    <td class="px-6 py-4 text-[11px] text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $perms[0]->description }}">
                                        {{ $perms[0]->description }}
                                    </td>
                                    
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-6">
                                            @foreach($perms as $p)
                                                <div class="flex flex-col items-center gap-1.5" title="{{ $p->description }}">
                                                    <span class="text-[10px] font-bold text-gray-500 capitalize tracking-wider">{{ $p->action_label }}</span>
                                                    
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $p->name }}" class="sr-only peer">
                                                        <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
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
                                    <p class="font-bold text-gray-700 dark:text-gray-400">Nenhuma permissão cadastrada no sistema ainda.</p>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60">
                <a href="{{ route('roles.index') }}" class="px-5 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
                @if(feature('acl.role.permissoes') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.permissoes')))
                    <button type="submit" class="flex items-center gap-2 px-6 py-2 text-sm font-bold text-white transition-colors rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Permissões
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if(feature('acl.role.permissoes') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.permissoes')))
        <x-fab 
            main-wire-click="save"
            main-color="bg-ponkan-500 hover:bg-ponkan-600"
            main-icon="ph-bold ph-floppy-disk" />
    @endif
</div>