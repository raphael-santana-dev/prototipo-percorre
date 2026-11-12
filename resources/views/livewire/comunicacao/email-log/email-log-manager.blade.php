<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <x-page-header 
        title="Monitor e Métricas de E-mails" 
        subtitle="Rastreamento individual de entregas, leituras e cliques"
        icon="ph ph-envelope-open"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="filters">
            <div class="flex flex-wrap gap-2 items-center">
                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Status:</span>
                    <select wire:model.live="filtro_status" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="pendente">Pendentes (Na Fila)</option>
                        <option value="enviado">Enviados</option>
                        <option value="erro">Falhas de Envio</option>
                    </select>
                </div>

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Origem:</span>
                    <select wire:model.live="filtro_origem" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todas</option>
                        <option value="comunicado">Envio Manual / Campanha</option>
                        <option value="automacao">Automação de Sistema</option>
                    </select>
                </div>

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Engajamento:</span>
                    <select wire:model.live="filtro_engajamento" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="abertos">Apenas Abertos</option>
                        <option value="nao_abertos">Não Abertos</option>
                        <option value="clicados">Com Clique em Link</option>
                    </select>
                </div>

                @if($filtro_status !== '' || $filtro_origem !== '' || $filtro_engajamento !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 dark:bg-gray-800 rounded-lg transition">
                        Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    {{-- INDICADORES DE ENGAJAMENTO --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Total Entregues</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalEnviados }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Taxa de Abertura</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $taxaAbertura }}% <span class="text-xs font-normal text-gray-500">({{ $totalAbertos }} lidos)</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Taxa de Cliques (CTR)</span>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $taxaCliques }}% <span class="text-xs font-normal text-gray-500">({{ $totalClicados }} c/ clique)</span>
            </div>
        </div>
    </div>

    <div wire:poll.10s>
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $log)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                        #{{ $log->id }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $log->destinatario }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate max-w-xs" title="{{ $log->assunto }}">{{ $log->assunto }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5 capitalize">{{ $log->origem }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-xs text-gray-700 dark:text-gray-300">
                            {{ $log->data_agendamento ? $log->data_agendamento->format('d/m/Y H:i') : 'Imediato' }}
                        </div>
                        @if($log->data_envio)
                            <div class="text-[11px] font-mono text-gray-400 mt-0.5">
                                Env: {{ $log->data_envio->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            @if($log->aberturas_count > 0)
                                <span class="px-2 py-0.5 rounded border text-[11px] font-medium bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800" title="Primeira abertura em {{ $log->aberto_em?->format('d/m/Y H:i:s') }}">
                                    Aberto ({{ $log->aberturas_count }}x)
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded border text-[11px] font-medium bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                                    Não aberto
                                </span>
                            @endif

                            @if($log->cliques_count > 0)
                                <span class="px-2 py-0.5 rounded border text-[11px] font-medium bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800" title="Último clique em {{ $log->ultimo_clique_em?->format('d/m/Y H:i:s') }}">
                                    {{ $log->cliques_count }} {{ $log->cliques_count == 1 ? 'clique' : 'cliques' }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border 
                            {{ $log->status === 'pendente' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                            {{ $log->status === 'enviado' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                            {{ $log->status === 'erro' ? 'bg-red-50 text-red-700 border-red-200' : '' }}
                        ">
                            @if($log->status === 'pendente') Fila / Agendado
                            @elseif($log->status === 'enviado') Enviado
                            @elseif($log->status === 'erro') Erro
                            @else {{ ucfirst($log->status) }}
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if($log->status === 'erro')
                                <button wire:click="verErro({{ $log->id }})" class="p-1.5 text-red-600 rounded hover:bg-red-50 transition" title="Ver Log de Erro">
                                    <i class="text-base ph ph-warning-circle"></i>
                                </button>
                            @endif
                            <button wire:click="verMetricas({{ $log->id }})" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Rastreamento de Abertura e Cliques">
                                <i class="text-base ph ph-chart-bar"></i>
                            </button>
                            <button wire:click="verPreview({{ $log->id }})" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Pré-visualizar E-mail">
                                <i class="text-base ph ph-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm">
                        Nenhum registro encontrado na fila de e-mails.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- MODAL DE MÉTRICAS E RASTREAMENTO DETALHADO --}}
    @if($modalMetricasAberto && $logSelecionado)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 w-full max-w-3xl rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden" @click.away="$set('modalMetricasAberto', false)">
                
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Rastreamento de Engajamento do E-mail
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Destinatário: <strong class="text-gray-800 dark:text-gray-200">{{ $logSelecionado->destinatario }}</strong></p>
                    </div>
                    <button wire:click="$set('modalMetricasAberto', false)" class="text-gray-400 hover:text-gray-700 p-1"><i class="ph ph-x text-lg"></i></button>
                </div>

                <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] text-gray-500 block">Total Aberturas</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ $logSelecionado->aberturas_count }}x</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] text-gray-500 block">1ª Abertura</span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $logSelecionado->aberto_em ? $logSelecionado->aberto_em->format('d/m/Y H:i') : 'Não aberto' }}</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] text-gray-500 block">Total de Cliques</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ $logSelecionado->cliques_count }}x</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] text-gray-500 block">Último Clique</span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $logSelecionado->ultimo_clique_em ? $logSelecionado->ultimo_clique_em->format('d/m/Y H:i') : 'Sem cliques' }}</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2.5">
                            Linha do Tempo de Interações
                        </h4>

                        @if($logSelecionado->eventos && $logSelecionado->eventos->count() > 0)
                            <div class="divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                @foreach($logSelecionado->eventos as $ev)
                                    <div class="p-3.5 bg-white dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                @if($ev->tipo === 'clique')
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-purpura-50 text-purpura-700 border border-purpura-200">
                                                        Clique
                                                    </span>
                                                    <span class="text-xs font-semibold text-gray-900 dark:text-white truncate">"{{ $ev->texto_link }}"</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Leitura
                                                    </span>
                                                    <span class="text-xs text-gray-600 dark:text-gray-300">Abriu o e-mail</span>
                                                @endif
                                            </div>

                                            @if($ev->tipo === 'clique' && $ev->url_destino)
                                                <div class="text-xs text-gray-500 truncate">
                                                    Destino: <a href="{{ $ev->url_destino }}" target="_blank" class="text-purpura-600 hover:underline font-mono">{{ $ev->url_destino }}</a>
                                                </div>
                                            @endif

                                            <div class="text-[11px] text-gray-400 flex flex-wrap items-center gap-3">
                                                <span>{{ $ev->dispositivo }}</span>
                                                <span>•</span>
                                                <span>{{ $ev->navegador }} ({{ $ev->sistema_operacional }})</span>
                                                <span>•</span>
                                                <span>IP: {{ $ev->ip }}</span>
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-mono text-gray-500">
                                                {{ $ev->created_at->format('d/m/Y H:i:s') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-6 text-center bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 text-xs">
                                Nenhuma interação de leitura ou clique registrada até o momento.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="button" wire:click="$set('modalMetricasAberto', false)" class="btn btn--secondary btn--small">Fechar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE PREVIEW DO E-MAIL --}}
    @if($modalPreviewAberto && $logSelecionado)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 w-full max-w-3xl rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden" @click.away="$set('modalPreviewAberto', false)">
                
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Visualização de E-mail
                        </h3>
                        <button wire:click="$set('modalPreviewAberto', false)" class="text-gray-400 hover:text-gray-700 p-1"><i class="ph ph-x text-lg"></i></button>
                    </div>
                    
                    <div class="space-y-1 text-xs bg-gray-50 dark:bg-gray-900 p-3 rounded border border-gray-200 dark:border-gray-700">
                        <p><span class="text-gray-500 w-16 inline-block">Para:</span> <strong class="text-gray-900 dark:text-white">{{ $logSelecionado->destinatario }}</strong></p>
                        <p><span class="text-gray-500 w-16 inline-block">Assunto:</span> <strong class="text-gray-900 dark:text-white">{{ $logSelecionado->assunto }}</strong></p>
                    </div>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto bg-white prose prose-sm max-w-none">
                    {!! $logSelecionado->corpo !!}
                </div>

                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="button" wire:click="$set('modalPreviewAberto', false)" class="btn btn--secondary btn--small">Fechar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE ERRO --}}
    @if($modalErroAberto && $logSelecionado)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-6" @click.away="$set('modalErroAberto', false)">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                    <h3 class="text-sm font-semibold text-red-600">
                        Falha no Disparo
                    </h3>
                    <button wire:click="$set('modalErroAberto', false)" class="text-gray-400 hover:text-gray-700"><i class="ph ph-x text-lg"></i></button>
                </div>
                <p class="text-xs text-gray-500 mb-3">Erro retornado para o destinatário <b>{{ $logSelecionado->destinatario }}</b>:</p>
                
                <div class="bg-gray-900 text-red-300 p-4 rounded-lg font-mono text-xs max-h-64 overflow-y-auto break-words whitespace-pre-wrap">
                    {{ $logSelecionado->erro_mensagem ?? 'Erro desconhecido retornado pelo servidor.' }}
                </div>

                <div class="flex justify-end pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('modalErroAberto', false)" class="btn btn--secondary btn--small">Fechar</button>
                </div>
            </div>
        </div>
    @endif
</div>