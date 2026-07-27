<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2 dark:text-white">
            <i class="ph ph-toggle-right text-purpura-500"></i> Feature Toggles
        </h1>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus"></i> Novas Features
        </button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 rounded-md text-pistache-100 bg-pistache-500"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="space-y-6">
        @forelse($featuresByModule as $moduleName => $featuresGroup)
            <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 dark:bg-gray-900 dark:border-gray-700">
                    <h3 class="text-sm font-bold tracking-wider text-gray-700 uppercase dark:text-gray-300">
                        Módulo: <span class="text-purpura-500">{{ $moduleName }}</span>
                    </h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                        @foreach($featuresGroup as $feature)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $feature->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $feature->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap w-32 text-center">
                                    <button wire:click="toggle('{{ $feature->name }}', {{ $feature->is_active ? 'true' : 'false' }})" 
                                            class="relative inline-flex items-center h-6 rounded-full w-11 focus:outline-none transition-colors ease-in-out duration-200 {{ $feature->is_active ? 'bg-pistache-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="inline-block w-4 h-4 transform bg-white rounded-full transition ease-in-out duration-200 {{ $feature->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap w-24">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Editar">
                                            <i class="text-xl ph ph-pencil-simple"></i>
                                        </button>
                                        <button wire:click="delete({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir esta feature permanentemente?') || event.stopImmediatePropagation()">
                                            <i class="text-xl ph ph-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="p-8 text-center text-gray-500 bg-white border border-gray-100 rounded-xl dark:bg-gray-800 dark:border-gray-700">
                Nenhuma feature cadastrada.
            </div>
        @endforelse
    </div>

    <!-- Modal de Inserção Múltipla / Edição -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6 dark:bg-gray-800">
                    
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Feature' : 'Cadastrar Features' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="hidden md:grid md:grid-cols-12 md:gap-4 mb-2">
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Módulo</div>
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ação</div>
                            <div class="col-span-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</div>
                            <div class="col-span-1"></div>
                        </div>

                        @foreach($items as $index => $item)
                            <div class="grid grid-cols-1 gap-4 p-4 mb-4 border border-gray-200 rounded-lg md:p-0 md:border-none md:grid-cols-12 dark:border-gray-700 bg-gray-50/50 md:bg-transparent dark:bg-gray-800/50 items-start">
                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Módulo</label>
                                    <input type="text" wire:model="items.{{ $index }}.module" class="w-full text-sm" placeholder="ex: sistema">
                                    @error("items.{$index}.module") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Ação</label>
                                    <input type="text" wire:model="items.{{ $index }}.action" class="w-full text-sm" placeholder="ex: tema">
                                    @error("items.{$index}.action") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-5">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Descrição</label>
                                    <input type="text" wire:model="items.{{ $index }}.description" class="w-full text-sm" placeholder="O que esta feature controla?">
                                    @error("items.{$index}.description") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex items-center justify-end md:justify-center md:col-span-1 mt-1">
                                    @if(count($items) > 1 && !$isEditMode)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-red-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-gray-700" title="Remover Linha">
                                            <i class="text-lg ph-bold ph-trash"></i>
                                        </button>
                                    @endif
                                </div>
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