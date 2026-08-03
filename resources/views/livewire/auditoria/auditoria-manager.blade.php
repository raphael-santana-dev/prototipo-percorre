<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Auditoria de Sistema</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rastreabilidade completa de alterações no banco de dados e acessos.</p>
    </div>

    {{-- TABELA PADRONIZADA LIMPA --}}
    <x-table :headers="$this->headers" :registros="$registros">
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
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhum registro de auditoria encontrado.
                </td>
            </tr>
        @endforelse
    </x-table>
</div>