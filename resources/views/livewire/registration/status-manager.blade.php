<div class="p-6 max-w-7xl mx-auto font-sans">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-4 rounded-md text-pistache-100 bg-pistache-500">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-tag text-purpura-500"></i> Status de Inscrição
        </h2>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus"></i> Novo Status
        </button>
    </div>

    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $status)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap dark:text-gray-400">#{{ $status->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-purpura-100 text-purpura-700 uppercase">
                        {{ $status->nome }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $status->descricao ?: '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full shadow-sm uppercase tracking-wider" 
                        style="background-color: {{ $status->cor ?? '#9CA3AF' }}; color: #ffffff;">
                        {{ $status->nome }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="edit({{ $status->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                            <i class="text-xl ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $status->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir este status?') || event.stopImmediatePropagation()">
                            <i class="text-xl ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhum status encontrado.</p>
                    <p class="text-sm">Ajuste os filtros ou crie um novo status.</p>
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
                    
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Nome do Status <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Aprovado" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                            @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
                            <textarea wire:model="descricao" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white"></textarea>
                            @error('descricao') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Input Color Formatado -->
                        <div>
                            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Cor da Tag</label>
                            <div class="flex items-center gap-4">
                                <input type="color" wire:model="cor" class="w-14 h-14 p-1 bg-white border border-gray-200 rounded-lg cursor-pointer dark:bg-gray-700 dark:border-gray-600">
                                <span class="text-sm text-gray-500 font-mono">{{ $cor ?? '#9CA3AF' }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6">
                            <button type="button" wire:click="$set('showModal', false)" class="px-6 py-3 text-sm font-bold text-purpura-600 bg-white border border-purpura-200 rounded-xl hover:bg-purpura-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-xl shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>