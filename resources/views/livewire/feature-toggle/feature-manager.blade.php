<div class="p-6 max-w-7xl mx-auto font-sans relative">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-4 rounded-md text-pistache-100 bg-pistache-500">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-toggle-right text-purpura-500"></i> Gerenciamento de Features
        </h2>
        <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus text-lg"></i> Nova Feature
        </button>
    </div>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $feature)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $feature->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        {{ $feature->module }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $feature->name }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $feature->description }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-toggle :status="$feature->is_active" action="toggleStatus({{ $feature->id }})" />
                    
                    <div class="text-[10px] mt-1 font-bold {{ $feature->is_active ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $feature->is_active ? 'ATIVO' : 'INATIVO' }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="abrirModal({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Feature">
                            <i class="text-xl ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="excluir({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Feature" onclick="confirm('Excluir esta feature permanentemente?') || event.stopImmediatePropagation()">
                            <i class="text-xl ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhuma feature encontrada.</p>
                    <p class="text-sm">Cadastre uma nova feature para começar.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO DE GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $feature )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $feature->name }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-700 bg-gray-100 rounded-full dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-600">{{ $feature->module }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">
                        {{ $feature->description }}
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <x-toggle :status="$feature->is_active" action="toggleStatus({{ $feature->id }})" />
                        
                        <div class="flex items-center gap-2">
                            <button wire:click="abrirModal({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Feature">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                            <button wire:click="excluir({{ $feature->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Feature" onclick="confirm('Excluir esta feature permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Padrão -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $featureId ? 'Editar Feature' : 'Nova Feature' }}
                    </h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Módulo <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="module" placeholder="ex: sistema" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('module') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Ação <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="action" placeholder="ex: tema" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            
                            {{-- Preview em tempo real de como vai ficar o nome técnico usando AlpineJS --}}
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">Nome final: <strong x-text="$wire.module && $wire.action ? $wire.module.toLowerCase() + '.' + $wire.action.toLowerCase() : 'modulo.acao'"></strong></p>
                            @error('action') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição <span class="text-red-500">*</span></label>
                            <textarea wire:model="description" rows="2" placeholder="O que esta feature controla?" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="fecharModal" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Feature
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- TOAST SYSTEM --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>