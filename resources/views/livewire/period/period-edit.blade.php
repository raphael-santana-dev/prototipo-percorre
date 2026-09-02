<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Dossiê do Ciclo (Edição)" 
        icon="ph ph-calendar-check"
        badge="Administração">
        
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a href="{{ route('ciclos.show', $cicloId) }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-700 bg-purpura-50 border-purpura-200 hover:bg-purpura-100 transition shadow-sm dark:bg-purpura-900/30 dark:border-purpura-700 dark:text-purpura-400 flex items-center gap-2">
                    <i class="ph-bold ph-eye"></i> Ver Detalhes
                </a>
                <a href="{{ route('ciclos.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                    <i class="ph-bold ph-arrow-left"></i> Voltar
                </a>
            </div>
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
            {{-- IMPORTAÇÃO DA BIBLIOTECA SORTABLE --}}
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

            {{-- STATUS CRM - DRAG AND DROP --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"
                 x-data="{
                     initSortable() {
                         new Sortable(this.$refs.statusList, {
                             animation: 150,
                             handle: '.drag-handle', // Só arrasta se clicar no ícone de pontinhos
                             ghostClass: 'opacity-50',
                             onEnd: () => {
                                 // Coleta os IDs na nova ordem visual e manda para o Back-end
                                 let items = Array.from(this.$refs.statusList.children).map(el => el.dataset.id);
                                 $wire.atualizarOrdemStatus(items);
                             }
                         });
                     }
                 }" x-init="initSortable()">
                 
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-4 gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 m-0 flex items-center gap-2">
                            <i class="ph-fill ph-funnel text-purpura-500"></i> Funil de Status do CRM
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Adicione os status desejados e <b>arraste-os para cima ou para baixo</b> para definir a ordem das etapas.</p>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <select wire:model="novoStatusSelecionado" class="flex-1 md:w-48 text-sm font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                            <option value="">Adicionar etapa...</option>
                            @foreach($statusDisponiveis as $st)
                                @if(!in_array($st->id, $statusSelecionados))
                                    <option value="{{ $st->id }}">{{ $st->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" wire:click="adicionarStatusPipeline" class="bg-purpura-100 text-purpura-800 hover:bg-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-400 px-4 py-2 rounded-lg font-bold transition">
                            <i class="ph-bold ph-plus"></i>
                        </button>
                    </div>
                </div>
                
                <!-- LISTA ORDENÁVEL -->
                <div x-ref="statusList" class="flex flex-col gap-2 p-1">
                    @forelse($statusSelecionados as $index => $statusId)
                        @php $statusObj = $statusDisponiveis->firstWhere('id', $statusId); @endphp
                        @if($statusObj)
                            <div data-id="{{ $statusId }}" wire:key="status-{{ $statusId }}" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg group transition-colors hover:border-purpura-300">
                                <div class="flex items-center gap-4">
                                    <i class="ph-bold ph-dots-six-vertical text-gray-400 cursor-grab active:cursor-grabbing drag-handle text-2xl hover:text-gray-600"></i>
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] font-black">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $statusObj->nome }}</span>
                                    </div>
                                </div>
                                <button type="button" wire:click="removerStatusPipeline('{{ $statusId }}')" class="text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 p-2 rounded-lg transition" title="Remover Etapa">
                                    <i class="ph-bold ph-trash text-lg"></i>
                                </button>
                            </div>
                        @endif
                    @empty
                        <div class="text-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-gray-400">
                            Nenhuma etapa configurada para este ciclo. Adicione os status acima.
                        </div>
                    @endforelse
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

                <div class="space-y-3">
                    @forelse($ofertasVagas as $index => $oferta)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm flex flex-col xl:flex-row gap-3 items-end transition-colors hover:border-purpura-300">
                            
                            <div class="flex-1 w-full">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade</label>
                                <select wire:model.live="ofertasVagas.{{ $index }}.unidade_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                                    <option value="">Selecione...</option>
                                    @foreach($unidadesDb as $u) 
                                        @if(in_array((string)$u->id, $unidadesSelecionadas))
                                            <option value="{{ $u->id }}">{{ $u->nome }}</option> 
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex-1 w-full">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso</label>
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
                            
                            <div class="flex-1 w-full">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno</label>
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

                            <div class="w-full xl:w-20">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1" title="Vagas">Vagas</label>
                                <input type="number" wire:model="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-black text-purpura-600 dark:text-purpura-400 text-center">
                            </div>
                            <div class="w-full xl:w-20">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1" title="Idade Mínima">Id. Mín</label>
                                <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_min" min="0" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-semibold text-center">
                            </div>
                            <div class="w-full xl:w-20">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1" title="Idade Máxima">Id. Máx</label>
                                <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_max" min="0" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-semibold text-center">
                            </div>

                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <div class="w-full xl:w-auto">
                                    <button type="button" wire:click="removeOferta({{ $index }})" class="w-full xl:w-auto px-3 py-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-lg flex items-center justify-center shadow-sm hover:bg-red-500 hover:text-white transition" title="Remover Oferta">
                                        <i class="ph-bold ph-trash text-sm"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                            <i class="ph ph-warning-circle text-4xl text-gray-400 mb-2"></i>
                            <p class="text-sm font-bold text-gray-600 dark:text-gray-400 mb-2">Sem limite de vagas ou idade configurada</p>
                            <p class="text-xs text-gray-500 mb-4 max-w-lg mx-auto">Para definir o limite de alunos aprovados ou restringir idades por unidade e curso neste ciclo, adicione uma oferta no botão acima.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- SEÇÃO 4: DOCUMENTOS EXIGIDOS PARA MATRÍCULA --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 m-0 flex items-center gap-2">
                            <i class="ph-fill ph-files text-purpura-500"></i> Documentos Exigidos na Matrícula
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Configure quais arquivos o candidato deverá enviar para o Portal da IA especificamente neste ciclo.</p>
                    </div>
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <button type="button" wire:click="addDocumento" class="px-4 py-2 bg-purpura-100 text-purpura-800 hover:bg-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-400 text-xs font-bold uppercase rounded-lg transition flex items-center gap-2 shadow-sm">
                            <i class="ph-bold ph-plus text-sm"></i> Novo Documento
                        </button>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($documentosExigidos as $index => $doc)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm flex flex-col md:flex-row gap-4 items-end transition-colors hover:border-purpura-300">
                            
                            <div class="flex-1 w-full md:w-1/3">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome do Documento <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="documentosExigidos.{{ $index }}.nome" placeholder="Ex: RG Frente e Verso" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-bold">
                                @error("documentosExigidos.$index.nome") <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="flex-1 w-full md:w-1/2">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Instruções Opcionais (Aparece na tela do aluno)</label>
                                <input type="text" wire:model="documentosExigidos.{{ $index }}.descricao" placeholder="Ex: A foto deve estar legível e fora do plástico" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                            </div>

                            <div class="w-full md:w-auto flex items-center justify-between gap-4 pb-1.5">
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" wire:model="documentosExigidos.{{ $index }}.is_obrigatorio" class="sr-only">
                                        <div class="block bg-gray-200 dark:bg-gray-700 w-10 h-6 rounded-full transition-colors duration-300" :class="{ 'bg-green-500': @js($doc['is_obrigatorio']) }"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300" :class="{ 'transform translate-x-4': @js($doc['is_obrigatorio']) }"></div>
                                    </div>
                                    <span class="ml-2 text-xs font-bold text-gray-600 dark:text-gray-300" x-text="@js($doc['is_obrigatorio']) ? 'Obrigatório' : 'Opcional'"></span>
                                </label>

                                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                    <button type="button" wire:click="removeDocumento({{ $index }})" class="p-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 dark:border-red-800 rounded-lg shadow-sm hover:bg-red-500 hover:text-white transition" title="Remover Documento">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30">
                            <i class="ph ph-files text-4xl text-gray-400 mb-2"></i>
                            <p class="text-sm font-bold text-gray-600 dark:text-gray-400">Nenhum documento exigido.</p>
                            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Os candidatos deste ciclo não precisarão enviar documentos na fase de matrícula até que você adicione as exigências acima.</p>
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