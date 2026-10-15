<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-toggle-right text-purpura-500"></i> Gerenciamento de Features
        </h2>
        <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus text-lg"></i> Nova Feature
        </button>
    </div>

    <!-- Filtros -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-magnifying-glass text-purpura-500"></i> Palavra-chave
            </label>
            <input type="text" wire:model.live.debounce.300ms="filtro_keyword" placeholder="Buscar por nome ou descrição..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 text-sm focus:ring-purpura-500 focus:border-purpura-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-squares-four text-purpura-500"></i> Módulo
            </label>
            <select wire:model.live="filtro_module" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Módulos</option>
                @foreach($modulosDisponiveis as $modulo) 
                    <option value="{{ $modulo }}">{{ ucfirst($modulo) }}</option> 
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-toggle-left text-purpura-500"></i> Status
            </label>
            <select wire:model.live="filtro_status" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos</option>
                <option value="1">Apenas Ativos</option>
                <option value="0">Apenas Inativos</option>
            </select>
        </div>
    </div>

    <!-- Tabela Matriz Compacta -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-32">Módulo</th>
                        <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-40">Submódulo</th>
                        <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider min-w-[150px]">Descrição (Agrupada)</th>
                        <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Features (Ações e Toggles)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @php
                        $grouped = [];
                        foreach($registros as $feature) {
                            $parts = explode('.', $feature->name);
                            $action = array_pop($parts); 
                            
                            $prefix = strtolower($feature->module) . '.';
                            $subStr = str_starts_with($feature->name, $prefix) ? substr($feature->name, strlen($prefix)) : $feature->name;
                            $subParts = explode('.', $subStr);
                            array_pop($subParts);
                            
                            $subName = count($subParts) > 0 ? implode(' ', $subParts) : 'Geral';
                            $feature->action_label = $action;
                            $grouped[$feature->module][$subName][] = $feature;
                        }
                    @endphp

                    @forelse($grouped as $module => $submodules)
                        @foreach($submodules as $subName => $features)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3 text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider align-middle">
                                    {{ $module ?: 'GERAL' }}
                                </td>
                                
                                <td class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-300 capitalize align-middle">
                                    {{ $subName }}
                                </td>
                                
                                <td class="px-4 py-3 text-[10px] text-gray-500 dark:text-gray-400 max-w-[200px] truncate align-middle" title="{{ $features[0]->description }}">
                                    {{ $features[0]->description }}
                                </td>
                                
                                <td class="px-4 py-2 align-middle">
                                    <div class="flex items-center justify-end gap-6 h-full">
                                        @foreach($features as $f)
                                            <div class="flex flex-col items-center justify-center gap-1" title="{{ $f->description }}">
                                                <span class="text-[9px] font-bold text-gray-500 capitalize tracking-wider">{{ $f->action_label }}</span>
                                                
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <!-- TOGGLE ATIVAR/DESATIVAR -->
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" class="sr-only peer" @if($f->is_active) checked @endif wire:click="toggleStatus({{ $f->id }})">
                                                        <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                                    </label>

                                                    <!-- AÇÕES DA FEATURE -->
                                                    <div class="flex items-center gap-1">
                                                        <button wire:click="abrirModal({{ $f->id }})" class="text-blue-500 hover:text-blue-700 transition" title="Editar"><i class="ph-bold ph-pencil-simple text-[11px]"></i></button>
                                                        <button wire:click="excluir({{ $f->id }})" onclick="confirm('Excluir feature permanentemente?') || event.stopImmediatePropagation()" class="text-red-500 hover:text-red-700 transition" title="Excluir"><i class="ph-bold ph-trash text-[11px]"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-gray-500 bg-gray-50/30 dark:bg-gray-900/30">
                                <i class="ph-fill ph-toggle-left text-3xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-400">Nenhuma Feature encontrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $registros->links() }}
        </div>
    </div>

    <!-- MODAIS E COMPONENTES OCULTOS -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
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
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="ignorarCriacaoAusentes"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="relative z-[60] inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-full dark:bg-yellow-900/30">
                            <i class="text-2xl text-yellow-600 ph-fill ph-warning-circle dark:text-yellow-500"></i>
                        </div>
                        <div class="mt-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Correspondência não encontrada</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                O sistema não encontrou as seguintes permissões para atualizar:
                            </p>
                            <ul class="mt-3 space-y-1 text-sm font-bold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-3 rounded-md border border-gray-100 dark:border-gray-700">
                                @foreach($pendenciasReplicacao as $pendencia)
                                    <li class="flex items-center gap-2"><i class="text-red-500 ph-bold ph-x"></i> {{ $pendencia }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Deseja criar os cadastros ausentes automaticamente no ACL agora?</p>
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