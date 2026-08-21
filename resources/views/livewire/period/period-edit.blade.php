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

    <form wire:submit.prevent="salvar" class="space-y-6">
        
        {{-- DADOS BÁSICOS --}}
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

        {{-- MATRIZ DE VAGAS EM CASCATA --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 m-0">
                    <i class="ph ph-users-three text-purpura-500"></i> Distribuição e Limite de Vagas
                </h3>
                <button type="button" wire:click="addOferta" class="px-4 py-1.5 bg-purpura-100 text-purpura-800 hover:bg-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-400 text-xs font-bold uppercase rounded-lg transition flex items-center gap-1">
                    <i class="ph-bold ph-plus"></i> Adicionar Oferta
                </button>
            </div>
            
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Determine quantas vagas estarão disponíveis por unidade, curso e turno.
            </p>

            <div class="space-y-4">
                @forelse($ofertasVagas as $index => $oferta)
                    <div class="grid grid-cols-12 gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm relative group">
                        
                        {{-- CASCATA 1: UNIDADE --}}
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">1. Unidade</label>
                            <select wire:model.live="ofertasVagas.{{ $index }}.unidade_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2 focus:ring-purpura-500">
                                <option value="">Selecione...</option>
                                @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                            </select>
                        </div>
                        
                        {{-- CASCATA 2: CURSO (Filtra baseado na Unidade selecionada) --}}
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">2. Curso</label>
                            <select wire:model.live="ofertasVagas.{{ $index }}.curso_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2 focus:ring-purpura-500" @if(!$oferta['unidade_id']) disabled @endif>
                                <option value="">Selecione...</option>
                                @if($oferta['unidade_id'])
                                    @foreach($cursosDb as $c)
                                        @if($c->unidades->contains('id', $oferta['unidade_id']))
                                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        {{-- CASCATA 3: TURNO (Filtra baseado no Curso selecionado) --}}
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">3. Turno</label>
                            <select wire:model="ofertasVagas.{{ $index }}.turno_id" class="w-full text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2 focus:ring-purpura-500" @if(!$oferta['curso_id']) disabled @endif>
                                <option value="">Selecione...</option>
                                @if($oferta['curso_id'])
                                    @php $cursoSelecionado = $cursosDb->firstWhere('id', $oferta['curso_id']); @endphp
                                    @if($cursoSelecionado)
                                        @foreach($cursoSelecionado->turnosVinculados as $t)
                                            <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                        @endforeach
                                    @endif
                                @endif
                            </select>
                        </div>

                        {{-- VAGAS --}}
                        <div class="col-span-10 md:col-span-2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Total de Vagas</label>
                            <input type="number" wire:model="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2 focus:ring-purpura-500 text-center font-black text-purpura-600 dark:text-purpura-400">
                        </div>
                        
                        <button type="button" wire:click="removeOferta({{ $index }})" class="absolute -right-2 -top-2 w-7 h-7 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-full flex items-center justify-center shadow hover:bg-red-500 hover:text-white transition" title="Remover Oferta">
                            <i class="ph-bold ph-trash"></i>
                        </button>
                    </div>
                @empty
                    <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400 mb-2">Não há limite de vagas definido.</p>
                        <button type="button" wire:click="addOferta" class="px-4 py-2 bg-white text-purpura-600 border border-purpura-200 rounded-lg font-bold text-sm shadow-sm hover:bg-purpura-50 transition">
                            Configurar 1ª Oferta
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- CURSOS CHECKBOXES --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 m-0">
                        <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos Visíveis no Formulário
                    </label>
                    <button type="button" wire:click="toggleTodosCursos" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">Selecionar Todos</button>
                </div>
                <div class="grid grid-cols-1 gap-2 p-1 overflow-y-auto custom-scrollbar flex-1">
                    @foreach($cursosDb as $curso)
                        <label class="flex items-center gap-2 p-3 transition-colors border border-gray-100 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900">
                            <input type="checkbox" wire:model="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $curso->nome }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- STATUS CRM CHECKBOXES --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 m-0">
                        <i class="ph ph-funnel text-purpura-500"></i> Funil de Status do CRM
                    </label>
                    <button type="button" wire:click="toggleTodosStatus" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">Selecionar Todos</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-1 overflow-y-auto custom-scrollbar flex-1">
                    @foreach($statusDisponiveis as $st)
                        <label class="flex items-center gap-2 p-3 transition-colors border border-gray-100 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900">
                            <input type="checkbox" wire:model="statusSelecionados" value="{{ $st->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $st->nome }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="px-8 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-xl shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-xl"></i> Salvar Dossiê do Ciclo
            </button>
        </div>
    </form>
</div>