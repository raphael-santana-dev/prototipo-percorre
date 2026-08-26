<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Dossiê do Ciclo (Edição)" 
        icon="ph ph-calendar-check"
        badge="Administração">
        
        <x-slot name="actions">
            <a href="{{ route('ciclos.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="space-y-8">
        
        {{-- SEÇÃO 1: DADOS BÁSICOS --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200">Datas e Regras Globais</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome de Exibição</label>
                    <input type="text" wire:model="nome" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm font-bold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Abertura</label>
                    <input type="datetime-local" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Encerramento</label>
                    <input type="datetime-local" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                </div>
            </div>
            
            <div class="flex items-center pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">
                    Ativar este ciclo imediatamente (desativará os demais)
                </label>
            </div>
        </div>

        {{-- SEÇÃO 2: MACOS EXPLORER (ESTRUTURA ACADÊMICA) --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-1 flex items-center gap-2">
                <i class="ph-fill ph-tree-structure text-purpura-500"></i> Estrutura Acadêmica Ofertada
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Navegue pelas colunas para marcar as unidades, cursos e turnos que estarão visíveis no formulário público de inscrição deste ciclo.</p>
            
            <div class="flex flex-col md:flex-row h-[420px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                
                {{-- COLUNA 1: UNIDADES --}}
                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        1. Unidades
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                        @foreach($unidadesDb as $u)
                            <div wire:click="setActiveUnidade({{ $u->id }})" 
                                 class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/30 ring-1 ring-purpura-200 dark:ring-purpura-800' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                    <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $u->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="text-sm font-bold {{ $activeUnidadeId == $u->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $u->nome }}</span>
                                </label>
                                <i class="ph ph-caret-right text-lg {{ $activeUnidadeId == $u->id ? 'text-purpura-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- COLUNA 2: CURSOS --}}
                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        2. Cursos da Unidade Selecionada
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                        @if($activeUnidadeId)
                            @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                <div wire:click="setActiveCurso({{ $c->id }})" 
                                     class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/30 ring-1 ring-purpura-200 dark:ring-purpura-800' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                        <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $c->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="text-sm font-bold {{ $activeCursoId == $c->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $c->nome }}</span>
                                    </label>
                                    <i class="ph ph-caret-right text-lg {{ $activeCursoId == $c->id ? 'text-purpura-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                </div>
                            @endforeach
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 opacity-60">
                                <i class="ph ph-buildings text-3xl mb-2"></i>
                                <span class="text-xs font-bold uppercase tracking-wider">Selecione uma Unidade</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- COLUNA 3: TURNOS --}}
                <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900/30">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        3. Turnos do Curso
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                        @if($activeCursoId)
                            @foreach($cursosDb->firstWhere('id', $activeCursoId)->turnosVinculados as $t)
                                <div class="flex items-center p-2.5 rounded-lg transition hover:bg-white dark:hover:bg-gray-700">
                                    <label class="flex items-center gap-2 cursor-pointer flex-1">
                                        <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $t->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t->nome }}</span>
                                    </label>
                                </div>
                            @endforeach
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 opacity-60">
                                <i class="ph ph-graduation-cap text-3xl mb-2"></i>
                                <span class="text-xs font-bold uppercase tracking-wider">Selecione um Curso</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- SEÇÃO 3: STATUS CRM E OFERTAS (Lado a Lado ou Stacked) --}}
        <div class="space-y-6">
            
            {{-- STATUS CRM CHECKBOXES (Agora fica isolado como você pediu) --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 m-0 flex items-center gap-2">
                            <i class="ph-fill ph-funnel text-purpura-500"></i> Funil de Status do CRM
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Marque os status que farão parte da esteira deste processo seletivo.</p>
                    </div>
                    <button type="button" wire:click="toggleTodosStatus" class="text-[10px] px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded font-bold text-gray-600 dark:text-gray-300 uppercase transition">Selecionar Todos</button>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 p-1">
                    @foreach($statusDisponiveis as $st)
                        <label class="flex items-center gap-2 p-3 transition-colors border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 shadow-sm">
                            <input type="checkbox" wire:model="statusSelecionados" value="{{ $st->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate" title="{{ $st->nome }}">{{ $st->nome }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- MATRIZ DE VAGAS EM CASCATA (Movida para baixo) --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 m-0 flex items-center gap-2">
                            <i class="ph-fill ph-users-three text-purpura-500"></i> Distribuição e Limite de Vagas
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Defina os limites de vagas específicos. Os dropdowns abaixo só exibirão as opções que você marcou na Estrutura Acadêmica acima.
                        </p>
                    </div>
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <button type="button" wire:click="addOferta" class="px-4 py-2 bg-purpura-100 text-purpura-800 hover:bg-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-400 text-xs font-bold uppercase rounded-lg transition flex items-center gap-2 shadow-sm">
                            <i class="ph-bold ph-plus text-sm"></i> Nova Oferta
                        </button>
                    @endif
                </div>

                <div class="space-y-4">
                    @forelse($ofertasVagas as $index => $oferta)
                        <div class="grid grid-cols-12 gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm relative group">
                            
                            {{-- CASCATA 1: UNIDADE --}}
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">1. Unidade</label>
                                <select wire:model.live="ofertasVagas.{{ $index }}.unidade_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2.5 focus:ring-purpura-500">
                                    <option value="">Selecione...</option>
                                    @foreach($unidadesDb as $u) 
                                        @if(in_array((string)$u->id, $unidadesSelecionadas))
                                            <option value="{{ $u->id }}">{{ $u->nome }}</option> 
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- CASCATA 2: CURSO (Filtra por Unidade E Cursos Marcados no Explorer) --}}
                            <div class="col-span-12 md:col-span-4">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">2. Curso</label>
                                <select wire:model.live="ofertasVagas.{{ $index }}.curso_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2.5 focus:ring-purpura-500" @if(!$oferta['unidade_id']) disabled @endif>
                                    <option value="">Selecione...</option>
                                    @if($oferta['unidade_id'])
                                        @foreach($cursosDb as $c)
                                            @if(in_array((string)$c->id, $cursosSelecionados) && $c->unidades->contains('id', $oferta['unidade_id']))
                                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            {{-- CASCATA 3: TURNO (Filtra por Curso E Turnos Marcados no Explorer) --}}
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">3. Turno</label>
                                <select wire:model="ofertasVagas.{{ $index }}.turno_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2.5 focus:ring-purpura-500" @if(!$oferta['curso_id']) disabled @endif>
                                    <option value="">Selecione...</option>
                                    @if($oferta['curso_id'])
                                        @php $cursoSelecionado = $cursosDb->firstWhere('id', $oferta['curso_id']); @endphp
                                        @if($cursoSelecionado)
                                            @foreach($cursoSelecionado->turnosVinculados as $t)
                                                @if(in_array((string)$t->id, $turnosSelecionados))
                                                    <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endif
                                </select>
                            </div>

                            {{-- VAGAS --}}
                            <div class="col-span-10 md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Total de Vagas</label>
                                <input type="number" wire:model="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2.5 focus:ring-purpura-500 text-center font-black text-purpura-600 dark:text-purpura-400">
                            </div>
                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <button type="button" wire:click="removeOferta({{ $index }})" class="absolute -right-2 -top-2 w-8 h-8 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-full flex items-center justify-center shadow-md hover:bg-red-500 hover:text-white transition" title="Remover Oferta">
                                    <i class="ph-bold ph-trash text-sm"></i>
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                            <i class="ph ph-warning-circle text-4xl text-gray-400 mb-2"></i>
                            <p class="text-sm font-bold text-gray-600 dark:text-gray-400 mb-2">Sem limite de vagas</p>
                            <p class="text-xs text-gray-500 mb-4 max-w-lg mx-auto">Para definir o limite de alunos aprovados por unidade e curso neste ciclo, adicione uma oferta no botão acima.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 pb-10">
            <button type="submit" class="px-8 py-3.5 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-xl"></i> Salvar Dossiê do Ciclo
            </button>
        </div>
    </form>
</div>