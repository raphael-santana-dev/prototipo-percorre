<div class="w-full relative">
    <x-page-header 
        title="Auditoria de Sistema" 
        subtitle="Rastreabilidade de alterações no banco de dados e acessos"
        icon="ph ph-magnifying-glass"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end w-full">
                
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                        Buscar
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="filtro_keyword" placeholder="Usuário, IP ou ID do Registro..." class="w-full">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                        Ação
                    </label>
                    <select wire:model.live="filtro_acao" class="w-full">
                        <option value="">Todas</option>
                        @foreach($acoesDisponiveis as $acao)
                            <option value="{{ $acao }}">{{ ucfirst($acao) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                        Tabela
                    </label>
                    <select wire:model.live="filtro_tabela" class="w-full">
                        <option value="">Todas</option>
                        @foreach($tabelasDisponiveis as $tabela)
                            <option value="{{ $tabela }}">{{ Str::limit($tabela, 18) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2" x-data="{
                    initZero(e) {
                        if (!e.target.value) {
                            let d = new Date();
                            let y = d.getFullYear();
                            let m = String(d.getMonth() + 1).padStart(2, '0');
                            let day = String(d.getDate()).padStart(2, '0');
                            e.target.value = `${y}-${m}-${day}T00:00`;
                            e.target.dispatchEvent(new Event('input'));
                        }
                    }
                }">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                        De (Período)
                    </label>
                    <input type="datetime-local" 
                           wire:model.live="filtro_data_inicio" 
                           @focus="initZero"
                           class="w-full">
                </div>

                <div class="md:col-span-2" x-data="{
                    initEnd(e) {
                        if (!e.target.value) {
                            let d = new Date();
                            let y = d.getFullYear();
                            let m = String(d.getMonth() + 1).padStart(2, '0');
                            let day = String(d.getDate()).padStart(2, '0');
                            e.target.value = `${y}-${m}-${day}T23:59`;
                            e.target.dispatchEvent(new Event('input'));
                        }
                    }
                }">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                        Até (Período)
                    </label>
                    <input type="datetime-local" 
                           wire:model.live="filtro_data_fim" 
                           @focus="initEnd"
                           class="w-full">
                </div>

                @if($filtro_keyword !== '' || $filtro_acao !== '' || $filtro_tabela !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <div class="md:col-span-12 flex justify-end pt-2">
                        <button type="button" wire:click="limparFiltros" class="btn btn--secondary btn--small">
                            <i class="ph ph-x"></i> Limpar Filtros
                        </button>
                    </div>
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
        
        @forelse($registros as $log)
            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors" wire:key="audit-row-{{ $log->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ $log->id }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-gray-900 dark:text-white font-mono">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $log->usuario_nome ?? 'Sistema' }}</div>
                    <div class="text-[11px] font-mono text-gray-400">{{ $log->ip ?? 'IP Desconhecido' }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border uppercase
                        @if($log->acao === 'criacao') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800
                        @elseif($log->acao === 'atualizacao') bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800
                        @elseif($log->acao === 'exclusao' || $log->acao === 'acesso_negado') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800
                        @else bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 @endif">
                        {{ $log->acao }}
                    </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300 font-mono">
                    {{ $log->tabela_alterada }}
                    @if($log->registro_id) <span class="text-gray-400 ml-1">#{{ $log->registro_id }}</span> @endif
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('auditoria.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('auditoria.visualizar')))
                            <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Ver Detalhes do Log">
                                <i class="text-base ph ph-info"></i>
                            </button>
                        @else
                            <span class="text-[11px] text-gray-400">Restrito</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                    Nenhum registro de auditoria encontrado com esses filtros.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $log)
                <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 hover:border-gray-300 transition">
                    <div class="flex items-start justify-between mb-2">
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded border uppercase
                            @if($log->acao === 'criacao') bg-emerald-50 text-emerald-700 border-emerald-200
                            @elseif($log->acao === 'atualizacao') bg-blue-50 text-blue-700 border-blue-200
                            @elseif($log->acao === 'exclusao') bg-red-50 text-red-700 border-red-200
                            @else bg-gray-100 text-gray-700 border-gray-200 @endif">
                            {{ $log->acao }}
                        </span>
                        <span class="text-[11px] font-mono text-gray-400">#{{ $log->id }}</span>
                    </div>
                    
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate font-mono">{{ $log->tabela_alterada }}</div>
                    <div class="text-xs text-gray-500 mb-3">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                    
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="text-xs text-gray-600 dark:text-gray-300 truncate max-w-[140px]">
                            {{ $log->usuario_nome ?? 'Sistema' }}
                        </div>
                        @if(feature('auditoria.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('auditoria.visualizar')))
                            <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded transition-colors">
                                <i class="text-base ph ph-info"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>
</div>