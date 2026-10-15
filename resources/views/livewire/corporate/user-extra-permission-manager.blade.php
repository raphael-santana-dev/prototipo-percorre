<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ filtroModulo: '', filtroSubmodulo: '' }">
    <x-page-header 
        title="Permissões Extras" 
        icon="ph ph-shield-plus"
        badge="{{ strtoupper($userName) }}">

        <x-slot name="actions">
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                <i class="text-lg ph ph-arrow-left"></i> Voltar para Usuários
            </a>
        </x-slot>
    </x-page-header>

    <!-- BARRA DE FILTROS (Instantâneo com Alpine.js) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-squares-four text-purpura-500"></i> Módulo
            </label>
            <select x-model="filtroModulo" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 text-sm focus:ring-purpura-500 focus:border-purpura-500">
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
            <input type="text" x-model="filtroSubmodulo" placeholder="Buscar submódulo por palavra-chave..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 text-sm focus:ring-purpura-500 focus:border-purpura-500">
        </div>
    </div>

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        
        <div class="px-6 py-3 bg-blue-50 border-b border-blue-100 dark:bg-blue-900/30 dark:border-blue-800 flex items-center gap-3">
            <i class="ph-fill ph-info text-blue-500 text-xl"></i>
            <p class="text-xs text-blue-800 dark:text-blue-300 leading-snug m-0">
                <strong>Atenção:</strong> Permissões herdadas do grupo principal não podem ser alteradas aqui. <br>
                As permissões ativadas manualmente (Extras) aceitam uma <strong>Data de Validade</strong> opcional. Deixe o campo de data vazio para que a permissão seja permanente.
            </p>
        </div>

        <form wire:submit.prevent="save">
            <div class="overflow-x-auto custom-scrollbar" x-data="{ selected: @entangle('selectedPermissions') }">
                <!-- Tabela Compacta (padding reduzido) -->
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-32">Módulo</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-40">Submódulo</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider min-w-[150px]">Descrição</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Permissões Avulsas (Extras)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @php
                            // Agrupamento inteligente para a Tabela
                            $grouped = [];
                            foreach($permissionsByModule as $module => $permissions) {
                                foreach($permissions as $perm) {
                                    if ($perm->name === 'financeiro.visao_global') continue;

                                    $parts = explode('.', $perm->name);
                                    $action = array_pop($parts); // Última palavra é a ação
                                    
                                    // Remove o módulo do prefixo
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
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors"
                                    x-show="(filtroModulo === '' || filtroModulo === '{{ strtoupper($module ?: 'GERAL') }}') && (filtroSubmodulo === '' || '{{ strtolower($subName) }}'.includes(filtroSubmodulo.toLowerCase()))"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <!-- Células com padding reduzido (py-2) e alinhamento central (items-center) -->
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
                                                @php
                                                    $isInherited = in_array((int) $p->id, $rolePermissions);
                                                @endphp

                                                <div class="flex flex-col items-center justify-center gap-1 w-[72px]" title="{{ $p->description }}">
                                                    <span class="text-[9px] font-bold text-gray-500 capitalize tracking-wider">{{ $p->action_label }}</span>
                                                    
                                                    @if($isInherited)
                                                        <!-- TOGGLE BLOQUEADO (HERDADO DA ROLE) -->
                                                        <div class="relative inline-flex items-center cursor-not-allowed opacity-50 my-1">
                                                            <div class="w-7 h-4 bg-emerald-500 rounded-full"></div>
                                                            <div class="absolute top-[2px] right-[2px] bg-white border-gray-300 border rounded-full h-3 w-3"></div>
                                                        </div>
                                                        <span class="text-[8px] bg-gray-100 border border-gray-200 text-gray-500 px-1 py-0.5 rounded uppercase font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">Herdada</span>
                                                    @else
                                                        <!-- TOGGLE ATIVO (PERMISSÃO EXTRA) -->
                                                        <label class="relative inline-flex items-center cursor-pointer my-1">
                                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $p->id }}" class="sr-only peer">
                                                            <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-purpura-500"></div>
                                                        </label>

                                                        <!-- CAMPO DE DATA (Aparece apenas quando o toggle está ativo) -->
                                                        <div x-show="selected.includes('{{ $p->id }}') || selected.includes({{ $p->id }})" class="w-full mt-0.5" x-cloak x-transition>
                                                            <input type="date" wire:model="expirations.{{ $p->id }}" class="w-full text-[9px] py-0.5 px-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded shadow-sm focus:ring-purpura-500 focus:border-purpura-500 leading-tight h-5" title="Vazio = Permanente">
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-500 bg-gray-50/30 dark:bg-gray-900/30">
                                    <i class="ph-fill ph-shield-warning text-3xl text-gray-300 mb-2"></i>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-400">Nenhuma permissão cadastrada.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Rodapé de Ações -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60">
                @if(feature('usuario.permissoes_extras') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.permissoes_extras')))
                    <button type="submit" class="flex items-center gap-2 px-6 py-2 text-sm font-bold text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Permissões Extras
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>