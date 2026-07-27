<div class="p-6 max-w-7xl mx-auto font-sans relative">
    @if (session()->has('sucesso'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gry-800">Etapas</h2>

        <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-4 py-2 rounded-full border border-purple-200">
            Visão Global (Administrador)
        </span>

        <button @click="$dispatch('abrir-modal-etapa')">
            + Nova Etapa
        </button>
    </div>

    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao">

        @forelse($registros as $etapa)
            <tr class="bg-white border-b hover:bg-gray-100">
                <td class="px-6 py-4 font-medium text-gray-900">{{ $etapa->id }}</td>
                <td class="px-6 py-4">{{ $etapa->numero }}</td>
                <td class="px-6 py-4">{{ $etapa->nome }}</td>
                <td class="px-6 py-4 text-right">
                    {{-- Botão Editar --}}
                    <button @click="$dispatch('abrir-modal-etapa', { id: {{ $etapa->id }} })" class="text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 p-1.5 rounded-md transition" title="Editar Etapa">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>

                    {{-- Botão Excluir --}}
                    <button wire:click="excluir({{ $etapa->id }})" wire:confirm="Tem certeza que deseja excluir esta etapa?" class="text-red-500 dark:text-red-400 hover:text-red-700 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 p-1.5 rounded-md transition" title="Excluir Etapa">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhuma etapa encontrada.</p>
                    <p class="text-sm">Ajuste os filtros ou crie uma nova etapa.</p>
                </td>
            </tr>   
        @endforelse

    </x-admin.table>


    <div x-data="{ show: false, msg: '' }" 
        @etapa-salva.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span x-text="msg"></span>
    </div>
</div>