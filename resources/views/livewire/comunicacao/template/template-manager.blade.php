<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Templates de E-mail" 
        icon="ph ph-envelope-simple"
        badge=""
        :breadcrumbs="$breadcrumbs">

        @if(feature('template.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('template.criar')))
            <x-slot name="actions">
                <a href="{{ route('templates.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold text-sm">
                    <i class="ph ph-plus text-lg"></i> Novo Template
                </a>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="flex gap-2">
                <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar nome ou assunto..." class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-64">
                
                @if($filtro_busca !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        
        @forelse($registros as $template)
            <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500">
                    #{{ $template->id }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900">{{ $template->nome }}</div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm text-gray-600 truncate max-w-sm">{{ $template->assunto }}</div>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('template.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('template.editar')))
                            <a href="{{ route('templates.edit', $template->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </a>
                        @endif
                        @if(feature('template.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('template.excluir')))
                            <button wire:click="excluir({{ $template->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50" title="Excluir" onclick="confirm('Excluir este template permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                        <i class="ph ph-layout text-3xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600">Nenhum template cadastrado.</p>
                    <p class="text-xs mt-1">Crie templates com variáveis inteligentes para automatizar sua comunicação.</p>
                </td>
            </tr>
        @endforelse

    </x-table>

    {{-- TOAST SYSTEM GLOBAL --}}
    <div x-data="{ show: false, msg: '', type: 'sucesso' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; type = 'sucesso'; setTimeout(() => show = false, 3500);"
        @erro.window="show = true; msg = $event.detail.msg; type = 'erro'; setTimeout(() => show = false, 4500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        :class="type === 'sucesso' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed bottom-8 right-8 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph" :class="type === 'sucesso' ? 'ph-check-circle' : 'ph-warning-circle'"></i>
        <span x-text="msg"></span>
    </div>
</div>