<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header title="Categorias de Conteúdo" icon="ph ph-tag" badge="Portal">
        
        <x-slot name="actions">
            <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold text-sm">
                <i class="ph ph-plus text-lg"></i> Nova Categoria
            </button>
        </x-slot>

        <x-slot name="filters">
            <div class="flex gap-2">
                <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar categoria..." class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                @if($filtro_busca !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <i class="ph-bold ph-x"></i>
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

        @forelse($registros as $categoria)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $categoria->id }}
                </td>
                <td class="px-4 py-3">
                    <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $categoria->nome }}</div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">{{ $categoria->conteudos_count }} conteúdos vinculados</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2 cursor-pointer" wire:click="toggleStatus({{ $categoria->id }})">
                        <div class="relative inline-flex items-center h-5 rounded-full w-9 transition-colors {{ $categoria->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="inline-block w-3.5 h-3.5 transform bg-white rounded-full transition-transform {{ $categoria->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </div>
                        <span class="text-[10px] font-bold {{ $categoria->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $categoria->is_active ? 'ATIVA' : 'INATIVA' }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="edit({{ $categoria->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="excluir({{ $categoria->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Tem certeza que deseja excluir esta categoria permanentemente?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="ph-fill ph-tag text-3xl mb-2 text-gray-300 dark:text-gray-600"></i><br>
                    Nenhuma categoria encontrada.
                </td>
            </tr>
        @endforelse
    </x-table>

    @if($modalAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-tag text-purpura-600"></i> {{ $isEditMode ? 'Editar Categoria' : 'Nova Categoria' }}
                    </h3>
                    <button wire:click="$set('modalAberto', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <form wire:submit.prevent="salvar" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1 dark:text-gray-300">Nome da Categoria <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="nome" placeholder="Ex: Notícias, Eventos, Dicas..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm" required>
                        @error('nome') <span class="text-red-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-5 mt-2 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('modalAberto', false)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">
                            {{ $isEditMode ? 'Atualizar Categoria' : 'Criar Categoria' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>