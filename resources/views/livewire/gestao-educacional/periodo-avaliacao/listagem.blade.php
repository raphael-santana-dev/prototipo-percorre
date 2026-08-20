<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Ciclos de Avaliação" 
        icon="ph ph-calendar-check"
        badge="Configurações">
        
        <x-slot name="actions">
            <a href="{{ route('avaliacoes.periodos.create') }}" wire:navigate class="px-4 py-2 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-plus text-lg"></i> Novo Período
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar por Ano..." class="w-full pl-10 pr-4 py-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 bg-white dark:bg-gray-800 text-sm shadow-sm transition dark:text-white">
                <i class="ph ph-magnifying-glass text-gray-400 absolute left-3 top-2.5 text-lg"></i>
            </div>
        </x-slot>
    </x-page-header>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="false">
        
        @forelse($registros as $periodo)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="font-black text-gray-800 dark:text-white text-lg">{{ $periodo->ano }}</span>
                    <span class="text-xs font-bold text-purpura-600 uppercase tracking-wider ml-1">Ciclo {{ $periodo->ciclo }}</span>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap font-mono text-sm text-gray-600 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($periodo->data_inicio)->format('d/m/Y') }} à {{ \Carbon\Carbon::parse($periodo->data_fim)->format('d/m/Y') }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-center font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $periodo->fases_count }} Fase(s)
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @if($periodo->status === '1')
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 uppercase tracking-wider border border-green-200 dark:border-green-800">Aberto</span>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 uppercase tracking-wider border border-gray-200 dark:border-gray-600">Fechado</span>
                    @endif
                </td>
                
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                    <a href="{{ route('avaliacoes.periodos.edit', $periodo->id) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600 inline-block" title="Configurar Período">
                        <i class="text-xl ph ph-gear"></i>
                    </a>
                    <button wire:click="excluir({{ $periodo->id }})" wire:confirm="Tem certeza que deseja excluir?" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600 inline-block" title="Excluir">
                        <i class="text-xl ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <i class="ph ph-calendar-blank text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nenhum período de avaliação cadastrado.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>