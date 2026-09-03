<div class="p-6 max-w-7xl mx-auto font-sans relative">

    {{-- CABEÇALHO UNIFICADO --}}
    <x-page-header 
        title="Gerenciamento de Ciclos (Semestres)" 
        icon="ph ph-calendar-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="actions">
            @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Ciclo
                </button>
            @endif
        </x-slot>

        <x-slot name="filters">
            <div class="flex gap-2">
                <select wire:model.live="filtro_ano" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todos os Anos</option>
                    @if(isset($anosDisponiveis))
                        @foreach($anosDisponiveis as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    @endif
                </select>
                
                <select wire:model.live="filtro_semestre" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Semestre...</option>
                    <option value="1">1º Semestre</option>
                    <option value="2">2º Semestre</option>
                </select>

                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todos os Status</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>

                @if($filtro_ano !== '' || $filtro_semestre !== '' || $filtro_status !== '')
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

        @forelse ($registros as $ciclo)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $ciclo->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $ciclo->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ $ciclo->data_inicio->format('d/m/Y H:i') }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ $ciclo->data_fim->format('d/m/Y H:i') }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    <span class="px-3 py-1 text-[10px] font-bold text-purpura-700 bg-purpura-100 rounded-full dark:bg-purpura-900/30 dark:text-purpura-400 uppercase tracking-wider border border-purpura-200">
                        {{ $ciclo->inscricoes_count ?? 0 }} INSCRIÇÕES
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                            <span class="text-[10px] font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        </div>
                    @else
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $ciclo->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                            {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('ciclo.quick-view'))
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida"><i class="text-lg ph ph-info"></i></button>
                        @endif
                        @if(feature('ciclo.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.visualizar')))
                            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Detalhes e Inscrições"><i class="text-lg ph ph-eye"></i></a>
                        @endif

                        @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                            <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar Ciclo e Campos"><i class="text-lg ph ph-copy"></i></button>
                        @endif
                        
                        @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                            <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Ciclo Completo"><i class="text-lg ph ph-pencil-simple"></i></a>
                            <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Formulário"><i class="text-lg ph ph-list-dashes"></i></a>
                            <a href="{{ route('ciclos.regras', ['id' => $ciclo->id, 'slug' => $ciclo->slug]) }}" class="p-1.5 text-yellow-600 transition-colors rounded-lg hover:bg-yellow-50 dark:hover:bg-gray-600" title="Regras de Pontuação"><i class="text-lg ph ph-star"></i></a>
                        @endif

                        @if(feature('ciclo.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.excluir')))
                            <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Ciclo" onclick="confirm('Excluir permanentemente este ciclo do sistema?')"><i class="text-lg ph ph-trash"></i></button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum ciclo encontrado.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou crie um novo ciclo.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO EM GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $ciclo )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $ciclo->nome }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-white bg-purpura-500 rounded-full">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <span class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Abertura:</span> {{ $ciclo->data_inicio->format('d/m/Y H:i') }}<br>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 mt-2 mb-1">Encerramento:</span> {{ $ciclo->data_fim->format('d/m/Y H:i') }}
                    </div>
                    <div class="mb-4 text-xs font-bold text-purpura-600 dark:text-purpura-400 flex items-center gap-1">
                        <i class="ph-fill ph-users"></i> {{ $ciclo->inscricoes_count ?? 0 }} inscrições registradas
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                            @else
                                <span class="px-2 py-1 text-[9px] font-bold uppercase tracking-wider rounded-full border {{ $ciclo->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                    {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-info"></i></button>
                            
                            @if(feature('ciclo.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.visualizar')))
                                <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-eye"></i></a>
                            @endif

                            @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                                <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-emerald-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-copy"></i></button>
                            @endif

                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-pencil-simple"></i></a>
                                <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-list-dashes"></i></a>
                                <a href="{{ route('ciclos.regras', $ciclo->id) }}" class="p-1.5 text-yellow-600 transition-colors rounded-lg hover:bg-yellow-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-star"></i></a>
                            @endif

                            @if(feature('ciclo.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.excluir')))
                                <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 dark:hover:bg-gray-600"><i class="text-lg ph ph-trash"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>
    
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">Novo Ciclo</h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome de Exibição (Ex: Processo Seletivo 2026)</label>
                            <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Ano</label>
                                <input type="number" wire:model="ano" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Semestre</label>
                                <select wire:model="semestre" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="1">1º Semestre</option>
                                    <option value="2">2º Semestre</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Abertura</label>
                                <input type="datetime-local" wire:model="data_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Encerramento</label>
                                <input type="datetime-local" wire:model="data_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">Ativar este ciclo imediatamente</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('modalAberto', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-6 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700">Criar Ciclo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>