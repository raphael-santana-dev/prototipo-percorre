<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-pistache-100 bg-pistache-500 font-medium shadow-sm">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-page-header 
        title="Gerenciamento de Turnos" 
        icon="ph ph-clock"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                <i class="ph ph-plus text-lg"></i> Novo Turno
            </button>
        </x-slot>

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $turno)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $turno->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap font-bold text-gray-900 dark:text-white">
                    {{ $turno->nome }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$turno->status" action="toggleStatus({{ $turno->id }})" />
                        <span class="text-[10px] font-bold {{ $turno->status ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $turno->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @can('turno.editar')
                            <button wire:click="edit({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endcan
                        
                        @can('turno.excluir')
                            <button wire:click="delete({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()" title="Excluir">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum turno encontrado.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou crie um novo turno.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO EM GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $turno )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $turno->nome }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $turno->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-1.5">
                        <i class="ph-fill ph-clock text-amber-500"></i> {{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }} às {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <x-toggle :status="$turno->status" action="toggleStatus({{ $turno->id }})" />
                            <div class="text-[10px] mt-1 font-bold {{ $turno->status ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $turno->status ? 'ATIVO' : 'INATIVO' }}
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-1">
                            @can('turno.editar')
                                <button wire:click="edit({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Editar">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endcan
                            
                            @can('turno.excluir')
                                <button wire:click="delete({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()" title="Excluir">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Integrado -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Turno' : 'Novo Turno' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome (ex: Manhã) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Horário Início <span class="text-red-500">*</span></label>
                                <input type="time" wire:model="horario_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('horario_inicio') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Horário Fim <span class="text-red-500">*</span></label>
                                <input type="time" wire:model="horario_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('horario_fim') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white shadow-sm rounded-lg bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Turno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>