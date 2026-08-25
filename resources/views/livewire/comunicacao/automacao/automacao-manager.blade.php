<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Automações (Triggers)" 
        icon="ph ph-lightning"
        badge=""
        :breadcrumbs="$breadcrumbs">

        @if(feature('automacao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.criar')))
            <x-slot name="actions">
                <a href="{{ route('automacoes.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                    <i class="ph ph-plus text-lg"></i> Nova Regra
                </a>
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
        
        @forelse($registros as $regra)
            <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500">
                    #{{ $regra->id }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900">{{ $regra->nome }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                        <i class="ph-fill ph-lightning text-blue-500 mr-1"></i> {{ $regra->evento_gatilho }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($regra->template)
                        <button wire:click="previewTemplate({{ $regra->template_id }})" class="group flex items-center gap-1.5 text-sm text-gray-600 font-medium truncate max-w-xs hover:text-purpura-600 transition outline-none">
                            <i class="ph ph-layout text-gray-400 group-hover:text-purpura-500 transition-colors"></i>
                            <span class="truncate border-b border-dashed border-transparent group-hover:border-purpura-300 transition-colors">{{ $regra->template->nome }}</span>
                            <i class="ph-bold ph-eye text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </button>
                    @else
                        <span class="text-sm text-red-500 font-medium"><i class="ph ph-warning-circle"></i> Template Excluído</span>
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                        <button wire:click="toggleStatus({{ $regra->id }})" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500 {{ $regra->status ? 'bg-green-500' : 'bg-gray-300' }}">
                            <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform {{ $regra->status ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    @else
                        <span class="w-2 h-2 rounded-full {{ $regra->status ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('automacao.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.visualizar')))
                            <a href="{{ route('automacoes.show', $regra->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50" title="Ver Histórico de Disparos">
                                <i class="text-lg ph ph-chart-line-up"></i>
                            </a>
                        @endif
                        @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                            <a href="{{ route('automacoes.edit', $regra->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50" title="Editar Regra">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </a>
                        @endif
                        @if(feature('automacao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.excluir')))
                            <button wire:click="excluir({{ $regra->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50" title="Excluir" onclick="confirm('Excluir esta regra de automação permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                        <i class="ph ph-lightning text-3xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600">Nenhuma automação configurada.</p>
                    <p class="text-xs mt-1">Crie gatilhos para enviar e-mails automaticamente quando as coisas acontecerem.</p>
                </td>
            </tr>
        @endforelse
    </x-table>

    {{-- TOAST --}}
    <div x-data="{ show: false, msg: '' }" @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3000);" x-show="show" x-transition class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle"></i> <span x-text="msg"></span>
    </div>
</div>