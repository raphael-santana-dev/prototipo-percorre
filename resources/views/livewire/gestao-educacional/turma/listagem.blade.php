<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Gestão de Turmas" 
        icon="ph ph-chalkboard"
        badge="Acadêmico">
        
        <x-slot name="actions">
            <a href="{{ route('turmas.create') }}" wire:navigate class="px-4 py-2 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-plus text-lg"></i> Nova Turma
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar turma pelo nome..." class="w-full pl-10 pr-4 py-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 bg-white dark:bg-gray-800 text-sm shadow-sm transition dark:text-white">
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
        
        @forelse($registros as $turma)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="font-black text-gray-800 dark:text-white text-md block">{{ $turma->nome }}</span>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mt-0.5">
                        <i class="ph ph-clock"></i> {{ $turma->turno->nome ?? 'N/A' }} • Ano {{ $turma->ano }}
                    </span>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-gray-700 dark:text-gray-300 text-sm truncate max-w-[200px]" title="{{ $turma->curso->nome ?? 'N/A' }}">
                        {{ $turma->curso->nome ?? 'N/A' }}
                    </div>
                    <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold mt-0.5 uppercase tracking-wider">
                        <i class="ph ph-map-pin"></i> {{ $turma->unidade->nome ?? 'N/A' }}
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 border border-gray-200 text-gray-800 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 font-black text-sm rounded shadow-sm">
                        <i class="ph-bold ph-users"></i> {{ $turma->matriculas_count }}
                    </span>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @if($turma->status)
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 uppercase tracking-wider border border-green-200 dark:border-green-800">Ativa</span>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-wider border border-red-200 dark:border-red-800">Inativa</span>
                    @endif
                </td>
                
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                    <a href="{{ route('turmas.edit', $turma->id) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600 inline-block" title="Editar Turma">
                        <i class="text-xl ph ph-pencil-simple"></i>
                    </a>
                    <button wire:click="excluir({{ $turma->id }})" wire:confirm="Tem certeza que deseja excluir esta turma?" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600 inline-block" title="Excluir">
                        <i class="text-xl ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <i class="ph ph-chalkboard text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nenhuma turma cadastrada.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>