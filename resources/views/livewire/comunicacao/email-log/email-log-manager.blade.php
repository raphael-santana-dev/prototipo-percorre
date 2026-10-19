<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Monitor e Métricas de E-mails" 
        icon="ph ph-envelope-open"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="filters">
            <div class="flex flex-wrap gap-3 items-center">
                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes (Na Fila)</option>
                    <option value="enviado">Enviados (Sucesso)</option>
                    <option value="erro">Falhas de Envio</option>
                </select>

                <select wire:model.live="filtro_origem" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todas as Origens</option>
                    <option value="comunicado">Envio Manual / Campanha</option>
                    <option value="automacao">Automação de Sistema</option>
                </select>

                <select wire:model.live="filtro_engajamento" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todo Engajamento</option>
                    <option value="abertos">Apenas Abertos (Lidos)</option>
                    <option value="nao_abertos">Não Abertos</option>
                    <option value="clicados">Com Clique em Botão/Link</option>
                </select>

                @if($filtro_status !== '' || $filtro_origem !== '' || $filtro_engajamento !== '')
                    <button wire:click="limparFiltros" class="px-3.5 py-2 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5">
                        <i class="ph-bold ph-x"></i> Limpar Filtros
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    {{-- CARDS DE MÉTRICAS DE ENGAJAMENTO --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-2xl">
                <i class="ph-fill ph-paper-plane-tilt"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Entregues</p>
                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalEnviados }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 text-2xl">
                <i class="ph-fill ph-envelope-open"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Taxa de Abertura</p>
                <h3 class="text-2xl font-extrabold text-emerald-600">{{ $taxaAbertura }}% <span class="text-xs font-semibold text-gray-400">({{ $totalAbertos }} lidos)</span></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-purpura-50 dark:bg-purpura-900/30 flex items-center justify-center text-purpura-600 text-2xl">
                <i class="ph-fill ph-cursor-click"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Taxa de Cliques (CTR)</p>
                <h3 class="text-2xl font-extrabold text-purpura-600">{{ $taxaCliques }}% <span class="text-xs font-semibold text-gray-400">({{ $totalClicados }} c/ clique)</span></h3>
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
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $log->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">{{ $log->destinatario }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-bold text-gray-800 truncate max-w-xs" title="{{ $log->assunto }}">{{ $log->assunto }}</div>
                        <div class="text-[10px] uppercase font-bold text-gray-400 mt-0.5"><i class="ph ph-tag"></i> {{ $log->origem }}</div>
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm text-gray-700">
                            <i class="ph ph-calendar-plus text-gray-400"></i> {{ $log->data_agendamento ? $log->data_agendamento->format('d/m/Y H:i') : 'Imediato' }}
                        </div>
                        @if($log->data_envio)
                            <div class="text-xs font-bold text-green-600 mt-0.5">
                                <i class="ph-bold ph-check"></i> {{ $log->data_envio->format('d/m/Y H:i:s') }}
                            </div>
                        @endif
                    </td>

                    {{-- COLUNA DE ENGAJAMENTO (ABERTURA E CLIQUES) --}}
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            @if($log->aberturas_count > 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Primeira abertura em {{ $log->aberto_em?->format('d/m/Y H:i:s') }}">
                                    <i class="ph-fill ph-envelope-open"></i> Aberto ({{ $log->aberturas_count }}x)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                    <i class="ph ph-envelope"></i> Não aberto
                                </span>
                            @endif

                            @if($log->cliques_count > 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purpura-50 text-purpura-700 border border-purpura-200" title="Último clique em {{ $log->ultimo_clique_em?->format('d/m/Y H:i:s') }}">
                                    <i class="ph-fill ph-cursor-click"></i> {{ $log->cliques_count }} {{ $log->cliques_count == 1 ? 'clique' : 'cliques' }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase tracking-wider border 
                            {{ $log->status === 'pendente' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : '' }}
                            {{ $log->status === 'enviado' ? 'bg-green-100 text-green-800 border-green-200' : '' }}
                            {{ $log->status === 'erro' ? 'bg-red-100 text-red-800 border-red-200' : '' }}
                        ">
                            @if($log->status === 'pendente') <i class="ph ph-clock mr-1"></i> Fila/Agendado
                            @elseif($log->status === 'enviado') <i class="ph-bold ph-check mr-1"></i> Enviado
                            @elseif($log->status === 'erro') <i class="ph-bold ph-warning mr-1"></i> Erro
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if($log->status === 'erro')
                                <button wire:click="verErro({{ $log->id }})" class="p-1.5 text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Ver Log de Erro">
                                    <i class="text-lg ph-fill ph-warning-circle"></i>
                                </button>
                            @endif
                            <button wire:click="verMetricas({{ $log->id }})" class="p-1.5 text-purpura-600 transition-colors rounded-lg hover:bg-purpura-50" title="Ver Rastreamento de Abertura e Links Clicados">
                                <i class="text-lg ph-bold ph-chart-bar"></i>
                            </button>
                            <button wire:click="verPreview({{ $log->id }})" class="p-1.5 text-blue-500 transition-colors rounded-lg hover:bg-blue-50" title="Pré-visualizar E-mail">
                                <i class="text-lg ph-bold ph-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                            <i class="ph ph-envelope-open text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-600">Fila de e-mails vazia.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- MODAL DE MÉTRICAS E RASTREAMENTO DETALHADO (ABERTURAS E URLS CLICADAS) --}}
    @if($modalMetricasAberto && $logSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="$set('modalMetricasAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-3xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle">
                    
                    <div class="bg-gray-900 text-white px-6 py-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-base font-bold flex items-center gap-2">
                                <i class="ph-fill ph-chart-line-up text-purpura-400 text-xl"></i> Rastreamento de Engajamento do E-mail
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Destinatário: <strong class="text-white">{{ $logSelecionado->destinatario }}</strong></p>
                        </div>
                        <button wire:click="$set('modalMetricasAberto', false)" class="text-gray-400 hover:text-white transition"><i class="ph-bold ph-x text-xl"></i></button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Total Aberturas</span>
                                <span class="text-lg font-black text-emerald-600">{{ $logSelecionado->aberturas_count }}x</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">1ª Abertura</span>
                                <span class="text-xs font-bold text-gray-800">{{ $logSelecionado->aberto_em ? $logSelecionado->aberto_em->format('d/m/Y H:i:s') : 'Não aberto' }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Total de Cliques</span>
                                <span class="text-lg font-black text-purpura-600">{{ $logSelecionado->cliques_count }}x</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Último Clique</span>
                                <span class="text-xs font-bold text-gray-800">{{ $logSelecionado->ultimo_clique_em ? $logSelecionado->ultimo_clique_em->format('d/m/Y H:i:s') : 'Sem cliques' }}</span>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-1.5">
                                <i class="ph-bold ph-clock-counter-clockwise text-purpura-600"></i> Linha do Tempo de Interações (Aberturas e URLs Clicadas)
                            </h4>

                            @if($logSelecionado->eventos && $logSelecionado->eventos->count() > 0)
                                <div class="space-y-2.5">
                                    @foreach($logSelecionado->eventos as $ev)
                                        <div class="p-3.5 rounded-xl border {{ $ev->tipo === 'clique' ? 'bg-purpura-50/40 border-purpura-200' : 'bg-emerald-50/30 border-emerald-200' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="space-y-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    @if($ev->tipo === 'clique')
                                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-purpura-600 text-white flex items-center gap-1">
                                                            <i class="ph-fill ph-cursor-click"></i> Clicou no Botão
                                                        </span>
                                                        <span class="text-xs font-bold text-gray-900 truncate">"{{ $ev->texto_link }}"</span>
                                                    @else
                                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-emerald-600 text-white flex items-center gap-1">
                                                            <i class="ph-fill ph-envelope-open"></i> Abriu o E-mail
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($ev->tipo === 'clique' && $ev->url_destino)
                                                    <div class="text-xs text-gray-600 truncate">
                                                        <span class="font-bold text-gray-500">URL redirecionada:</span>
                                                        <a href="{{ $ev->url_destino }}" target="_blank" class="text-purpura-700 hover:underline font-mono">{{ $ev->url_destino }}</a>
                                                    </div>
                                                @endif

                                                <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-3 pt-0.5">
                                                    <span><i class="ph-bold ph-devices"></i> {{ $ev->dispositivo }}</span>
                                                    <span><i class="ph-bold ph-globe"></i> {{ $ev->navegador }} ({{ $ev->sistema_operacional }})</span>
                                                    <span><i class="ph-bold ph-map-pin"></i> IP: {{ $ev->ip }}</span>
                                                </div>
                                            </div>

                                            <div class="text-right shrink-0">
                                                <span class="text-xs font-mono font-bold text-gray-700 bg-white px-2.5 py-1 rounded border border-gray-200">
                                                    {{ $ev->created_at->format('d/m/Y H:i:s') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200 text-gray-400 text-sm">
                                    Nenhuma interação de leitura ou clique registrada até o momento.
                                </div>
                            @endif
                        </div>

                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="button" wire:click="$set('modalMetricasAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-200 rounded-lg text-gray-700 hover:bg-gray-300 transition">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($modalPreviewAberto && $logSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="$set('modalPreviewAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-3xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-envelope-simple text-purpura-600"></i> Visualização de E-mail
                            </h3>
                            <button wire:click="$set('modalPreviewAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                        </div>
                        
                        <div class="space-y-1 text-sm bg-white p-3 rounded border border-gray-200 shadow-sm">
                            <p><span class="font-bold text-gray-500 w-16 inline-block">Para:</span> <span class="font-bold text-gray-900">{{ $logSelecionado->destinatario }}</span></p>
                            <p><span class="font-bold text-gray-500 w-16 inline-block">Assunto:</span> <span class="font-bold text-gray-900">{{ $logSelecionado->assunto }}</span></p>
                        </div>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto bg-white prose max-w-none">
                        {!! $logSelecionado->corpo !!}
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="button" wire:click="$set('modalPreviewAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-200 rounded-lg text-gray-700 hover:bg-gray-300 transition">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($modalErroAberto && $logSelecionado)
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalErroAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <h3 class="mb-2 text-lg font-extrabold text-red-600 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Falha no Disparo
                    </h3>
                    <p class="text-xs text-gray-500 font-medium mb-4">O provedor de e-mail retornou o seguinte erro para o destinatário <b>{{ $logSelecionado->destinatario }}</b>:</p>
                    
                    <div class="bg-gray-900 text-red-400 p-4 rounded-lg font-mono text-[11px] max-h-64 overflow-y-auto shadow-inner break-words whitespace-pre-wrap">
                        {{ $logSelecionado->erro_mensagem ?? 'Erro desconhecido retornado pelo servidor.' }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('modalErroAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200 transition">Fechar Detalhes</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>