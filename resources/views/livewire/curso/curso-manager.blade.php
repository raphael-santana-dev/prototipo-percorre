<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Cursos" 
        icon="ph ph-graduation-cap"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            @if(feature('curso.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.criar')))
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Curso
                </button>
            @endif
        </x-slot>

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $curso)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $curso->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Slug: {{ $curso->slug }}</div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-center text-sm text-gray-600 dark:text-gray-300">
                    @if($curso->min_idade || $curso->max_idade)
                        {{ $curso->min_idade ?? 'Livre' }} a {{ $curso->max_idade ?? 'Sem limite' }} anos
                    @else
                        <span class="text-gray-400">Livre</span>
                    @endif
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if(feature('curso.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.editar')))
                        <x-toggle :status="$curso->status" action="toggleStatus({{ $curso->id }})" />
                        <div class="text-[10px] mt-1 font-bold {{ $curso->status ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $curso->status ? 'ATIVO' : 'INATIVO' }}
                        </div>
                    @else
                        {{-- Se a feature estiver desligada OU ele não tiver permissão, vê apenas o emblema --}}
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $curso->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                            {{ $curso->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    @endif
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('curso.visualizar'))
                            <button wire:click="showQuickView({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>
                            
                            <a href="{{ route('cursos.show', $curso->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Página Completa">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                        @endif

                        @if(feature('curso.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.editar')))                            <button wire:click="edit({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif

                        @if(feature('curso.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.excluir')))
                            <button wire:click="delete({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Tem certeza que deseja excluir este curso?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhum curso encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ( $registros as $curso )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate pr-2">{{ $curso->nome }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $curso->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        @if($curso->min_idade || $curso->max_idade)
                            {{ $curso->min_idade ?? 'Livre' }} a {{ $curso->max_idade ?? 'Sem limite' }} anos
                        @else
                            <span class="text-gray-400">Livre</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Aceita fora do Estado?</span>
                        @if($curso->permite_estado_diferente)
                            <i class="text-lg ph-fill ph-check-circle text-pistache-500"></i>
                        @else
                            <i class="text-lg ph-fill ph-x-circle text-red-500"></i>
                        @endif
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        
                        <div>
                            @if(feature('curso.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.editar')))
                                <x-toggle :status="$curso->status" action="toggleStatus({{ $curso->id }})" />
                            @else
                                <span class="px-2 py-1 text-[9px] font-bold uppercase tracking-wider rounded-full border {{ $curso->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                    {{ $curso->status ? 'ATIVO' : 'INATIVO' }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            @if(feature('curso.visualizar'))
                                <a href="{{ route('cursos.show', $curso->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Detalhes">
                                    <i class="text-lg ph ph-eye"></i>
                                </a>
                            @endif
                            
                            @if(feature('curso.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.editar')))                                <button wire:click="abrirModal({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Informações">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endif

                            @if(feature('curso.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('curso.excluir')))
                                <button wire:click="excluir({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Curso" onclick="confirm('Excluir este curso permanentemente?') || event.stopImmediatePropagation()">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
        
    </x-table>

    <!-- Modal Integrado -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="openModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Curso' : 'Novo Curso' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome do Curso <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Idade Mínima</label>
                                <input type="number" wire:model="min_idade" placeholder="Opcional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('min_idade') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Idade Máxima</label>
                                <input type="number" wire:model="max_idade" placeholder="Opcional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('max_idade') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model="status" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="Ativo">Ativo</option>
                                    <option value="Inativo">Inativo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Relacionamentos: Unidades e Turnos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 mt-2 border-t border-gray-100 dark:border-gray-700">
                            
                            <!-- Box de Unidades -->
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-buildings text-purpura-500"></i> Unidades que ofertam
                                </label>
                                <div class="flex flex-col gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-40 overflow-y-auto">
                                    @forelse($unidadesDisponiveis as $unidade)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="unidadesSelecionadas" value="{{ $unidade->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $unidade->nome }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Nenhuma unidade ativa.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Box de Turnos -->
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-clock text-ponkan-500"></i> Turnos de aula
                                </label>
                                <div class="flex flex-col gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-40 overflow-y-auto">
                                    @forelse($turnosDisponiveis as $turno)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="turnosSelecionados" value="{{ $turno->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $turno->nome }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Nenhum turno cadastrado.</p>
                                    @endforelse
                                </div>
                            </div>

                        </div>

                        <!-- Configurações Adicionais -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" wire:model="permite_estado_diferente" id="estadoDif" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="estadoDif" class="font-bold text-gray-900 dark:text-white">Permitir alunos de outro Estado</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Marca se o curso aceita matrículas de residentes fora da UF da unidade.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Curso</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- TOAST SYSTEM --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>