<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-breadcrumb :items="$breadcrumbs" />

    {{-- CABEÇALHO PADRONIZADO DA IMAGEM --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-white border border-gray-100 shadow-sm flex items-center justify-center shrink-0">
                <i class="ph ph-calendar-check text-3xl text-emerald-500"></i>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $ciclo->nome }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $ciclo->ano }} / {{ $ciclo->semestre }}º Semestre</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition">
                <i class="ph-fill ph-kanban"></i> Abrir Funil CRM
            </a>
            
            <div class="text-right hidden sm:block">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Inscrições</p>
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-0.5">
                    {{ \Carbon\Carbon::parse($ciclo->data_inicio)->format('d/m/y') }} a {{ \Carbon\Carbon::parse($ciclo->data_fim)->format('d/m/y') }}
                </p>
            </div>

            <!-- TOGGLE DE STATUS -->
            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                @endif
                <span class="text-xs font-bold uppercase {{ $ciclo->status ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $ciclo->status ? 'Ativo' : 'Encerrado' }}
                </span>
            </div>
        </div>
    </div>

    {{-- CARDS DE MÉTRICAS --}}
    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    {{-- NUVEM DE TAGS: ESTRUTURA ACADÊMICA --}}
    <div class="mb-8 mt-6">
        <h3 class="font-extrabold text-gray-900 dark:text-white mb-3 text-lg">Estrutura Acadêmica (Cursos e Unidades)</h3>
        
        <div class="flex flex-col gap-4 bg-gray-50/50 p-4 rounded-xl border border-gray-100 dark:bg-gray-800/30 dark:border-gray-700">
            
            {{-- CURSOS --}}
            <div>
                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Cursos Ofertados</span>
                <div class="flex flex-wrap gap-2 items-center">
                    @forelse($ciclo->cursos as $curso)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 border-l-4 border-l-orange-400 rounded-md text-sm font-bold text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                            {{ $curso->nome }}
                            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                                <button wire:click="removerCurso({{ $curso->id }})" class="text-gray-400 hover:text-red-500 transition-colors focus:outline-none ml-1">
                                    <i class="ph-bold ph-x"></i>
                                </button>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic mr-2">Nenhum curso ofertado.</p>
                    @endforelse

                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2 ml-auto">
                            <select wire:model="cursoSelecionado" class="border-gray-300 rounded-md text-xs py-1.5 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm w-48 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Adicionar novo curso...</option>
                                @foreach($cursosDisponiveis as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endforeach
                            </select>
                            <button wire:click="adicionarCurso" class="px-3 py-1.5 bg-gray-900 hover:bg-black text-white font-bold rounded-md shadow-sm transition text-xs dark:bg-gray-600">
                                Vincular
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- UNIDADES --}}
            <div class="pt-4 border-t border-gray-200/60 dark:border-gray-700/60">
                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Unidades Vinculadas</span>
                <div class="flex flex-wrap gap-2 items-center">
                    @forelse($ciclo->unidades as $unidade)
                        <div class="px-3 py-1.5 bg-white border border-gray-200 rounded-md text-xs font-bold text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 flex items-center gap-1.5">
                            <i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $unidade->nome }}
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic">Nenhuma unidade vinculada.</p>
                    @endforelse
                </div>
            </div>
            
        </div>
    </div>

    <hr class="border-gray-100 dark:border-gray-700 mb-6">

    {{-- CABEÇALHO DA TABELA E BOTÕES DE AÇÃO --}}
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">Tabela de Inscrições</h3>
        
        <div class="flex items-center gap-2">
            @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                <button wire:click="recalcularPontuacoes" 
                        wire:confirm="Processar scores dos alunos Deste Ciclo?"
                        class="flex items-center px-3 py-1.5 gap-2 bg-yellow-50 border border-yellow-200 text-yellow-700 font-bold rounded-lg text-xs shadow-sm">
                    <i class="ph-bold ph-calculator"></i> Recalcular Scores
                </button>
                <button wire:click="gerarRanking" 
                        wire:confirm="Gerar ranking para os alunos Deste Ciclo?"
                        class="flex items-center px-3 py-1.5 gap-2 bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold rounded-lg text-xs shadow-sm">
                    <i class="ph-bold ph-medal"></i> Gerar Ranking
                </button>
            @endif
        </div>
    </div>

    {{-- FILTROS DA TABELA --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Buscar por Nome ou CPF..." class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
        <select wire:model.live="filtroStatus" class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            <option value="">Todos os Status</option>
            @foreach($statusInscricoesDb as $status) <option value="{{ $status->id }}">{{ $status->nome }}</option> @endforeach
        </select>
        <select wire:model.live="filtroUnidade" class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            <option value="">Todas as Unidades</option>
            @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
        </select>
        <select wire:model.live="filtroCurso" class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-purpura-500 focus:border-purpura-500 w-full dark:bg-gray-700 dark:border-gray-600">
            <option value="">Todos os Cursos</option>
            @foreach($ciclo->cursos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
        </select>
        <button wire:click="limparFiltros" class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-md py-[9px] shadow-sm transition dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 text-sm">
            <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
        </button>
    </div>

    @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
        {{-- BOTÕES DE SELEÇÃO RÁPIDA DE LOTE --}}
        <div class="flex justify-end items-center mb-4 gap-2">
            <span class="text-xs font-bold text-gray-500 uppercase">Selecionar rápido:</span>
            <button wire:click="selecionarQuantidade(10)" class="text-xs px-3 py-1 bg-white border border-gray-200 rounded-lg shadow-sm font-bold">10</button>
            <button wire:click="selecionarQuantidade(50)" class="text-xs px-3 py-1 bg-white border border-gray-200 rounded-lg shadow-sm font-bold">50</button>
        </div>

        @if(count($selecionadas) > 0)
        <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-xl mb-4 flex justify-between items-center shadow-sm">
            <div class="flex items-center">
                <span class="font-bold text-indigo-800">{{ count($selecionadas) }} selecionadas</span>
                <button wire:click="desmarcarTodas" class="ml-4 text-sm text-indigo-600 hover:underline">Limpar</button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-indigo-800 uppercase">Mover para:</span>
                @foreach($statusInscricoesDb as $status)
                    <button wire:click="alterarStatusLoteRapido({{ $status->id }})" class="px-3 py-1 bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded text-xs font-bold shadow-sm">
                        {{ $status->nome }}
                    </button>
                @endforeach
                <button wire:click="abrirModalLote" class="bg-purpura-500 hover:bg-purpura-600 text-white px-3 py-1 rounded text-xs font-bold">Modal</button>
            </div>
        </div>
        @endif
    @endif

    {{-- A TABELA EM SI --}}
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
                <td class="px-4 py-2"><div class="font-bold text-sm">{{ $inscricao->nome }}</div><div class="text-[11px] text-gray-400">{{ $inscricao->cpf }}</div></td>
                <td class="px-4 py-2"><div class="font-semibold text-sm">{{ $inscricao->curso->nome ?? '-' }}</div><div class="text-[11px] text-gray-400">{{ $inscricao->unidade->nome ?? '-' }}</div></td>
                <td class="px-4 py-2 text-xs text-gray-600">Passo {{ $inscricao->etapa_atual }}</td>
                
                {{-- Coluna de Ranking --}}
                <td class="px-4 py-2 text-center align-top">
                    <span class="px-2 py-1 text-xs font-bold {{ $inscricao->pontuacao_total > 0 ? 'text-green-700 bg-green-50 border border-green-200' : 'text-gray-400 bg-gray-50' }} rounded-full inline-block mb-1">
                        {{ $inscricao->pontuacao_total ?? 0 }} pts
                    </span>
                    @if($inscricao->posicao_ranking_geral)
                        <div class="mt-1 grid grid-cols-1 gap-1 text-[9px] font-bold w-max mx-auto text-left">
                            <span class="bg-gray-100 px-1.5 py-0.5 rounded border"><span class="text-gray-400">GERAL:</span> {{ $inscricao->posicao_ranking_geral }}º</span>
                            @if($inscricao->posicao_ranking)<span class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border"><span class="opacity-50">TURMA:</span> {{ $inscricao->posicao_ranking }}º</span>@endif
                        </div>
                    @endif
                </td>
                
                <td class="px-4 py-2">
                    @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded border" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">{{ $inscricao->statusInscricao->nome ?? 'Pendente' }}</span>
                </td>
                
                <td class="px-4 py-2 text-right">
                    <button wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-purpura-500 rounded"><i class="text-xl ph ph-info"></i></button>
                    <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 text-gray-400 hover:text-ponkan-500 rounded"><i class="text-xl ph ph-eye"></i></a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">Nenhuma inscrição registrada neste ciclo.</td></tr>   
        @endforelse

        {{-- SLOT DO GRID --}}
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

    {{-- MODAL DE ALTERAÇÃO EM LOTE --}}
    @if($modalLoteAberto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-4">Alterar Status em Lote ({{ count($selecionadas) }})</h3>
            <select wire:model="novoStatusId" class="w-full border-gray-300 rounded-md p-2 mb-6">
                <option value="">-- Selecione o novo status --</option>
                @foreach($statusInscricoesDb as $status) <option value="{{ $status->id }}">{{ $status->nome }}</option> @endforeach
            </select>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('modalLoteAberto', false)" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button wire:click="salvarStatusEmLote" class="px-4 py-2 bg-purpura-500 text-white rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>
    @endif
</div>