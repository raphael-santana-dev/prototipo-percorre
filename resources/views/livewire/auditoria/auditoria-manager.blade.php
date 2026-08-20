<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Auditoria de Sistema" 
        icon="ph ph-magnifying-glass"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        {{-- ÁREA DOS FILTROS (Com injeção dinâmica de Hora via Alpine.js) --}}
        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                <!-- Buscar Keyword -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-text-aa text-purpura-500"></i> Buscar
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="filtro_keyword" placeholder="Usuário, IP ou ID do Registro..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>

                <!-- Seletor de Ação -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-tag text-purpura-500"></i> Ação
                    </label>
                    <select wire:model.live="filtro_acao" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todas</option>
                        @foreach($acoesDisponiveis as $acao)
                            <option value="{{ $acao }}">{{ ucfirst($acao) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Seletor de Tabela -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-database text-purpura-500"></i> Tabela
                    </label>
                    <select wire:model.live="filtro_tabela" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todas</option>
                        @foreach($tabelasDisponiveis as $tabela)
                            <option value="{{ $tabela }}">{{ Str::limit($tabela, 15) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Data de Início (De) com Alpine.js -->
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
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-plus text-purpura-500"></i> De (Período)
                    </label>
                    <input type="datetime-local" 
                           wire:model.live="filtro_data_inicio" 
                           @focus="initZero"
                           class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>

                <!-- Data Fim (Até) com Alpine.js -->
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
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-check text-purpura-500"></i> Até (Período)
                    </label>
                    <input type="datetime-local" 
                           wire:model.live="filtro_data_fim" 
                           @focus="initEnd"
                           class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>

                @if($filtro_keyword !== '' || $filtro_acao !== '' || $filtro_tabela !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <div class="md:col-span-12 flex justify-end mt-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="limparFiltros" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-bold ph-x"></i> Limpar Filtros
                        </button>
                    </div>
                @endif

            </div>
        </x-slot>

    </x-page-header>

    {{-- Explicação de Contexto --}}
    <div class="mb-6 -mt-2 text-sm text-gray-500 dark:text-gray-400 font-medium">
        Rastreabilidade completa de alterações no banco de dados e acessos.
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
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $log->id }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-800 dark:text-white">{{ $log->usuario_nome ?? 'Sistema' }}</div>
                    <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500">{{ $log->ip ?? 'IP Desconhecido' }}</div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider
                        @if($log->acao === 'criacao') bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800
                        @elseif($log->acao === 'atualizacao') bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800
                        @elseif($log->acao === 'exclusao') bg-red-100 text-red-800 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800
                        @elseif($log->acao === 'login') bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800
                        @else bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 @endif">
                        {{ $log->acao }}
                    </span>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300 font-mono">
                    {{ $log->tabela_alterada }}
                    @if($log->registro_id) <span class="text-gray-400 ml-1 font-bold">#{{ $log->registro_id }}</span> @endif
                </td>
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Detalhes do Log">
                            <i class="text-lg ph ph-info"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum registro de auditoria encontrado com esses filtros.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $log)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full uppercase tracking-wider
                            @if($log->acao === 'criacao') bg-green-100 text-green-800 border border-green-200
                            @elseif($log->acao === 'atualizacao') bg-blue-100 text-blue-800 border border-blue-200
                            @elseif($log->acao === 'exclusao') bg-red-100 text-red-800 border border-red-200
                            @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                            {{ $log->acao }}
                        </span>
                        <span class="text-[10px] font-medium text-gray-400">#{{ $log->id }}</span>
                    </div>
                    
                    <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $log->tabela_alterada }}</div>
                    <div class="text-[10px] text-gray-500 font-bold mb-3">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                    
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="text-[10px] font-bold text-gray-500 truncate max-w-[120px] flex items-center gap-1.5">
                            <i class="ph-fill ph-user text-gray-400 text-sm"></i> {{ $log->usuario_nome ?? 'Sistema' }}
                        </div>
                        <button wire:click="showQuickView({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-purpura-500 rounded-lg dark:hover:bg-gray-600 transition-colors">
                            <i class="text-lg ph ph-info"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- TOAST SYSTEM --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>