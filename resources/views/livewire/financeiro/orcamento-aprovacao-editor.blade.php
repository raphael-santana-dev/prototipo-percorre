<div class="min-h-screen bg-gray-50 flex flex-col font-sans" x-data="{ timelineAberta: true }">
    
    {{-- BARRA SUPERIOR (HEADER) --}}
    <div class="bg-white border-b border-gray-300 shadow-sm px-6 py-3 flex items-center justify-between sticky top-0 z-40">
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
            <button @click="timelineAberta = !timelineAberta" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm" :class="timelineAberta ? 'bg-gray-200' : ''">
                <i class="ph-bold ph-sidebar-simple"></i> Painel de Decisão
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden relative">
        
        {{-- ÁREA DA PLANILHA EXCEL (LEITURA) --}}
        <div class="flex-1 overflow-auto custom-scrollbar p-4 transition-all duration-300" :class="timelineAberta ? 'pr-[380px]' : ''">
            
            <div class="mb-4 bg-white border border-gray-300 p-3 shadow-sm flex items-center gap-3">
                <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa do Gestor:</label>
                <div class="w-full text-sm bg-gray-50 px-3 py-1.5 text-gray-600 italic border border-gray-200 rounded">
                    {{ $orcamento->descricao_despesa ?: 'Nenhuma observação geral enviada.' }}
                </div>
            </div>

            <div class="bg-white border border-gray-400 shadow-sm relative">
                <table class="w-full border-collapse text-left whitespace-nowrap table-fixed min-w-[1800px]">
                    <thead>
                        <tr>
                            <th class="w-[280px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider sticky left-0 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Natureza Financeira</th>
                            <th class="w-[200px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider sticky left-[280px] z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Anotações da Linha</th>
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                <th class="w-[100px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider text-right">
                                    {{ $mes }}
                                </th>
                            @endforeach
                            <th class="w-[120px] border border-gray-300 bg-emerald-100 p-2 text-[10px] font-black text-emerald-800 uppercase tracking-wider text-right">Total Linha</th>
                            <th class="w-[160px] border border-gray-300 bg-gray-100 p-2 text-center text-[10px] font-black text-gray-700 uppercase">Decisão</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalGeral = 0; @endphp
                        @foreach($orcamento->itens as $item)
                            @php
                                $bgRow = match($item->status) {
                                    'Aprovado' => 'bg-emerald-50/40',
                                    'Reprovado' => 'bg-red-50/40',
                                    'Aprovado com ressalvas' => 'bg-yellow-50/40',
                                    default => 'hover:bg-gray-50'
                                };
                            @endphp
                            <tr class="transition-colors {{ $bgRow }}">
                                
                                {{-- COLUNA 1: NATUREZA (FIXA) --}}
                                <td class="border border-gray-300 bg-white sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] p-0 {{ $bgRow === 'hover:bg-gray-50' ? '' : '!bg-transparent' }}">
                                    <div class="px-2 py-1.5 flex flex-col justify-center h-full">
                                        <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item->descricao }}">{{ $item->descricao }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[9px] font-mono text-gray-500">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                            @if($item->status === 'Corrigido')
                                                <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1 py-0.5 uppercase flex items-center gap-1"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUNA 2: OBSERVAÇÃO (FIXA) --}}
                                <td class="border border-gray-300 bg-white sticky left-[280px] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] p-0 {{ $bgRow === 'hover:bg-gray-50' ? '' : '!bg-transparent' }}">
                                    <div class="w-full h-full px-2 py-2 text-[11px] text-gray-600 truncate bg-transparent" title="{{ $item->descricao }}">
                                        {{ $item->descricao ?: '-' }}
                                    </div>
                                </td>

                                {{-- COLUNAS DOS MESES --}}
                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $valorMes = (float)($item->{"valor_$sigla"} ?? 0);
                                        $totalLinha += $valorMes;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-right text-xs font-medium {{ $valorMes == 0 ? 'text-gray-400' : 'text-gray-900' }}">
                                        {{ number_format($valorMes, 2, ',', '.') }}
                                    </td>
                                @endforeach

                                {{-- COLUNA TOTAL E AÇÕES DE APROVAÇÃO --}}
                                @php $totalGeral += $totalLinha; @endphp
                                <td class="border border-gray-300 bg-emerald-50/30 p-2 text-right text-xs font-black text-emerald-900 select-none">
                                    {{ number_format($totalLinha, 2, ',', '.') }}
                                </td>
                                
                                <td class="border border-gray-300 p-1 bg-gray-50/50">
                                    @if(in_array($item->status, ['Criado', 'Corrigido']))
                                        <div class="flex items-center gap-1 justify-center">
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado')" class="p-1 text-emerald-600 hover:bg-emerald-100 rounded border border-emerald-200 bg-white shadow-sm transition" title="Aprovar Item"><i class="ph-bold ph-check text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado com ressalvas')" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded border border-yellow-200 bg-white shadow-sm transition" title="Aprovar com Ressalvas"><i class="ph-bold ph-warning text-base"></i></button>
                                            <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Reprovado')" class="p-1 text-red-600 hover:bg-red-100 rounded border border-red-200 bg-white shadow-sm transition" title="Reprovar Item"><i class="ph-bold ph-x text-base"></i></button>
                                        </div>
                                    @else
                                        <div class="flex justify-center items-center h-full">
                                            @if($item->status === 'Aprovado')
                                                <span class="text-[10px] font-bold uppercase text-emerald-600 flex items-center gap-1"><i class="ph-bold ph-check"></i> Aprovado</span>
                                            @elseif($item->status === 'Aprovado com ressalvas')
                                                <span class="text-[10px] font-bold uppercase text-yellow-600 flex items-center gap-1"><i class="ph-bold ph-warning"></i> Ressalvas</span>
                                            @elseif($item->status === 'Reprovado')
                                                <span class="text-[10px] font-bold uppercase text-red-600 flex items-center gap-1"><i class="ph-bold ph-x"></i> Reprovado</span>
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
                        <tr class="bg-gray-200 border-t-2 border-gray-400">
                            <td colspan="2" class="border border-gray-300 p-2 sticky left-0 z-20 bg-gray-200 text-left">
                                <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest ml-2">Total Solicitado</span>
                            </td>
                            @foreach($meses as $sigla)
                                <td class="border border-gray-300 p-2 text-right text-xs font-black text-gray-900">{{ number_format($totaisMensais[$sigla], 2, ',', '.') }}</td>
                            @endforeach
                            <td class="border border-gray-300 bg-emerald-200 p-2 text-right text-sm font-black text-emerald-900">{{ number_format($totalGeral, 2, ',', '.') }}</td>
                            <td class="border border-gray-300"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BARRA LATERAL: TIMELINE DE APROVAÇÕES E AÇÕES EM LOTE --}}
        <div x-show="timelineAberta" x-transition x-cloak class="w-[380px] bg-white border-l border-gray-300 shadow-2xl flex flex-col absolute right-0 top-0 bottom-0 z-30 transition-transform">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Histórico / Logs</h3>
                <button @click="timelineAberta = false" class="text-gray-400 hover:text-gray-700"><i class="ph-bold ph-x text-lg"></i></button>
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

            {{-- PAINEL DE DECISÃO EM LOTE (Rodapé da Sidebar) --}}
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