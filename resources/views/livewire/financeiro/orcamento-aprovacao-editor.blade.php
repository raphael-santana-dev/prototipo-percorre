<div class="font-sans flex flex-col transition-all duration-300 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden text-gray-800" 
     x-data="{ modoExpandido: false, navbarOculta: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val)); $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val))"
     @comentario-adicionado.window="setTimeout(() => { if($refs.chatContainer) $refs.chatContainer.scrollTo({top: $refs.chatContainer.scrollHeight, behavior: 'smooth'}) }, 100)"
     :class="modoExpandido ? 'h-full border-none rounded-none' : 'min-h-[600px] h-[calc(100vh-140px)]'">
    
    <style>
        body.modo-expandido main > div > div.max-w-7xl { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
        
        .table-financeira th { position: sticky; top: 0; z-index: 40; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .table-financeira td { border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 0.5rem; vertical-align: top; }
        .table-financeira tfoot th, .table-financeira tfoot td { position: sticky; bottom: 0; z-index: 50; background-color: #f3f4f6; border-top: 2px solid #d1d5db; border-bottom: 0; }
        .bg-aprovado { background-color: #f0fdf4 !important; }
        .bg-reprovado { background-color: #fef2f2 !important; }
        .bg-ressalvas { background-color: #fefce8 !important; }
    </style>
    
    {{-- BARRA SUPERIOR FIXA --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0 sticky top-0 z-[60]">
        <div class="flex items-center gap-4">
            <button wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 transition" title="Voltar à Listagem"><i class="ph-bold ph-arrow-left"></i></button>
            <div class="border-l border-gray-200 pl-4">
                <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-check-square-offset text-gray-500"></i> Análise de Orçamento
                </h1>
                <p class="text-[11px] font-medium text-gray-500 uppercase tracking-widest mt-0.5">
                    C. Custo: <span class="text-gray-900 font-bold">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'S/ Vínculo' }} ({{ $orcamento->ccusto }})</span> | Ano: {{ $orcamento->ano }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="flex flex-col items-end border-r border-gray-200 pr-4">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Status Global</span>
                <span class="text-sm font-bold uppercase text-gray-800">{{ $orcamento->status === 'Finalizado' ? 'Aguardando Avaliação' : $orcamento->status }}</span>
            </div>

            <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-md border border-gray-200">
                <button @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-3 py-1.5 text-xs font-medium rounded transition-colors hover:bg-white hover:shadow-sm" title="Expandir Tela">
                    <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                </button>
                <button x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-medium rounded transition-colors hover:bg-white hover:shadow-sm" title="Ocultar Menus">
                    <i class="ph-bold" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                    <span class="hidden xl:inline ml-1" x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ALERTA DE PEDIDO DE REABERTURA --}}
    @if($orcamento->reabertura_solicitada)
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-3 flex justify-between items-center shrink-0">
            <div>
                <h4 class="text-sm font-bold text-amber-800 flex items-center gap-2"><i class="ph-bold ph-warning-circle text-lg"></i> Pedido de Reabertura Pendente</h4>
                <p class="text-xs text-amber-700 mt-0.5">O Gestor necessita corrigir este orçamento. Motivo: <span class="italic">"{{ $orcamento->reabertura_motivo }}"</span></p>
            </div>
            <button wire:click="$set('modalAprovarReaberturaAberto', true)" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-md shadow-sm transition">Analisar Pedido</button>
        </div>
    @endif

    {{-- ÁREA DINÂMICA: PLANILHA --}}
    <div class="flex flex-1 w-full overflow-hidden relative bg-white">
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative">
            
            {{-- BARRA DE AÇÕES DA PLANILHA (Lote e Chat) --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0" x-data="{ temSelecao: @entangle('itensSelecionados').live }">
                <div class="flex items-center gap-3 w-full max-w-2xl">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider shrink-0">Justificativa do Gestor:</label>
                    <div class="w-full text-sm bg-white px-3 py-1.5 text-gray-700 border border-gray-200 rounded-md truncate" title="{{ $orcamento->descricao_despesa }}">
                        {{ $orcamento->descricao_despesa ?: 'Nenhuma observação enviada.' }}
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @php
                        $totalPendentes = $orcamento->itens->whereIn('status', ['Criado', 'Corrigido'])->count();
                    @endphp
                    @if($totalPendentes > 0 && !$orcamento->reabertura_solicitada)
                        <template x-if="temSelecao && Object.keys(temSelecao).length > 0">
                            <div class="flex items-center gap-1 bg-white p-1 rounded-md border border-gray-200 mr-2 shadow-sm">
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado')" class="px-3 py-1.5 text-emerald-700 hover:bg-emerald-50 rounded text-xs font-medium transition">Aprovar Sel.</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Reprovado')" class="px-3 py-1.5 text-red-700 hover:bg-red-50 rounded text-xs font-medium transition">Reprovar Sel.</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado com ressalvas')" class="px-3 py-1.5 text-yellow-700 hover:bg-yellow-50 rounded text-xs font-medium transition">Ressalvas Sel.</button>
                            </div>
                        </template>
                        <template x-if="!temSelecao || Object.keys(temSelecao).length === 0">
                            <div class="flex items-center gap-1 bg-white p-1 rounded-md border border-gray-200 mr-2 shadow-sm">
                                <button wire:click="abrirModalAvaliacaoRestantes('Aprovado')" class="px-3 py-1.5 text-emerald-700 hover:bg-emerald-50 rounded text-xs font-medium transition">Aprovar Tudo ({{ $totalPendentes }})</button>
                                <button wire:click="abrirModalAvaliacaoRestantes('Reprovado')" class="px-3 py-1.5 text-red-700 hover:bg-red-50 rounded text-xs font-medium transition">Reprovar Tudo ({{ $totalPendentes }})</button>
                            </div>
                        </template>
                    @endif

                    <button @click="$wire.set('drawerComentariosAberto', true)" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm rounded-md text-sm font-medium flex items-center gap-2 transition">
                        <i class="ph ph-chat-text"></i> Histórico / Comentários
                    </button>
                </div>
            </div>

            <div x-ref="tableContainer" class="flex-1 overflow-auto custom-scrollbar relative">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0 table-financeira">
                    <thead>
                        <tr>
                            <th class="w-[80px] min-w-[80px] left-0 z-[60] text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 cursor-pointer mt-0.5">
                                    <span>ID</span>
                                </div>
                            </th>
                            <th class="w-[100px] min-w-[100px] left-[80px] z-[60] text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Código</th>
                            <th class="w-[250px] min-w-[250px] left-[180px] z-[60] text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nome da Natureza</th>
                            <th class="w-[220px] min-w-[220px] left-[430px] z-[60] text-[10px] font-bold text-gray-500 uppercase tracking-wider">Observações</th>
                            
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                <th class="w-[140px] text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">
                                    {{ $mes }}
                                </th>
                            @endforeach
                            
                            <th class="w-[120px] text-[10px] font-bold text-gray-800 uppercase tracking-wider text-right bg-gray-100">Total Linha</th>
                            <th class="w-[160px] text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Decisão</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalGeral = 0; @endphp
                        @foreach($orcamento->itens as $item)
                            @php
                                $bgClass = match($item->status) {
                                    'Aprovado' => 'bg-aprovado hover:bg-green-100',
                                    'Reprovado' => 'bg-reprovado hover:bg-red-100',
                                    'Aprovado com ressalvas' => 'bg-ressalvas hover:bg-yellow-100',
                                    default => 'bg-white hover:bg-gray-50'
                                };
                            @endphp
                            <tr wire:key="linha-aprovacao-{{ $item->id }}" class="group transition-colors {{ $bgClass }}">
                                
                                <td class="sticky left-0 z-[30] {{ $bgClass }} text-center align-top">
                                    <div class="flex items-center justify-center gap-2 px-1 py-1">
                                        @if(in_array($item->status, ['Criado', 'Corrigido']))
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $item->id }}" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 cursor-pointer">
                                        @endif
                                        <span class="text-xs text-gray-500">{{ $item->id }}</span>
                                    </div>
                                </td>

                                <td class="sticky left-[80px] z-[30] {{ $bgClass }} text-center align-top">
                                    <span class="text-xs font-mono text-gray-700 py-1 block">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                </td>

                                <td class="sticky left-[180px] z-[30] {{ $bgClass }} align-top">
                                    <div class="flex flex-col w-full overflow-hidden py-1">
                                        <span class="text-xs font-medium text-gray-900 truncate" title="{{ $item->natureza ? $item->natureza->descricao : 'N/D' }}">{{ $item->natureza ? $item->natureza->descricao : 'N/D' }}</span>
                                        @if($item->status === 'Corrigido')
                                            <span class="text-[9px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 uppercase flex items-center gap-1 rounded w-max mt-1"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="sticky left-[430px] z-[30] {{ $bgClass }} align-top">
                                    @php
                                        $natNome = $item->natureza ? $item->natureza->descricao : 'Natureza Importada';
                                        $obs = trim($item->descricao);
                                        if ($obs === $natNome || $obs === 'Natureza Importada' || $obs === '') $obs = '-';
                                    @endphp
                                    <div class="w-full text-xs text-gray-600 line-clamp-3 py-1" title="{{ $obs }}">{{ $obs }}</div>
                                </td>

                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $valorMes = (float)($item->{"valor_$sigla"} ?? 0);
                                        $previsto = (float)($item->{"previsto_$sigla"} ?? 0);
                                        $totalLinha += $valorMes;
                                        $diferenca = $valorMes - $previsto;
                                        $corDiff = $diferenca > 0 ? 'text-red-600' : ($diferenca < 0 ? 'text-emerald-600' : 'text-gray-400');
                                    @endphp
                                    <td class="align-top relative p-2">
                                        <div class="w-full text-xs text-right font-medium text-gray-900 p-1.5 {{ $valorMes == 0 ? 'text-gray-400' : '' }}">
                                            {{ number_format($valorMes, 2, ',', '.') }}
                                        </div>
                                        <div class="mt-1 flex flex-col items-end text-[10px] text-gray-500 leading-tight">
                                            <span>Base: {{ number_format($previsto, 2, ',', '.') }}</span>
                                            @if($diferenca != 0)
                                                <span class="{{ $corDiff }} font-medium">Dif: {{ $diferenca > 0 ? '+' : '' }}{{ number_format($diferenca, 2, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach

                                @php $totalGeral += $totalLinha; @endphp
                                <td class="bg-gray-50 text-right text-xs font-bold text-gray-900 align-top py-3">
                                    {{ number_format($totalLinha, 2, ',', '.') }}
                                </td>
                                
                                <td class="align-top py-2 px-1">
                                    @if(in_array($item->status, ['Criado', 'Corrigido']))
                                        <div class="flex items-center gap-1 justify-center">
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 border border-transparent hover:border-emerald-200 rounded transition" title="Aprovar"><i class="ph-bold ph-check text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado com ressalvas')" class="p-1.5 text-yellow-600 hover:bg-yellow-50 border border-transparent hover:border-yellow-200 rounded transition" title="Ressalvas"><i class="ph-bold ph-warning text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Reprovado')" class="p-1.5 text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition" title="Reprovar"><i class="ph-bold ph-x text-base"></i></button>
                                        </div>
                                    @else
                                        <div class="flex justify-center items-center h-full">
                                            @if($item->status === 'Aprovado')
                                                <span class="text-[10px] font-bold uppercase text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-200 flex items-center gap-1"><i class="ph-bold ph-check"></i> Aprovado</span>
                                            @elseif($item->status === 'Aprovado com ressalvas')
                                                <span class="text-[10px] font-bold uppercase text-yellow-700 bg-yellow-50 px-2 py-1 rounded-md border border-yellow-200 flex items-center gap-1"><i class="ph-bold ph-warning"></i> Ressalvas</span>
                                            @elseif($item->status === 'Reprovado')
                                                <span class="text-[10px] font-bold uppercase text-red-700 bg-red-50 px-2 py-1 rounded-md border border-red-200 flex items-center gap-1"><i class="ph-bold ph-x"></i> Reprovado</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $totaisMensais = [];
                            foreach($meses as $sigla) $totaisMensais[$sigla] = $orcamento->itens->sum("valor_$sigla");
                        @endphp
                        <tr>
                            <th colspan="4" class="sticky left-0 bottom-0 z-[70] text-right pr-4 text-xs uppercase text-gray-700 tracking-wider">Total Solicitado</th>
                            @foreach($meses as $sigla)
                                <th class="text-right text-xs font-bold text-gray-900 py-3 px-2">{{ number_format($totaisMensais[$sigla], 2, ',', '.') }}</th>
                            @endforeach
                            <th class="text-right text-sm font-bold text-gray-900 bg-gray-200 py-3 px-2">{{ number_format($totalGeral, 2, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAIS (Flat UI) --}}
    @if($modalAvaliacaoAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2"><i class="ph-fill ph-chat-text text-gray-500"></i> Observação da Análise</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Você está avaliando o(s) item(ns) como: <strong class="uppercase text-gray-800">{{ $statusAvaliacao }}</strong>.<br>Insira um comentário se necessário.
                </p>
                <textarea wire:model="comentarioAvaliacao" rows="3" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500" placeholder="Comentário opcional..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-5">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition">Cancelar</button>
                    <button wire:click="confirmarAvaliacao" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-black rounded-md shadow-sm transition flex items-center gap-2">
                        <span wire:loading.remove wire:target="confirmarAvaliacao">Confirmar</span>
                        <span wire:loading wire:target="confirmarAvaliacao"><i class="ph-bold ph-spinner animate-spin"></i> Processando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($modalAprovarReaberturaAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-bold text-gray-900 mb-2 flex items-center gap-2"><i class="ph-fill ph-lock-key-open text-orange-500"></i> Avaliar Pedido de Reabertura</h3>
                <p class="text-xs text-gray-600 mb-4">Ao aprovar, o status do orçamento volta para "Em Elaboração" e o Gestor poderá editar novamente. Defina um prazo limite (opcional) para fechamento automático.</p>
                
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide mb-1">Prazo Limite para Edição</label>
                    <input type="datetime-local" wire:model="prazoReabertura" class="w-full text-sm border-gray-300 rounded-md focus:border-gray-500 focus:ring-gray-500 p-2">
                </div>

                <div class="flex justify-end gap-2 mt-4 border-t border-gray-100 pt-4">
                    <button wire:click="negarReabertura" class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-200 hover:bg-red-50 rounded-md transition">Negar Pedido</button>
                    <button wire:click="aprovarReabertura" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-black rounded-md shadow-sm transition">Aprovar Reabertura</button>
                </div>
            </div>
        </div>
    @endif

    {{-- NAVIGATION DRAWER: COMENTÁRIOS / TIMELINE --}}
    <div class="relative z-[150]" aria-modal="true" x-show="$wire.drawerComentariosAberto" x-cloak>
        <div x-show="$wire.drawerComentariosAberto" x-transition.opacity class="fixed inset-0 bg-gray-900/50 transition-opacity"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="$wire.drawerComentariosAberto" 
                         x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" 
                         x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" 
                         class="pointer-events-auto w-screen max-w-md h-full flex flex-col bg-white shadow-xl" @click.away="$wire.set('drawerComentariosAberto', false)">
                         
                        <div class="bg-white border-b border-gray-200 px-5 py-4 shrink-0 flex items-center justify-between">
                            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph ph-chat-text text-gray-500"></i> Histórico do Orçamento
                            </h2>
                            <button type="button" @click="$wire.set('drawerComentariosAberto', false)" class="text-gray-400 hover:text-gray-600 transition p-1">
                                <i class="ph-bold ph-x text-lg"></i>
                            </button>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-4 bg-gray-50 flex flex-col gap-4" x-ref="chatContainer" x-init="$watch('$wire.drawerComentariosAberto', val => { if(val) setTimeout(() => $el.scrollTop = $el.scrollHeight, 100) })">
                            @php
                                $todasAvaliacoes = collect();
                                foreach($orcamento->itens as $itemLinha) {
                                    if(!empty($itemLinha->avaliacoes)) {
                                        foreach($itemLinha->avaliacoes as $aval) {
                                            $avalArray = is_array($aval) ? $aval : $aval->toArray();
                                            $avalArray['natureza_codigo'] = $itemLinha->natureza_codigo;
                                            $avalArray['natureza_descricao'] = $itemLinha->natureza ? $itemLinha->natureza->descricao : '';
                                            $todasAvaliacoes->push($avalArray);
                                        }
                                    }
                                }
                                if(!empty($orcamento->logsGerais)) {
                                    foreach($orcamento->logsGerais as $aval) {
                                        $avalArray = is_array($aval) ? $aval : $aval->toArray();
                                        $avalArray['natureza_codigo'] = 'GERAL';
                                        $avalArray['natureza_descricao'] = 'Ação Global do Orçamento';
                                        $todasAvaliacoes->push($avalArray);
                                    }
                                }
                                $todasAvaliacoes = $todasAvaliacoes->sortBy('created_at');
                            @endphp

                            @foreach($todasAvaliacoes as $aval)
                                @php
                                    $isMeu = ($aval['user_id'] == auth()->id());
                                    $isComentarioPuro = (($aval['tipo_evento'] ?? '') === 'comentario_chat');
                                    $nomeDisplay = $isMeu ? 'Você' : ($aval['user_nome'] ?? 'Usuário');
                                @endphp
                                <div class="flex gap-3 w-full">
                                    <div class="shrink-0 mt-1">
                                        <div class="w-8 h-8 rounded bg-white border border-gray-200 flex items-center justify-center text-xs font-bold text-gray-500 uppercase shadow-sm">
                                            {{ substr($nomeDisplay, 0, 2) }}
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0 flex flex-col">
                                        <div class="flex items-baseline gap-2 mb-1">
                                            <span class="text-xs font-semibold text-gray-900">{{ $nomeDisplay }}</span>
                                            <span class="text-[10px] text-gray-500">{{ \Carbon\Carbon::parse($aval['created_at'] ?? now())->format('d/m/Y H:i') }}</span>
                                        </div>
                                        
                                        <div class="bg-white border border-gray-200 rounded-md p-3 shadow-sm">
                                            @if(!$isComentarioPuro)
                                                <div class="mb-2 text-[10px] font-semibold text-gray-600 bg-gray-50 px-2 py-1 rounded border border-gray-100 inline-block">
                                                    <span class="uppercase text-gray-800">{{ $aval['status_aplicado'] ?? '' }}</span>
                                                    @if(($aval['natureza_codigo'] ?? '') !== 'GERAL')
                                                        <span class="mx-1 text-gray-300">•</span>
                                                        <span class="text-gray-500">{{ $aval['natureza_codigo'] ?? '' }} - {{ $aval['natureza_descricao'] ?? '' }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if(!empty($aval['comentario']))
                                                <p class="text-xs text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $aval['comentario'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-4 border-t border-gray-200 bg-white shrink-0">
                            <form wire:submit.prevent="adicionarComentarioGlobal" class="flex flex-col gap-2">
                                <textarea wire:model="novoComentario" @keydown.enter.prevent="$wire.adicionarComentarioGlobal()" rows="2" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 resize-none p-2" placeholder="Escreva um comentário..."></textarea>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] text-gray-400">Pressione Enter para enviar</span>
                                    <button type="submit" class="px-4 py-1.5 bg-gray-900 hover:bg-black text-white text-xs font-medium rounded-md shadow-sm transition-colors">Adicionar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>