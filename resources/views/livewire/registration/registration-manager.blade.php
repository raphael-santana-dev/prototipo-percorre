<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Inscrições</h2>

        <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-4 py-2 rounded-full border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800">
            Visão Global (Administrador)
        </span>
    </div>

    <div class="flex items-center gap-2 w-full md:w-auto">
        <button wire:click="recalcularScoresGlobais" 
                wire:confirm="Processar TODAS as inscrições de TODOS os ciclos? Isso pode levar alguns segundos."
                wire:loading.attr="disabled"
                class="flex items-center justify-center px-4 py-2 gap-2 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 text-yellow-700 font-bold rounded-lg transition shadow-sm disabled:opacity-50">
            <i wire:loading.remove wire:target="recalcularScoresGlobais" class="ph-bold ph-calculator text-lg"></i>
            <i wire:loading wire:target="recalcularScoresGlobais" class="ph-bold ph-spinner animate-spin text-lg"></i>
            <span class="text-sm">Recalcular Scores</span>
        </button>

        <button wire:click="gerarRankingGlobal" 
                wire:confirm="Gerar ranking (Geral e por Turma) para TODOS os ciclos baseados nas notas atuais?"
                wire:loading.attr="disabled"
                class="flex items-center justify-center px-4 py-2 gap-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 font-bold rounded-lg transition shadow-sm disabled:opacity-50">
            <i wire:loading.remove wire:target="gerarRankingGlobal" class="ph-bold ph-medal text-lg"></i>
            <i wire:loading wire:target="gerarRankingGlobal" class="ph-bold ph-spinner animate-spin text-lg"></i>
            <span class="text-sm">Gerar Rankings</span>
        </button>
    </div>

    
    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    {{-- BLOCO DE FILTROS COM GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
        
        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-magnifying-glass text-purpura-500"></i> Buscar Candidato
            </label>
            <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Nome ou CPF..." class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-tag text-purpura-500"></i> Status
            </label>
            <select wire:model.live="filtroStatus" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Status</option>
                @foreach($statusInscricoesDb as $status) 
                    <option value="{{ $status->id }}">{{ $status->nome }}</option> 
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-calendar-check text-purpura-500"></i> Semestre / Ciclo
            </label>
            <select wire:model.live="filtroCiclo" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Semestres</option>
                @foreach($ciclosDb as $ciclo) 
                    <option value="{{ $ciclo->id }}">{{ $ciclo->nome }}</option> 
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-buildings text-purpura-500"></i> Unidade
            </label>
            <select wire:model.live="filtroUnidade" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todas as Unidades</option>
                @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-clock text-purpura-500"></i> Turno
            </label>
            <select wire:model.live="filtroTurno" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Turnos</option>
                @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-graduation-cap text-purpura-500"></i> Curso
            </label>
            <select wire:model.live="filtroCurso" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Cursos</option>
                @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
            </select>
        </div>
    </div>

    {{-- BOTÕES DE SELEÇÃO RÁPIDA E BARRA DE LOTE --}}
    <div class="flex justify-end items-center mb-4 gap-2">
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Selecionar rápido:</span>
        <button wire:click="selecionarQuantidade(10)" class="text-xs px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-lg shadow-sm transition">Os primeiros 10</button>
        <button wire:click="selecionarQuantidade(50)" class="text-xs px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-lg shadow-sm transition">Os primeiros 50</button>
    </div>

    @if(count($selecionadas) > 0)
    <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 p-4 rounded-xl mb-6 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
        <div class="flex items-center">
            <span class="font-bold text-indigo-800 dark:text-indigo-300 text-lg">{{ count($selecionadas) }} selecionadas</span>
            <button wire:click="desmarcarTodas" class="ml-4 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 hover:underline font-medium">Limpar seleção</button>
        </div>
        
        <div class="flex flex-wrap items-center justify-end gap-2">
            <span class="text-xs font-bold text-indigo-800 dark:text-indigo-300 uppercase mr-1">Alterar status para:</span>
            
            @foreach($statusInscricoesDb as $status)
                <button wire:click="alterarStatusLoteRapido({{ $status->id }})" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-600 dark:hover:bg-indigo-500 hover:text-white rounded-md text-xs font-bold transition shadow-sm">
                    {{ $status->nome }}
                </button>
            @endforeach

            <div class="w-px h-6 bg-indigo-200 dark:bg-indigo-700 mx-1 hidden md:block"></div>
            <button wire:click="abrirModalLote" class="bg-purpura-500 hover:bg-purpura-600 text-white px-4 py-1.5 rounded-md shadow text-xs font-bold transition">
                Ver no Modal
            </button>
        </div>
    </div>
    @endif

    {{-- TABELA DE DADOS --}}
    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        {{-- SLOT PADRÃO (LISTA MINIMALISTA) --}}
        @forelse($registros as $inscricao)
            <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                
                {{-- Reduzimos o padding de py-4 para py-2.5 --}}
                <td class="px-4 py-2.5 text-center">
                    <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                </td>

                <td class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-xs">#{{ $inscricao->id }}</td>
                
                <td class="px-4 py-2.5">
                    <div class="font-bold text-gray-900 text-sm dark:text-white">{{ $inscricao->nome }}</div>
                    <div class="text-[11px] text-gray-400 dark:text-gray-500">{{ $inscricao->cpf }}</div>
                </td>
                
                <td class="px-4 py-2.5">
                    <div class="font-semibold text-gray-700 text-sm dark:text-gray-300">{{ $inscricao->curso->nome ?? 'Não selecionado' }}</div>
                    <div class="text-[11px] text-gray-400">{{ $inscricao->unidade->nome ?? '-' }}</div>
                </td>
                
                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-400">
                    Passo {{ $inscricao->etapa_atual }}
                </td>

                {{-- NOVA COLUNA: SCORE E RANKING MULTIPLO --}}
                <td class="px-4 py-2.5 text-center align-top">
                    <span class="px-2 py-1 text-xs font-bold {{ $inscricao->pontuacao_total > 0 ? 'text-green-700 bg-green-50 border border-green-200' : 'text-gray-400 bg-gray-50' }} rounded-full inline-block mb-1">
                        {{ $inscricao->pontuacao_total ?? 0 }} pts
                    </span>
                    
                    @if($inscricao->posicao_ranking_geral)
                        <div class="mt-1 grid grid-cols-1 gap-1 text-[9px] font-bold w-max mx-auto text-left">
                            
                            <!-- 1. Geral -->
                            <span class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                <span class="text-gray-400">GERAL:</span> {{ $inscricao->posicao_ranking_geral }}º
                            </span>
                            
                            <!-- 2. Unidade -->
                            @if($inscricao->posicao_ranking_unidade)
                                <span class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800">
                                    <span class="opacity-50">UNID:</span> {{ $inscricao->posicao_ranking_unidade }}º
                                </span>
                            @endif

                            <!-- 3. Curso -->
                            @if($inscricao->posicao_ranking_curso)
                                <span class="bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800">
                                    <span class="opacity-50">CURSO:</span> {{ $inscricao->posicao_ranking_curso }}º
                                </span>
                            @endif
                            
                            <!-- 4. Turma (Unidade + Curso + Turno) -->
                            @if($inscricao->posicao_ranking)
                                @php
                                    $corRanking = match($inscricao->posicao_ranking) {
                                        1 => 'bg-yellow-100 text-yellow-800 border-yellow-300', 
                                        2 => 'bg-gray-200 text-gray-700 border-gray-300',      
                                        3 => 'bg-orange-100 text-orange-800 border-orange-300', 
                                        default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    };
                                @endphp
                                <span class="{{ $corRanking }} px-1.5 py-0.5 rounded border shadow-sm flex items-center gap-1">
                                    @if($inscricao->posicao_ranking <= 3) <i class="ph-fill ph-medal"></i> @endif
                                    <span class="opacity-50">TURMA:</span> {{ $inscricao->posicao_ranking }}º
                                </span>
                            @endif
                        </div>
                    @endif
                </td>
                
                <td class="px-4 py-2.5">
                    @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded border whitespace-nowrap" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                        {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 text-right">
                    <button wire:click="showQuickView({{ $inscricao->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                        <i class="text-xl ph ph-info"></i>
                    </button>

                    <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                        <i class="text-xl ph ph-eye"></i>
                    </a>

                    <button wire:click="#" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente essa inscrição do sistema?') || event.stopImmediatePropagation()">
                        <i class="text-xl ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhuma inscrição encontrada.
                </td>
            </tr>   
        @endforelse

        {{-- SLOT DO GRID (CARDS MINIMALISTAS) --}}
        <x-slot name="gridSlot">
            @foreach($registros as $inscricao)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    
                    <!-- Topo do Card (Status + Ações) -->
                    <div class="flex items-center justify-between mb-4">
                        @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                        <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded border flex items-center gap-1.5" style="background-color: {{ $corHex }}10; color: {{ $corHex }}; border-color: {{ $corHex }}30;">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $corHex }};"></span>
                            {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                            <button wire:click="showQuickView({{ $inscricao->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                <i class="text-xl ph ph-info"></i>
                            </button>

                            <a href="#" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                                <i class="text-xl ph ph-eye"></i>
                            </a>

                            <button wire:click="#" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente essa inscrição do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-xl ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Meio do Card (Avatar/Icone e Nome) -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-10 h-10 text-xl text-gray-400 bg-gray-50 rounded-full dark:bg-gray-700 dark:text-gray-300 shrink-0">
                            <i class="ph ph-user"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold text-gray-900 truncate dark:text-white">{{ $inscricao->nome }}</h4>
                            <p class="text-xs text-gray-500 truncate dark:text-gray-400">ID: {{ $inscricao->id }} • {{ $inscricao->cpf }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-50 dark:border-gray-700/50 border-dashed my-2"></div>

                    <!-- Rodapé do Card (Info extra) -->
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <i class="text-sm ph ph-graduation-cap"></i>
                            <span class="truncate max-w-[120px]">{{ $inscricao->curso->nome ?? 'Não selecionado' }}</span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div class="text-xs font-bold text-gray-600 dark:text-gray-300">
                                {{ $inscricao->pontuacao_total ?? 0 }} pts
                            </div>
                            @if($inscricao->posicao_ranking_geral)
                                <div class="flex items-center gap-1">
                                    <span class="text-[9px] font-bold bg-gray-100 px-1.5 py-0.5 rounded border">G: {{ $inscricao->posicao_ranking_geral }}º</span>
                                    <span class="text-[9px] font-bold bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border">T: {{ $inscricao->posicao_ranking }}º</span>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            @endforeach
        </x-slot>

    </x-table>

    {{-- MODAL DE ALTERAÇÃO EM LOTE --}}
    @if($modalLoteAberto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md flex flex-col overflow-hidden">
            
            <div class="flex justify-between items-center p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Alterar Status em Lote</h3>
                <button wire:click="$set('modalLoteAberto', false)" class="text-gray-400 hover:text-red-500 transition">
                    <i class="text-2xl ph ph-x"></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 p-4 rounded-lg mb-6 border border-indigo-100 dark:border-indigo-800">
                    Você está prestes a alterar o status de <strong class="text-lg">{{ count($selecionadas) }}</strong> inscrições simultaneamente.
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Selecione o Novo Status <span class="text-red-500">*</span></label>
                    <select wire:model="novoStatusId" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                        <option value="">-- Selecione --</option>
                        @foreach($statusInscricoesDb as $status)
                            <option value="{{ $status->id }}">{{ $status->nome }}</option>
                        @endforeach
                    </select>
                    @error('novoStatusId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="p-5 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                <button wire:click="$set('modalLoteAberto', false)" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-lg transition shadow-sm">
                    Cancelar
                </button>
                <button wire:click="salvarStatusEmLote" class="px-4 py-2 bg-purpura-500 hover:bg-purpura-600 text-white font-bold rounded-lg transition shadow-sm">
                    Confirmar Alteração
                </button>
            </div>
        </div>
    </div>
    @endif

    <x-fab :actions="$this->fabActions"
    main-color="bg-gray-800 hover:bg-black" 
    sub-btn-bg="bg-indigo-50 hover:bg-indigo-100" />

    {{-- TOAST --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>