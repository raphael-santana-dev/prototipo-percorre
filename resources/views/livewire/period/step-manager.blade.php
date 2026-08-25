<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Etapas" 
        icon="ph ph-clipboard-text"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        @if(feature('etapa.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('etapa.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Nova Etapa
                </button>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="flex gap-2">
                <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar etapa..." class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-64">
                
                @if($filtro_busca !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid ?? true"
        :modoExibicao="$modoExibicao ?? 'lista'">

        @forelse($registros as $etapa)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $etapa->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ $etapa->numero }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">
                    {{ $etapa->nome }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if($etapa->numero !== 1 || auth()->user()->hasRole('dev'))
                            @if(feature('etapa.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('etapa.editar')))
                                <button wire:click="edit({{ $etapa->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Etapa">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endif
                            @if(feature('etapa.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('etapa.excluir')))
                                <button wire:click="delete({{ $etapa->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Etapa" onclick="confirm('Tem certeza que deseja excluir esta etapa?') || event.stopImmediatePropagation()">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endif
                        @else
                            <span class="px-2 py-0.5 text-[10px] font-bold text-gray-500 bg-gray-100 border border-gray-200 rounded uppercase tracking-wider dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">Etapa Fixa</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhuma etapa encontrada.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou crie uma nova etapa.</p>
                </td>
            </tr>   
        @endforelse

        {{-- VISÃO DE GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach($registros as $etapa)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $etapa->nome }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $etapa->id }}</span>
                    </div>
                    
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Ordem no Funil: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $etapa->numero }}</span>
                    </div>
                    
                    <div class="flex items-center justify-end mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-1">
                            @if($etapa->numero !== 1 || auth()->user()->hasRole('dev'))
                                @if(feature('etapa.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('etapa.editar')))
                                    <button wire:click="edit({{ $etapa->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600">
                                        <i class="text-lg ph ph-pencil-simple"></i>
                                    </button>
                                @endif
                                @if(feature('etapa.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('etapa.excluir')))
                                    <button wire:click="delete({{ $etapa->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir esta etapa?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph ph-trash"></i>
                                    </button>
                                @endif
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold text-gray-500 bg-gray-100 border border-gray-200 rounded uppercase tracking-wider dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">Etapa Fixa</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                <!-- Overlay Escuro -->
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <!-- Container Principal -->
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Etapa' : 'Cadastrar Nova Etapa' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-5">
                        
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome da Etapa <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Análise de Documentos" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Ordem de Execução <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="numero" placeholder="Ex: 1" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Define em qual posição esta etapa aparecerá no funil.</p>
                            @error('numero') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição / Instruções</label>
                            <textarea wire:model="descricao" rows="3" placeholder="Detalhes opcionais sobre o que acontece nesta etapa..." class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @error('descricao') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white shadow-sm rounded-lg bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Etapa
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>