<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Gerenciamento de Unidades" 
        icon="ph ph-buildings"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        @if(feature('unidade.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Nova Unidade
                </button>
            </x-slot>
        @endif

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        {{-- VISÃO EM LISTA --}}
        @forelse($registros as $unidade)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $unidade->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $unidade->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $unidade->endereco }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-gray-300">{{ $unidade->email ?: 'Sem e-mail' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $unidade->telefone ?: 'Sem telefone' }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$unidade->status === 'Ativa'" action="toggleStatus({{ $unidade->id }})" />
                        <span class="text-[10px] font-bold {{ $unidade->status === 'Ativa' ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $unidade->status === 'Ativa' ? 'ATIVA' : 'INATIVA' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('unidade.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.visualizar')))
                            <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>
                            
                            <a href="{{ route('unidades.show', $unidade->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Página Completa">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                        @endif

                        @if(feature('unidade.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.editar')))
                            <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif

                        @if(feature('unidade.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.excluir')))
                            <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Unidade" onclick="confirm('Excluir permanentemente esta unidade do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhuma unidade cadastrada.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou crie uma nova unidade.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO EM GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $unidade )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $unidade->nome }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $unidade->id }}</span>
                    </div>
                    
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        <i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $unidade->cidade }}/{{ $unidade->estado }}<br>
                        <i class="ph-fill ph-envelope-simple text-gray-400"></i> {{ $unidade->email ?: 'Sem e-mail' }}
                    </div>

                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            @if(feature('unidade.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.editar')))
                                <x-toggle :status="$unidade->status === 'Ativa'" action="toggleStatus({{ $unidade->id }})" />
                                <div class="text-[10px] mt-1 font-bold {{ $unidade->status === 'Ativa' ? 'text-green-600' : 'text-gray-500' }}">
                                    {{ $unidade->status === 'Ativa' ? 'ATIVA' : 'INATIVA' }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-1">
                            @if(feature('unidade.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.visualizar')))
                                <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                    <i class="text-lg ph ph-info"></i>
                                </button>
                                <a href="{{ route('unidades.show', $unidade->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Página Completa">
                                    <i class="text-lg ph ph-eye"></i>
                                </a>
                            @endif

                            @if(feature('unidade.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.editar')))
                                <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endif

                            @if(feature('unidade.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('unidade.excluir')))
                                <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir esta unidade?') || event.stopImmediatePropagation()" title="Excluir">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="openModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Unidade' : 'Nova Unidade' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome da Unidade <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            
                            <!-- Seção de Endereço -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:col-span-2 p-4 border border-gray-100 rounded-xl bg-gray-50/50 dark:bg-gray-900/30 dark:border-gray-700">
                                
                                <div class="col-span-1 md:col-span-12 mb-1">
                                    <h4 class="text-sm font-bold text-purpura-600 dark:text-purpura-400"><i class="ph ph-map-pin"></i> Localização</h4>
                                </div>

                                <div class="col-span-1 md:col-span-3">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">CEP <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="cep" x-mask="99999-999" placeholder="00000-000" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('cep') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-1 md:col-span-7">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Logradouro / Rua</label>
                                    <input type="text" wire:model="logradouro" readonly class="w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Número <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="numero" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('numero') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-1 md:col-span-4">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Complemento</label>
                                    <input type="text" wire:model="complemento" placeholder="Opcional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div class="col-span-1 md:col-span-4">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Bairro</label>
                                    <input type="text" wire:model="bairro" readonly class="w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                                </div>

                                <div class="col-span-1 md:col-span-3">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Cidade</label>
                                    <input type="text" wire:model="cidade" readonly class="w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                                </div>

                                <div class="col-span-1 md:col-span-1">
                                    <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">UF</label>
                                    <input type="text" wire:model="estado" readonly class="w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                                </div>
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">E-mail Corporativo</label>
                                <input type="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Telefone</label>
                                <input type="text" wire:model="telefone" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model="status" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="Ativa">Ativa</option>
                                    <option value="Inativa">Inativa</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Data de Inauguração</label>
                                <input type="date" wire:model="data_inauguracao" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <!-- Seção de Relacionamento: Cursos da Unidade -->
                            <div class="col-span-1 pt-4 mt-2 border-t border-gray-100 md:col-span-2 dark:border-gray-700">
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos oferecidos nesta unidade
                                </label>
                                <div class="grid grid-cols-1 gap-2 p-4 border border-gray-200 rounded-lg sm:grid-cols-2 lg:grid-cols-3 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-48 overflow-y-auto">
                                    @forelse($cursosDisponiveis as $curso)
                                        <label class="flex items-center gap-2 p-2 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700">
                                            <input type="checkbox" wire:model="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300" title="{{ $curso->nome }}">
                                                {{ $curso->nome }}
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-500 col-span-full dark:text-gray-400">Nenhum curso ativo cadastrado no sistema.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Unidade</button>
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