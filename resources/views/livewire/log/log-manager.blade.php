<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Logs de Auditoria</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Acompanhamento e rastreabilidade das ações dos usuários no sistema.</p>
        </div>
        <button wire:click="limparFiltros" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-brand-purple dark:hover:text-purpura-400 transition">
            Limpar Filtros
        </button>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 transition-colors duration-300">
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Ação</label>
            <select wire:model.live="filtro_acao" class="w-full bg-transparent border-gray-300 dark:border-gray-600 dark:text-white rounded-md shadow-sm text-sm focus:ring-brand-purple focus:border-brand-purple py-2">
                <option value="">Todas as Ações</option>
                <option value="criacao">Criação</option>
                <option value="atualizacao">Atualização</option>
                <option value="exclusao">Exclusão</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Módulo / Tabela</label>
            <input wire:model.live.debounce.300ms="filtro_modelo" type="text" placeholder="Ex: Curso, Usuario..." class="w-full bg-transparent border-gray-300 dark:border-gray-600 dark:text-white rounded-md shadow-sm text-sm focus:ring-brand-purple focus:border-brand-purple py-2">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Data Início</label>
            <input wire:model.live="filtro_data_inicio" type="date" class="w-full bg-transparent border-gray-300 dark:border-gray-600 dark:text-white rounded-md shadow-sm text-sm focus:ring-brand-purple focus:border-brand-purple py-2">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Data Fim</label>
            <input wire:model.live="filtro_data_fim" type="date" class="w-full bg-transparent border-gray-300 dark:border-gray-600 dark:text-white rounded-md shadow-sm text-sm focus:ring-brand-purple focus:border-brand-purple py-2">
        </div>
    </div>

    {{-- TABELA PADRONIZADA --}}
    <x-table :headers="$this->headers" :registros="$registros" :ordenacaoCampo="$ordenacaoCampo" :ordenacaoDirecao="$ordenacaoDirecao">
        @forelse($registros as $log)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-800 dark:text-white">{{ $log->usuario->name ?? 'Sistema / Desconhecido' }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $log->ip_address ?? 'IP não registrado' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase
                        @if($log->acao === 'criacao') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                        @elseif($log->acao === 'exclusao') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                        {{ $log->acao }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 font-mono">
                    {{ class_basename($log->modelo) }} #{{ $log->modelo_id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-2">
                    {{-- Visualização Rápida no Modal --}}
                    <button @click="$dispatch('abrir-modal-log', { id: {{ $log->id }} })" class="text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 p-1.5 rounded-md transition" title="Visualização Rápida">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                    {{-- Visualização Completa na Página --}}
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-1.5 rounded-md transition" title="Ver Detalhes Completos">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhum registro de auditoria encontrado.
                </td>
            </tr>
        @endforelse
    </x-table>
</div>