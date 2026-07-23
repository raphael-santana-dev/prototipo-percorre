<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Turnos</h1>
        @can('turno.criar')
            <button wire:click="openModal" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                Novo Turno
            </button>
        @endcan
    </div>

    @if (session()->has('success'))
        <div class="p-4 text-green-700 bg-green-100 border-l-4 border-green-500 rounded-md">{{ session('success') }}</div>
    @endif

    <!-- Tabela de Listagem -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Início</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Fim</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($turnos as $turno)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $turno->nome }}</td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }}</td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                            @can('turno.editar')
                                <button wire:click="edit({{ $turno->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            @endcan
                            
                            @can('turno.excluir')
                                <button wire:click="delete({{ $turno->id }})" class="ml-4 text-red-600 hover:text-red-900" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()">
                                    Excluir
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum turno cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal (Controlado pela variável $showModal do Livewire) -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                <!-- Background overlay escuro -->
                <div class="fixed inset-0 transition-opacity bg-gray-500/75" aria-hidden="true" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <!-- Painel do Modal (AQUI FORAM ADICIONADAS AS CLASSES relative z-10) -->
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                            {{ $isEditMode ? 'Editar Turno' : 'Novo Turno' }}
                        </h3>
                        <div class="mt-4">
                            <form wire:submit="save">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nome (ex: Manhã)</label>
                                        <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        @error('nome') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700">Horário Início</label>
                                            <input type="time" wire:model="horario_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('horario_inicio') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700">Horário Fim</label>
                                            <input type="time" wire:model="horario_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('horario_fim') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                        Salvar
                                    </button>
                                    <button type="button" wire:click="closeModal" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>