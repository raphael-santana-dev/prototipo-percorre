<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Gerenciamento de Permissões" 
        icon="ph ph-key"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        @if(feature('acl.permissao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.permissao.criar')))
            <x-slot name="actions">
                <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Nova Permissão
                </button>
            </x-slot>
        @endif

        {{-- FILTROS INTEGRADOS AO HEADER --}}
        <x-slot name="filters" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                    <i class="ph ph-magnifying-glass text-purpura-500"></i> Palavra-chave
                </label>
                <input type="text" wire:model.live.debounce.300ms="filtro_keyword" placeholder="Buscar por nome ou descrição..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                    <i class="ph ph-squares-four text-purpura-500"></i> Módulo
                </label>
                <select wire:model.live="filtro_module" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todos os Módulos</option>
                    @foreach($modulosDisponiveis as $modulo) 
                        <option value="{{ $modulo }}">{{ ucfirst($modulo) }}</option> 
                    @endforeach
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $permission)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $permission->id }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase tracking-wider dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        {{ $permission->module }}
                    </span>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $permission->name }}</div>
                </td>
                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300 max-w-xs truncate">
                    {{ $permission->description }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('acl.permissao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.permissao.editar')))
                            <button wire:click="abrirModal({{ $permission->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Permissão">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif
                        @if(feature('acl.permissao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.permissao.excluir')))
                            <button wire:click="excluir({{ $permission->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Permissão" onclick="confirm('Excluir esta permissão pode quebrar o acesso ao sistema. Continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhuma permissão encontrada.</p>
                    <p class="text-xs mt-1">Cadastre uma nova permissão ou altere os filtros.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO DE GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $permission )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $permission->name }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-700 bg-gray-100 rounded-full dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-600">{{ $permission->module }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        {{ $permission->description }}
                    </div>
                    <div class="flex items-center justify-end mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-1">
                            <button wire:click="abrirModal({{ $permission->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Permissão">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                            <button wire:click="excluir({{ $permission->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Permissão" onclick="confirm('Excluir esta permissão pode quebrar o acesso ao sistema. Continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Multi-Insert / Edit -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $permissionId ? 'Editar Permissão' : 'Nova Permissão' }}
                    </h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div class="hidden md:grid md:grid-cols-12 md:gap-4 mb-2">
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Módulo <span class="text-red-500">*</span></div>
                            <div class="col-span-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Ação <span class="text-red-500">*</span></div>
                            <div class="col-span-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição <span class="text-red-500">*</span></div>
                            <div class="col-span-1"></div>
                        </div>

                        @foreach($items as $index => $item)
                            <div class="grid grid-cols-1 gap-4 p-4 mb-4 border border-gray-200 rounded-lg md:p-0 md:border-none md:grid-cols-12 dark:border-gray-700 bg-gray-50/50 md:bg-transparent dark:bg-gray-800/50 items-start">
                                
                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Módulo</label>
                                    <input type="text" wire:model="items.{{ $index }}.module" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: turno">
                                    @error("items.{$index}.module") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="md:col-span-4">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Ação</label>
                                    <input type="text" wire:model="items.{{ $index }}.action" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: criar">
                                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">Chave: <strong x-text="$wire.items[{{ $index }}].module && $wire.items[{{ $index }}].action ? $wire.items[{{ $index }}].module.toLowerCase() + '.' + $wire.items[{{ $index }}].action.toLowerCase() : 'modulo.acao'"></strong></p>
                                    @error("items.{$index}.action") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="md:col-span-4">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Descrição</label>
                                    <textarea wire:model="items.{{ $index }}.description" rows="2" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="O que esta permissão libera?"></textarea>
                                    @error("items.{$index}.description") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex items-center justify-end md:justify-center md:col-span-1 md:mt-1">
                                    @if(count($items) > 1 && !$permissionId)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-red-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-gray-700" title="Remover Linha">
                                            <i class="text-lg ph-bold ph-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if(!$permissionId)
                            <div class="pt-2">
                                <button type="button" wire:click="addItem" class="flex items-center gap-2 px-3 py-1.5 text-sm font-bold text-purpura-600 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-gray-700 dark:text-purpura-400">
                                    <i class="ph-bold ph-plus"></i> Adicionar outra linha
                                </button>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="fecharModal" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Permissões
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- TOAST SYSTEM --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>