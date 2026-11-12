<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Turnos" 
        subtitle="Configure os períodos e horários padrão utilizados nas unidades e cursos"
        icon="ph ph-clock"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm flex items-center gap-2">
                <i class="ph-bold ph-plus"></i> Novo Turno
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
            <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50" wire:key="turno-row-{{ $turno->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                    #{{ str_pad($turno->id, 2, '0', STR_PAD_LEFT) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap font-bold text-sm text-gray-900 dark:text-white">
                    {{ $turno->nome }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$turno->status" action="toggleStatus({{ $turno->id }})" />
                        <span class="text-[10px] font-extrabold {{ $turno->status ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $turno->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="edit({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700" title="Editar">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $turno->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()" title="Excluir">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum turno encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ($registros as $turno)
                <div class="flex flex-col p-5 bg-white border border-gray-200/80 shadow-2xs rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:border-purpura-300 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $turno->nome }}</div>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300">#{{ $turno->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-1.5 font-mono">
                        <i class="ph-fill ph-clock text-purpura-500"></i>
                        <span>{{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }} às {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$turno->status" action="toggleStatus({{ $turno->id }})" />
                            <span class="text-[10px] font-extrabold {{ $turno->status ? 'text-emerald-600' : 'text-gray-400' }}">
                                {{ $turno->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="edit({{ $turno->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                            <button wire:click="delete({{ $turno->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg" onclick="confirm('Excluir este turno permanentemente?') || event.stopImmediatePropagation()" title="Excluir">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    @if($showModal)
        <x-modal title="{{ $isEditMode ? 'Editar Turno' : 'Novo Turno' }}" max-width="md" close-method="closeModal">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Nome do Turno <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nome" placeholder="Ex: Manhã, Tarde, Noite, Integral" class="w-full text-sm border-gray-300 rounded-xl shadow-2xs focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                    @error('nome') <span class="block mt-1 text-xs text-red-500 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Horário Início <span class="text-red-500">*</span></label>
                        <input type="time" wire:model="horario_inicio" class="w-full text-sm border-gray-300 rounded-xl shadow-2xs focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        @error('horario_inicio') <span class="block mt-1 text-xs text-red-500 font-semibold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Horário Fim <span class="text-red-500">*</span></label>
                        <input type="time" wire:model="horario_fim" class="w-full text-sm border-gray-300 rounded-xl shadow-2xs focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        @error('horario_fim') <span class="block mt-1 text-xs text-red-500 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="closeModal" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                        <i class="ph-bold ph-check"></i> Salvar Turno
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>