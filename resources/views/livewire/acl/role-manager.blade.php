<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Grupos" 
        icon="ph ph-shield-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        @if(feature('acl.role.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novos Grupos
                </button>
            </x-slot>
        @endif

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse($registros as $role)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 text-sm font-medium text-gray-500 whitespace-nowrap dark:text-gray-400">
                    #{{ $role->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="inline-flex px-3 py-1 text-[10px] font-bold text-purpura-700 bg-purpura-100 rounded-full uppercase tracking-wider border border-purpura-200">
                        {{ $role->name }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                        <i class="ph-fill ph-users"></i> {{ $role->users_count }} usuários
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('acl.role.permissoes') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.permissoes')))
                            <a href="{{ route('roles.permissions', $role->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Gerenciar Permissões do Grupo">
                                <i class="text-lg ph ph-key"></i>
                            </a>
                        @endif
                        
                        @if(!in_array($role->name, ['dev', 'admin']))
                            @if(feature('acl.role.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.editar')))
                                <button wire:click="edit({{ $role->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Editar Nome">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endif
                            @if(feature('acl.role.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.excluir')))
                                <button wire:click="delete({{ $role->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Grupo" onclick="confirm('Excluir este grupo permanentemente?') || event.stopImmediatePropagation()">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Nenhum grupo cadastrado.</td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $role)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex px-3 py-1 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded-full uppercase tracking-wider">{{ $role->name }}</span>
                        <span class="text-[10px] font-medium text-gray-400">#{{ $role->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-bold flex items-center gap-1.5">
                        <i class="ph-fill ph-users"></i> {{ $role->users_count }} usuários
                    </div>
                    <div class="flex items-center justify-end mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('roles.permissions', $role->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Permissões">
                                <i class="text-lg ph ph-key"></i>
                            </a>
                            @if(!in_array($role->name, ['dev', 'admin']))
                                <button wire:click="edit({{ $role->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Editar">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                                <button wire:click="delete({{ $role->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir este grupo?') || event.stopImmediatePropagation()">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal de Inserção Múltipla / Edição -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 dark:bg-gray-800">
                    
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Grupo' : 'Cadastrar Grupos' }}
                    </h3>
                    
                    <form wire:submit.prevent="save" class="space-y-4">
                        
                        @foreach($items as $index => $item)
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Nome do Grupo {{ count($items) > 1 ? ($index + 1) : '' }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="items.{{ $index }}.name" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: gestor">
                                    @error("items.{$index}.name") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                @if(count($items) > 1 && !$isEditMode)
                                    <div class="pt-6">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-red-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-gray-700" title="Remover Linha">
                                            <i class="text-lg ph-bold ph-trash"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if(!$isEditMode)
                            <div class="pt-2">
                                <button type="button" wire:click="addItem" class="flex items-center gap-2 px-3 py-1.5 text-sm font-bold text-purpura-600 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-gray-700 dark:text-purpura-400">
                                    <i class="ph-bold ph-plus"></i> Adicionar outra linha
                                </button>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 shadow-sm">
                                Salvar Tudo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>