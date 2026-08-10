<div class="p-6 max-w-7xl mx-auto font-sans">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-4 rounded-md text-pistache-100 bg-pistache-500">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-calendar-check text-purpura-500"></i> Gerenciamento de Turnos
        </h2>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus text-lg"></i> Novo Turno
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

        @forelse ($registros as $turno)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $turno->id }}</td>
                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $turno->nome }}</td>
                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }}</td>
                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-toggle :status="$turno->status" action="toggleStatus({{ $turno->id }})" />

                    <div class="text-[10px] mt-1 font-bold {{ $turno->status ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $turno->status ? 'ATIVO' : 'INATIVO' }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        @can('turno.editar')
                            <button wire:click="edit({{ $turno->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50">
                                <i class="text-xl ph ph-pencil-simple"></i>
                            </button>
                        @endcan
                        
                        @can('turno.excluir')
                            <button wire:click="delete({{ $turno->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-xl ph ph-trash"></i>
                            </button>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhum turno encontrado.</p>
                    <p class="text-sm">Ajuste os filtros ou crie um novo turno.</p>
                </td>
            </tr>
        @endforelse
    </x-table>

    <!-- Modal Corrigido com Padrão do Sistema -->
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