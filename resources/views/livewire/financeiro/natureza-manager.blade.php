<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Naturezas Financeiras" 
        icon="ph ph-tag"
        badge="Módulo Financeiro"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
                <button wire:click="abrirModal" class="btn btn--primary btn--small">
                    <i class="ph ph-plus text-lg"></i> Nova Natureza
                </button>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6 mt-6">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroCodigo" placeholder="Pesquisar por Código..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroDescricao" placeholder="Pesquisar por Descrição..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <select wire:model.live="filtroDisponivel" class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Status de Disponibilidade</option>
                    <option value="1">Apenas Disponíveis</option>
                    <option value="0">Apenas Ocultas</option>
                </select>
                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white">
                    <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                </button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <x-table 
                :headers="$this->headers" 
                :registros="$registros" 
                :ordenacaoCampo="$ordenacaoCampo" 
                :ordenacaoDirecao="$ordenacaoDirecao" 
                :permiteGrid="$permiteGrid" 
                :modoExibicao="$modoExibicao">

                @forelse($registros as $natureza)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                        <td class="px-4 py-3 t-label-12 text-gray-500 dark:text-gray-400">#{{ $natureza->id }}</td>
                        <td class="px-4 py-3 text-sm font-mono font-bold text-purpura-600 dark:text-purpura-400">{{ $natureza->codigo }}</td>
                        <td class="px-4 py-3 t-body-14-semibold text-gray-900 dark:text-white">{{ $natureza->descricao }}</td>
                        <td class="px-4 py-3 text-center">
                            @if(feature('financeiro.natureza.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('financeiro.natureza.editar')))
                                <button wire:click="toggleDisponibilidade({{ $natureza->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border transition-colors {{ $natureza->disponivel_orcamento ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-red-50 hover:text-red-700 hover:border-red-200' : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200' }}">
                                    {!! $natureza->disponivel_orcamento ? '<i class="ph-bold ph-check"></i> SIM' : '<i class="ph-bold ph-x"></i> NÃO' !!}
                                </button>
                            @else
                                <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $natureza->disponivel_orcamento ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                                    {{ $natureza->disponivel_orcamento ? 'SIM' : 'NÃO' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(feature('financeiro.natureza.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('financeiro.natureza.editar')))
                                    <button wire:click="edit({{ $natureza->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                                        <i class="text-lg ph ph-pencil-simple"></i>
                                    </button>
                                @endif
                                @if(feature('financeiro.natureza.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('financeiro.natureza.excluir')))
                                    <button wire:click="excluir({{ $natureza->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Tem a certeza que deseja excluir esta Natureza permanentemente?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph ph-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="ph-fill ph-tag text-4xl mb-3 block opacity-50"></i>
                            <p>Nenhuma Natureza encontrada.</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>

    {{-- Modal de Criação / Edição --}}
    @if($modalAberto)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800" x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between mb-5 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-tag text-purpura-500"></i> {{ $isEditMode ? 'Editar Natureza' : 'Nova Natureza' }}
                    </h3>
                    <button wire:click="fecharModal" class="text-gray-400 hover:text-red-500 transition">
                        <i class="ph-bold ph-x text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="salvar" class="space-y-4">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Código Protheus <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="codigo" placeholder="Ex: D01016" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white font-mono">
                            @error('codigo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Descrição da Natureza <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="descricao" placeholder="Ex: Despesas com Pessoal" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            @error('descricao') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 mt-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="disponivel_orcamento" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700">
                            <div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">Disponível para Orçamento</span>
                                <span class="text-xs text-gray-500">Se marcado, os gestores poderão visualizar esta natureza ao elaborar orçamentos na plataforma web.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">
                            <i class="ph-bold ph-floppy-disk"></i> Guardar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif
</div>