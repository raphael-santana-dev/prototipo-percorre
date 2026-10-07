<div class="min-h-screen bg-gray-50 flex flex-col font-sans" x-data="{ timelineAberta: false }">
    
    {{-- BARRA SUPERIOR (HEADER) --}}
    <div class="bg-white border-b border-gray-300 shadow-sm px-6 py-3 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-4">
            <button wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition"><i class="ph-bold ph-arrow-left"></i></button>
            <div class="border-l border-gray-300 pl-4">
                <h1 class="text-lg font-black text-gray-900 flex items-center gap-2 tracking-tight">
                    <i class="ph-fill ph-microsoft-excel-logo text-emerald-600"></i> Planilha de Orçamento
                </h1>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-widest mt-0.5">
                    Centro de Custo: <span class="text-purpura-600 font-bold">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'S/ Vínculo' }} ({{ $orcamento->ccusto }})</span> | Ano: {{ $orcamento->ano }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="flex flex-col items-end mr-4 border-r border-gray-200 pr-4">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Status da Planilha</span>
                @php
                    $statusColor = match($orcamento->status) {
                        'Em elaboração' => 'text-blue-600', 'Finalizado' => 'text-purple-600',
                        'Aprovado' => 'text-emerald-600', 'Aprovado com ressalvas' => 'text-yellow-600',
                        'Reprovado' => 'text-red-600', default => 'text-gray-600'
                    };
                @endphp
                <span class="text-sm font-black uppercase {{ $statusColor }}">{{ $orcamento->status }}</span>
            </div>

            <button @click="timelineAberta = !timelineAberta" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100">
                <i class="ph-bold ph-clock-counter-clockwise"></i> Histórico
            </button>

            @if(!$isLockedGlobal)
                <button wire:click="salvarOrcamento" class="btn btn--secondary btn--small !text-emerald-700 !border-emerald-300 hover:!bg-emerald-50">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar
                </button>
                <button wire:click="finalizarOrcamento" class="btn btn--primary btn--small shadow-sm" onclick="confirm('Submeter para aprovação da Diretoria?') || event.stopImmediatePropagation()">
                    <i class="ph-bold ph-paper-plane-tilt"></i> Enviar p/ Aprovação
                </button>
            @endif
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden relative">
        
        {{-- ÁREA DA PLANILHA EXCEL --}}
        <div class="flex-1 overflow-auto custom-scrollbar p-4" :class="timelineAberta ? 'pr-80' : ''">
            
            <div class="mb-4 bg-white border border-gray-300 p-3 shadow-sm flex items-center gap-3">
                <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa Global:</label>
                <input type="text" wire:model="justificativaGeral" placeholder="Motivo ou observações gerais..." class="w-full text-sm border-none bg-gray-50 focus:ring-1 focus:ring-emerald-500 px-3 py-1.5" @if($isLockedGlobal) disabled @endif>
            </div>

            <div class="bg-white border border-gray-400 shadow-sm relative">
                <table class="w-full border-collapse text-left whitespace-nowrap table-fixed min-w-[1800px]">
                    <thead>
                        <tr>
                            <th class="w-[300px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider sticky left-0 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Natureza Financeira</th>
                            <th class="w-[200px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider sticky left-[300px] z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Observações da Linha</th>
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                <th class="w-[100px] border border-gray-300 bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider text-right">
                                    {{ $mes }} {!! ($orcamento->ano < $anoSimulacao) ? '<i class="ph-bold ph-lock-key ml-1"></i>' : '' !!}
                                </th>
                            @endforeach
                            <th class="w-[120px] border border-gray-300 bg-emerald-100 p-2 text-[10px] font-black text-emerald-800 uppercase tracking-wider text-right">Total Linha</th>
                            <th class="w-[50px] border border-gray-300 bg-gray-100 p-2 text-center"><i class="ph-bold ph-gear"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalGeral = 0; @endphp
                        @foreach($itensOrcamento as $index => $item)
                            <tr class="hover:bg-blue-50/40 transition-colors focus-within:bg-blue-50/40">
                                
                                {{-- COLUNA 1: NATUREZA (FIXA) --}}
                                <td class="border border-gray-300 bg-white sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] p-0">
                                    @if(!empty($item['id']))
                                        <div class="px-2 py-1.5 flex flex-col justify-center h-full">
                                            <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item['descricao'] }}">{{ $item['descricao'] }}</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] font-mono text-gray-500">{{ $item['natureza_codigo'] ?: 'N/D' }}</span>
                                                @if($item['status'] === 'Reprovado') <span class="text-[8px] font-bold bg-red-100 text-red-700 px-1 py-0.5 uppercase">Reprovado</span>
                                                @elseif($item['status'] === 'Aprovado com ressalvas') <span class="text-[8px] font-bold bg-yellow-100 text-yellow-700 px-1 py-0.5 uppercase">Ressalvas</span>
                                                @elseif($item['status'] === 'Aprovado') <span class="text-[8px] font-bold bg-emerald-100 text-emerald-700 px-1 py-0.5 uppercase">Aprovado</span>
                                                @elseif($item['status'] === 'Corrigido') <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1 py-0.5 uppercase">Re-Analisar</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <select wire:model="itensOrcamento.{{ $index }}.natureza_codigo" class="w-full h-full border-none text-xs font-bold text-gray-800 focus:ring-2 focus:ring-inset focus:ring-emerald-500 px-2 py-1 bg-transparent appearance-none cursor-pointer">
                                            <option value="">Selecione...</option>
                                            @foreach($todasNaturezas as $nat)
                                                <option value="{{ $nat->codigo }}">{{ $nat->codigo }} - {{ $nat->descricao }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>

                                {{-- COLUNA 2: OBSERVAÇÃO (FIXA) --}}
                                <td class="border border-gray-300 bg-white sticky left-[300px] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] p-0">
                                    <input type="text" wire:model="itensOrcamento.{{ $index }}.descricao" class="w-full h-full border-none text-[11px] text-gray-600 focus:ring-2 focus:ring-inset focus:ring-emerald-500 px-2 py-1.5 bg-transparent" placeholder="Anotações..." @if($isLockedGlobal) disabled @endif>
                                </td>

                                {{-- COLUNAS DOS MESES --}}
                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $isDisabled = $isLockedGlobal || ($orcamento->ano < $anoSimulacao);
                                        $valorMes = (float)($item["valor_$sigla"] ?? 0);
                                        $previsto = (float)($item["previsto_$sigla"] ?? 0);
                                        $totalLinha += $valorMes;
                                    @endphp
                                    <td class="border border-gray-300 p-0 relative {{ $isDisabled ? 'bg-gray-100' : 'bg-white' }}">
                                        @if($previsto > 0 && $previsto != $valorMes)
                                            <div class="absolute top-0.5 right-1 text-[8px] font-bold text-gray-400 select-none">B: {{ number_format($previsto, 0, '', '') }}</div>
                                        @endif
                                        <input type="number" step="0.01" wire:model.live.debounce.500ms="itensOrcamento.{{ $index }}.valor_{{ $sigla }}" class="w-full h-full border-none text-xs text-right font-medium text-gray-900 focus:ring-2 focus:ring-inset focus:ring-emerald-500 px-2 py-2 bg-transparent {{ $valorMes == 0 ? 'text-gray-300' : '' }} {{ $previsto > 0 && $previsto != $valorMes ? 'pt-4' : '' }}" @if($isDisabled) disabled @endif>
                                    </td>
                                @endforeach

                                {{-- COLUNA TOTAL E AÇÕES --}}
                                @php $totalGeral += $totalLinha; @endphp
                                <td class="border border-gray-300 bg-emerald-50/30 p-2 text-right text-xs font-black text-emerald-900 select-none">
                                    {{ number_format($totalLinha, 2, ',', '.') }}
                                </td>
                                <td class="border border-gray-300 bg-white p-1 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if(!empty($item['avaliacoes']))
                                            <button type="button" @click="timelineAberta = true" class="text-orange-500 hover:text-orange-700 p-0.5" title="Ver feedback"><i class="ph-fill ph-warning-circle text-lg"></i></button>
                                        @endif
                                        @if(!$isLockedGlobal)
                                            <button type="button" wire:click="removerItem({{ $index }})" class="text-gray-400 hover:text-red-600 p-0.5" title="Remover"><i class="ph-bold ph-trash text-sm"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $totaisMensais = [];
                            foreach($meses as $sigla) $totaisMensais[$sigla] = collect($itensOrcamento)->sum("valor_$sigla");
                        @endphp
                        <tr class="bg-gray-200 border-t-2 border-gray-400">
                            <td colspan="2" class="border border-gray-300 p-2 sticky left-0 z-20 bg-gray-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Totais Calculados</span>
                                    @if(!$isLockedGlobal)
                                        <button wire:click="adicionarItem" class="text-[10px] font-bold uppercase bg-white border border-gray-300 px-2 py-0.5 hover:bg-gray-50 text-gray-700 shadow-sm"><i class="ph-bold ph-plus"></i> Linha</button>
                                    @endif
                                </div>
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

        {{-- BARRA LATERAL: TIMELINE DE APROVAÇÕES (TOGGLE) --}}
        <div x-show="timelineAberta" x-transition x-cloak class="w-80 bg-white border-l border-gray-300 shadow-xl flex flex-col absolute right-0 top-0 bottom-0 z-30">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Histórico / Logs</h3>
                <button @click="timelineAberta = false" class="text-gray-400 hover:text-gray-700"><i class="ph-bold ph-x text-lg"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar relative">
                <div class="absolute left-6 top-4 bottom-4 w-px bg-gray-200"></div>
                @php
                    $todasAvaliacoes = collect();
                    foreach($itensOrcamento as $itemLinha) {
                        if(!empty($itemLinha['avaliacoes'])) {
                            foreach($itemLinha['avaliacoes'] as $aval) {
                                $avalArray = is_array($aval) ? $aval : $aval->toArray();
                                $avalArray['natureza_codigo'] = $itemLinha['natureza_codigo'];
                                $todasAvaliacoes->push($avalArray);
                            }
                        }
                    }
                    $todasAvaliacoes = $todasAvaliacoes->sortByDesc('created_at');
                @endphp

                @forelse($todasAvaliacoes as $aval)
                    @php $cor = match($aval['status_aplicado']) { 'Aprovado' => 'bg-emerald-500', 'Aprovado com ressalvas' => 'bg-yellow-500', 'Reprovado' => 'bg-red-500', default => 'bg-gray-500' }; @endphp
                    <div class="relative pl-6 mb-5">
                        <div class="absolute w-2.5 h-2.5 rounded-full {{ $cor }} border-2 border-white left-[-5px] top-1"></div>
                        <div class="text-[10px] text-gray-400 font-bold mb-0.5">{{ \Carbon\Carbon::parse($aval['created_at'])->format('d/m/Y H:i') }} - {{ $aval['user_nome'] }}</div>
                        <div class="text-[11px] font-bold text-gray-900">{{ $aval['natureza_codigo'] }} <span class="{{ str_contains($aval['status_aplicado'], 'Reprovado') ? 'text-red-600' : 'text-emerald-600' }}">({{ $aval['status_aplicado'] }})</span></div>
                        <div class="text-xs text-gray-600 mt-1 bg-gray-50 p-2 border border-gray-100 italic">"{{ $aval['comentario'] }}"</div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 font-bold text-center mt-4">Nenhum log registrado.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- BOTÃO FLUTUANTE (NOVA NATUREZA) --}}
    @if(!$isLockedGlobal)
        <button wire:click="abrirModalNovaNatureza" class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-12 h-12 rounded-full shadow-lg bg-gray-800 hover:bg-black text-white transition-transform transform hover:scale-105" title="Criar Natureza Inexistente">
            <i class="ph-bold ph-plus text-xl"></i>
        </button>
    @endif

    {{-- MODAL CADASTRAR NOVA NATUREZA --}}
    @if($modalNovaNaturezaAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200">
                <h3 class="text-base font-black text-gray-900 mb-4">Nova Natureza Financeira</h3>
                <input type="text" wire:model="novaNaturezaDescricao" class="w-full text-sm border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 mb-1" placeholder="Nome da despesa...">
                @error('novaNaturezaDescricao') <span class="text-red-500 text-[10px] font-bold block mb-3">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('modalNovaNaturezaAberto', false)" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200">Cancelar</button>
                    <button wire:click="salvarNovaNatureza" class="px-3 py-1.5 text-xs font-bold text-white bg-gray-800 hover:bg-black">Cadastrar</button>
                </div>
            </div>
        </div>
    @endif
</div>