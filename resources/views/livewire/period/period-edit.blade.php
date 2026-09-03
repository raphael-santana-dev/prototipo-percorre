<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaAtiva: 'geral' }">
    
    {{-- CABEÇALHO UNIFICADO COM BOTÃO SALVAR GERAL --}}
    <x-page-header 
        title="Edição do Ciclo" 
        icon="ph ph-calendar-check"
        badge="Administrativo">
        
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a href="{{ route('ciclos.show', $cicloId) }}" class="px-3 py-2 text-xs font-bold border rounded-lg text-purpura-700 bg-purpura-50 border-purpura-200 hover:bg-purpura-100 transition shadow-sm dark:bg-purpura-900/30 dark:border-purpura-700 dark:text-purpura-400 flex items-center gap-1.5">
                    <i class="ph-bold ph-eye text-sm"></i> Ver Detalhes
                </a>
                <a href="{{ route('ciclos.index') }}" wire:navigate class="px-3 py-2 text-xs font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-1.5">
                    <i class="ph-bold ph-arrow-left text-sm"></i> Voltar
                </a>
                {{-- BOTÃO SALVAR GERAL (TOPO) --}}
                <button type="submit" form="formCicloPrincipal" class="px-5 py-2 text-xs font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-base"></i> Salvar Ciclo
                </button>
            </div>
        </x-slot>
    </x-page-header>

    {{-- BARRA DE NAVEGAÇÃO POR ABAS --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-xl px-4 pt-2 shadow-sm">
        <nav class="flex flex-wrap gap-2 -mb-px" aria-label="Abas">
            {{-- ABA 1: GERAL --}}
            <button type="button" 
                    @click="abaAtiva = 'geral'" 
                    :class="abaAtiva === 'geral' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-sliders text-base"></i>
                <span>Geral & Vigência</span>
            </button>

            {{-- ABA 2: ESTRUTURA ACADÊMICA --}}
            <button type="button" 
                    @click="abaAtiva = 'estrutura'" 
                    :class="abaAtiva === 'estrutura' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-tree-structure text-base"></i>
                <span>Estrutura Ofertada</span>
                @if(count($cursosSelecionados) > 0)
                    <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-purpura-100 text-purpura-700 dark:bg-purpura-900/40 dark:text-purpura-300 font-bold">
                        {{ count($cursosSelecionados) }}
                    </span>
                @endif
            </button>

            {{-- ABA 3: VAGAS & MATRIZ --}}
            <button type="button" 
                    @click="abaAtiva = 'vagas'" 
                    :class="abaAtiva === 'vagas' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-users-three text-base"></i>
                <span>Distribuição de Vagas</span>
                @if(count($ofertasVagas) > 0)
                    <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 font-bold">
                        {{ count($ofertasVagas) }}
                    </span>
                @endif
            </button>

            {{-- ABA 4: FUNIL CRM --}}
            <button type="button" 
                    @click="abaAtiva = 'crm'" 
                    :class="abaAtiva === 'crm' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-funnel text-base"></i>
                <span>Funil CRM (Etapas)</span>
                @if(count($statusSelecionados) > 0)
                    <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 font-bold">
                        {{ count($statusSelecionados) }}
                    </span>
                @endif
            </button>

            {{-- ABA 5: DOCUMENTOS --}}
            <button type="button" 
                    @click="abaAtiva = 'documentos'" 
                    :class="abaAtiva === 'documentos' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-files text-base"></i>
                <span>Documentos Exigidos</span>
                @if(count($documentosExigidos) > 0)
                    <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-purpura-100 text-purpura-700 dark:bg-purpura-900/40 dark:text-purpura-300 font-bold">
                        {{ count($documentosExigidos) }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- FORMULÁRIO PRINCIPAL INTEGRADO --}}
    <form id="formCicloPrincipal" wire:submit.prevent="salvar" class="space-y-6">
        
        {{-- ======================================================== --}}
        {{-- ABA 1: DADOS GERAIS & VIGÊNCIA                            --}}
        {{-- ======================================================== --}}
        <div x-show="abaAtiva === 'geral'" x-cloak class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
            <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="ph-fill ph-calendar text-purpura-600"></i> Informações do Período e Vigência
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Defina o nome de exibição pública, os semestres letivos e a janela de tempo em que as inscrições ficarão ativas.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome de Exibição</label>
                    <input type="text" wire:model="nome" placeholder="Ex: 2º Semestre 2026" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm font-bold">
                    @error('nome') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ano <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="ano" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm font-bold">
                    @error('ano') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Semestre <span class="text-red-500">*</span></label>
                    <select wire:model="semestre" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm font-bold">
                        <option value="1">1º Semestre</option>
                        <option value="2">2º Semestre</option>
                    </select>
                    @error('semestre') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data e Hora de Abertura <span class="text-red-500">*</span></label>
                    <input type="datetime-local" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                    @error('data_inicio') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data e Hora de Encerramento <span class="text-red-500">*</span></label>
                    <input type="datetime-local" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                    @error('data_fim') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="flex items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300 cursor-pointer">
                    Ativar este ciclo imediatamente no portal público (desativará outros ciclos simultâneos)[cite: 37]
                </label>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- ABA 2: ESTRUTURA ACADÊMICA (EXPLORER)                     --}}
        {{-- ======================================================== --}}
        <div x-show="abaAtiva === 'estrutura'" x-cloak class="space-y-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <i class="ph-fill ph-tree-structure text-purpura-500"></i> Matriz de Seleção Cascata
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Navegue pelas colunas para habilitar os itens disponíveis no formulário deste semestre.[cite: 37]</p>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-900 px-2.5 py-1 rounded border border-gray-200 dark:border-gray-700">
                    Estilo macOS Finder[cite: 2]
                </span>
            </div>
            
            <div class="flex flex-col md:flex-row h-[420px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                {{-- COLUNA 1: UNIDADES --}}
                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        1. Unidades Ofertadas[cite: 37]
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

                {{-- COLUNA 2: CURSOS DA UNIDADE --}}
                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        2. Cursos da Unidade[cite: 37]
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

                {{-- COLUNA 3: TURNOS DO CURSO --}}
                <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900/30">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500 tracking-wider">
                        3. Turnos Vinculados[cite: 37]
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

        {{-- ======================================================== --}}
        {{-- ABA 3: MATRIZ DE VAGAS E IDADES                           --}}
        {{-- ======================================================== --}}
        <div x-show="abaAtiva === 'vagas'" x-cloak class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 gap-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-users-three text-purpura-500"></i> Ofertas e Limites de Vagas
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Defina vagas e restrições etárias. Apenas as combinações marcadas na Estrutura Acadêmica aparecerão nas seleções abaixo.[cite: 37]
                    </p>
                </div>
                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    <button type="button" wire:click="addOferta" class="px-3.5 py-2 bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-300 dark:border-purpura-700 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                        <i class="ph-bold ph-plus text-sm"></i> Nova Oferta
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($ofertasVagas as $index => $oferta)
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm flex flex-col xl:flex-row gap-3 items-end transition-colors hover:border-purpura-300">
                        {{-- Unidade --}}
                        <div class="flex-1 w-full">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade[cite: 37]</label>
                            <select wire:model.live="ofertasVagas.{{ $index }}.unidade_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                                <option value="">Selecione...</option>
                                @foreach($unidadesDb as $u) 
                                    @if(in_array((string)$u->id, $unidadesSelecionadas))
                                        <option value="{{ $u->id }}">{{ $u->nome }}</option> 
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- Curso --}}
                        <div class="flex-1 w-full">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso[cite: 37]</label>
                            <select wire:model.live="ofertasVagas.{{ $index }}.curso_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500" @if(!$oferta['unidade_id']) disabled @endif>
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
                        
                        {{-- Turno --}}
                        <div class="flex-1 w-full">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno[cite: 37]</label>
                            <select wire:model="ofertasVagas.{{ $index }}.turno_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500" @if(!$oferta['curso_id']) disabled @endif>
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

                        {{-- Quantidades e Idades --}}
                        <div class="w-full xl:w-24">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Vagas[cite: 37]</label>
                            <input type="number" wire:model="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-black text-purpura-600 dark:text-purpura-400 text-center">
                        </div>
                        <div class="w-full xl:w-20">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Mín[cite: 37]</label>
                            <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_min" min="0" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-semibold text-center">
                        </div>
                        <div class="w-full xl:w-20">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Máx[cite: 37]</label>
                            <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_max" min="0" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-semibold text-center">
                        </div>

                        @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                            <div class="w-full xl:w-auto">
                                <button type="button" wire:click="removeOferta({{ $index }})" class="w-full xl:w-auto p-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-lg flex items-center justify-center shadow-sm hover:bg-red-500 hover:text-white transition" title="Remover Oferta">
                                    <i class="ph-bold ph-trash text-base"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                        <i class="ph ph-warning-circle text-4xl text-gray-400 mb-2"></i>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Nenhuma oferta de vaga configurada neste ciclo.[cite: 37]</p>
                        <p class="text-xs text-gray-500 mt-0.5">Adicione ofertas para limitar inscrições ou estipular faixas etárias por curso e unidade.[cite: 37]</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- ABA 4: FUNIL CRM (PIPELINE DE STATUS)                     --}}
        {{-- ======================================================== --}}
        <div x-show="abaAtiva === 'crm'" x-cloak class="space-y-4">
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"
                 x-data="{
                     initSortable() {
                         new Sortable(this.$refs.statusList, {
                             animation: 150,
                             handle: '.drag-handle',
                             ghostClass: 'opacity-50',
                             onEnd: () => {
                                 let items = Array.from(this.$refs.statusList.children).map(el => el.dataset.id);
                                 $wire.atualizarOrdemStatus(items);
                             }
                         });
                     }
                 }" x-init="initSortable()">
                 
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-4 gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white m-0 flex items-center gap-2">
                            <i class="ph-fill ph-funnel text-purpura-500"></i> Sequência do Funil Kanban
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Arraste os status pelas alças para ajustar a ordem exata das colunas deste processo seletivo.[cite: 37]</p>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <select wire:model="novoStatusSelecionado" class="flex-1 md:w-56 text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                            <option value="">Adicionar etapa ao funil...</option>
                            @foreach($statusDisponiveis as $st)
                                @if(!in_array($st->id, $statusSelecionados))
                                    <option value="{{ $st->id }}">{{ $st->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" wire:click="adicionarStatusPipeline" class="bg-purpura-600 text-white hover:bg-purpura-700 px-3.5 py-2 rounded-lg font-bold text-xs transition flex items-center gap-1 shadow-sm">
                            <i class="ph-bold ph-plus"></i> Inserir
                        </button>
                    </div>
                </div>
                
                {{-- LISTA DE STATUS ORDENÁVEL --}}
                <div x-ref="statusList" class="flex flex-col gap-2 p-1">
                    @forelse($statusSelecionados as $index => $statusId)
                        @php $statusObj = $statusDisponiveis->firstWhere('id', $statusId); @endphp
                        @if($statusObj)
                            <div data-id="{{ $statusId }}" wire:key="status-pipeline-{{ $statusId }}" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg group transition-colors hover:border-purpura-300">
                                <div class="flex items-center gap-3">
                                    <i class="ph-bold ph-dots-six-vertical text-gray-400 cursor-grab active:cursor-grabbing drag-handle text-xl hover:text-gray-700 dark:hover:text-gray-200"></i>
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-black">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $statusObj->nome }}</span>
                                </div>
                                <button type="button" wire:click="removerStatusPipeline('{{ $statusId }}')" class="text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 p-1.5 rounded-lg transition" title="Remover Etapa">
                                    <i class="ph-bold ph-trash text-base"></i>
                                </button>
                            </div>
                        @endif
                    @empty
                        <div class="text-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-gray-400">
                            Nenhum status configurado para a pipeline deste ciclo.[cite: 37]
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- ABA 5: DOCUMENTOS EXIGIDOS PARA MATRÍCULA                 --}}
        {{-- ======================================================== --}}
        <div x-show="abaAtiva === 'documentos'" x-cloak class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 gap-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-files text-purpura-500"></i> Documentos Exigidos no Portal de Matrícula
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Defina quais comprovações serão solicitadas aos candidatos aprovados deste ciclo.[cite: 37]
                    </p>
                </div>
                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    <button type="button" wire:click="addDocumento" class="px-3.5 py-2 bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-300 dark:border-purpura-700 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                        <i class="ph-bold ph-plus text-sm"></i> Novo Documento
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($documentosExigidos as $index => $doc)
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm flex flex-col md:flex-row gap-4 items-end transition-colors hover:border-purpura-300">
                        {{-- Nome --}}
                        <div class="flex-1 w-full md:w-1/3">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome do Documento <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="documentosExigidos.{{ $index }}.nome" placeholder="Ex: RG Frente e Verso" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-bold">
                            @error("documentosExigidos.$index.nome") <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        {{-- Descrição / Instruções --}}
                        <div class="flex-1 w-full md:w-1/2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Instruções aos Alunos</label>
                            <input type="text" wire:model="documentosExigidos.{{ $index }}.descricao" placeholder="Ex: Foto nítida e sem reflexos" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                        </div>

                        {{-- Switch Obrigatório & Remover --}}
                        <div class="w-full md:w-auto flex items-center justify-between gap-4 pb-1.5">
                            <label class="flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model="documentosExigidos.{{ $index }}.is_obrigatorio" class="w-4 h-4 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                <span class="ml-2 text-xs font-bold text-gray-700 dark:text-gray-300">Obrigatório</span>
                            </label>

                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <button type="button" wire:click="removeDocumento({{ $index }})" class="p-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-lg shadow-sm hover:bg-red-500 hover:text-white transition" title="Remover Documento">
                                    <i class="ph-bold ph-trash text-base"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                        <i class="ph ph-files text-4xl text-gray-400 mb-2"></i>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Nenhum documento exigido neste ciclo.[cite: 37]</p>
                        <p class="text-xs text-gray-500 mt-0.5">Adicione exigências documentais para ativar o portal de validação automática por IA.[cite: 37]</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- BARRA INFERIOR COM BOTÃO SALVAR GERAL --}}
        <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mt-6">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                As alterações realizadas em qualquer aba serão sincronizadas em conjunto.
            </span>
            <button type="submit" class="px-6 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg shadow-sm transition-all flex items-center gap-2 hover:-translate-y-0.5">
                <i class="ph-bold ph-floppy-disk text-base"></i> Salvar Ciclo Completo
            </button>
        </div>
    </form>
</div>