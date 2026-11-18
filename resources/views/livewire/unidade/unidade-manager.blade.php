<div class="w-full relative">
    <x-page-header 
        title="Unidades" 
        subtitle="Gerencie os polos presenciais e os turnos de cada curso ofertado na unidade"
        icon="ph ph-buildings"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                <i class="ph ph-plus"></i> Nova Unidade
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
                <input type="text" wire:model.live.debounce.400ms="filtroBusca" placeholder="Buscar por nome ou cidade..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        @forelse($registros as $unidade)
            <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-800/40" wire:key="uni-row-{{ $unidade->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ str_pad($unidade->id, 2, '0', STR_PAD_LEFT) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ $unidade->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $unidade->endereco }}</div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <button type="button" wire:click="showQuickView({{ $unidade->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition">
                        <span>{{ $unidade->cursos_count ?? 0 }} {{ ($unidade->cursos_count ?? 0) === 1 ? 'curso vinculado' : 'cursos vinculados' }}</span>
                    </button>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $unidade->email ?: 'Sem e-mail' }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $unidade->telefone ?: 'Sem telefone' }}</div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$unidade->status === 'Ativa'" action="toggleStatus({{ $unidade->id }})" />
                        <span class="text-[11px] font-medium {{ $unidade->status === 'Ativa' ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                            {{ $unidade->status === 'Ativa' ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Visualização Rápida">
                            <i class="text-base ph ph-info"></i>
                        </button>
                        <a href="{{ route('unidades.show', $unidade->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Página Completa">
                            <i class="text-base ph ph-eye"></i>
                        </a>
                        <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Editar">
                            <i class="text-base ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir Unidade" onclick="confirm('Excluir permanentemente esta unidade do sistema?') || event.stopImmediatePropagation()">
                            <i class="text-base ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm">
                    Nenhuma unidade encontrada.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $unidade)
                <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 hover:border-gray-300 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $unidade->nome }}</div>
                        <span class="px-2 py-0.5 text-[10px] font-mono text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300">#{{ $unidade->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 space-y-1">
                        <div>{{ $unidade->cidade }}/{{ $unidade->estado }}</div>
                        <div>{{ $unidade->cursos_count ?? 0 }} curso(s) vinculado(s)</div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$unidade->status === 'Ativa'" action="toggleStatus({{ $unidade->id }})" />
                            <span class="text-[11px] font-medium {{ $unidade->status === 'Ativa' ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">{{ $unidade->status === 'Ativa' ? 'Ativa' : 'Inativa' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded"><i class="text-base ph ph-info"></i></button>
                            <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded"><i class="text-base ph ph-pencil-simple"></i></button>
                            <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded" onclick="confirm('Excluir esta unidade?') || event.stopImmediatePropagation()"><i class="text-base ph ph-trash"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL PADRONIZADO COM <x-modal> --}}
    @if($showModal)
        <x-modal
            title="{{ $isEditMode ? 'Editar Unidade' : 'Nova Unidade' }}"
            subtitle="Configure os dados de localização e os turnos específicos de cada curso nesta unidade."
            max-width="4xl"
            close-method="$set('showModal', false)">

            <form wire:submit="save" class="space-y-6">
                <!-- 1. Identificação e Contato -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-6">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da Unidade <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="nome" placeholder="Ex: Unidade Santo André - Centro" class="w-full">
                        @error('nome') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select wire:model="status" class="w-full">
                            <option value="Ativa">Ativa</option>
                            <option value="Inativa">Inativa</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Inauguração</label>
                        <input type="date" wire:model="data_inauguracao" class="w-full">
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail Corporativo</label>
                        <input type="email" wire:model="email" placeholder="contato.unidade@percorre.org" class="w-full">
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone / WhatsApp</label>
                        <input type="text" wire:model="telefone" placeholder="(11) 99999-9999" class="w-full">
                    </div>
                </div>

                <!-- 2. Endereço (ViaCEP) -->
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">CEP <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.500ms="cep" x-mask="99999-999" placeholder="00000-000" class="w-full font-mono">
                        @error('cep') <span class="text-xs text-red-600 mt-0.5 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-7">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Logradouro / Rua</label>
                        <input type="text" wire:model="logradouro" readonly class="w-full bg-gray-100 text-gray-500 dark:bg-gray-800">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Número <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="numero" class="w-full">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Complemento</label>
                        <input type="text" wire:model="complemento" placeholder="Opcional" class="w-full">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Bairro</label>
                        <input type="text" wire:model="bairro" readonly class="w-full bg-gray-100 text-gray-500 dark:bg-gray-800">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cidade</label>
                        <input type="text" wire:model="cidade" readonly class="w-full bg-gray-100 text-gray-500 dark:bg-gray-800">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">UF</label>
                        <input type="text" wire:model="estado" readonly class="w-full text-center font-semibold bg-gray-100 text-gray-500 dark:bg-gray-800">
                    </div>
                </div>

                <!-- 3. Matriz Contextual: Cursos da Unidade -> Turnos daquele Curso nesta Unidade -->
                <div class="space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
                                Cursos e Turnos Ofertados nesta Unidade
                            </h4>
                            <p class="text-xs text-gray-500">
                                Selecione um curso à esquerda e marque os turnos em que ele funciona nesta unidade.
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            {{ count($cursosSelecionados) }} curso(s) • {{ count($turnosSelecionados) }} turno(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 h-[300px] border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                        
                        <!-- COLUNA 1: CURSOS -->
                        <div class="flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase text-gray-500 pl-1">1. Cursos</span>
                                <input type="text" wire:model.live.debounce.250ms="buscaCursoModal" placeholder="Filtrar curso..." class="w-40 text-xs py-1 px-2.5">
                            </div>

                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                @forelse($cursosDisponiveis as $curso)
                                    @php
                                        $isCursoAtivo = (int) $activeCursoId === (int) $curso->id;
                                        $qtdTurnosCurso = method_exists($this, 'contarTurnosDoCurso') ? $this->contarTurnosDoCurso($curso->id) : 0;
                                    @endphp
                                    <div wire:key="uni-modal-curso-{{ $curso->id }}"
                                         wire:click="setActiveCurso({{ $curso->id }})"
                                         class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $isCursoAtivo ? 'bg-gray-100 dark:bg-gray-800 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                                        <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                            <input type="checkbox"
                                                   wire:model.live="cursosSelecionados"
                                                   value="{{ $curso->id }}"
                                                   wire:change="setActiveCurso({{ $curso->id }})"
                                                   class="w-4 h-4 rounded border-gray-300 text-purpura-600">
                                            <span class="text-xs text-gray-800 dark:text-gray-200 truncate">
                                                {{ $curso->nome }}
                                            </span>
                                        </label>

                                        <div class="flex items-center gap-2 shrink-0">
                                            @if($qtdTurnosCurso > 0)
                                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ $qtdTurnosCurso }}
                                                </span>
                                            @endif
                                            <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-gray-400">Nenhum curso encontrado.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- COLUNA 2: TURNOS ESPECÍFICOS DO CURSO NESTA UNIDADE -->
                        <div class="flex flex-col bg-gray-50/40 dark:bg-gray-900/50 overflow-hidden">
                            @php
                                $cursoFocado = $activeCursoId ? \App\Models\Curso::find($activeCursoId) : null;
                                $listaTurnosCol2 = isset($turnosDisponiveis) ? $turnosDisponiveis : ($cursoFocado->turnosVinculados ?? []);
                            @endphp
                            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase text-gray-500 truncate pl-1">
                                    2. Turnos {{ $cursoFocado ? '— ' . $cursoFocado->nome : '' }}
                                </span>
                                @if($cursoFocado && method_exists($this, 'toggleTodosTurnosDoCursoAtivo'))
                                    <button type="button" wire:click="toggleTodosTurnosDoCursoAtivo" class="text-[11px] font-medium text-purpura-600 hover:underline shrink-0 pr-1">
                                        Marcar todos
                                    </button>
                                @endif
                            </div>

                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                @if($cursoFocado)
                                    @forelse($listaTurnosCol2 as $turno)
                                        @php
                                            $chaveCombo = "{$cursoFocado->id}-{$turno->id}";
                                            $isTurnoMarcado = in_array($chaveCombo, $turnosSelecionados, true);
                                        @endphp
                                        <label wire:key="uni-modal-turno-{{ $chaveCombo }}"
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
                                        <div class="h-full flex items-center justify-center text-gray-400 text-xs p-4 text-center">
                                            Nenhum turno ativo cadastrado.
                                        </div>
                                    @endforelse
                                @else
                                    <div class="h-full flex items-center justify-center text-gray-400 p-6 text-center text-xs">
                                        Selecione um curso à esquerda para definir seus turnos nesta unidade.
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('showModal', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                        {{ $isEditMode ? 'Salvar Alterações' : 'Cadastrar Unidade' }}
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>