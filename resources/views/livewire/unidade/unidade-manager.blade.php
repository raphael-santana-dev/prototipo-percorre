<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Unidades" 
        subtitle="Gerencie os polos presenciais e os turnos de cada curso ofertado na unidade"
        icon="ph ph-buildings"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm flex items-center gap-2">
                <i class="ph-bold ph-plus"></i> Nova Unidade
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
                <input type="text" wire:model.live.debounce.400ms="filtroBusca" placeholder="Buscar por nome ou cidade..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        @forelse($registros as $unidade)
            <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50" wire:key="uni-row-{{ $unidade->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                    #{{ str_pad($unidade->id, 2, '0', STR_PAD_LEFT) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $unidade->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $unidade->endereco }}</div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <button type="button" wire:click="showQuickView({{ $unidade->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-purpura-50 text-purpura-700 border border-purpura-200 hover:bg-purpura-100 transition">
                        <i class="ph-fill ph-graduation-cap"></i>
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
                        <span class="text-[10px] font-extrabold {{ $unidade->status === 'Ativa' ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $unidade->status === 'Ativa' ? 'ATIVA' : 'INATIVA' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700" title="Visualização Rápida">
                            <i class="text-lg ph ph-info"></i>
                        </button>
                        <a href="{{ route('unidades.show', $unidade->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-700" title="Página Completa">
                            <i class="text-lg ph ph-eye"></i>
                        </a>
                        <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700" title="Editar">
                            <i class="text-lg ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700" title="Excluir Unidade" onclick="confirm('Excluir permanentemente esta unidade do sistema?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                    <p class="font-semibold text-gray-500">Nenhuma unidade encontrada.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $unidade)
                <div class="flex flex-col p-5 bg-white border border-gray-200/80 shadow-2xs rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:border-purpura-300 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $unidade->nome }}</div>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300">#{{ $unidade->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 space-y-1">
                        <div><i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $unidade->cidade }}/{{ $unidade->estado }}</div>
                        <div><i class="ph-fill ph-graduation-cap text-indigo-500"></i> {{ $unidade->cursos_count ?? 0 }} curso(s)</div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$unidade->status === 'Ativa'" action="toggleStatus({{ $unidade->id }})" />
                            <span class="text-[10px] font-extrabold {{ $unidade->status === 'Ativa' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $unidade->status === 'Ativa' ? 'ATIVA' : 'INATIVA' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickView({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 rounded-lg"><i class="text-lg ph ph-info"></i></button>
                            <button wire:click="edit({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg"><i class="text-lg ph ph-pencil-simple"></i></button>
                            <button wire:click="delete({{ $unidade->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg" onclick="confirm('Excluir esta unidade?') || event.stopImmediatePropagation()"><i class="text-lg ph ph-trash"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL MINIMALISTA DE UNIDADE E MATRIZ CURSO -> TURNO --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[92vh]" @click.away="closeModal">
                
                <!-- Header Minimalista -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 flex items-center justify-center border border-purpura-100 dark:border-purpura-800">
                            <i class="ph-fill ph-buildings text-lg"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ $isEditMode ? 'Editar Unidade' : 'Nova Unidade' }}</h3>
                            <p class="text-xs text-gray-500">Configure os dados de localização e os turnos específicos de cada curso nesta unidade.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1.5 rounded-lg transition">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Corpo Scrollável -->
                <form wire:submit="save" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                    
                    <!-- 1. Identificação e Contato -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Nome da Unidade <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Unidade Santo André - Centro" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-purpura-500">
                            @error('nome') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Status</label>
                            <select wire:model="status" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                                <option value="Ativa">Ativa</option>
                                <option value="Inativa">Inativa</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Inauguração</label>
                            <input type="date" wire:model="data_inauguracao" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">E-mail Corporativo</label>
                            <input type="email" wire:model="email" placeholder="contato.unidade@percorre.org" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Telefone / WhatsApp</label>
                            <input type="text" wire:model="telefone" placeholder="(11) 99999-9999" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        </div>
                    </div>

                    <!-- 2. Endereço Compacto (ViaCEP) -->
                    <div class="p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700 grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">CEP <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.500ms="cep" x-mask="99999-999" placeholder="00000-000" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            @error('cep') <span class="text-[11px] text-red-500 font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-7">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Logradouro / Rua</label>
                            <input type="text" wire:model="logradouro" readonly class="w-full text-xs rounded-lg border-gray-200 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Número <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="numero" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Complemento</label>
                            <input type="text" wire:model="complemento" placeholder="Opcional" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Bairro</label>
                            <input type="text" wire:model="bairro" readonly class="w-full text-xs rounded-lg border-gray-200 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Cidade</label>
                            <input type="text" wire:model="cidade" readonly class="w-full text-xs rounded-lg border-gray-200 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">UF</label>
                            <input type="text" wire:model="estado" readonly class="w-full text-xs text-center font-bold rounded-lg border-gray-200 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700">
                        </div>
                    </div>

                    <!-- 3. Matriz Contextual: Cursos da Unidade -> Turnos daquele Curso nesta Unidade -->
                    <div class="space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-1.5">
                                    <i class="ph-fill ph-tree-structure text-purpura-600 text-sm"></i> Cursos e Turnos Ofertados nesta Unidade
                                </h4>
                                <p class="text-[11px] text-gray-500">
                                    Selecione um curso à esquerda e marque os turnos em que ele funciona <b>especificamente nesta unidade</b>.
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purpura-50 text-purpura-700 border border-purpura-200">
                                {{ count($cursosSelecionados) }} curso(s) • {{ count($turnosSelecionados) }} combinação(ões) de turno
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 h-[310px] border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                            
                            <!-- COLUNA 1: CURSOS -->
                            <div class="flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 pl-1">1. Cursos</span>
                                    <input type="text" wire:model.live.debounce.250ms="buscaCursoModal" placeholder="Filtrar curso..." class="w-40 text-xs py-1 px-2.5 rounded-lg border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                                </div>

                                <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                    @forelse($cursosDisponiveis as $curso)
                                        @php
                                            $isCursoAtivo = (int) $activeCursoId === (int) $curso->id;
                                            $qtdTurnosCurso = $this->contarTurnosDoCurso($curso->id);
                                        @endphp
                                        <div wire:key="uni-modal-curso-{{ $curso->id }}"
                                             wire:click="setActiveCurso({{ $curso->id }})"
                                             class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition {{ $isCursoAtivo ? 'bg-purpura-50/90 dark:bg-purpura-900/30 ring-1 ring-purpura-300 dark:ring-purpura-700' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                                            <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                                <input type="checkbox"
                                                       wire:model.live="cursosSelecionados"
                                                       value="{{ $curso->id }}"
                                                       wire:change="setActiveCurso({{ $curso->id }})"
                                                       class="w-4 h-4 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500">
                                                <span class="text-xs font-bold truncate {{ $isCursoAtivo ? 'text-purpura-800 dark:text-purpura-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                    {{ $curso->nome }}
                                                </span>
                                            </label>

                                            <div class="flex items-center gap-2 shrink-0">
                                                @if($qtdTurnosCurso > 0)
                                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {{ $qtdTurnosCurso }} {{ $qtdTurnosCurso === 1 ? 'turno' : 'turnos' }}
                                                    </span>
                                                @endif
                                                <i class="ph-bold ph-caret-right text-sm {{ $isCursoAtivo ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-6 text-center text-xs text-gray-400">Nenhum curso encontrado.</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- COLUNA 2: TURNOS ESPECÍFICOS DO CURSO SELECIONADO NESTA UNIDADE -->
                            <div class="flex flex-col bg-gray-50/40 dark:bg-gray-900/50 overflow-hidden">
                                @php
                                    $cursoFocado = $activeCursoId ? \App\Models\Curso::find($activeCursoId) : null;
                                @endphp
                                <div class="p-2.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 truncate pl-1">
                                        2. Turnos {{ $cursoFocado ? '— ' . $cursoFocado->nome : '' }}
                                    </span>
                                    @if($cursoFocado)
                                        <button type="button" wire:click="toggleTodosTurnosDoCursoAtivo" class="text-[11px] font-bold text-purpura-600 hover:underline shrink-0 pr-1">
                                            Marcar/Desmarcar todos
                                        </button>
                                    @endif
                                </div>

                                <div class="flex-1 overflow-y-auto p-2.5 space-y-1.5 custom-scrollbar">
                                    @if($cursoFocado)
                                        @forelse($turnosDisponiveis as $turno)
                                            @php
                                                $chaveCombo = "{$cursoFocado->id}-{$turno->id}";
                                                $isTurnoMarcado = in_array($chaveCombo, $turnosSelecionados, true);
                                            @endphp
                                            <label wire:key="uni-modal-turno-{{ $chaveCombo }}"
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
                                            <div class="h-full flex flex-col items-center justify-center text-gray-400 text-xs p-4 text-center">
                                                Nenhum turno ativo cadastrado no sistema.
                                            </div>
                                        @endforelse
                                    @else
                                        <div class="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                                            <i class="ph-duotone ph-graduation-cap text-3xl mb-1.5"></i>
                                            <span class="text-xs font-semibold">Clique em um curso à esquerda para definir seus turnos nesta unidade.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="closeModal" class="btn btn--secondary btn--medium">Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                            <i class="ph-bold ph-check"></i> {{ $isEditMode ? 'Salvar Alterações' : 'Cadastrar Unidade' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>