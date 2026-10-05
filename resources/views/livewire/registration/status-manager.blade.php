<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Status de Inscrição" 
        icon="ph ph-tag"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        @if(feature('status.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="btn btn--primary btn--medium">
                    <i class="ph ph-plus text-lg"></i> Novo Status
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

        @forelse ($registros as $status)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $status->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="tag tag--small tag--filled tag--purpura">
                        {{ $status->nome }}
                    </span>
                </td>

                <td class="px-4 py-2 5 whitespace-nowrap">
                    <span class="tag tag--small tag--filled tag--purpura">
                        {{ $status->titulo_amigavel ?: '-' }}
                    </span>
                </td>

                <td class="px-4 py-1.5 whitespace-nowrap">
                    @if(feature('status.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$status->visivel_estudante" action="toggleVisibilidade({{ $status->id }})" />
                            <span class="text-[10px] font-bold {{ $status->visivel_estudante ? 'text-green-600' : 'text-gray-400' }}">{{ $status->visivel_estudante ? 'VISÍVEL' : 'INVISÍVEL' }}</span>
                        </div>
                    @else
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded border {{ $status->visivel_estudante ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">{{ $status->visivel_estudante ? 'VISÍVEL' : 'INVISÍVEL' }}</span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300 max-w-xs truncate">
                    {{ $status->descricao ?: '-' }}
                </td>

                
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="inline-flex px-3 py-1 text-[10px] font-bold rounded-full shadow-sm uppercase tracking-wider" 
                        style="background-color: {{ $status->cor ?? '#9CA3AF' }}; color: #ffffff;">
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('status.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.editar')))
                            <button wire:click="edit({{ $status->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif
                        @if(feature('status.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.excluir')))
                            <button wire:click="delete({{ $status->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir este status?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum status encontrado.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou crie um novo status.</p>
                </td>
            </tr>
        @endforelse
    </x-table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="openModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-5 text-xl font-extrabold text-gray-900 dark:text-white">
                        {{ $isEditMode ? 'Editar Status' : 'Novo Status' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block mb-2 t-label-12-semibold text-gray-700 dark:text-gray-300">Nome do Status <span class="text-pitaya-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Aprovado" 
                                class="w-full {{ $isInUse ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $isInUse ? 'readonly' : '' }}>
                            @if($isInUse)
                                <span class="block mt-1 text-xs text-ponkan-600 font-bold"><i class="ph-fill ph-warning-circle"></i> O nome não pode ser alterado pois já existem inscrições usando este status.</span>
                            @endif
                            @error('nome') <span class="block mt-1 text-xs text-pitaya-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 t-label-12-semibold text-gray-700 dark:text-gray-300">Título Amigável</label>
                            <input type="text" wire:model="titulo_amigavel" placeholder="Ex: Aprovado" class="w-full mt-1">
                            @error('titulo_amigavel') <span class="block mt-1 text-xs text-pitaya-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 t-label-12-semibold text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
                            <textarea wire:model="descricao" rows="3" class="w-full mt-1"></textarea>
                            @error('descricao') <span class="block mt-1 text-xs text-pitaya-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 t-label-12-semibold text-gray-700 dark:text-gray-300">Cor da Tag</label>
                            <div class="flex items-center gap-4 mt-1">
                                <input type="color" wire:model="cor" class="w-12 h-12 p-1 bg-white border border-gray-300 rounded-md cursor-pointer shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                <span class="text-sm text-gray-500 font-mono">{{ $cor ?? '#9CA3AF' }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 t-label-12-semibold text-gray-700 dark:text-gray-300">Visível para Estudante</label>
                            <div class="flex items-center gap-4 mt-1">
                                <input type="checkbox" wire:model="visivel_estudante" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <span class="text-sm text-gray-500">Se marcado, este status será visível para os estudantes. Caso contrátio, o sistema irá apresentar um status genérico (Aguardando Atualização ...)</span>
                            </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="btn btn--secondary btn--medium">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn--primary btn--medium">
                                Salvar Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>