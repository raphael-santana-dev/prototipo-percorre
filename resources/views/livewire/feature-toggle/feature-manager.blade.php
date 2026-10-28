<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Features" 
        icon="ph ph-toggle-right"
        badge="Sistema"
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="actions">
            <button wire:click="abrirModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                <i class="ph-bold ph-plus"></i> Nova Feature
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

        {{-- CAMPO DE BUSCA INLINE NA TABELA --}}
        <x-slot name="search">
            <div class="relative w-full">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.500ms="filtro_keyword" placeholder="Buscar feature por nome ou descrição..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition-colors dark:bg-gray-800 dark:border-gray-700">
            </div>
        </x-slot>

        {{-- FILTROS EM FORMATO CHIPS --}}
        <x-slot name="filters">
            <div class="w-full flex flex-col gap-4">
                <div wire:ignore.self x-data="{
                    visible: [],
                    allFilters: ['modulo', 'status'],
                    init() {
                        if ($wire.filtro_module && !this.visible.includes('modulo')) this.visible.push('modulo');
                        if ($wire.filtro_status !== '' && !this.visible.includes('status')) this.visible.push('status');
                    },
                    add(f) { if(!this.visible.includes(f)) this.visible.push(f); },
                    remove(f) {
                        if(f === 'modulo') $wire.set('filtro_module', '');
                        if(f === 'status') $wire.set('filtro_status', '');
                        this.visible = this.visible.filter(i => i !== f);
                    },
                    get canAddMore() { return this.visible.length < this.allFilters.length; }
                }" class="flex flex-wrap items-center gap-2">
                    
                    {{-- Chip: Módulo --}}
                    <div x-show="visible.includes('modulo')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Módulo</span>
                        <select wire:model.live="filtro_module" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Todos</option>
                            @foreach($modulosDisponiveis as $modulo) <option value="{{ $modulo }}">{{ ucfirst($modulo) }}</option> @endforeach
                        </select>
                        <button @click="remove('modulo')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                    </div>

                    {{-- Chip: Status --}}
                    <div x-show="visible.includes('status')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Status</span>
                        <select wire:model.live="filtro_status" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Todos</option>
                            <option value="1">Apenas Ativos</option>
                            <option value="0">Apenas Inativos</option>
                        </select>
                        <button @click="remove('status')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                    </div>

                    {{-- Add Filtro --}}
                    <div x-show="canAddMore" x-data="{ open: false }" class="relative ml-1 shrink-0" x-cloak>
                        <button @click="open = !open" class="text-sm font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors h-9 px-2 focus:outline-none">
                            + Adicionar Filtro
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute left-0 top-full mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 py-2">
                            <button x-show="!visible.includes('modulo')" @click="add('modulo'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-squares-four text-gray-400 text-lg"></i> Módulo</button>
                            <button x-show="!visible.includes('status')" @click="add('status'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-toggle-left text-gray-400 text-lg"></i> Status</button>
                        </div>
                    </div>

                    <button x-show="$wire.filtro_module || $wire.filtro_status !== '' || $wire.filtro_keyword" x-cloak wire:click="limparFiltros" @click="visible = []" class="text-sm font-medium text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-1 transition">
                        Limpar Todos
                    </button>
                </div>
            </div>
        </x-slot>

        {{-- CORPO DA TABELA PADRONIZADO --}}
        @forelse($registros as $feature)
            <tr wire:key="row-{{ $feature->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                <td class="px-4 py-2.5 t-label-12 text-gray-500 whitespace-nowrap">#{{ $feature->id }}</td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">
                        {{ $feature->module }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5">
                    <div class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[250px]">{{ $feature->name }}</div>
                </td>

                <td class="px-4 py-2.5">
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[300px]">{{ $feature->description ?: '-' }}</div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <!-- TOGGLE VIA COMPONENT x-toggle -->
                        <x-toggle :status="$feature->is_active" action="toggleStatus({{ $feature->id }})" />
                        
                        <span class="text-[10px] font-bold {{ $feature->is_active ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $feature->is_active ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-1">
                        <button wire:click="abrirModal({{ $feature->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                            <i class="text-lg ph-bold ph-pencil-simple"></i>
                        </button>
                        <button wire:click="excluir({{ $feature->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir esta feature permanentemente?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph-bold ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Nenhuma feature encontrada.</td></tr>
        @endforelse

        {{-- VISÃO MOBILE (GRID SLOT) --}}
        <x-slot name="gridSlot">
            @foreach($registros as $feature)
                <div wire:key="card-{{ $feature->id }}" class="card !p-4 !gap-0 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">
                            {{ $feature->module }}
                        </span>
                        <span class="text-[10px] font-bold text-gray-400">#{{ $feature->id }}</span>
                    </div>

                    <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-1">{{ $feature->name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">{{ $feature->description ?: '-' }}</p>

                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700 w-full">
                        <div class="flex items-center gap-2">
                            <!-- TOGGLE VIA COMPONENT x-toggle -->
                            <x-toggle :status="$feature->is_active" action="toggleStatus({{ $feature->id }})" />
                        </div>
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="abrirModal({{ $feature->id }})" class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md transition"><i class="text-lg ph-bold ph-pencil-simple"></i></button>
                            <button wire:click="excluir({{ $feature->id }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 rounded-md transition" onclick="confirm('Excluir feature?') || event.stopImmediatePropagation()"><i class="text-lg ph-bold ph-trash"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- MODAIS E COMPONENTES OCULTOS (MANTIDOS INTACTOS) -->
    @if($modalAberto)
        <div class="fixed inset-0 z-[120] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $featureId ? 'Editar Feature' : 'Nova Feature' }}
                    </h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div class="hidden md:grid md:grid-cols-12 md:gap-4 mb-2">
                            <div class="col-span-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Módulo <span class="text-red-500">*</span></div>
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Submódulo</div>
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ação <span class="text-red-500">*</span></div>
                            <div class="col-span-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</div>
                            <div class="col-span-1"></div>
                        </div>

                        @foreach($items as $index => $item)
                            <div class="grid grid-cols-1 gap-4 p-4 mb-4 border border-gray-200 rounded-lg md:p-0 md:border-none md:grid-cols-12 dark:border-gray-700 bg-gray-50/50 md:bg-transparent dark:bg-gray-800/50 items-start">
                                
                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Módulo *</label>
                                    <input type="text" wire:model="items.{{ $index }}.module" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: sistema">
                                    @error("items.{$index}.module") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Submódulo (Opcional)</label>
                                    <input type="text" wire:model="items.{{ $index }}.submodule" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: tema">
                                    @error("items.{$index}.submodule") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Ação *</label>
                                    <input type="text" wire:model="items.{{ $index }}.action" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ex: criar">
                                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">Chave: <strong x-text="[$wire.items[{{ $index }}].module, $wire.items[{{ $index }}].submodule, $wire.items[{{ $index }}].action].filter(Boolean).map(s => String(s).toLowerCase().trim()).join('.') || 'modulo.submodulo.acao'"></strong></p>
                                    @error("items.{$index}.action") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="md:col-span-3">
                                    <label class="block mb-1 text-sm font-semibold text-gray-700 md:hidden dark:text-gray-300">Descrição</label>
                                    <textarea wire:model="items.{{ $index }}.description" rows="2" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="O que esta feature controla?"></textarea>
                                    @error("items.{$index}.description") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex items-center justify-end md:justify-center md:col-span-1 md:mt-1">
                                    @if(count($items) > 1 && !$featureId)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-red-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-gray-700" title="Remover Linha">
                                            <i class="text-lg ph-bold ph-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if(!$featureId)
                            <div class="pt-2">
                                <button type="button" wire:click="addItem" class="flex items-center gap-2 px-3 py-1.5 text-sm font-bold text-purpura-600 transition-colors bg-purpura-100 rounded-lg hover:bg-purpura-200 dark:bg-gray-700 dark:text-purpura-400">
                                    <i class="ph-bold ph-plus"></i> Adicionar outra linha
                                </button>
                            </div>
                        @endif

                        <div class="flex items-center pt-2 mt-4 bg-purpura-50 dark:bg-gray-900/50 p-3 rounded-lg border border-purpura-100 dark:border-gray-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="replicar_para_permissoes" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Replicar em Permissões (ACL)</span>
                                    <span class="text-[10px] text-gray-500 font-medium">Cria ou atualiza uma permissão com o mesmo módulo, ação e descrição.</span>
                                </div>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="fecharModal" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Tudo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DE ALERTA DE CADASTRO AUSENTE -->
    @if($modalConfirmacaoAberto)
        <div class="fixed inset-0 z-[120] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="ignorarCriacaoAusentes"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="relative z-[120] inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-full dark:bg-yellow-900/30">
                            <i class="text-2xl text-yellow-600 ph-fill ph-warning-circle dark:text-yellow-500"></i>
                        </div>
                        <div class="mt-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Correspondência não encontrada</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                O sistema não encontrou os seguintes Feature Toggles para atualizar:
                            </p>
                            <ul class="mt-3 space-y-1 text-sm font-bold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-3 rounded-md border border-gray-100 dark:border-gray-700">
                                @foreach($pendenciasReplicacao as $pendencia)
                                    <li class="flex items-center gap-2"><i class="text-red-500 ph-bold ph-x"></i> {{ $pendencia }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Deseja criar as Features ausentes automaticamente agora?</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 sm:mt-8 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="ignorarCriacaoAusentes" class="px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Não, atualizar apenas existentes
                        </button>
                        <button type="button" wire:click="confirmarCriacaoAusentes" class="px-4 py-2 text-sm font-bold text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700">
                            Sim, criar os ausentes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>