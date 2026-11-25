<div class="w-full relative font-sans"
     x-data="{ modalAberto: @entangle('modalAberto') }"
     x-effect="document.body.classList.toggle('overflow-hidden', modalAberto)">

    <x-page-header
        title="Escolas"
        subtitle="Cadastro de escolas estaduais e municipais por região"
        icon="ph ph-buildings"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.criar'))
                <button type="button" wire:click="abrirModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Nova Escola
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

        <x-slot name="search">
            <div class="relative w-full sm:w-72 shrink-0">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="filtro_busca"
                       placeholder="Buscar por nome ou região..."
                       class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        <x-slot name="filters">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Tipo:</span>
                    <select wire:model.live="filtro_tipo" class="border-none shadow-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="estadual">Estadual</option>
                        <option value="municipal">Municipal</option>
                    </select>
                </div>

                @if($regioesDisponiveis->isNotEmpty())
                    <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                        <span class="text-[11px] text-gray-400 mr-1.5">Região:</span>
                        <select wire:model.live="filtro_regiao" class="border-none shadow-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                            <option value="">Todas</option>
                            @foreach($regioesDisponiveis as $reg)
                                <option value="{{ $reg }}">{{ $reg }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Status:</span>
                    <select wire:model.live="filtro_status" class="border-none shadow-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="1">Ativas</option>
                        <option value="0">Inativas</option>
                    </select>
                </div>

                @if($filtro_busca !== '' || $filtro_tipo !== '' || $filtro_regiao !== '' || $filtro_status !== '')
                    <button type="button" wire:click="limparFiltros" class="h-9 px-2.5 text-xs font-medium text-gray-500 hover:text-gray-900 transition">
                        Limpar
                    </button>
                @endif
            </div>
        </x-slot>

        @forelse($registros as $escola)
            <tr wire:key="escola-row-{{ $escola->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                <td class="px-4 py-2.5 text-xs font-mono text-gray-400 whitespace-nowrap">
                    #{{ str_pad($escola->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $escola->nome }}
                </td>

                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $escola->tipo === 'estadual' ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-800' : 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                        {{ ucfirst($escola->tipo) }}
                    </span>
                </td>

                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                    {{ $escola->regiao }}
                </td>

                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.editar'))
                        <div class="inline-flex items-center gap-2">
                            <x-toggle :status="$escola->status" action="toggleStatus({{ $escola->id }})" />
                            <span class="text-[11px] font-medium {{ $escola->status ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                {{ $escola->status ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $escola->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                            {{ $escola->status ? 'Ativa' : 'Inativa' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-1">
                        @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.editar'))
                            <button type="button" wire:click="abrirModal({{ $escola->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 rounded transition" title="Editar">
                                <i class="ph ph-pencil-simple text-base"></i>
                            </button>
                        @endif

                        @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.excluir'))
                            <button type="button" wire:click="excluir({{ $escola->id }})" wire:confirm="Excluir esta escola permanentemente?" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 rounded transition" title="Excluir">
                                <i class="ph ph-trash text-base"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-xs text-gray-500">
                    Nenhuma escola encontrada.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $escola)
                <div wire:key="escola-card-{{ $escola->id }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded border {{ $escola->tipo === 'estadual' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                            {{ ucfirst($escola->tipo) }}
                        </span>
                        <span class="text-[11px] font-mono text-gray-400">#{{ str_pad($escola->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $escola->nome }}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Região: {{ $escola->regiao }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 mt-auto border-t border-gray-100 dark:border-gray-700">
                        <span class="text-[11px] font-medium {{ $escola->status ? 'text-emerald-700' : 'text-gray-400' }}">
                            {{ $escola->status ? 'Ativa' : 'Inativa' }}
                        </span>
                        <div class="flex items-center gap-1">
                            @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.editar'))
                                <button type="button" wire:click="abrirModal({{ $escola->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded transition"><i class="ph ph-pencil-simple text-base"></i></button>
                            @endif
                            @if(auth()->user()->hasRole('dev') || auth()->user()->can('escola.excluir'))
                                <button type="button" wire:click="excluir({{ $escola->id }})" wire:confirm="Excluir esta escola permanentemente?" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition"><i class="ph ph-trash text-base"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL DE CADASTRO / EDIÇÃO --}}
    @if($modalAberto)
        <template x-teleport="body">
            <div class="fixed inset-0 z-[160] flex items-center justify-center bg-black/50 p-4 font-sans"
                 @keydown.escape.window="$wire.fecharModal()"
                 role="dialog"
                 aria-modal="true">
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-md overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $escolaId ? 'Editar Escola' : 'Nova Escola' }}
                        </h3>
                        <button type="button" wire:click="fecharModal" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1">
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="salvar" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nome da Escola <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="nome" placeholder="Ex: E.E. Prof. João da Silva" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm py-2 px-3">
                            @error('nome') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tipo <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="tipo" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm py-2 px-3">
                                    <option value="estadual">Estadual</option>
                                    <option value="municipal">Municipal</option>
                                </select>
                                @error('tipo') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Região <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="regiao" placeholder="Ex: Zona Sul" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm py-2 px-3">
                                @error('regiao') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <label class="flex items-center gap-2 pt-1 cursor-pointer select-none">
                            <input type="checkbox" wire:model="status" class="w-4 h-4 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Escola ativa</span>
                        </label>

                        <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                            <button type="button" wire:click="fecharModal" class="px-3.5 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold transition">
                                {{ $escolaId ? 'Salvar Alterações' : 'Cadastrar Escola' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif
</div>