<div class="w-full relative">

    <x-page-header 
        title="Cursos" 
        subtitle="Gerencie o catálogo de cursos e defina em quais unidades e turnos cada curso é ofertado"
        icon="ph ph-graduation-cap"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                <i class="ph ph-plus"></i> Novo Curso
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
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.400ms="filtroBusca" placeholder="Buscar curso por nome..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        @forelse ($registros as $curso)
            @php
                $isAtivo = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo'], true);
            @endphp
            <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-800/40" wire:key="curso-row-{{ $curso->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ str_pad($curso->id, 2, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ $curso->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                        <span>Slug: {{ $curso->slug }}</span>
                        @if($curso->permite_estado_diferente)
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Aceita outra UF</span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <button type="button" wire:click="showQuickView({{ $curso->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition">
                        <span>{{ $curso->unidades_count ?? $curso->unidades()->count() }} unidade(s)</span>
                    </button>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$isAtivo" action="toggleStatus({{ $curso->id }})" />
                        <span class="text-[11px] font-medium {{ $isAtivo ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                            {{ $isAtivo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Visualização Rápida">
                            <i class="text-base ph ph-info"></i>
                        </button>
                        <a href="{{ route('cursos.show', $curso->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Página Completa">
                            <i class="text-base ph ph-eye"></i>
                        </a>
                        <button wire:click="edit({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Editar Curso">
                            <i class="text-base ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $curso->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir" onclick="confirm('Tem certeza que deseja excluir este curso?') || event.stopImmediatePropagation()">
                            <i class="text-base ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-500 text-sm">
                    Nenhum curso encontrado.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ($registros as $curso)
                @php $isAtivoCard = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo'], true); @endphp
                <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 hover:border-gray-300 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate pr-2">{{ $curso->nome }}</div>
                        <span class="px-2 py-0.5 text-[10px] font-mono text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300">#{{ $curso->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 space-y-1">
                        <div>{{ $curso->unidades_count ?? $curso->unidades()->count() }} unidade(s)</div>
                        <div>{{ $curso->permite_estado_diferente ? 'Aceita alunos de outra UF' : 'Apenas UF local' }}</div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$isAtivoCard" action="toggleStatus({{ $curso->id }})" />
                            <span class="text-[11px] font-medium {{ $isAtivoCard ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">{{ $isAtivoCard ? 'Ativo' : 'Inativo' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded"><i class="text-base ph ph-info"></i></button>
                            <button wire:click="edit({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded"><i class="text-base ph ph-pencil-simple"></i></button>
                            <button wire:click="delete({{ $curso->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded" onclick="confirm('Excluir este curso permanentemente?') || event.stopImmediatePropagation()"><i class="text-base ph ph-trash"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL PADRONIZADO COM <x-modal> --}}
    @if($showModal)
        <x-modal
            title="{{ $isEditMode ? 'Editar Curso' : 'Novo Curso' }}"
            subtitle="Defina o nome do curso e os turnos habilitados em cada unidade."
            max-width="3xl"
            close-method="$set('showModal', false)">

            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Curso <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="nome" placeholder="Ex: Administração Financeira" class="w-full">
                        @error('nome') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select wire:model="status" class="w-full">
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                    </div>
                </div>

                <!-- Matriz Contextual: 1. Unidades -> 2. Turnos do Curso naquela Unidade -->
                <div class="space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
                                Estrutura de Oferta (Unidade → Turnos)
                            </h4>
                            <p class="text-xs text-gray-500">
                                Clique em uma unidade à esquerda e marque os turnos em que este curso ocorre naquela unidade.
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            {{ count($unidadesSelecionadas) }} unidade(s) • {{ count($turnosSelecionados) }} turno(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 h-[280px] border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                        
                        <!-- COLUNA 1: UNIDADES -->
                        <div class="flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-[11px] font-medium uppercase text-gray-500 pl-3">
                                1. Unidades Ofertantes
                            </div>
                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                @forelse($unidadesDisponiveis as $unidade)
                                    @php
                                        $isUnidadeAtiva = isset($activeUnidadeId) && (int) $activeUnidadeId === (int) $unidade->id;
                                        $qtdTurnosUni = method_exists($this, 'contarTurnosNaUnidade') ? $this->contarTurnosNaUnidade($unidade->id) : 0;
                                    @endphp
                                    <div wire:key="curso-modal-uni-{{ $unidade->id }}"
                                         wire:click="setActiveUnidade({{ $unidade->id }})"
                                         class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $isUnidadeAtiva ? 'bg-gray-100 dark:bg-gray-800 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                                        <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                            <input type="checkbox"
                                                   wire:model.live="unidadesSelecionadas"
                                                   value="{{ $unidade->id }}"
                                                   wire:change="setActiveUnidade({{ $unidade->id }})"
                                                   class="w-4 h-4 rounded border-gray-300 text-purpura-600">
                                            <span class="text-xs text-gray-800 dark:text-gray-200 truncate">
                                                {{ $unidade->nome }}
                                            </span>
                                        </label>

                                        <div class="flex items-center gap-2 shrink-0">
                                            @if($qtdTurnosUni > 0)
                                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ $qtdTurnosUni }}
                                                </span>
                                            @endif
                                            <i class="ph ph-caret-right text-xs text-gray-400"></i>
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
                                $unidadeFocada = isset($activeUnidadeId) && $activeUnidadeId ? $unidadesDisponiveis->firstWhere('id', $activeUnidadeId) : null;
                            @endphp
                            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase text-gray-500 truncate pl-1">
                                    2. Turnos {{ $unidadeFocada ? '— ' . $unidadeFocada->nome : '' }}
                                </span>
                                @if($unidadeFocada && count($unidadesSelecionadas) > 1 && method_exists($this, 'replicarTurnosParaUnidadesMarcadas'))
                                    <button type="button"
                                            wire:click="replicarTurnosParaUnidadesMarcadas"
                                            class="text-[11px] font-medium text-purpura-600 hover:underline shrink-0 pr-1"
                                            title="Copia os turnos marcados nesta unidade para todas as outras unidades selecionadas">
                                        Replicar p/ todas
                                    </button>
                                @endif
                            </div>

                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                @if($unidadeFocada)
                                    @forelse($turnosDisponiveis as $turno)
                                        @php
                                            $chaveCombo = "{$unidadeFocada->id}-{$turno->id}";
                                            $isTurnoMarcado = in_array($chaveCombo, $turnosSelecionados, true);
                                        @endphp
                                        <label wire:key="curso-modal-turno-{{ $chaveCombo }}"
                                               class="flex items-center justify-between p-2 rounded border cursor-pointer transition {{ $isTurnoMarcado ? 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600' : 'border-transparent hover:bg-white dark:hover:bg-gray-800' }}">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox"
                                                       wire:model.live="turnosSelecionados"
                                                       value="{{ $chaveCombo }}"
                                                       class="w-4 h-4 rounded border-gray-300 text-purpura-600">
                                                <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $turno->nome }}</span>
                                            </div>
                                            @if($turno->horario_inicio && $turno->horario_fim)
                                                <span class="text-[10px] font-mono text-gray-400">
                                                    {{ \Carbon\Carbon::parse($turno->horario_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($turno->horario_fim)->format('H:i') }}
                                                </span>
                                            @endif
                                        </label>
                                    @empty
                                        <div class="p-6 text-center text-xs text-gray-400">Nenhum turno cadastrado.</div>
                                    @endforelse
                                @else
                                    <div class="h-full flex items-center justify-center text-gray-400 p-6 text-center text-xs">
                                        Selecione uma unidade à esquerda para habilitar os turnos.
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <label class="flex items-start gap-2.5 p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 cursor-pointer">
                    <input type="checkbox" wire:model="permite_estado_diferente" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-purpura-600">
                    <div class="text-xs">
                        <span class="font-medium text-gray-900 dark:text-white block">Permitir candidatos residentes em outro Estado (UF)</span>
                        <span class="text-gray-500 block">Se marcado, candidatos fora da UF da unidade poderão escolher este curso no formulário de inscrição.</span>
                    </div>
                </label>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('showModal', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                        {{ $isEditMode ? 'Salvar Alterações' : 'Cadastrar Curso' }}
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>