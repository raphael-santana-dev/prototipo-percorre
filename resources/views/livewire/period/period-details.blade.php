<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaAtiva: 'visao-geral' }">
    
    <x-breadcrumb :items="$breadcrumbs" />

    {{-- CABEÇALHO UNIFICADO E MINIMALISTA --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800 flex items-center justify-center shrink-0">
                <i class="ph ph-calendar-check text-2xl text-emerald-600 dark:text-emerald-400"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-gray-900 dark:text-white">{{ $ciclo->nome }}</h1>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded border {{ $ciclo->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                        {{ $ciclo->status ? 'Ativo' : 'Encerrado' }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $ciclo->ano }} / {{ $ciclo->semestre }}º Semestre • Período: <b>{{ \Carbon\Carbon::parse($ciclo->data_inicio)->format('d/m/Y') }}</b> até <b>{{ \Carbon\Carbon::parse($ciclo->data_fim)->format('d/m/Y') }}</b>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
            <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition">
                <i class="ph-fill ph-kanban text-sm"></i> Funil CRM
            </a>
            
            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 transition">
                    <i class="ph-bold ph-pencil-simple text-sm"></i> Editar Ciclo
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-gray-200 dark:border-gray-700">
                    <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                </div>
            @endif
        </div>
    </div>

    {{-- NAVEGAÇÃO ENTRE ABAS --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-xl px-4 pt-2 shadow-sm">
        <nav class="flex gap-4 -mb-px">
            <button type="button" 
                    @click="abaAtiva = 'visao-geral'" 
                    :class="abaAtiva === 'visao-geral' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'"
                    class="py-3 px-3 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-chart-pie-slice text-base"></i>
                <span>Visão Geral & Estrutura</span>
            </button>

            <button type="button" 
                    @click="abaAtiva = 'inscricoes'" 
                    :class="abaAtiva === 'inscricoes' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'"
                    class="py-3 px-3 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-users text-base"></i>
                <span>Inscrições Registradas</span>
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ $registros->total() }}
                </span>
            </button>
        </nav>
    </div>

    {{-- ABA 1: VISÃO GERAL & ESTRUTURA ACADÊMICA --}}
    <div x-show="abaAtiva === 'visao-geral'" x-cloak class="space-y-6">
        
        @if(isset($metricas))
            <x-summary-cards :metricas="$metricas" />
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-extrabold text-gray-900 dark:text-white mb-4 text-sm flex items-center gap-2">
                <i class="ph-bold ph-tree-structure text-purpura-600"></i> Estrutura Acadêmica Vinculada
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- CURSOS --}}
                <div class="border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col justify-between">
                    <div>
                        <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-3">Cursos Ofertados ({{ $ciclo->cursos->count() }})</span>
                        <div class="flex flex-wrap gap-2">
                            @forelse($ciclo->cursos as $curso)
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-gray-200 rounded-md text-xs font-bold text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                    <span>{{ $curso->nome }}</span>
                                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                        <button wire:click="removerCurso({{ $curso->id }})" class="text-gray-400 hover:text-red-500 transition-colors ml-1" title="Desvincular">
                                            <i class="ph-bold ph-x text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 italic">Nenhum curso ofertado neste período.</p>
                            @endforelse
                        </div>
                    </div>

                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <select wire:model="cursoSelecionado" class="border-gray-300 rounded-lg text-xs py-1.5 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm flex-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Adicionar novo curso...</option>
                                @foreach($cursosDisponiveis as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endforeach
                            </select>
                            <button wire:click="adicionarCurso" class="px-3.5 py-1.5 bg-gray-900 hover:bg-black text-white font-bold rounded-lg shadow-sm transition text-xs">
                                Vincular
                            </button>
                        </div>
                    @endif
                </div>

                {{-- UNIDADES --}}
                <div class="border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 bg-gray-50/50 dark:bg-gray-900/30">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-3">Unidades Vinculadas ({{ $ciclo->unidades->count() }})</span>
                    <div class="flex flex-wrap gap-2">
                        @forelse($ciclo->unidades as $unidade)
                            <div class="px-2.5 py-1 bg-white border border-gray-200 rounded-md text-xs font-bold text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                <i class="ph-fill ph-map-pin text-purpura-500 text-xs"></i> {{ $unidade->nome }}
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Nenhuma unidade vinculada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ABA 2: INSCRIÇÕES (TABELA, FILTROS E OPERAÇÕES) --}}
    <div x-show="abaAtiva === 'inscricoes'" x-cloak class="space-y-4">
        
        {{-- AÇÕES DE RECALCULO E AUDITORIA --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Operações de Pontuação
            </span>
            
            <div class="flex items-center gap-2">
                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    <button wire:click="recalcularPontuacoes" 
                            wire:confirm="Processar scores dos alunos Deste Ciclo?"
                            class="flex items-center px-3 py-1.5 gap-1.5 bg-yellow-50 border border-yellow-200 text-yellow-700 hover:bg-yellow-100 font-bold rounded-lg text-xs shadow-sm transition">
                        <i class="ph-bold ph-calculator text-sm"></i> Recalcular Scores
                    </button>
                    <button wire:click="gerarRanking" 
                            wire:confirm="Gerar ranking para os alunos Deste Ciclo?"
                            class="flex items-center px-3 py-1.5 gap-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 font-bold rounded-lg text-xs shadow-sm transition">
                        <i class="ph-bold ph-medal text-sm"></i> Gerar Ranking
                    </button>
                @endif
            </div>
        </div>

        {{-- BARRA DE FILTROS --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Buscar por Nome ou CPF..." class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            <select wire:model.live="filtroStatus" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
                <option value="">Todos os Status</option>
                @foreach($statusInscricoesDb as $status) <option value="{{ $status->id }}">{{ $status->nome }}</option> @endforeach
            </select>
            <select wire:model.live="filtroUnidade" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
                <option value="">Todas as Unidades</option>
                @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
            </select>
            <select wire:model.live="filtroCurso" class="rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
                <option value="">Todos os Cursos</option>
                @foreach($ciclo->cursos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
            </select>
            <button wire:click="limparFiltros" class="w-full flex items-center justify-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg shadow-sm transition dark:bg-gray-700 dark:text-gray-300 text-xs py-2">
                <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
            </button>
        </div>

        {{-- SELEÇÃO EM LOTE --}}
        @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
            <div class="flex justify-between items-center px-1">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Selecionar rápido:</span>
                    <button wire:click="selecionarQuantidade(10)" class="text-[11px] px-2.5 py-1 bg-white border border-gray-200 rounded-lg shadow-sm font-bold text-gray-700 hover:bg-gray-50">10</button>
                    <button wire:click="selecionarQuantidade(50)" class="text-[11px] px-2.5 py-1 bg-white border border-gray-200 rounded-lg shadow-sm font-bold text-gray-700 hover:bg-gray-50">50</button>
                </div>
            </div>

            @if(count($selecionadas) > 0)
                <div class="bg-indigo-50 border border-indigo-200 p-3 rounded-xl flex flex-col sm:flex-row justify-between items-center gap-3 shadow-sm">
                    <div class="flex items-center">
                        <span class="font-bold text-indigo-900 text-xs">{{ count($selecionadas) }} selecionadas</span>
                        <button wire:click="desmarcarTodas" class="ml-3 text-xs text-indigo-600 hover:underline font-bold">Limpar</button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[10px] font-bold text-indigo-800 uppercase mr-1">Mover para:</span>
                        @foreach($statusInscricoesDb as $status)
                            <button wire:click="alterarStatusLoteRapido({{ $status->id }})" class="px-2.5 py-1 bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-md text-[11px] font-bold shadow-sm transition">
                                {{ $status->nome }}
                            </button>
                        @endforeach
                        <button wire:click="abrirModalLote" class="bg-purpura-600 hover:bg-purpura-700 text-white px-2.5 py-1 rounded-md text-[11px] font-bold shadow-sm transition">
                            Modal
                        </button>
                    </div>
                </div>
            @endif
        @endif

        {{-- TABELA DE DADOS --}}
        <x-table
            :headers="$this->headers"
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">

            @forelse($registros as $inscricao)
                <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                    @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
                        <td class="px-4 py-2 text-center"><input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 rounded"></td>
                    @endif
                    <td class="px-4 py-2 font-medium text-gray-500 text-xs">#{{ $inscricao->id }}</td>
                    <td class="px-4 py-2"><div class="font-bold text-sm text-gray-900 dark:text-white">{{ $inscricao->nome }}</div><div class="text-[11px] text-gray-400">{{ $inscricao->cpf }}</div></td>
                    <td class="px-4 py-2"><div class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ $inscricao->curso->nome ?? '-' }}</div><div class="text-[11px] text-gray-400">{{ $inscricao->unidade->nome ?? '-' }}</div></td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">Passo {{ $inscricao->etapa_atual }}</td>
                    
                    <td class="px-4 py-2 text-center align-top">
                        <span class="px-2 py-0.5 text-xs font-bold {{ $inscricao->pontuacao_total > 0 ? 'text-green-700 bg-green-50 border border-green-200' : 'text-gray-400 bg-gray-50' }} rounded-full inline-block">
                            {{ $inscricao->pontuacao_total ?? 0 }} pts
                        </span>
                        @if($inscricao->posicao_ranking_geral)
                            <div class="mt-1 flex items-center justify-center gap-1 text-[9px] font-bold">
                                <span class="bg-gray-100 px-1.5 py-0.5 rounded border"><span class="text-gray-400">G:</span> {{ $inscricao->posicao_ranking_geral }}º</span>
                                @if($inscricao->posicao_ranking)<span class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border"><span class="opacity-50">T:</span> {{ $inscricao->posicao_ranking }}º</span>@endif
                            </div>
                        @endif
                    </td>
                    
                    <td class="px-4 py-2">
                        @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                        <span class="px-2.5 py-0.5 text-[10px] uppercase font-bold rounded border" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                            {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                        </span>
                    </td>
                    
                    <td class="px-4 py-2 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 rounded transition" title="Visualização Rápida">
                                <i class="text-base ph ph-info"></i>
                            </button>
                            <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 text-gray-400 hover:text-ponkan-500 rounded transition" title="Ver Detalhes">
                                <i class="text-base ph ph-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">Nenhuma inscrição registrada neste ciclo.</td></tr>
            @endforelse

            <x-slot name="gridSlot">
                @foreach($registros as $inscricao)
                    <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                        <div class="flex justify-between mb-3">
                            <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded border flex items-center gap-1">{{ $inscricao->statusInscricao->nome ?? 'Pendente' }}</span>
                            <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 rounded">
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 truncate">{{ $inscricao->nome }}</h4>
                        <p class="text-xs text-gray-500 truncate mb-3">{{ $inscricao->cpf }}</p>
                        <div class="mt-auto border-t border-gray-50 pt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-500 truncate max-w-[120px]">{{ $inscricao->curso->nome ?? '-' }}</span>
                            <span class="text-xs font-bold text-gray-600">{{ $inscricao->pontuacao_total ?? 0 }} pts</span>
                        </div>
                    </div>
                @endforeach
            </x-slot>
        </x-table>
    </div>

    {{-- MODAL DE ALTERAÇÃO EM LOTE --}}
    @if($modalLoteAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Alterar Status em Lote ({{ count($selecionadas) }})</h3>
                <select wire:model="novoStatusId" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg p-2 text-xs font-bold mb-6">
                    <option value="">-- Selecione o novo status --</option>
                    @foreach($statusInscricoesDb as $status) <option value="{{ $status->id }}">{{ $status->nome }}</option> @endforeach
                </select>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('modalLoteAberto', false)" class="px-4 py-2 border rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                    <button wire:click="salvarStatusEmLote" class="px-4 py-2 bg-purpura-600 text-white rounded-lg text-xs font-bold hover:bg-purpura-700 shadow-sm">Confirmar</button>
                </div>
            </div>
        </div>
    @endif

</div>