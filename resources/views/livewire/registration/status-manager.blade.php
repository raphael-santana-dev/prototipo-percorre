<div class="p-6 mx-auto font-sans max-w-7xl">
    @if (session()->has('success'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-pistache-100 bg-pistache-500">
            <i class="text-lg ph ph-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-tag text-purpura-500"></i> Status de Inscrição
        </h2>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus"></i> Novo Status
        </button>
    </div>

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">ID</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Nome do Status</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Descrição</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                @forelse($statuses as $status)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap dark:text-gray-400">#{{ $status->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-purpura-100 text-purpura-700 uppercase">
                                {{ $status->nome }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $status->descricao ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $status->id }})" class="p-2 text-blue-700 transition-colors bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50" title="Editar">
                                    <i class="text-xl ph ph-pencil-simple"></i>
                                </button>
                                <button wire:click="delete({{ $status->id }})" class="p-2 text-red-700 transition-colors bg-red-100 rounded-lg hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50" title="Excluir" onclick="confirm('Excluir este status?') || event.stopImmediatePropagation()">
                                    <i class="text-xl ph ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum status cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 bg-white border-t border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            {{ $statuses->links() }}
        </div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="openModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Status' : 'Novo Status' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome do Status <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Aprovado" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
                            <textarea wire:model="descricao" rows="3" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @error('descricao') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>