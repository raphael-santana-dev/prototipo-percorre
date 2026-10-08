<div class="px-2 md:px-6 py-4 h-[calc(100vh-60px)] flex flex-col font-sans relative w-full overflow-hidden">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    {{-- WRAPPER DO TOPO: overflow-visible e z-30 para não cortar dropdowns --}}
    <div class="mb-4 flex flex-col gap-3 shrink-0 w-full relative z-30">
        
        {{-- CARD PRINCIPAL: Título, Busca e Filtros --}}
        <div class="card !p-4 shrink-0 shadow-sm flex flex-col w-full gap-4 relative z-30 overflow-visible">
            
            {{-- LINHA SUPERIOR: Título, Busca e Ordenação --}}
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center w-full gap-4">
                
                {{-- Esquerda: Título com Distintivo de Ciclo Ativo / Último Registrado / Geral --}}
                <div class="flex items-center gap-3 w-full lg:w-auto shrink-0 flex-wrap">
                    <h2 class="t-heading-small text-gray-900 dark:text-white flex items-center gap-2 whitespace-nowrap">
                        <i class="ph-fill ph-kanban text-purpura-500"></i> Fluxo: {{ $ciclo->nome ?? 'Todos os Ciclos' }}
                    </h2>
                    @if($ciclo)
                        @if($ciclo->status && $ciclo->data_inicio <= now() && $ciclo->data_fim >= now())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Ciclo Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-sm">
                                <i class="ph-bold ph-clock-counter-clockwise text-xs"></i> Último Ciclo Registado
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-sm">
                            <i class="ph-bold ph-globe text-xs"></i> Geral (Todos os Ciclos)
                        </span>
                    @endif
                </div>

                {{-- Direita: Busca e Ordenação --}}
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto lg:ml-auto">
                    <div class="relative w-full sm:w-64 shrink-0">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" wire:model.live.debounce.500ms="filtroBusca" placeholder="Nome, E-mail ou CPF" class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 shadow-sm transition-colors dark:bg-gray-800 dark:border-gray-700">
                    </div>

                    <div class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm w-full sm:w-auto shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Ordenar</span>
                        <select wire:model.live="ordenacao" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 dark:text-gray-200 cursor-pointer font-medium w-full sm:w-auto truncate">
                            <option value="posicao_ranking_geral_asc">Melhor Pos. Geral</option>    
                            <option value="recentes">Mais Recentes</option>
                            <option value="pontuacao_desc">Maior Pontuação</option>
                            <option value="pontuacao_asc">Menor Pontuação</option>
                            <option value="posicao_ranking_geral_desc">Pior Pos. Geral</option>
                            @if($unidadesDb->count() === 1)
                                <option value="posicao_ranking_unidade_asc">Melhor Pos. Unidade</option>
                                <option value="posicao_ranking_unidade_desc">Pior Pos. Unidade</option>
                            @endif
                            <option value="nome_asc">Nome (A-Z)</option>
                            <option value="nome_desc">Nome (Z-A)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- LINHA INFERIOR: Chips e Ações de Top X --}}
            <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between w-full border-t border-gray-100 dark:border-gray-800 pt-4 gap-4 relative z-30 overflow-visible"
                 x-data="{ visible: [], allFilters: ['curso', 'unidade', 'data'], init() { if ($wire.filtroCurso) this.visible.push('curso'); if ($wire.filtroUnidade) this.visible.push('unidade'); if ($wire.filtroDataInicio || $wire.filtroDataFim) this.visible.push('data'); }, add(f) { if(!this.visible.includes(f)) this.visible.push(f); }, remove(f) { if (f === 'curso') $wire.set('filtroCurso', ''); if (f === 'unidade') $wire.set('filtroUnidade', ''); if (f === 'data') { $wire.set('filtroDataInicio', ''); $wire.set('filtroDataFim', ''); } this.visible = this.visible.filter(i => i !== f); }, get canAddMore() { return this.visible.length < this.allFilters.length; } }">
                
                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto overflow-visible">
                    
                    {{-- CHIP FIXO: CICLO (com opção "Qualquer") --}}
                    <div class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0 gap-1.5">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Ciclo</span>
                        <select wire:model.live="cicloId" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-1 pr-7 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[170px] truncate">
                            <option value="">Qualquer</option>
                            @foreach($ciclosDb as $cId => $cNome) 
                                <option value="{{ $cId }}">{{ $cNome }}</option> 
                            @endforeach
                        </select>
                        @if($ciclo)
                            @if($ciclo->status && $ciclo->data_inicio <= now() && $ciclo->data_fim >= now())
                                <span class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ativo
                                </span>
                            @else
                                <span class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    Histórico
                                </span>
                            @endif
                        @else
                            <span class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                Geral
                            </span>
                        @endif
                    </div>

                    {{-- Chip Dinâmico: Curso --}}
                    <div x-show="visible.includes('curso')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Curso</span>
                        <select wire:model.live="filtroCurso" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[150px] truncate">
                            <option value="">Qualquer</option>
                            @foreach($cursosDb as $cur) <option value="{{ $cur->id }}">{{ $cur->nome }}</option> @endforeach
                        </select>
                        <button @click="remove('curso')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                    </div>

                    {{-- Chip Dinâmico ou Fixo: Unidade --}}
                    @if($unidadesDb->count() === 1)
                        <div class="flex items-center h-9 px-2.5 rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-800 shadow-sm shrink-0">
                            <i class="ph-fill ph-map-pin mr-1.5 text-indigo-500"></i>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-700 dark:text-indigo-300">{{ $unidadesDb->first()->nome }}</span>
                        </div>
                    @else
                        <div x-show="visible.includes('unidade')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0">
                            <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Unidade</span>
                            <select wire:model.live="filtroUnidade" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[150px] truncate">
                                <option value="">Qualquer</option>
                                @foreach($unidadesDb as $uni) <option value="{{ $uni->id }}">{{ $uni->nome }}</option> @endforeach
                            </select>
                            <button @click="remove('unidade')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                        </div>
                    @endif

                    {{-- Chip Dinâmico: Data de/até --}}
                    <div x-show="visible.includes('data')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Data</span>
                        <input type="date" wire:model.live="filtroDataInicio" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 px-2 text-gray-800 dark:text-gray-200 w-[115px] sm:w-[130px]" title="De">
                        <span class="text-gray-400 text-[10px] font-bold uppercase">Até</span>
                        <input type="date" wire:model.live="filtroDataFim" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-1 pr-2 text-gray-800 dark:text-gray-200 w-[115px] sm:w-[130px]" title="Até">
                        <button @click="remove('data')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                    </div>

                    {{-- Dropdown Adicionar Filtro (z-[100] garantindo sobreposição limpa) --}}
                    <div x-show="canAddMore" x-data="{ open: false }" class="relative ml-1 shrink-0" x-cloak>
                        <button @click="open = !open" class="text-sm font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 transition-colors h-9 px-2 focus:outline-none">
                            + Filtro
                        </button>
                        <div x-show="open" 
                             @click.away="open = false" 
                             class="absolute left-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl z-[100] py-2" 
                             x-cloak>
                            <button x-show="!visible.includes('curso')" @click="add('curso'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3">
                                <i class="ph-bold ph-graduation-cap text-gray-400 text-lg"></i> Curso
                            </button>
                            @if($unidadesDb->count() > 1)
                            <button x-show="!visible.includes('unidade')" @click="add('unidade'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3">
                                <i class="ph-bold ph-buildings text-gray-400 text-lg"></i> Unidade
                            </button>
                            @endif
                            <button x-show="!visible.includes('data')" @click="add('data'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3">
                                <i class="ph-bold ph-calendar text-gray-400 text-lg"></i> Intervalo de Datas
                            </button>
                        </div>
                    </div>

                    <button x-show="$wire.filtroCurso || $wire.filtroUnidade || $wire.filtroDataInicio || $wire.filtroDataFim || $wire.filtroBusca" x-cloak wire:click="limparFiltros" @click="visible = []" class="text-sm font-bold text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-1 transition shrink-0">
                        Limpar Todos
                    </button>
                </div>

                @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
                    <div class="flex flex-wrap items-center gap-3 text-sm mt-3 xl:mt-0 w-full xl:w-auto justify-start xl:justify-end shrink-0">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hidden sm:inline-block">Seleção Rápida:</span>
                        <button wire:click="selecionarTop(10)" class="text-purpura-600 font-bold hover:underline">Top 10</button> &middot;
                        <button wire:click="selecionarTop(50)" class="text-purpura-600 font-bold hover:underline">Top 50</button> &middot;
                        <button wire:click="selecionarTop(100)" class="text-purpura-600 font-bold hover:underline">Top 100</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- BARRA ESCURA DE AÇÕES EM LOTE --}}
        @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
            <div x-data="{ count: @entangle('selecionados').live }" x-show="count.length > 0" x-cloak 
                 class="bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 p-3 rounded-lg flex flex-col lg:flex-row justify-between items-center gap-4 shadow-sm w-full transition-all">
                
                <div class="flex items-center shrink-0">
                    <span class="font-medium text-white text-sm"><span x-text="count.length"></span> inscrições selecionadas</span>
                    <button wire:click="$set('selecionados', [])" class="ml-4 text-xs text-gray-400 hover:text-white font-medium transition">Limpar Seleção</button>
                </div>
                
                <div class="flex flex-wrap items-center justify-end gap-3 w-full lg:w-auto">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select wire:model="statusDestinoLote" class="w-full sm:w-auto h-9 !py-0 text-sm border-transparent bg-gray-800 text-white focus:ring-1 focus:ring-white rounded-lg">
                            <option value="">Alterar status para...</option>
                            @foreach($colunas as $col)
                                <option value="{{ $col->id }}">{{ $col->nome }}</option>
                            @endforeach
                        </select>
                        <button wire:click="moverLote" class="btn btn--primary btn--small bg-blue-600 hover:bg-blue-700 border-none">
                            Aplicar
                        </button>
                    </div>
                </div>
            </div>
        @endif
        
    </div>

    {{-- ÁREA DAS COLUNAS DO KANBAN --}}
    <div class="flex-1 flex gap-4 overflow-hidden relative z-10">
        
        <div class="flex-1 overflow-x-auto overflow-y-hidden custom-scrollbar pb-4"
             x-data="{
                 initSortable() {
                     document.querySelectorAll('.kanban-coluna').forEach(el => {
                         if (el._sortable) {
                             el._sortable.destroy();
                         }
                         el._sortable = new Sortable(el, {
                             group: 'crm-pipeline', 
                             animation: 150,
                             ghostClass: 'opacity-50',
                             onEnd: (evt) => {
                                 let inscricaoId = evt.item.dataset.id;
                                 let novoStatusId = evt.to.dataset.status;
                                 if(evt.from !== evt.to) {
                                     @this.atualizarStatus(inscricaoId, novoStatusId);
                                 }
                             }
                         });
                     });
                 }
             }" 
             x-init="
                 initSortable();
                 document.addEventListener('livewire:navigated', () => initSortable());
                 if (window.Livewire) {
                     Livewire.hook('morph.updated', () => { initSortable(); });
                 }
             ">
             
            <div class="flex h-full gap-4 items-start w-max px-1">
                @if($colunas->isNotEmpty())
                    @foreach($colunas as $coluna)
                        <div class="w-80 flex flex-col max-h-full bg-gray-100/50 border border-gray-200 dark:bg-gray-800/40 dark:border-gray-700 rounded-xl overflow-hidden shrink-0">
                            
                            <div class="p-4 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center shrink-0">
                                <h3 class="font-bold text-gray-700 dark:text-gray-300 text-xs uppercase tracking-wide">{{ $coluna->nome }}</h3>
                                <span class="tag tag--small tag--outline tag--neutral shadow-sm">
                                    {{ isset($resumo[$coluna->id]['total']) ? $resumo[$coluna->id]['total'] : 0 }}
                                </span>
                            </div>

                            <div class="p-3 flex-1 overflow-y-auto custom-scrollbar kanban-coluna space-y-3 min-h-[150px]" 
                                 data-status="{{ $coluna->id }}"
                                 x-on:scroll.debounce.150ms="if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 60) { $wire.carregarMais({{ $coluna->id }}) }">
                                
                                @if(isset($inscricoesGrupadas[$coluna->id]))
                                    @foreach($inscricoesGrupadas[$coluna->id] as $inscricao)
                                        
                                        <div wire:key="card-{{ $inscricao->id }}" class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab active:cursor-grabbing hover:border-purpura-400 dark:hover:border-purpura-500 hover:shadow-md transition group relative flex flex-col gap-2" data-id="{{ $inscricao->id }}">
                                            
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" wire:model.live="selecionados" value="{{ $inscricao->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer" onmousedown="event.stopPropagation()">
                                                    <span class="text-[10px] font-bold text-gray-400 font-mono">#{{ str_pad($inscricao->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                                
                                                <span class="tag tag--small tag--filled tag--ponkan" title="Pontuação Total">
                                                    {{ $inscricao->pontuacao_total ?? 0 }} pts
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate" title="{{ $inscricao->nome }}">{{ $inscricao->nome }}</h4>
                                            
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/80 rounded border border-gray-100 dark:border-gray-700 p-2">
                                                <div class="flex items-center gap-1" title="Ranking Geral">
                                                    <i class="ph-fill ph-trophy text-ponkan-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_geral ?? '-' }}º</span>
                                                </div>
                                                <div class="w-px h-3 bg-gray-300 dark:bg-gray-600"></div>
                                                <div class="flex items-center gap-1" title="Ranking na Unidade">
                                                    <i class="ph-fill ph-buildings text-blue-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_unidade ?? '-' }}º</span>
                                                </div>
                                                <div class="w-px h-3 bg-gray-300 dark:bg-gray-600"></div>
                                                <div class="flex items-center gap-1" title="Ranking no Curso">
                                                    <i class="ph-fill ph-graduation-cap text-purpura-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_curso ?? '-' }}º</span>
                                                </div>
                                            </div>

                                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-2 space-y-2 border border-gray-100 dark:border-gray-700 mt-1">
                                                <div class="flex items-center gap-1.5 text-[10px] font-medium text-gray-600 dark:text-gray-400 leading-none">
                                                    <i class="ph-fill ph-map-pin text-purpura-400 shrink-0"></i> <span class="truncate">{{ $inscricao->unidade->nome ?? 'Unidade não inf.' }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[10px] font-medium text-gray-600 dark:text-gray-400 leading-none">
                                                    <i class="ph-fill ph-book-open text-purpura-400 shrink-0"></i> <span class="truncate">{{ $inscricao->curso->nome ?? 'Curso não inf.' }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[10px] font-medium text-gray-600 dark:text-gray-400 leading-none">
                                                    <i class="ph-fill ph-clock text-purpura-400 shrink-0"></i> <span class="truncate">{{ $inscricao->turno->nome ?? 'Turno não inf.' }}</span>
                                                </div>
                                            </div>

                                            <div class="flex justify-between items-center mt-2 pt-3 border-t border-gray-100 dark:border-gray-700" onmousedown="event.stopPropagation()">
                                                
                                                <span class="text-[9px] font-bold text-gray-400 flex items-center gap-1 uppercase tracking-wide">
                                                    <i class="ph-fill ph-clock text-gray-300"></i> {{ $inscricao->updated_at ? $inscricao->updated_at->diffForHumans(null, true, true) : 'Recente' }}
                                                </span>
                                                
                                                <div class="flex items-center gap-0.5">
                                                    <button type="button" wire:click="showContactInfo({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-lg transition" title="Ver Contatos e Endereço">
                                                        <i class="ph-bold ph-address-book text-[16px]"></i>
                                                    </button>
                                                    <button type="button" @click="$dispatch('open-regras-crm', { id: {{ $inscricao->id }} })" class="p-1.5 text-gray-400 hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-700 rounded-lg transition" title="Ver Acertos e Pontos">
                                                        <i class="ph-bold ph-list-numbers text-[16px]"></i>
                                                    </button>
                                                    <button type="button" wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700 rounded-lg transition" title="Ações Rápidas">
                                                        <i class="ph-bold ph-eye text-[16px]"></i>
                                                    </button>
                                                    <a href="{{ route('inscricoes.show', $inscricao->id) }}" target="_blank" class="p-1.5 text-gray-400 hover:text-pistache-600 hover:bg-pistache-50 dark:hover:bg-gray-700 rounded-lg transition" title="Ficha Completa da Inscrição">
                                                        <i class="ph-bold ph-arrow-square-out text-[16px]"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if(isset($resumo[$coluna->id]) && $resumo[$coluna->id]['total'] > count($inscricoesGrupadas[$coluna->id] ?? []))
                                    <div class="py-4 flex flex-col items-center justify-center text-purpura-400 opacity-60">
                                        <i class="ph-bold ph-spinner animate-spin text-2xl mb-2"></i>
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Carregando Mais...</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="w-full flex items-center justify-center p-12 text-gray-400">
                        Nenhuma etapa ou coluna encontrada para exibir.
                    </div>
                @endif
            </div>
        </div>

        {{-- BARRA LATERAL: Relação de Vagas e Totalizadores --}}
        <div class="w-64 shrink-0 bg-transparent border-l border-gray-200 dark:border-gray-700 pl-4 flex flex-col h-full overflow-y-auto custom-scrollbar">
            
            {{-- Card de Ocupação e Relação de Vagas --}}
            @if(($relacaoVagas['total_vagas'] ?? 0) > 0)
                <div class="border border-indigo-200 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl p-3.5 mb-3.5 shadow-sm">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1">
                            <i class="ph-bold ph-graduation-cap"></i> Relação de Vagas
                        </span>
                        <span class="text-[10px] font-extrabold text-indigo-600 dark:text-indigo-300">
                            {{ $relacaoVagas['percentual_preenchido'] ?? 0 }}%
                        </span>
                    </div>

                    <div class="text-xl font-black text-gray-900 dark:text-gray-100 tracking-tight">
                        {{ number_format($relacaoVagas['vagas_ocupadas'] ?? 0, 0, ',', '.') }} 
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">/ {{ number_format($relacaoVagas['total_vagas'] ?? 0, 0, ',', '.') }}</span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2.5 overflow-hidden">
                        <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500" style="width: {{ $relacaoVagas['percentual_preenchido'] ?? 0 }}%"></div>
                    </div>

                    <div class="flex justify-between items-center text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-2 pt-1 border-t border-indigo-100/80 dark:border-indigo-900/40">
                        <span>Restantes: <strong class="text-gray-700 dark:text-gray-300">{{ number_format($relacaoVagas['saldo_disponivel'] ?? 0, 0, ',', '.') }}</strong></span>
                        @if(($relacaoVagas['total_vagas'] ?? 0) > 0 && $totalInscricoes > 0)
                            <span>C/V: <strong class="text-indigo-600 dark:text-indigo-400">{{ round($totalInscricoes / max(1, $relacaoVagas['total_vagas']), 1) }}</strong></span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Total Geral de Inscritos --}}
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-3 mb-4 text-center bg-white/40 dark:bg-gray-800/40">
                <span class="block t-label-12-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Total de Candidatos</span>
                <span class="text-2xl font-black text-gray-800 dark:text-gray-200">{{ number_format($totalInscricoes, 0, ',', '.') }}</span>
            </div>
            
            {{-- Distribuição por Status do Pipeline --}}
            <div class="space-y-0 flex-1 border-t border-gray-200 dark:border-gray-700">
                @foreach($resumo as $id => $dado)
                    <div class="flex justify-between items-center text-sm py-2.5 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition px-1">
                        <span class="font-bold text-gray-600 dark:text-gray-400 text-[10px] uppercase truncate w-32" title="{{ $dado['nome'] }}">{{ $dado['nome'] }}</span>
                        <span class="font-black text-gray-800 dark:text-gray-300 text-xs">{{ number_format($dado['total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- MODAL ANTI-SPAM --}}
    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="card !w-full !max-w-2xl !p-0 shadow-2xl">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-pitaya-50 dark:bg-pitaya-900/20 w-full">
                    <h3 class="text-lg font-bold text-pitaya-700 dark:text-pitaya-400 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta de E-mail Duplicado
                    </h3>
                    <button wire:click="cancelarAntiSpam" class="text-gray-400 hover:text-pitaya-600 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar w-full">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 font-medium leading-relaxed">
                        O sistema detetou que <strong>{{ count($conflitosAntiSpam) }}</strong> {{ count($conflitosAntiSpam) == 1 ? 'candidato já recebeu' : 'candidatos já receberam' }} o e-mail automático configurado para a etapa <strong>{{ $acaoPendenteNomeStatus ?? 'selecionada' }}</strong>.
                    </p>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 font-bold uppercase text-[10px]">Candidato</th>
                                    <th class="px-4 py-2 font-bold uppercase text-[10px] text-right">Ação de Remoção</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($conflitosAntiSpam as $conflito)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition" wire:key="conflito-{{ $conflito['id'] }}">
                                        <td class="px-4 py-3">
                                            <span class="block font-bold text-gray-900 dark:text-white">{{ $conflito['nome'] }}</span>
                                            <span class="text-xs text-gray-500">{{ $conflito['email'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button wire:click="removerConflitoAntiSpam({{ $conflito['id'] }})" class="btn btn--secondary btn--small !text-pitaya-600 !border-pitaya-200 hover:!bg-pitaya-50">
                                                Tirar da Lista
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center gap-4 w-full">
                    <button wire:click="cancelarAntiSpam" class="btn btn--secondary btn--medium">
                        Cancelar Tudo
                    </button>
                    <button wire:click="prosseguirComReenvioAntiSpam" class="btn btn--primary btn--medium !bg-pitaya-600 hover:!bg-pitaya-700 border-none">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Prosseguir e Reenviar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>