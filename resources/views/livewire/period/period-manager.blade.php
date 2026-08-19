<div class="p-6 max-w-7xl mx-auto font-sans relative">

    {{-- CABEÇALHO UNIFICADO --}}
    <x-page-header 
        title="Gerenciamento de Ciclos (Semestres)" 
        icon="ph ph-calendar-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="actions">
            <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                <i class="ph ph-plus text-lg"></i> Novo Ciclo
            </button>
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
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                        <span class="text-[10px] font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                            <i class="text-lg ph ph-info"></i>
                        </button>
                        
                        <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Detalhes e Inscrições">
                            <i class="text-lg ph ph-eye"></i>
                        </a>

                        <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar Ciclo e Campos" onclick="confirm('Deseja realmente criar uma cópia exata deste ciclo, incluindo todos os campos do formulário?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-copy"></i>
                        </button>
                        
                        <button wire:click="abrirModal({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Ciclo">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </button>
                        
                        <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Formulário (Perguntas)">
                            <i class="text-lg ph ph-list-dashes"></i>
                        </a>

                        <a href="{{ route('ciclos.regras', ['id' => $ciclo->id, 'slug' => $ciclo->slug]) }}" class="p-1.5 text-yellow-600 transition-colors rounded hover:bg-yellow-50 dark:hover:bg-gray-600" title="Regras de Pontuação">
                            <i class="text-lg ph ph-star"></i>
                        </a>

                        <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Ciclo" onclick="confirm('Excluir permanentemente este ciclo do sistema?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
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
                        <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>
                            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Detalhes e Inscrições">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                            <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar Ciclo e Campos" onclick="confirm('Deseja realmente criar uma cópia exata deste ciclo?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-copy"></i>
                            </button>
                            <button wire:click="abrirModal({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Ciclo">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                            <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Formulário (Perguntas)">
                                <i class="text-lg ph ph-list-dashes"></i>
                            </a>
                            <a href="{{ route('ciclos.regras', $ciclo->id) }}" class="p-1.5 text-yellow-600 transition-colors rounded hover:bg-yellow-50 dark:hover:bg-gray-600" title="Regras de Pontuação">
                                <i class="text-lg ph ph-star"></i>
                            </a>
                            <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Ciclo" onclick="confirm('Excluir permanentemente este ciclo do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Corrigido com Padrão do Sistema -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $cicloId ? 'Editar Ciclo' : 'Novo Ciclo' }}
                    </h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome de Exibição (Ex: Processo Seletivo 2026)</label>
                            <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1">
                                <i class="ph-fill ph-info"></i> Se em branco, o sistema gerará automaticamente.
                            </p>
                            @error('nome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Ano</label>
                                <input type="number" wire:model="ano" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('ano') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Semestre</label>
                                <select wire:model="semestre" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="1">1º Semestre</option>
                                    <option value="2">2º Semestre</option>
                                </select>
                                @error('semestre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Data/Hora Abertura</label>
                                <input type="datetime-local" wire:model="data_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('data_inicio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Data/Hora Encerramento</label>
                                <input type="datetime-local" wire:model="data_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('data_fim') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Seleção de Cursos do Processo Seletivo -->
                        <div class="col-span-1 md:col-span-2 pt-4 mt-2 border-t border-gray-100 dark:border-gray-700 mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos ofertados neste Ciclo
                            </label>
                            <div class="grid grid-cols-1 gap-2 p-3 border border-gray-200 rounded-lg sm:grid-cols-2 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-48 overflow-y-auto">
                                @forelse($cursosDisponiveis as $curso)
                                    <label class="flex items-center gap-2 p-2 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700">
                                        <input type="checkbox" wire:model="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                        <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300" title="{{ $curso->nome }}">
                                            {{ $curso->nome }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500 col-span-full dark:text-gray-400">Nenhum curso ativo encontrado. Cadastre-os primeiro.</p>
                                @endforelse
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Apenas os cursos marcados acima aparecerão no formulário público de inscrição deste semestre.</p>
                        </div>

                        <!-- Seleção de Status do CRM -->
                        <div class="col-span-1 md:col-span-2 pt-4 mt-2 border-t border-gray-100 dark:border-gray-700 mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                <i class="ph ph-funnel text-purpura-500"></i> Funil de Status (CRM)
                            </label>
                            <div class="grid grid-cols-1 gap-2 p-3 border border-gray-200 rounded-lg sm:grid-cols-2 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-48 overflow-y-auto">
                                @forelse($statusDisponiveis as $st)
                                    <label class="flex items-center gap-2 p-2 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700">
                                        <input type="checkbox" wire:model="statusSelecionados" value="{{ $st->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                        <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $st->nome }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500">Nenhum status cadastrado.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">
                                Ativar este ciclo imediatamente
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 ml-7">Isso desativará outros ciclos para evitar conflitos no formulário.</p>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="fecharModal" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Ciclo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>