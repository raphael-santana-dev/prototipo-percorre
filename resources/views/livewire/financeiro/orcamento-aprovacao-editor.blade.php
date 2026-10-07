<div class="font-sans flex flex-col transition-all duration-300 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden" 
     x-data="{ timelineAberta: true, modoExpandido: false, navbarOculta: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val)); $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val))"
     :class="modoExpandido ? 'h-full border-none rounded-none' : 'min-h-[600px] h-[calc(100vh-180px)]'">
    
    {{-- CSS Dinâmico: Força o modo Tela Cheia sem interferir no resto do sistema --}}
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

            {{-- BOTÕES DE VISUALIZAÇÃO --}}
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg border border-gray-200">
                <button @click="modoExpandido = !modoExpandido" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors" :class="modoExpandido ? 'bg-white shadow-sm text-purpura-600' : 'text-gray-600 hover:text-gray-900'" title="Expandir Área de Trabalho">
                    <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                </button>
                
                {{-- Botão que SÓ aparece no Modo Expandido --}}
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

    {{-- ÁREA DINÂMICA: PLANILHA + SIDEBAR --}}
    <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50">
        
        {{-- ÁREA DA PLANILHA EXCEL (LEITURA) --}}
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative" :class="timelineAberta ? 'pr-[380px]' : ''">
            
            <div class="p-3 bg-white border-b border-gray-300 shadow-sm flex items-center gap-3 shrink-0 z-10 relative">
                <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa do Gestor:</label>
                <div class="w-full text-sm bg-gray-50 px-3 py-1.5 text-gray-600 italic border border-gray-200 rounded-md">
                    {{ $orcamento->descricao_despesa ?: 'Nenhuma observação geral enviada pelo Gestor.' }}
                </div>
            </div>

            <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0 border-t border-l border-gray-300">
                    <thead>
                        <tr>
                            <th class="w-[300px] min-w-[300px] sticky left-0 top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Natureza Financeira</th>
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
                                    'Aprovado' => 'bg-[#eefcf5]', // verde muito suave sólido
                                    'Reprovado' => 'bg-[#fff5f5]', // vermelho suave sólido
                                    'Aprovado com ressalvas' => 'bg-[#fffbeb]', // amarelo suave sólido
                                    default => 'bg-white'
                                };
                            @endphp
                            <tr class="group transition-colors {{ $bgRowSolid }} hover:bg-blue-50">
                                
                                {{-- COLUNA 1: NATUREZA (FIXA) --}}
                                <td class="sticky left-0 z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex flex-col mt-1 px-1 w-[280px]">
                                        <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item->descricao }}">{{ $item->descricao }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] font-mono text-gray-500">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                            @if($item->status === 'Corrigido')
                                                <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 uppercase flex items-center gap-1 rounded shadow-sm"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUNA 2: OBSERVAÇÃO (FIXA) --}}
                                <td class="sticky left-[300px] z-[30] {{ $bgRowSolid }} group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="w-full min-w-[180px] h-full px-2 py-2 text-[11px] text-gray-600 truncate bg-transparent" title="{{ $item->descricao }}">
                                        {{ $item->descricao ?: '-' }}
                                    </div>
                                </td>

                                {{-- COLUNAS DOS MESES --}}
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
                                    <td class="border-b border-r border-gray-300 p-2 align-top transition-colors z-0">
                                        <div class="relative">
                                            @if($previsto > 0 && $previsto != $valorMes)
                                                <div class="absolute -top-1.5 right-1 bg-white px-1 text-[8px] font-bold text-gray-400 rounded-sm">Base: {{ number_format($previsto, 0, '', '') }}</div>
                                            @endif
                                            <div class="w-full text-xs text-right font-bold text-gray-900 border border-gray-300 bg-white rounded p-1.5 {{ $valorMes == 0 ? 'text-gray-400' : '' }}">
                                                {{ number_format($valorMes, 2, ',', '.') }}
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2 flex flex-col items-end justify-center text-[9px] font-medium leading-tight space-y-0.5 px-1">
                                            <span class="text-gray-500">Previsto: R$ {{ number_format($previsto, 2, ',', '.') }}</span>
                                            @if($diferenca != 0)
                                                <span class="{{ $corDiff }} font-bold bg-white/70 px-1.5 py-0.5 rounded shadow-sm flex items-center justify-end w-full border border-gray-200">
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
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado')" class="p-1 text-emerald-600 hover:bg-emerald-100 rounded border border-emerald-200 bg-white shadow-sm transition" title="Aprovar Item"><i class="ph-bold ph-check text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado com ressalvas')" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded border border-yellow-200 bg-white shadow-sm transition" title="Aprovar com Ressalvas"><i class="ph-bold ph-warning text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Reprovado')" class="p-1 text-red-600 hover:bg-red-100 rounded border border-red-200 bg-white shadow-sm transition" title="Reprovar Item"><i class="ph-bold ph-x text-base"></i></button>
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

        {{-- BARRA LATERAL: TIMELINE DE APROVAÇÕES E AÇÕES EM LOTE (SCROLL INTERNO INDEPENDENTE) --}}
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
                    $todasAvaliacoes = $todasAvaliacoes->sortByDesc('created_at');
                @endphp

                @forelse($todasAvaliacoes as $aval)
                    @php $cor = match($aval['status_aplicado']) { 'Aprovado' => 'bg-emerald-500', 'Aprovado com ressalvas' => 'bg-yellow-500', 'Reprovado' => 'bg-red-500', default => 'bg-gray-500' }; @endphp
                    <div class="relative pl-8 mb-6">
                        <div class="absolute w-3 h-3 rounded-full {{ $cor }} border-2 border-white left-[-6px] top-1 shadow-sm"></div>
                        <div class="bg-white p-3.5 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-black text-gray-900">{{ $aval['user_nome'] ?? 'Diretoria' }}</span>
                                <span class="text-[9px] font-bold text-gray-400">{{ \Carbon\Carbon::parse($aval['created_at'])->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5 mb-2">
                                <span class="text-[10px] font-bold uppercase {{ str_contains($aval['status_aplicado'], 'Reprovado') ? 'text-red-600' : 'text-emerald-600' }}">{{ $aval['status_aplicado'] }}</span>
                                <span class="text-[9px] text-gray-500 font-medium leading-tight" title="{{ $aval['natureza_descricao'] }}">{{ $aval['natureza_codigo'] }} - {{ $aval['natureza_descricao'] }}</span>
                            </div>
                            <p class="text-sm text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100 italic">"{{ $aval['comentario'] }}"</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 relative z-10">
                        <i class="ph-fill ph-chat-slash text-4xl text-gray-300 mb-2"></i>
                        <p class="text-xs font-bold text-gray-400 uppercase">Nenhuma avaliação.</p>
                    </div>
                @endforelse
            </div>

            {{-- PAINEL DE DECISÃO EM LOTE (Rodapé da Sidebar Fixo) --}}
            <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <p class="text-[10px] font-bold text-gray-500 uppercase text-center mb-3 tracking-widest">Avaliar Pendentes em Lote</p>
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="abrirModalAvaliacaoLote('Aprovado')" class="py-2.5 bg-white text-emerald-700 border border-emerald-200 shadow-sm rounded-lg text-xs font-bold hover:bg-emerald-50 transition">Aprovar Todos</button>
                    <button wire:click="abrirModalAvaliacaoLote('Reprovado')" class="py-2.5 bg-white text-red-700 border border-red-200 shadow-sm rounded-lg text-xs font-bold hover:bg-red-50 transition">Reprovar Todos</button>
                    <button wire:click="abrirModalAvaliacaoLote('Aprovado com ressalvas')" class="col-span-2 py-2.5 bg-white text-yellow-700 border border-yellow-200 shadow-sm rounded-lg text-xs font-bold hover:bg-yellow-50 transition">Aprovar com Ressalvas (Todos)</button>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL DE COMENTÁRIO DO APROVADOR --}}
    @if($modalAvaliacaoAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-lg font-black text-gray-900 mb-1 flex items-center gap-2"><i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Justificativa de Decisão</h3>
                <p class="text-xs text-gray-500 mb-4">
                    @if($isAvaliacaoLote)
                        Avaliando <strong>TODOS OS ITENS PENDENTES</strong> como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>.
                    @else
                        Avaliando a Natureza como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>.
                    @endif
                    O gestor receberá este feedback.
                </p>
                <textarea wire:model="comentarioAvaliacao" rows="4" class="w-full rounded-xl border-gray-300 bg-gray-50 text-sm focus:ring-purpura-500" placeholder="Digite as diretrizes de correção..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="confirmarAvaliacao" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">Confirmar Decisão</button>
                </div>
            </div>
        </div>
    @endif
</div>