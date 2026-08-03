<div class="p-6 max-w-7xl mx-auto font-sans">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-4 rounded-md text-pistache-100 bg-pistache-500">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-calendar-check text-purpura-500"></i> Gerenciamento de Ciclos (Semestres)
        </h2>
        <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus text-lg"></i> Novo Ciclo
        </button>
    </div>

    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $ciclo)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $ciclo->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $ciclo->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $ciclo->data_inicio->format('d/m/Y H:i') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $ciclo->data_fim->format('d/m/Y H:i') }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 text-xs font-bold text-purpura-700 bg-purpura-100 rounded-full dark:bg-purpura-900/30 dark:text-purpura-400">
                        {{ $ciclo->inscricoes_count ?? 0 }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />

                    <div class="text-[10px] mt-1 font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600 dark:hover:text-purpura-400" title="Visualização Rápida">
                            <i class="text-lg ph ph-dots-three-outline-vertical"></i>
                        </button>
                        <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-2 text-purpura-700 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50" title="Ver Detalhes e Inscrições">
                            <i class="text-xl ph ph-arrow-square-out"></i>
                        </a>
                        
                        <button wire:click="abrirModal({{ $ciclo->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Ciclo">
                            <i class="text-xl ph ph-pencil-simple"></i>
                        </button>
                        
                        <a href="{{ route('ciclos.campos', $ciclo->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Formulário (Perguntas)">
                            <i class="text-xl ph ph-list-dashes"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhum ciclo encontrado.</p>
                    <p class="text-sm">Ajuste os filtros ou crie um novo ciclo.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ( $registros as $ciclo )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $ciclo->nome }}</div>
                        <span class="px-2 py-1 text-xs font-bold text-white bg-purpura-500 rounded-full">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Abertura: {{ $ciclo->data_inicio->format('d/m/Y H:i') }}<br>
                        Encerramento: {{ $ciclo->data_fim->format('d/m/Y H:i') }}
                    </div>
                    <div class="mb-4 text-xs font-bold text-purpura-600 dark:text-purpura-400 flex items-center gap-1">
                        <i class="ph-fill ph-users"></i> {{ $ciclo->inscricoes_count ?? 0 }} inscrições registradas
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                        <div class="flex items-center gap-2">
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600 dark:hover:text-purpura-400" title="Visualização Rápida">
                                <i class="text-lg ph ph-dots-three-outline-vertical"></i>
                            </button>
                            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-2 text-purpura-700 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50" title="Ver Detalhes e Inscrições">
                                <i class="text-lg ph ph-arrow-square-out"></i>
                            </a>
                            <button wire:click="abrirModal({{ $ciclo->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Ciclo">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                            <a href="{{ route('ciclos.campos', $ciclo->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Formulário (Perguntas)">
                                <i class="text-lg ph ph-list-dashes"></i>
                            </a>
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