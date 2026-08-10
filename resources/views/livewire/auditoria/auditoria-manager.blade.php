<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Auditoria de Sistema</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rastreabilidade completa de alterações no banco de dados e acessos.</p>
    </div>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        @forelse($registros as $log)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-800 dark:text-white">{{ $log->usuario_nome ?? 'Sistema' }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $log->ip ?? 'IP Desconhecido' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase
                        @if($log->acao === 'criacao') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                        @elseif($log->acao === 'atualizacao') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                        @elseif($log->acao === 'exclusao') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                        @elseif($log->acao === 'login') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                        {{ $log->acao }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 font-mono">
                    {{ $log->tabela_alterada }}
                    @if($log->registro_id) <span class="text-gray-400">#{{ $log->registro_id }}</span> @endif
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button wire:click="showQuickView({{ $log->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Detalhes do Log">
                        <i class="text-xl ph ph-info"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhum registro de auditoria encontrado.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $log)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full uppercase
                            @if($log->acao === 'criacao') bg-green-100 text-green-800
                            @elseif($log->acao === 'atualizacao') bg-blue-100 text-blue-800
                            @elseif($log->acao === 'exclusao') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $log->acao }}
                        </span>
                        <span class="text-[10px] text-gray-400">#{{ $log->id }}</span>
                    </div>
                    
                    <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $log->tabela_alterada }}</div>
                    <div class="text-[10px] text-gray-500 mb-3">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                    
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="text-[10px] font-medium text-gray-500 truncate max-w-[120px]">
                            <i class="ph-fill ph-user text-gray-400"></i> {{ $log->usuario_nome ?? 'Sistema' }}
                        </div>
                        <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-purpura-500 rounded-lg dark:hover:bg-gray-600"><i class="text-lg ph ph-info"></i></button>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>
</div>