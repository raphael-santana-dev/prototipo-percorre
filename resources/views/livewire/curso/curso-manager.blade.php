<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Cursos" 
        subtitle="Gerencie o catálogo de cursos e defina em quais unidades e turnos cada curso é ofertado"
        icon="ph ph-graduation-cap"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm flex items-center gap-2">
                <i class="ph-bold ph-plus"></i> Novo Curso
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

        <x-slot name="search">
            <div class="relative w-full sm:w-72 shrink-0">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.400ms="filtroBusca" placeholder="Buscar curso por nome..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        @forelse ($registros as $curso)
            @php
                $isAtivo = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo'], true);
            @endphp
            <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50" wire:key="curso-row-{{ $curso->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                    #{{ str_pad($curso->id, 2, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $curso->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                        <span>Slug: {{ $curso->slug }}</span>
                        @if($curso->permite_estado_diferente)
                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Aceita outra UF</span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <button type="button" wire:click="showQuickView({{ $curso->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-purpura-50 hover:text-purpura-700 border border-gray-200 transition">
                        <i class="ph-fill ph-buildings text-purpura-600"></i>
                        <span>{{ $curso->unidades_count ?? 0 }} {{ ($curso->unidades_count ?? 0) === 1 ? 'unidade' : 'unidades' }}</span>
                    </button>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$isAtivo" action="toggleStatus({{ $curso->id }})" />
                        <span class="text-[10px] font-extrabold {{ $isAtivo ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $isAtivo ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700" title="Visualização Rápida">
                            <i class="text-lg ph ph-info"></i>
                        </button>
                        <a href="{{ route('cursos.show', $curso->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-700" title="Página Completa">
                            <i class="text-lg ph ph-eye"></i>
                        </a>
                        <button wire:click="edit({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700" title="Editar Curso">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700" title="Excluir" onclick="confirm('Tem certeza que deseja excluir este curso?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhum curso encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ($registros as $curso)
                @php $isAtivoCard = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo'], true); @endphp
                <div class="flex flex-col p-5 bg-white border border-gray-200/80 shadow-2xs rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:border-purpura-300 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate pr-2">{{ $curso->nome }}</div>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300">#{{ $curso->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 space-y-1">
                        <div><i class="ph-fill ph-buildings text-purpura-500"></i> {{ $curso->unidades_count ?? 0 }} unidade(s)</div>
                        <div>
                            <i class="ph-fill {{ $curso->permite_estado_diferente ? 'ph-check-circle text-emerald-500' : 'ph-map-pin text-gray-400' }}"></i>
                            {{ $curso->permite_estado_diferente ? 'Aceita alunos de outra UF' : 'Apenas UF local' }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$isAtivoCard" action="toggleStatus({{ $curso->id }})" />
                            <span class="text-[10px] font-extrabold {{ $isAtivoCard ? 'text-emerald-600' : 'text-gray-400' }}">{{ $isAtivoCard ? 'ATIVO' : 'INATIVO' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 rounded-lg"><i class="text-lg ph ph-info"></i></button>
                            <button wire:click="edit({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg"><i class="text-lg ph ph-pencil-simple"></i></button>
                            <button wire:click="delete({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg" onclick="confirm('Excluir este curso permanentemente?') || event.stopImmediatePropagation()"><i class="text-lg ph ph-trash"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL MINIMALISTA DE CURSO E MATRIZ UNIDADE -> TURNO --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]" @click.away="closeModal">
                
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 flex items-center justify-center border border-purpura-100 dark:border-purpura-800">
                            <i class="ph-fill ph-graduation-cap text-lg"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ $isEditMode ? 'Editar Curso' : 'Novo Curso' }}</h3>
                            <p class="text-xs text-gray-500">Defina o nome do curso e os turnos habilitados em cada unidade.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1.5 rounded-lg transition">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <form wire:submit="save" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                        <div class="md:col-span-8">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Nome do Curso <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Administração Financeira" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-purpura-500">
                            @error('nome') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Status</label>
                            <select wire:model="status" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Matriz Contextual: 1. Unidades -> 2. Turnos do Curso naquela Unidade -->
                    <div class="space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-1.5">
                                    <i class="ph-fill ph-tree-structure text-purpura-600 text-sm"></i> Estrutura de Oferta (Unidade → Turnos)
                                </h4>
                                <p class="text-[11px] text-gray-500">
                                    Clique em uma unidade à esquerda e marque os turnos em que este curso ocorre naquela unidade.
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purpura-50 text-purpura-700 border border-purpura-200">
                                {{ count($unidadesSelecionadas) }} unidade(s) • {{ count($turnosSelecionados) }} turno(s)
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 h-[290px] border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                            
                            <!-- COLUNA 1: UNIDADES -->
                            <div class="flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase tracking-wider text-gray-500 pl-3">
                                    1. Unidades Ofertantes
                                </div>
                                <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                    @forelse($unidadesDisponiveis as $unidade)
                                        @php
                                            $isUnidadeAtiva = (int) $activeUnidadeId === (int) $unidade->id;
                                            $qtdTurnosUni = $this->contarTurnosNaUnidade($unidade->id);
                                        @endphp
                                        <div wire:key="curso-modal-uni-{{ $unidade->id }}"
                                             wire:click="setActiveUnidade({{ $unidade->id }})"
                                             class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition {{ $isUnidadeAtiva ? 'bg-purpura-50/90 dark:bg-purpura-900/30 ring-1 ring-purpura-300 dark:ring-purpura-700' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                                            <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                                <input type="checkbox"
                                                       wire:model.live="unidadesSelecionadas"
                                                       value="{{ $unidade->id }}"
                                                       wire:change="setActiveUnidade({{ $unidade->id }})"
                                                       class="w-4 h-4 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                                                <span class="text-xs font-bold truncate {{ $isUnidadeAtiva ? 'text-purpura-800 dark:text-purpura-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                    {{ $unidade->nome }}
                                                </span>
                                            </label>

                                            <div class="flex items-center gap-2 shrink-0">
                                                @if($qtdTurnosUni > 0)
                                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {{ $qtdTurnosUni }} {{ $qtdTurnosUni === 1 ? 'turno' : 'turnos' }}
                                                    </span>
                                                @endif
                                                <i class="ph-bold ph-caret-right text-sm {{ $isUnidadeAtiva ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-6 text-center text-xs text-gray-400">Nenhuma unidade ativa.</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- COLUNA 2: TURNOS DO CURSO NA UNIDADE SELECIONADA -->
                            <div class="flex flex-col bg-gray-50/40 dark:bg-gray-900/50 overflow-hidden">
                                @php
                                    $unidadeFocada = $activeUnidadeId ? $unidadesDisponiveis->firstWhere('id', $activeUnidadeId) : null;
                                @endphp
                                <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 truncate pl-1">
                                        2. Turnos {{ $unidadeFocada ? '— ' . $unidadeFocada->nome : '' }}
                                    </span>
                                    @if($unidadeFocada && count($unidadesSelecionadas) > 1)
                                        <button type="button"
                                                wire:click="replicarTurnosParaUnidadesMarcadas"
                                                class="text-[10px] font-bold text-purpura-600 hover:underline shrink-0 pr-1"
                                                title="Copia os turnos marcados nesta unidade para todas as outras unidades selecionadas">
                                            Replicar p/ todas
                                        </button>
                                    @endif
                                </div>

                                <div class="flex-1 overflow-y-auto p-2.5 space-y-1.5 custom-scrollbar">
                                    @if($unidadeFocada)
                                        @forelse($turnosDisponiveis as $turno)
                                            @php
                                                $chaveCombo = "{$unidadeFocada->id}-{$turno->id}";
                                                $isTurnoMarcado = in_array($chaveCombo, $turnosSelecionados, true);
                                            @endphp
                                            <label wire:key="curso-modal-turno-{{ $chaveCombo }}"
                                                   class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer transition {{ $isTurnoMarcado ? 'bg-white dark:bg-gray-800 border-purpura-300 dark:border-purpura-700 shadow-2xs' : 'bg-white/60 dark:bg-gray-800/40 border-gray-200/70 dark:border-gray-700 hover:bg-white' }}">
                                                <div class="flex items-center gap-2.5">
                                                    <input type="checkbox"
                                                           wire:model.live="turnosSelecionados"
                                                           value="{{ $chaveCombo }}"
                                                           class="w-4 h-4 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $turno->nome }}</span>
                                                </div>
                                                @if($turno->horario_inicio && $turno->horario_fim)
                                                    <span class="text-[10px] font-mono text-gray-400">
                                                        {{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }} às {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}
                                                    </span>
                                                @endif
                                            </label>
                                        @empty
                                            <div class="p-6 text-center text-xs text-gray-400">Nenhum turno cadastrado.</div>
                                        @endforelse
                                    @else
                                        <div class="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                                            <i class="ph-duotone ph-buildings text-3xl mb-1.5"></i>
                                            <span class="text-xs font-semibold">Selecione uma unidade à esquerda para habilitar os turnos.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <input type="checkbox" wire:model="permite_estado_diferente" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white block">Permitir candidatos residentes em outro Estado (UF)</span>
                            <span class="text-[11px] text-gray-500 block">Se marcado, candidatos fora da UF da unidade poderão escolher este curso no formulário de inscrição.</span>
                        </div>
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="closeModal" class="btn btn--secondary btn--medium">Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                            <i class="ph-bold ph-check"></i> {{ $isEditMode ? 'Salvar Alterações' : 'Cadastrar Curso' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>