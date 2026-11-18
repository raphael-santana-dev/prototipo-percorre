<div class="w-full relative">

    <x-page-header 
        title="Status de Inscrição" 
        subtitle="Etapas do funil seletivo e visibilidade no portal do estudante"
        icon="ph ph-tag"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        @if(feature('status.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Novo Status
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
            <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-800/40" wire:key="status-row-{{ $status->id }}">
                
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ $status->id }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $status->nome }}
                    </span>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                    {{ $status->titulo_amigavel ?: '-' }}
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    @if(feature('status.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$status->visivel_estudante" action="toggleVisibilidade({{ $status->id }})" />
                            <span class="text-[11px] font-medium {{ $status->visivel_estudante ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                {{ $status->visivel_estudante ? 'Visível' : 'Oculto' }}
                            </span>
                        </div>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $status->visivel_estudante ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                            {{ $status->visivel_estudante ? 'Visível' : 'Oculto' }}
                        </span>
                    @endif
                </td>
                
                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                    {{ $status->descricao ?: '-' }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="inline-flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full border border-gray-200 dark:border-gray-700 shrink-0" 
                              style="background-color: {{ $status->cor ?? '#9CA3AF' }};"></span>
                        <span class="text-xs font-mono text-gray-500">{{ $status->cor ?? '#9CA3AF' }}</span>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('status.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.editar')))
                            <button wire:click="edit({{ $status->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Editar">
                                <i class="text-base ph ph-pencil-simple"></i>
                            </button>
                        @endif
                        @if(feature('status.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('status.excluir')))
                            <button wire:click="delete({{ $status->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir" onclick="confirm('Excluir este status?') || event.stopImmediatePropagation()">
                                <i class="text-base ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm">
                    Nenhum status encontrado.
                </td>
            </tr>
        @endforelse
    </x-table>

    @if($showModal)
        <x-modal
            title="{{ $isEditMode ? 'Editar Status' : 'Novo Status' }}"
            subtitle="Configure a identificação, cor e visibilidade da etapa no portal do estudante"
            max-width="md"
            close-method="$set('showModal', false)">
            
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                        Nome do Status <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           wire:model="nome"
                           placeholder="Ex: Aprovado"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm {{ $isInUse ? 'opacity-60 cursor-not-allowed bg-gray-100' : '' }}"
                           {{ $isInUse ? 'readonly' : '' }}>
                    @if($isInUse)
                        <span class="block mt-1 text-xs text-amber-600 font-medium">
                            O nome não pode ser alterado pois já existem inscrições vinculadas a este status.
                        </span>
                    @endif
                    @error('nome') <span class="block mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Título Amigável (Portal do Estudante)</label>
                    <input type="text" wire:model="titulo_amigavel" placeholder="Ex: Aprovado na 1ª Etapa" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    @error('titulo_amigavel') <span class="block mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
                    <textarea wire:model="descricao" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"></textarea>
                    @error('descricao') <span class="block mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Cor de Identificação</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="cor" class="w-10 h-10 p-1 bg-white border border-gray-300 rounded-lg cursor-pointer dark:bg-gray-900 dark:border-gray-600">
                        <span class="text-xs text-gray-500 font-mono">{{ $cor ?? '#9CA3AF' }}</span>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="visivel_estudante" class="mt-0.5 w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500">
                        <div class="text-xs">
                            <span class="font-medium text-gray-900 dark:text-white block">Visível para o estudante</span>
                            <span class="text-gray-500 dark:text-gray-400">Se desmarcado, o portal exibirá um status genérico ("Aguardando atualização").</span>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end gap-2.5 pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('showModal', false)" class="btn btn--secondary btn--medium">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                        Salvar Status
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>