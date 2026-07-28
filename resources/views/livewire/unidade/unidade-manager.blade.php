<div class="p-6 mx-auto font-sans max-w-7xl">
    @if (session()->has('success'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-pistache-100 bg-pistache-500">
            <i class="text-lg ph ph-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-buildings text-purpura-500"></i> Unidades
        </h2>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus"></i> Nova Unidade
        </button>
    </div>

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Unidade</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Contato</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-center text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-xs font-bold tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                @forelse($unidades as $unidade)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-bold text-gray-900 dark:text-white">{{ $unidade->nome }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $unidade->endereco }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-300">{{ $unidade->email ?: 'Sem e-mail' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $unidade->telefone ?: 'Sem telefone' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($unidade->status === 'Ativa')
                                <span class="inline-flex px-2 text-xs font-bold text-pistache-700 bg-pistache-100 rounded-full uppercase">Ativa</span>
                            @else
                                <span class="inline-flex px-2 text-xs font-bold text-red-700 bg-red-100 rounded-full uppercase">Inativa</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Botão de Quick View (Painel Lateral) -->
                                <button x-data @click="$dispatch('open-quick-view', { model: 'Unidade', id: {{ $unidade->id }} })" class="p-2 text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Visualização Rápida">
                                    <i class="text-xl ph ph-eye"></i>
                                </button>
                                
                                <!-- Botão de Full View (Página Completa) -->
                                <a href="{{ route('unidades.show', $unidade->id) }}" class="p-2 text-purpura-700 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50" title="Página Completa">
                                    <i class="text-xl ph ph-arrow-square-out"></i>
                                </a>

                                <button wire:click="edit({{ $unidade->id }})" class="p-2 text-blue-700 transition-colors bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50" title="Editar">
                                    <i class="text-xl ph ph-pencil-simple"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma unidade cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 bg-white border-t border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            {{ $unidades->links() }}
        </div>
    </div>

    <!-- Modal de Inserção/Edição -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="openModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Unidade' : 'Nova Unidade' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome da Unidade <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Endereço Completo <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="endereco" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('endereco') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">E-mail Corporativo</label>
                                <input type="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Telefone</label>
                                <input type="text" wire:model="telefone" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model="status" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="Ativa">Ativa</option>
                                    <option value="Inativa">Inativa</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Data de Inauguração</label>
                                <input type="date" wire:model="data_inauguracao" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <!-- Seção de Relacionamento: Cursos da Unidade -->
                            <div class="col-span-1 pt-4 mt-2 border-t border-gray-100 md:col-span-2 dark:border-gray-700">
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos oferecidos nesta unidade
                                </label>
                                <div class="grid grid-cols-1 gap-2 p-4 border border-gray-200 rounded-lg sm:grid-cols-2 lg:grid-cols-3 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-48 overflow-y-auto">
                                    @forelse($cursosDisponiveis as $curso)
                                        <label class="flex items-center gap-2 p-2 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700">
                                            <input type="checkbox" wire:model="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300" title="{{ $curso->nome }}">
                                                {{ $curso->nome }}
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-500 col-span-full dark:text-gray-400">Nenhum curso ativo cadastrado no sistema.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Unidade</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>