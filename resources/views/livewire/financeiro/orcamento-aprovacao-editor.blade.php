<div class="font-sans flex flex-col transition-all duration-300 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden" 
     x-data="{ modoExpandido: false, navbarOculta: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val)); $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val))"
     @comentario-adicionado.window="setTimeout(() => { if($refs.chatContainer) $refs.chatContainer.scrollTo({top: $refs.chatContainer.scrollHeight, behavior: 'smooth'}) }, 100)"
     :class="modoExpandido ? 'h-full border-none rounded-none' : 'min-h-[600px] h-[calc(100vh-180px)]'">
    
    <style>
        body.modo-expandido main > div > div.max-w-7xl { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
    </style>
    
    {{-- BARRA SUPERIOR (HEADER FIXO) --}}
    <div class="bg-white border-b border-gray-300 px-6 py-3 flex items-center justify-between z-[60] shrink-0 sticky top-0">
        <div class="flex items-center gap-4">
            <button wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition" title="Voltar à Listagem"><i class="ph-bold ph-arrow-left"></i></button>
            <div class="border-l border-gray-300 pl-4">
                <h1 class="text-lg font-black text-gray-900 flex items-center gap-2 tracking-tight">
                    <i class="ph-fill ph-check-square-offset text-purpura-600"></i> Análise de Orçamento
                </h1>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-widest mt-0.5">
                    Centro de Custo: <span class="text-purpura-600 font-bold">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'S/ Vínculo' }} ({{ $orcamento->ccusto }})</span> | Ano: {{ $orcamento->ano }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="flex flex-col items-end mr-4 border-r border-gray-200 pr-4">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Status Global</span>
                @php
                    $statusColor = match($orcamento->status) {
                        'Em elaboração' => 'text-blue-600', 'Finalizado' => 'text-purple-600',
                        'Aprovado' => 'text-emerald-600', 'Aprovado com ressalvas' => 'text-yellow-600',
                        'Reprovado' => 'text-red-600', default => 'text-gray-600'
                    };
                @endphp
                <span class="text-sm font-black uppercase {{ $statusColor }}">{{ $orcamento->status === 'Finalizado' ? 'Aguardando Avaliação' : $orcamento->status }}</span>
            </div>

            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg border border-gray-200">
                <button @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors" :class="modoExpandido ? 'bg-white shadow-sm text-purpura-600' : 'text-gray-600 hover:text-gray-900'" title="Expandir Tela">
                    <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                </button>
                <button x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors text-gray-600 hover:text-gray-900" :class="navbarOculta ? 'bg-gray-200 text-gray-900' : ''" title="Ocultar Navbar do Sistema">
                    <i class="ph-bold" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                    <span class="hidden xl:inline ml-1" x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ALERTA DE PEDIDO DE REABERTURA --}}
    @if($orcamento->reabertura_solicitada)
        <div class="bg-orange-50 border-b border-orange-200 p-4 flex justify-between items-center z-[55] relative shrink-0">
            <div>
                <h4 class="text-sm font-black text-orange-800 flex items-center gap-2"><i class="ph-bold ph-warning-circle text-lg"></i> Pedido de Reabertura Pendente</h4>
                <p class="text-xs text-orange-700 mt-0.5">O Gestor necessita corrigir este orçamento que já estava bloqueado. <span class="font-bold ml-1">Motivo do Gestor:</span> <span class="italic">"{{ $orcamento->reabertura_motivo }}"</span></p>
            </div>
            <button wire:click="$set('modalAprovarReaberturaAberto', true)" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded shadow-sm transition">Analisar Pedido</button>
        </div>
    @endif

    {{-- ÁREA DINÂMICA: PLANILHA --}}
    <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50">
        
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative bg-white">
            
            {{-- BARRA DE AÇÕES DA PLANILHA (Lote e Chat) --}}
            <div class="p-3 bg-white border-b border-gray-300 shadow-sm flex items-center justify-between shrink-0 z-10 relative" x-data="{ temSelecao: @entangle('itensSelecionados').live }">
                <div class="flex items-center gap-3 w-full max-w-2xl">
                    <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa Global:</label>
                    <div class="w-full text-sm bg-gray-50 px-3 py-1.5 text-gray-600 italic border border-gray-200 rounded-md truncate" title="{{ $orcamento->descricao_despesa }}">
                        {{ $orcamento->descricao_despesa ?: 'Nenhuma observação enviada pelo Gestor.' }}
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @php
                        $totalPendentes = $orcamento->itens->whereIn('status', ['Criado', 'Corrigido'])->count();
                        $qtdSelecionados = count($itensSelecionados);
                    @endphp
                    @if($totalPendentes > 0 && !$orcamento->reabertura_solicitada)
                        <template x-if="temSelecao && Object.keys(temSelecao).length > 0">
                            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 mr-2">
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado')" class="px-2 py-1.5 bg-white text-emerald-700 border border-emerald-200 shadow-sm rounded text-[10px] font-bold hover:bg-emerald-50 transition">Aprovar Sel. (<span x-text="Object.keys(temSelecao).length"></span>)</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Reprovado')" class="px-2 py-1.5 bg-white text-red-700 border border-red-200 shadow-sm rounded text-[10px] font-bold hover:bg-red-50 transition">Reprovar Sel. (<span x-text="Object.keys(temSelecao).length"></span>)</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado com ressalvas')" class="px-2 py-1.5 bg-white text-yellow-700 border border-yellow-200 shadow-sm rounded text-[10px] font-bold hover:bg-yellow-50 transition">Ressalvas Sel.</button>
                            </div>
                        </template>
                        <template x-if="!temSelecao || Object.keys(temSelecao).length === 0">
                            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 mr-2">
                                <button wire:click="abrirModalAvaliacaoRestantes('Aprovado')" class="px-2 py-1.5 bg-white text-emerald-700 border border-emerald-200 shadow-sm rounded text-[10px] font-bold hover:bg-emerald-50 transition">Aprovar Tudo ({{ $totalPendentes }})</button>
                                <button wire:click="abrirModalAvaliacaoRestantes('Reprovado')" class="px-2 py-1.5 bg-white text-red-700 border border-red-200 shadow-sm rounded text-[10px] font-bold hover:bg-red-50 transition">Reprovar Tudo ({{ $totalPendentes }})</button>
                            </div>
                        </template>
                    @endif

                    <button @click="$wire.set('drawerComentariosAberto', true)" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm flex items-center gap-2">
                        <i class="ph-bold ph-chat-centered-text"></i> Histórico / Comentários
                    </button>
                </div>
            </div>

            <div x-ref="tableContainer" class="flex-1 overflow-auto custom-scrollbar relative">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0 border-t border-l border-gray-300">
                    <thead>
                        <tr>
                            <th class="w-[80px] min-w-[80px] sticky left-0 top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <div class="flex items-center justify-center gap-2">
                                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-400 text-emerald-600 focus:ring-emerald-500 mt-0.5 cursor-pointer" title="Selecionar todos os pendentes">
                                    <span>ID</span>
                                </div>
                            </th>
                            <th class="w-[100px] min-w-[100px] sticky left-[80px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] text-center">Código</th>
                            <th class="w-[250px] min-w-[250px] sticky left-[180px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Nome da Natureza</th>
                            <th class="w-[220px] min-w-[220px] sticky left-[430px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Observações</th>
                            
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                <th class="w-[140px] sticky top-0 z-[40] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider text-right border-b border-r border-gray-300">
                                    {{ $mes }}
                                </th>
                            @endforeach
                            
                            <th class="w-[120px] sticky top-0 z-[40] bg-emerald-100 p-3 text-[10px] font-black text-emerald-800 uppercase tracking-wider text-right border-b border-r border-gray-300">Total Linha</th>
                            <th class="w-[160px] sticky top-0 z-[40] bg-gray-100 p-3 text-center border-b border-r border-gray-300 text-[10px] font-black text-gray-700 uppercase tracking-wider">Decisão</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @php $totalGeral = 0; @endphp
                        @foreach($orcamento->itens as $item)
                            @php
                                $bgRowSolid = match($item->status) {
                                    'Aprovado' => 'bg-[#eefcf5]',
                                    'Reprovado' => 'bg-[#fff5f5]',
                                    'Aprovado com ressalvas' => 'bg-[#fffbeb]',
                                    default => 'bg-white'
                                };
                            @endphp
                            <tr wire:key="linha-aprovacao-{{ $item->id }}" class="group transition-colors {{ $bgRowSolid }} hover:bg-blue-50">
                                
                                <td class="sticky left-0 z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 text-center align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex items-center justify-center gap-2 mt-1 px-1">
                                        @if(in_array($item->status, ['Criado', 'Corrigido']))
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $item->id }}" class="rounded border-gray-400 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                        @endif
                                        <span class="text-xs font-bold text-gray-600">{{ $item->id }}</span>
                                    </div>
                                </td>

                                <td class="sticky left-[80px] z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 text-center align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <span class="text-xs font-mono font-bold text-gray-800 mt-1 block">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                </td>

                                <td class="sticky left-[180px] z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex flex-col mt-1 w-full overflow-hidden">
                                        <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item->natureza ? $item->natureza->descricao : 'N/D' }}">{{ $item->natureza ? $item->natureza->descricao : 'N/D' }}</span>
                                        @if($item->status === 'Corrigido')
                                            <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 uppercase flex items-center gap-1 rounded shadow-sm w-max mt-1"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="sticky left-[430px] z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    @php
                                        $natNome = $item->natureza ? $item->natureza->descricao : 'Natureza Importada (API)';
                                        $obs = trim($item->descricao);
                                        if ($obs === $natNome || $obs === 'Natureza Importada' || $obs === 'Natureza Importada (API)' || $obs === '') {
                                            $obs = '-';
                                        }
                                    @endphp
                                    <div class="w-full text-[11px] text-gray-600 bg-transparent line-clamp-3 mt-1" title="{{ $obs }}">
                                        {{ $obs }}
                                    </div>
                                </td>

                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $valorMes = (float)($item->{"valor_$sigla"} ?? 0);
                                        $previsto = (float)($item->{"previsto_$sigla"} ?? 0);
                                        $totalLinha += $valorMes;
                                        
                                        $diferenca = $valorMes - $previsto;
                                        $percentual = $previsto > 0 ? ($diferenca / $previsto) * 100 : ($valorMes > 0 ? 100 : 0);
                                        $sinal = $diferenca > 0 ? '+' : '';
                                        $corDiff = $diferenca > 0 ? 'text-red-600' : ($diferenca < 0 ? 'text-emerald-600' : 'text-gray-400');
                                    @endphp
                                    <td class="border-b border-r border-gray-300 p-2 align-top transition-colors z-0 relative">
                                        @if($previsto > 0 && $previsto != $valorMes)
                                            <div class="absolute -top-1.5 right-1 bg-white px-1 text-[8px] font-bold text-gray-400 rounded-sm">Base: {{ number_format($previsto, 0, '', '') }}</div>
                                        @endif
                                        <div class="w-full text-xs text-right font-bold text-gray-900 border border-gray-200 bg-white/80 rounded p-1.5 {{ $valorMes == 0 ? 'text-gray-400' : '' }}">
                                            {{ number_format($valorMes, 2, ',', '.') }}
                                        </div>
                                        
                                        <div class="mt-2 flex flex-col items-end justify-center text-[9px] font-medium leading-tight space-y-0.5 px-1">
                                            <span class="text-gray-500">Previsto: R$ {{ number_format($previsto, 2, ',', '.') }}</span>
                                            @if($diferenca != 0)
                                                <span class="{{ $corDiff }} font-bold bg-white/70 px-1.5 py-0.5 rounded shadow-sm flex items-center justify-end w-full border border-gray-100">
                                                    {{ $sinal }}R$ {{ number_format($diferenca, 2, ',', '.') }} 
                                                    <span class="ml-1 opacity-70">({{ $sinal }}{{ number_format($percentual, 1, ',', '.') }}%)</span>
                                                </span>
                                            @else
                                                <span class="text-gray-300 px-1.5 py-0.5">S/ Alteração</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach

                                @php $totalGeral += $totalLinha; @endphp
                                <td class="border-b border-r border-gray-300 bg-emerald-50/30 p-2 text-right text-xs font-black text-emerald-900 align-top pt-3 z-0">
                                    {{ number_format($totalLinha, 2, ',', '.') }}
                                </td>
                                
                                <td class="border-b border-gray-300 p-2 align-top pt-3 {{ $bgRowSolid === 'bg-white' ? 'bg-gray-50/50' : '' }} z-0">
                                    @if(in_array($item->status, ['Criado', 'Corrigido']))
                                        <div class="flex items-center gap-1 justify-center">
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado')" class="p-1.5 text-emerald-600 hover:bg-emerald-100 rounded border border-emerald-200 bg-white shadow-sm transition" title="Aprovar Item"><i class="ph-bold ph-check text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado com ressalvas')" class="p-1.5 text-yellow-600 hover:bg-yellow-100 rounded border border-yellow-200 bg-white shadow-sm transition" title="Aprovar com Ressalvas"><i class="ph-bold ph-warning text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Reprovado')" class="p-1.5 text-red-600 hover:bg-red-100 rounded border border-red-200 bg-white shadow-sm transition" title="Reprovar Item"><i class="ph-bold ph-x text-base"></i></button>
                                        </div>
                                    @else
                                        <div class="flex justify-center items-center h-full">
                                            @if($item->status === 'Aprovado')
                                                <span class="text-[10px] font-bold uppercase text-emerald-600 flex items-center gap-1 bg-white px-2 py-1 rounded shadow-sm border border-gray-200"><i class="ph-bold ph-check"></i> Aprovado</span>
                                            @elseif($item->status === 'Aprovado com ressalvas')
                                                <span class="text-[10px] font-bold uppercase text-yellow-600 flex items-center gap-1 bg-white px-2 py-1 rounded shadow-sm border border-gray-200"><i class="ph-bold ph-warning"></i> Ressalvas</span>
                                            @elseif($item->status === 'Reprovado')
                                                <span class="text-[10px] font-bold uppercase text-red-600 flex items-center gap-1 bg-white px-2 py-1 rounded shadow-sm border border-gray-200"><i class="ph-bold ph-x"></i> Reprovado</span>
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
                            <td colspan="4" class="border-t-2 border-b border-r border-gray-400 p-3 sticky left-0 bottom-0 z-[50] bg-gray-200 text-left shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest pl-2">Total Solicitado</span>
                            </td>
                            @foreach($meses as $sigla)
                                <td class="border-t-2 border-b border-r border-gray-400 bg-gray-200 p-3 text-right text-xs font-black text-gray-900 sticky bottom-0 z-[40]">{{ number_format($totaisMensais[$sigla], 2, ',', '.') }}</td>
                            @endforeach
                            <td class="border-t-2 border-b border-r border-gray-400 bg-emerald-200 p-3 text-right text-sm font-black text-emerald-900 sticky bottom-0 z-[40]">{{ number_format($totalGeral, 2, ',', '.') }}</td>
                            <td class="border-t-2 border-b border-gray-400 bg-gray-200 sticky bottom-0 z-[40]"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- NAVIGATION DRAWER: COMENTÁRIOS / EMPRESARIAL --}}
    <div class="relative z-[150]" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-show="$wire.drawerComentariosAberto" x-cloak>
        <div x-show="$wire.drawerComentariosAberto" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="$wire.drawerComentariosAberto" 
                         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                         x-transition:enter-start="translate-x-full" 
                         x-transition:enter-end="translate-x-0" 
                         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                         x-transition:leave-start="translate-x-0" 
                         x-transition:leave-end="translate-x-full" 
                         class="pointer-events-auto w-screen max-w-md h-[100dvh] sm:h-full flex flex-col" @click.away="$wire.set('drawerComentariosAberto', false)">
                         
                        <div class="flex flex-col h-full bg-white shadow-2xl overflow-hidden">
                            <!-- Cabeçalho do Drawer Minimalista -->
                            <div class="bg-white border-b border-gray-200 px-5 py-4 sm:px-6 shrink-0 flex items-center justify-between">
                                <h2 class="text-base font-bold leading-6 text-gray-900 flex items-center gap-2">
                                    <i class="ph-bold ph-chat-centered-text text-xl text-gray-400"></i> Histórico do Orçamento
                                </h2>
                                <button type="button" @click="$wire.set('drawerComentariosAberto', false)" class="relative rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition p-2 -mr-2">
                                    <i class="ph-bold ph-x text-lg"></i>
                                </button>
                            </div>
                            
                            <!-- Corpo do Chat (Estilo Corporativo / Timeline) -->
                            <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:px-6 bg-white relative flex flex-col gap-5" 
                                 x-ref="chatContainer" 
                                 x-init="$watch('$wire.drawerComentariosAberto', val => { if(val) setTimeout(() => $el.scrollTop = $el.scrollHeight, 100) })">
                                 
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

                                <div class="bg-gray-50 border border-gray-200 text-gray-600 text-[11px] p-3 rounded-md flex items-start gap-2 shadow-sm mb-2">
                                    <i class="ph-fill ph-info text-gray-400 text-sm mt-0.5 shrink-0"></i>
                                    <span>As mensagens e aprovações deste orçamento são guardadas de forma segura e não podem ser apagadas.</span>
                                </div>

                                @foreach($todasAvaliacoes as $aval)
                                    @php
                                        $isMeu = ($aval['user_id'] == auth()->id());
                                        $isComentarioPuro = (($aval['tipo_evento'] ?? '') === 'comentario_chat');
                                        $nomeDisplay = $isMeu ? 'Você (Diretoria)' : ($aval['user_nome'] ?? 'Usuário');
                                    @endphp

                                    <div class="flex gap-3 w-full">
                                        <!-- Avatar Corporativo -->
                                        <div class="shrink-0 mt-0.5">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 uppercase">
                                                {{ substr($nomeDisplay, 0, 2) }}
                                            </div>
                                        </div>
                                        
                                        <!-- Bloco da Mensagem -->
                                        <div class="flex-1 min-w-0 flex flex-col">
                                            <div class="flex items-baseline gap-2 mb-1">
                                                <span class="text-xs font-bold text-gray-900">{{ $nomeDisplay }}</span>
                                                <span class="text-[10px] font-medium text-gray-400">{{ \Carbon\Carbon::parse($aval['created_at'] ?? now())->format('d/m/Y H:i') }}</span>
                                            </div>
                                            
                                            <div class="bg-gray-50 border border-gray-200 rounded-r-lg rounded-bl-lg p-3 shadow-sm">
                                                @if(!$isComentarioPuro)
                                                    <div class="mb-2 text-[10px] font-bold text-gray-700 bg-white px-2 py-1.5 rounded border border-gray-200 shadow-sm inline-block">
                                                        <span class="uppercase {{ in_array($aval['status_aplicado'] ?? '', ['Reprovado','Reabertura Negada']) ? 'text-red-600' : 'text-emerald-600' }}">{{ $aval['status_aplicado'] ?? '' }}</span>
                                                        @if(($aval['natureza_codigo'] ?? '') !== 'GERAL')
                                                            <span class="mx-1 text-gray-300">•</span>
                                                            <span class="text-[9px] font-medium text-gray-500">{{ $aval['natureza_codigo'] ?? '' }} - {{ $aval['natureza_descricao'] ?? '' }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if(!empty($aval['comentario']))
                                                    <p class="text-[13px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $aval['comentario'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Rodapé Minimalista (Campo de digitar) -->
                            <div class="p-4 border-t border-gray-200 bg-white shrink-0 pb-safe">
                                <form wire:submit.prevent="adicionarComentarioGlobal" class="flex flex-col gap-2">
                                    <textarea wire:model="novoComentario" @keydown.enter.prevent="$wire.adicionarComentarioGlobal()" rows="2" class="w-full text-[13px] rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 resize-none py-2 px-3" placeholder="Escreva uma observação..."></textarea>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-[10px] text-gray-400">Pressione Enter para enviar</span>
                                        <button type="submit" class="px-4 py-1.5 bg-gray-900 hover:bg-black text-white text-[11px] uppercase tracking-wider font-bold rounded-md shadow-sm transition-colors">
                                            Add
                                        </button>
                                    </div>
                                </form>
                                @error('novoComentario') <span class="text-red-500 text-[10px] font-bold block mt-1 px-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAIS DE APROVAÇÃO, REABERTURA, ETC --}}
    @if($modalAvaliacaoAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-lg font-black text-gray-900 mb-1 flex items-center gap-2"><i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Justificativa / Comentário</h3>
                <p class="text-xs text-gray-500 mb-4">
                    @if($modoAvaliacao === 'selecionados')
                        Avaliando <strong>{{ count($itensSelecionados) }} ITEM(NS) SELECIONADO(S)</strong> como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>.
                    @elseif($modoAvaliacao === 'restantes')
                        Avaliando <strong>TODOS OS ITENS RESTANTES PENDENTES</strong> como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>.
                    @else
                        Avaliando a Natureza como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>.
                    @endif
                    <br>Pode incluir um comentário opcional abaixo.
                </p>
                <textarea wire:model="comentarioAvaliacao" rows="4" class="w-full rounded-xl border-gray-300 bg-gray-50 text-sm focus:ring-purpura-500" placeholder="Digite as observações (Opcional)..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="confirmarAvaliacao" wire:loading.attr="disabled" wire:target="confirmarAvaliacao" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="confirmarAvaliacao">Confirmar Decisão</span>
                        <span wire:loading wire:target="confirmarAvaliacao"><i class="ph-bold ph-spinner animate-spin"></i> Processando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($modalAprovarReaberturaAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-lg font-black text-orange-600 mb-2 flex items-center gap-2"><i class="ph-fill ph-lock-key-open"></i> Avaliar Reabertura</h3>
                <p class="text-xs text-gray-600 mb-4">O gestor quer editar o orçamento. Se aprovar, o status volta para "Em Elaboração". Pode também definir uma data limite (opcional) para o orçamento bloquear novamente e automaticamente.</p>
                
                <div class="mb-5">
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">Prazo Limite para Edição (Opcional)</label>
                    <input type="datetime-local" wire:model="prazoReabertura" class="w-full text-sm border-gray-300 bg-gray-50 rounded-lg focus:border-orange-500 focus:ring-orange-500 px-3 py-2">
                </div>

                <div class="flex justify-end gap-2 mt-4 border-t border-gray-100 pt-4">
                    <button wire:click="negarReabertura" class="px-4 py-2 text-xs font-bold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 rounded shadow-sm">Negar Pedido</button>
                    <button wire:click="$set('modalAprovarReaberturaAberto', false)" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded">Cancelar</button>
                    <button wire:click="aprovarReabertura" class="px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded shadow-sm">Aprovar Reabertura</button>
                </div>
            </div>
        </div>
    @endif
</div>