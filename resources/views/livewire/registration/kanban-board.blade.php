<div class="px-2 md:px-6 py-4 h-[calc(100vh-60px)] flex flex-col font-sans relative w-full overflow-hidden">

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <div class="mb-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 shrink-0">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
            <h2 class="text-xl md:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-kanban text-purpura-500"></i> Fluxo de Inscrição: {{ $ciclo->nome ?? 'Nenhum Ciclo Ativo' }}
            </h2>

            @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
                <div x-data="{ count: @entangle('selecionados').live }" x-show="count.length > 0" x-cloak class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <span class="text-xs font-bold text-indigo-800 dark:text-indigo-300 uppercase tracking-wider">
                        <span x-text="count.length"></span> selecionados
                    </span>
                    <select wire:model="statusDestinoLote" class="text-xs font-bold border-indigo-300 dark:border-indigo-700 bg-white dark:bg-gray-800 rounded-md py-1 focus:ring-purpura-500">
                        <option value="">Mover para coluna...</option>
                        @foreach($colunas as $col)
                            <option value="{{ $col->id }}">{{ $col->nome }}</option>
                        @endforeach
                    </select>
                    <button wire:click="moverLote" class="bg-indigo-600 text-white px-3 py-1 rounded-md text-xs font-bold shadow-sm hover:bg-indigo-700 transition">
                        Confirmar
                    </button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="text" wire:model.live.debounce.500ms="filtroBusca" placeholder="Nome ou CPF..." class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            
            <select wire:model.live="ordenacao" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600 bg-gray-50/50">
                <option value="recentes">Mais Recentes</option>
                <option value="pontuacao_asc">Pontuação (Ascendente)</option>
                <option value="pontuacao_desc">Pontuação (Descendente)</option>
                <option value="posicao_ranking_geral_asc">Posição Geral (Ascendente)</option>
                <option value="posicao_ranking_geral_desc">Posição Geral (Descendente)</option>
                @if($unidadesDb->count() === 1)
                    <option value="posicao_ranking_unidade_asc">Posição Unidade (Ascendente)</option>
                    <option value="posicao_ranking_unidade_desc">Posição Unidade (Descendente)</option>
                @endif
                <option value="nome_asc">Ordenar: Nome (A-Z)</option>
                <option value="nome_desc">Ordenar: Nome (Z-A)</option>
            </select>
            
            <select wire:model.live="filtroCurso" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
                <option value="">Todos os Cursos</option>
                @foreach($cursosDb as $cur) <option value="{{ $cur->id }}">{{ $cur->nome }}</option> @endforeach
            </select>
            
            @if($unidadesDb->count() === 1)
                <div class="rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 shadow-sm text-xs w-full flex items-center px-3 text-purpura-600 dark:text-purpura-400 font-bold uppercase tracking-wider h-9">
                    <i class="ph-fill ph-map-pin mr-2 text-purpura-400"></i> {{ $unidadesDb->first()->nome }}
                </div>
            @else
                <select wire:model.live="filtroUnidade" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Todas as Unidades</option>
                    @foreach($unidadesDb as $uni) <option value="{{ $uni->id }}">{{ $uni->nome }}</option> @endforeach
                </select>
            @endif
            
            <div class="relative w-full">
                <span class="absolute -top-2 left-2 bg-white dark:bg-gray-800 px-1 text-[9px] font-bold text-gray-500 uppercase tracking-wider">Registros Até</span>
                <input type="datetime-local" wire:model.live="filtroDataFim" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            </div>

            <button wire:click="limparFiltros" class="w-full flex items-center justify-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg shadow-sm transition dark:bg-gray-700 dark:text-gray-300 text-xs py-2">
                <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
            </button>
        </div>
    </div>

    <div class="flex-1 flex gap-4 overflow-hidden">
        
        <div class="flex-1 overflow-x-auto overflow-y-hidden custom-scrollbar pb-4"
             x-data="{
                 initSortable() {
                     document.querySelectorAll('.kanban-coluna').forEach(el => {
                         new Sortable(el, {
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
             }" x-init="initSortable()">
             
            <div class="flex h-full gap-4 items-start w-max px-1">
                @if($ciclo)
                    @foreach($colunas as $coluna)
                        <div class="w-80 flex flex-col max-h-full bg-gray-100/50 border border-gray-200 rounded-xl overflow-hidden shrink-0">
                            
                            <div class="p-3 bg-gray-100 border-b border-gray-200 flex justify-between items-center shrink-0">
                                <h3 class="font-bold text-gray-700 text-xs uppercase tracking-wide">{{ $coluna->nome }}</h3>
                                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">
                                    {{ isset($resumo[$coluna->id]['total']) ? $resumo[$coluna->id]['total'] : 0 }}
                                </span>
                            </div>

                            <div class="p-3 flex-1 overflow-y-auto custom-scrollbar kanban-coluna space-y-3 min-h-[150px]" 
                                 data-status="{{ $coluna->id }}"
                                 x-on:scroll.debounce.150ms="if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 60) { $wire.carregarMais({{ $coluna->id }}) }">
                                
                                @if(isset($inscricoesGrupadas[$coluna->id]))
                                    @foreach($inscricoesGrupadas[$coluna->id] as $inscricao)
                                        
                                        <div wire:key="card-{{ $inscricao->id }}" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab active:cursor-grabbing hover:border-purpura-400 dark:hover:border-purpura-500 hover:shadow-md transition group relative flex flex-col gap-2" data-id="{{ $inscricao->id }}">
                                            
                                            <!-- CABEÇALHO (ID, CHECKBOX, PONTOS) -->
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" wire:model.live="selecionados" value="{{ $inscricao->id }}" class="rounded text-purpura-600 border-gray-300 w-3 h-3 cursor-pointer" onmousedown="event.stopPropagation()">
                                                    <span class="text-[10px] font-bold text-gray-400 font-mono">#{{ str_pad($inscricao->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                                
                                                <span class="bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-[9px] font-black px-1.5 py-0.5 rounded border border-yellow-200 dark:border-yellow-800" title="Pontuação Total">
                                                    {{ $inscricao->pontuacao_total ?? 0 }} pts
                                                </span>
                                            </div>

                                            <!-- NOME DO ALUNO -->
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate" title="{{ $inscricao->nome }}">{{ $inscricao->nome }}</h4>
                                            
                                            <!-- EXIBIÇÃO DOS RANKINGS -->
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/80 rounded border border-gray-100 dark:border-gray-700 p-1.5">
                                                <div class="flex items-center gap-1" title="Ranking Geral">
                                                    <i class="ph-fill ph-trophy text-yellow-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_geral ?? $inscricao->posicao_ranking_geral ?? '-' }}º</span>
                                                </div>
                                                <div class="w-px h-3 bg-gray-300 dark:bg-gray-600"></div>
                                                <div class="flex items-center gap-1" title="Ranking na Unidade">
                                                    <i class="ph-fill ph-buildings text-blue-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_unidade ?? $inscricao->posicao_ranking_unidade ?? '-' }}º</span>
                                                </div>
                                                <div class="w-px h-3 bg-gray-300 dark:bg-gray-600"></div>
                                                <div class="flex items-center gap-1" title="Ranking no Curso">
                                                    <i class="ph-fill ph-graduation-cap text-purpura-500 text-[11px]"></i>
                                                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-300">{{ $inscricao->posicao_ranking_curso ?? $inscricao->posicao_ranking_curso ?? '-' }}º</span>
                                                </div>
                                            </div>

                                            <!-- INFOS DE UNIDADE/CURSO/TURNO (LINHA A LINHA) -->
                                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-2 space-y-1.5 border border-gray-100 dark:border-gray-700 mt-0.5">
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

                                            <!-- RODAPÉ (TEMPO E BOTÕES RÁPIDOS) -->
                                            <div class="flex justify-between items-center mt-1 pt-2 border-t border-gray-100 dark:border-gray-700" onmousedown="event.stopPropagation()">
                                                
                                                <span class="text-[9px] font-bold text-gray-400 flex items-center gap-1 uppercase tracking-wide">
                                                    <i class="ph-fill ph-clock text-gray-300"></i> {{ $inscricao->updated_at->diffForHumans(null, true, true) }}
                                                </span>
                                                
                                                <div class="flex items-center gap-0.5">
                                                    <!-- Botão de Contatos (Novo) -->
                                                    <button type="button" wire:click="showContactInfo({{ $inscricao->id }})" class="p-1 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 rounded transition" title="Ver Contatos e Endereço">
                                                        <i class="ph-bold ph-address-book text-[15px]"></i>
                                                    </button>

                                                    <!-- Botão do Mapa de Regras (Direto no Card) -->
                                                    <button type="button" @click="$dispatch('open-regras-crm', { id: {{ $inscricao->id }} })" class="p-1 text-gray-400 hover:text-yellow-500 hover:bg-yellow-50 dark:hover:bg-gray-700 rounded transition" title="Ver Acertos e Pontos">
                                                        <i class="ph-bold ph-list-numbers text-[15px]"></i>
                                                    </button>

                                                    <!-- Status rápido -->
                                                    <button type="button" wire:click="showQuickView({{ $inscricao->id }})" class="p-1 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700 rounded transition" title="Ações Rápidas">
                                                        <i class="ph-bold ph-eye text-[15px]"></i>
                                                    </button>

                                                    <!-- Link Externo para Perfil -->
                                                    <a href="{{ route('inscricoes.show', $inscricao->id) }}" target="_blank" class="p-1 text-gray-400 hover:text-ponkan-500 hover:bg-orange-50 dark:hover:bg-gray-700 rounded transition" title="Ficha Completa da Inscrição">
                                                        <i class="ph-bold ph-arrow-square-out text-[15px]"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                    @endforeach
                                @endif

                                @if(isset($resumo[$coluna->id]) && $resumo[$coluna->id]['total'] > count($inscricoesGrupadas[$coluna->id] ?? []))
                                    <div class="py-3 flex flex-col items-center justify-center text-purpura-400 opacity-60">
                                        <i class="ph-bold ph-spinner animate-spin text-xl mb-1"></i>
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Carregando Mais...</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="w-full flex items-center justify-center p-12 text-gray-400">
                        Nenhum ciclo ativo cadastrado no sistema.
                    </div>
                @endif
            </div>
        </div>

        @if($ciclo)
            <div class="w-56 shrink-0 bg-transparent border-l border-gray-200 dark:border-gray-700 pl-4 flex flex-col h-full overflow-y-auto custom-scrollbar">
                
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-4 mb-4 text-center">
                    <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Total</span>
                    <span class="text-3xl font-black text-gray-800 dark:text-gray-200">{{ number_format($totalInscricoes, 0, ',', '.') }}</span>
                </div>
                
                <div class="space-y-0 flex-1 border-t border-gray-200 dark:border-gray-700">
                    @foreach($resumo as $id => $dado)
                        <div class="flex justify-between items-center text-sm py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition px-1">
                            <span class="font-bold text-gray-600 dark:text-gray-400 text-[10px] uppercase truncate w-32" title="{{ $dado['nome'] }}">{{ $dado['nome'] }}</span>
                            <span class="font-black text-gray-800 dark:text-gray-300 text-xs">{{ number_format($dado['total'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-red-50 dark:bg-red-900/20">
                    <h3 class="text-lg font-bold text-red-700 dark:text-red-400 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta de E-mail Duplicado
                    </h3>
                    <button wire:click="cancelarAntiSpam" class="text-gray-400 hover:text-red-600 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 font-medium">
                        O sistema detectou que <strong>{{ count($conflitosAntiSpam) }}</strong> {{ count($conflitosAntiSpam) == 1 ? 'candidato já recebeu' : 'candidatos já receberam' }} o e-mail automático configurado para a etapa <strong>{{ $acaoPendenteNomeStatus ?? 'selecionada' }}</strong>.
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
                                            <button wire:click="removerConflitoAntiSpam({{ $conflito['id'] }})" class="text-xs font-bold text-red-500 hover:text-red-700 border border-red-200 hover:bg-red-50 px-2 py-1 rounded transition shadow-sm">
                                                Tirar da Lista
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center gap-4">
                    <button wire:click="cancelarAntiSpam" class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancelar Tudo
                    </button>
                    <button wire:click="prosseguirComReenvioAntiSpam" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition flex items-center gap-2">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Prosseguir e Reenviar E-mail
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>