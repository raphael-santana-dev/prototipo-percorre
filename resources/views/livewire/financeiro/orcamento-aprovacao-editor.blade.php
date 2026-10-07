<div class="font-sans flex flex-col transition-all duration-300 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden" 
     x-data="{ timelineAberta: true, modoExpandido: false, navbarOculta: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val)); $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val))"
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
                <button @click="modoExpandido = !modoExpandido" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors" :class="modoExpandido ? 'bg-white shadow-sm text-purpura-600' : 'text-gray-600 hover:text-gray-900'" title="Expandir Tela">
                    <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                </button>
                <button x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors text-gray-600 hover:text-gray-900" :class="navbarOculta ? 'bg-gray-200 text-gray-900' : ''" title="Ocultar Navbar do Sistema">
                    <i class="ph-bold" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                    <span class="hidden xl:inline ml-1" x-text="navbarOculta ? 'Menus Ocultos' : 'Ocultar Menus'"></span>
                </button>
            </div>

            <button @click="timelineAberta = !timelineAberta" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm" :class="timelineAberta ? 'bg-gray-200' : ''">
                <i class="ph-bold ph-sidebar-simple"></i> <span class="hidden sm:inline">Painel de Decisão</span>
            </button>
        </div>
    </div>

    {{-- ALERTA DE PEDIDO DE REABERTURA (SÓ APARECE SE REABERTURA FOR SOLICITADA) --}}
    @if($orcamento->reabertura_solicitada)
        <div class="bg-orange-50 border-b border-orange-200 p-4 flex justify-between items-center z-[55] relative shrink-0">
            <div>
                <h4 class="text-sm font-black text-orange-800 flex items-center gap-2"><i class="ph-bold ph-warning-circle text-lg"></i> Pedido de Reabertura Pendente</h4>
                <p class="text-xs text-orange-700 mt-0.5">O Gestor necessita corrigir este orçamento que já estava bloqueado. <span class="font-bold ml-1">Motivo do Gestor:</span> <span class="italic">"{{ $orcamento->reabertura_motivo }}"</span></p>
            </div>
            <button wire:click="$set('modalAprovarReaberturaAberto', true)" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded shadow-sm transition">Analisar Pedido</button>
        </div>
    @endif

    {{-- ÁREA DINÂMICA: PLANILHA + SIDEBAR --}}
    <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50">
        
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative bg-white" :class="timelineAberta ? 'pr-[380px]' : ''">
            
            <div class="p-3 bg-white border-b border-gray-200 shadow-sm flex items-center gap-3 shrink-0 z-10 relative">
                <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa do Gestor:</label>
                <div class="w-full text-sm bg-gray-50 px-3 py-1.5 text-gray-600 italic border border-gray-200 rounded-md">
                    {{ $orcamento->descricao_despesa ?: 'Nenhuma observação geral enviada pelo Gestor.' }}
                </div>
            </div>

            <div class="flex-1 overflow-auto custom-scrollbar relative">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0 border-t border-l border-gray-300">
                    <thead>
                        <tr>
                            <th class="w-[300px] min-w-[300px] sticky left-0 top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-400 text-emerald-600 focus:ring-emerald-500 mt-0.5 cursor-pointer" title="Selecionar todos os pendentes">
                                    <span>Natureza Financeira</span>
                                </div>
                            </th>
                            <th class="w-[200px] min-w-[200px] sticky left-[300px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Anotações da Linha</th>
                            
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
                            <tr class="group transition-colors {{ $bgRowSolid }} hover:bg-blue-50">
                                
                                <td class="sticky left-0 z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex items-start gap-2 mt-1 px-1 w-[280px]">
                                        @if(in_array($item->status, ['Criado', 'Corrigido']))
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $item->id }}" class="mt-0.5 rounded border-gray-400 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                        @else
                                            <div class="w-4 mt-0.5"></div>
                                        @endif
                                        
                                        <div class="flex flex-col w-full overflow-hidden">
                                            <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item->descricao }}">{{ $item->descricao }}</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] font-mono text-gray-500">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                                @if($item->status === 'Corrigido')
                                                    <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 uppercase flex items-center gap-1 rounded shadow-sm"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="sticky left-[300px] z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="w-full min-w-[180px] h-full px-2 py-2 text-[11px] text-gray-600 truncate bg-transparent" title="{{ $item->descricao }}">
                                        {{ $item->descricao ?: '-' }}
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
                            <td colspan="2" class="border-t-2 border-b border-r border-gray-400 p-3 sticky left-0 bottom-0 z-[50] bg-gray-200 text-left shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
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

        {{-- BARRA LATERAL: TIMELINE DE APROVAÇÕES E AÇÕES EM LOTE --}}
        <div x-show="timelineAberta" x-transition x-cloak class="w-[380px] bg-white border-l border-gray-300 shadow-[rgba(0,0,0,0.15)_0px_0px_20px] flex flex-col absolute right-0 top-0 bottom-0 z-[60] transition-transform">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Histórico / Logs</h3>
                <button @click="timelineAberta = false" class="text-gray-400 hover:text-gray-700 transition" title="Fechar Painel"><i class="ph-bold ph-x text-lg"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar relative">
                <div class="absolute left-7 top-4 bottom-4 w-px bg-gray-200"></div>
                @php
                    $todasAvaliacoes = collect();
                    foreach($orcamento->itens as $itemLinha) {
                        foreach($itemLinha->avaliacoes as $aval) {
                            $avalArray = is_array($aval) ? $aval : $aval->toArray();
                            $avalArray['natureza_codigo'] = $itemLinha->natureza_codigo;
                            $avalArray['natureza_descricao'] = $itemLinha->descricao;
                            $todasAvaliacoes->push($avalArray);
                        }
                    }
                    if(!empty($orcamento->logsGerais)) {
                        foreach($orcamento->logsGerais as $aval) {
                            $avalArray = is_array($aval) ? $aval : $aval->toArray();
                            $avalArray['natureza_codigo'] = 'GERAL';
                            $avalArray['natureza_descricao'] = 'Ação Global do Centro de Custo';
                            $todasAvaliacoes->push($avalArray);
                        }
                    }
                    $todasAvaliacoes = $todasAvaliacoes->sortByDesc('created_at');
                @endphp

                @forelse($todasAvaliacoes as $aval)
                    @php 
                        $cor = match($aval['status_aplicado']) { 
                            'Aprovado', 'Reabertura Aprovada' => 'bg-emerald-500', 
                            'Aprovado com ressalvas', 'Pedido de Reabertura' => 'bg-yellow-500', 
                            'Reprovado', 'Reabertura Negada' => 'bg-red-500', 
                            'Edição Pós-Aprovação' => 'bg-blue-500',
                            default => 'bg-gray-500' 
                        }; 
                    @endphp
                    <div class="relative pl-8 mb-6">
                        <div class="absolute w-3 h-3 rounded-full {{ $cor }} border-2 border-white left-[-6px] top-1 shadow-sm"></div>
                        <div class="bg-white p-3.5 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-black text-gray-900">{{ $aval['user_nome'] ?? 'Usuário' }}</span>
                                <span class="text-[9px] font-bold text-gray-400">{{ \Carbon\Carbon::parse($aval['created_at'])->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5 mb-2">
                                <span class="text-[10px] font-bold uppercase {{ in_array($aval['status_aplicado'], ['Reprovado','Reabertura Negada']) ? 'text-red-600' : (in_array($aval['status_aplicado'], ['Edição Pós-Aprovação']) ? 'text-blue-600' : 'text-emerald-600') }}">{{ $aval['status_aplicado'] }}</span>
                                <span class="text-[9px] text-gray-500 font-medium leading-tight" title="{{ $aval['natureza_descricao'] ?? '' }}">{{ $aval['natureza_codigo'] ?? '' }} {{ !empty($aval['natureza_descricao']) ? '- ' . $aval['natureza_descricao'] : '' }}</span>
                            </div>
                            @if(!empty($aval['comentario']))
                                <p class="text-sm text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100 italic">"{{ $aval['comentario'] }}"</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 relative z-10">
                        <i class="ph-fill ph-chat-slash text-4xl text-gray-300 mb-2"></i>
                        <p class="text-xs font-bold text-gray-400 uppercase">Nenhuma avaliação.</p>
                    </div>
                @endforelse
            </div>

            {{-- PAINEL INTELIGENTE DE DECISÃO EM LOTE --}}
            @php
                $totalPendentes = $orcamento->itens->whereIn('status', ['Criado', 'Corrigido'])->count();
                $qtdSelecionados = count($itensSelecionados);
                $qtdRestantes = $totalPendentes - $qtdSelecionados;
            @endphp

            @if($totalPendentes > 0 && !$orcamento->reabertura_solicitada)
                <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex flex-col gap-4">
                    
                    @if($qtdSelecionados > 0)
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-2 tracking-widest">
                                Selecionados ({{ $qtdSelecionados }})
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado')" class="py-2 bg-white text-emerald-700 border border-emerald-200 shadow-sm rounded text-[11px] font-bold hover:bg-emerald-50 transition">Aprovar</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Reprovado')" class="py-2 bg-white text-red-700 border border-red-200 shadow-sm rounded text-[11px] font-bold hover:bg-red-50 transition">Reprovar</button>
                                <button wire:click="abrirModalAvaliacaoSelecionados('Aprovado com ressalvas')" class="col-span-2 py-2 bg-white text-yellow-700 border border-yellow-200 shadow-sm rounded text-[11px] font-bold hover:bg-yellow-50 transition">Ressalvas (Selecionados)</button>
                            </div>
                        </div>
                    @endif
                    
                    @if($qtdRestantes > 0)
                        <div class="{{ $qtdSelecionados > 0 ? 'border-t border-gray-200 pt-4' : '' }}">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-2 tracking-widest">
                                {{ $qtdSelecionados > 0 ? 'Restantes' : 'Todos os Pendentes' }} ({{ $qtdRestantes }})
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="abrirModalAvaliacaoRestantes('Aprovado')" class="py-2 bg-white text-emerald-700 border border-emerald-200 shadow-sm rounded text-[11px] font-bold hover:bg-emerald-50 transition">Aprovar Restantes</button>
                                <button wire:click="abrirModalAvaliacaoRestantes('Reprovado')" class="py-2 bg-white text-red-700 border border-red-200 shadow-sm rounded text-[11px] font-bold hover:bg-red-50 transition">Reprovar Restantes</button>
                                <button wire:click="abrirModalAvaliacaoRestantes('Aprovado com ressalvas')" class="col-span-2 py-2 bg-white text-yellow-700 border border-yellow-200 shadow-sm rounded text-[11px] font-bold hover:bg-yellow-50 transition">Ressalvas (Restantes)</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>

    {{-- MODAL DE COMENTÁRIO DO APROVADOR (Opcional) --}}
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
                    <button wire:click="confirmarAvaliacao" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">Confirmar Decisão</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE RESPOSTA AO PEDIDO DE REABERTURA --}}
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