<div class="p-6 max-w-7xl mx-auto font-sans relative text-gray-800">
    
    <x-page-header 
        title="Centros de Custo" 
        subtitle="Consulta de centros de custo sincronizados com o TOTVS Protheus"
        icon="ph ph-buildings"
        badge="Módulo Financeiro"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            @if(auth()->user()->hasAnyRole(['dev', 'admin']) || auth()->user()->can('financeiro.centro_custo.sincronizar'))
                <button type="button" wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg shadow-2xs hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i>
                    <span>Sincronizar Protheus</span>
                </button>
            @endif
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xs border border-gray-200 dark:border-gray-700 overflow-hidden mb-6 mt-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" wire:model.live.debounce.400ms="filtroCodigo" placeholder="Pesquisar por Código..." class="rounded-md border-gray-300 text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.400ms="filtroNome" placeholder="Pesquisar por Nome..." class="rounded-md border-gray-300 text-sm w-full dark:bg-gray-700 dark:text-white">
                <select wire:model.live="filtroDisponivel" class="rounded-md border-gray-300 text-sm w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="1">Disponível para Orçamento</option>
                    <option value="0">Inativo</option>
                </select>
                <button type="button" wire:click="limparFiltros" class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-100 transition h-[38px] flex items-center justify-center gap-1.5">
                    <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                </button>
            </div>
        </div>

        <div class="relative bg-white dark:bg-gray-900">
            <x-table 
                :headers="$this->headers" 
                :registros="$registros" 
                :ordenacaoCampo="$ordenacaoCampo" 
                :ordenacaoDirecao="$ordenacaoDirecao" 
                :permiteGrid="$permiteGrid" 
                :modoExibicao="$modoExibicao">

                @forelse($registros as $centro)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">#{{ $centro->id }}</td>
                        <td class="px-4 py-3 text-xs font-mono font-medium text-gray-900 dark:text-white">{{ $centro->codigo }}</td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{{ $centro->nome }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($centro->disponivel_orcamento)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-medium rounded border bg-emerald-50 text-emerald-700 border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Disponível para Orçamento
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-medium rounded border bg-gray-100 text-gray-600 border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Inativo
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                            <i class="ph ph-buildings text-3xl mb-2 block mx-auto"></i>
                            <p>Nenhum Centro de Custo encontrado.</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
</div>