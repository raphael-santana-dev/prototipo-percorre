<div class="p-6 max-w-[1400px] mx-auto font-sans relative">
    <x-page-header title="Gestão de Publicações" icon="ph ph-newspaper" badge="Editorial">
        
        <x-slot name="actions">
            <a href="{{ route('conteudo.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold text-sm">
                <i class="ph ph-plus text-lg"></i> Nova Publicação
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 w-full">
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar</label>
                    <input wire:model.live.debounce.400ms="filtro_busca" type="text" placeholder="Título..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </div>
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Categoria</label>
                    <select wire:model.live="filtro_categoria" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todas</option>
                        @foreach($categoriasDb as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select wire:model.live="filtro_status" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tipo / Formato</label>
                    <select wire:model.live="filtro_tipo" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        <option value="padrao">Padrão</option>
                        <option value="carrossel">Carrossel</option>
                        <option value="story">Story</option>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Público-Alvo</label>
                    <select wire:model.live="filtro_publico" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        <option value="geral">Geral (Público)</option>
                        <option value="estudantes">Estudantes</option>
                        <option value="empresas">Empresas</option>
                        <option value="interno">Colaboradores (Interno)</option>
                    </select>
                </div>
                
                @if($filtro_busca !== '' || $filtro_categoria !== '' || $filtro_status !== '' || $filtro_tipo !== '' || $filtro_publico !== '')
                    <div class="w-full flex items-end">
                        <button wire:click="limparFiltros" class="w-full px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-1 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="ph-bold ph-x"></i> Limpar
                        </button>
                    </div>
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

        @forelse($registros as $conteudo)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @php $imgCapa = $conteudo->banner_desktop ?? $conteudo->banner_interno; @endphp
                    @if($imgCapa)
                        <img src="{{ Storage::url($imgCapa) }}" alt="Capa" class="w-12 h-10 object-cover rounded shadow-sm border border-gray-200">
                    @else
                        <div class="w-12 h-10 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center border border-gray-200 dark:border-gray-700 text-gray-400">
                            <i class="ph-fill ph-image"></i>
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="font-bold text-sm text-gray-900 dark:text-white truncate max-w-xs" title="{{ $conteudo->titulo }}">{{ $conteudo->titulo }}</div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">
                        <i class="ph-fill ph-tag text-purpura-500"></i> {{ $conteudo->categoria->nome ?? 'Sem Categoria' }}
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                        {{ $conteudo->tipo }}
                    </span>
                    <div class="flex gap-1 mt-1">
                        @foreach($conteudo->publico_alvo as $pub)
                            <span class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 uppercase font-medium dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                {{ $pub }}
                            </span>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    @if(!$conteudo->data_inicio && !$conteudo->data_fim)
                        <span class="text-xs text-gray-500">Sempre visível</span>
                    @else
                        <div class="text-[10px] text-gray-600 dark:text-gray-400 font-medium">
                            <span class="block">Início: {{ $conteudo->data_inicio ? $conteudo->data_inicio->format('d/m/Y H:i') : 'Imediato' }}</span>
                            <span class="block">Fim: {{ $conteudo->data_fim ? $conteudo->data_fim->format('d/m/Y H:i') : 'Indeterminado' }}</span>
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @if($conteudo->is_destaque)
                        <button wire:click="abrirModalDestaque({{ $conteudo->id }})" class="inline-flex flex-col items-center justify-center p-1 px-2 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition-colors group">
                            <i class="ph-fill ph-star text-yellow-500 text-lg group-hover:scale-110 transition-transform"></i>
                            <span class="text-[9px] font-bold text-yellow-700">Posição {{ $conteudo->ordem_destaque }}</span>
                        </button>
                    @else
                        <button wire:click="abrirModalDestaque({{ $conteudo->id }})" class="text-gray-300 hover:text-yellow-500 transition-colors" title="Destacar na Home">
                            <i class="ph ph-star text-2xl"></i>
                        </button>
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2 cursor-pointer" wire:click="toggleStatus({{ $conteudo->id }})">
                        <div class="relative inline-flex items-center h-5 rounded-full w-9 transition-colors {{ $conteudo->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="inline-block w-3.5 h-3.5 transform bg-white rounded-full transition-transform {{ $conteudo->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </div>
                        <span class="text-[10px] font-bold {{ $conteudo->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $conteudo->is_active ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('conteudo.show', $conteudo->slug) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-green-500 hover:bg-green-50 dark:hover:bg-gray-600" title="Ver Publicação (Abre numa nova aba)">
                            <i class="text-lg ph ph-arrow-square-out"></i>
                        </a>
                        <a href="{{ route('conteudo.edit', $conteudo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </a>
                        <button wire:click="excluir({{ $conteudo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Tem a certeza que deseja excluir esta publicação permanentemente?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="ph-fill ph-newspaper text-4xl mb-2 text-gray-300 dark:text-gray-600"></i><br>
                    <span class="font-bold">Nenhum conteúdo publicado.</span><br>
                    <span class="text-xs">Inicie a criação de artigos e destaques clicando no botão acima.</span>
                </td>
            </tr>
        @endforelse
    </x-table>

    {{-- MODAL DE CONFIGURAÇÃO DE DESTAQUE --}}
    @if($modalDestaqueAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-yellow-50 dark:bg-yellow-900/20">
                    <h3 class="text-lg font-bold text-yellow-700 dark:text-yellow-400 flex items-center gap-2">
                        <i class="ph-fill ph-star"></i> Gerir Destaque
                    </h3>
                    <button wire:click="$set('modalDestaqueAberto', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-medium">Os conteúdos em destaque aparecem no carrossel superior do portal.</p>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2 dark:text-gray-300">Posição no Carrossel (Máx 5) <span class="text-red-500">*</span></label>
                        <select wire:model="ordemDestaque" class="w-full px-3 py-2 text-sm font-bold border border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="1">1º Destaque (Principal)</option>
                            <option value="2">2º Destaque</option>
                            <option value="3">3º Destaque</option>
                            <option value="4">4º Destaque</option>
                            <option value="5">5º Destaque</option>
                        </select>
                        @error('ordemDestaque') <span class="text-red-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-[10px] text-gray-400 mt-2">Nota: Se já existir um conteúdo nesta posição, ele perderá o destaque para dar lugar a este.</p>
                    </div>
                </div>
                
                <div class="flex justify-between items-center gap-3 p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <button wire:click="removerDestaque({{ $conteudoDestaqueId }})" class="text-[11px] font-bold text-red-500 hover:underline">
                        Remover Destaque
                    </button>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('modalDestaqueAberto', false)" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors">Cancelar</button>
                        <button wire:click="salvarDestaque" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-bold shadow-sm transition-colors">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>