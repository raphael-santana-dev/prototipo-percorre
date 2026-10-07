<div class="font-sans flex flex-col transition-all duration-300 bg-gray-50" 
     x-data="{ timelineAberta: false, modoExpandido: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val))"
     :class="modoExpandido ? 'h-[calc(100vh-113px)] -mt-6' : 'min-h-screen'">
    
    {{-- CSS Dinâmico: Quebra as amarras do app.blade.php quando Expandido --}}
    <style>
        body.modo-expandido main > div > div.max-w-7xl { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; }
        body.modo-expandido main { overflow: hidden !important; }
    </style>

    {{-- BARRA SUPERIOR (HEADER) - Z-Index 30 garante que fica abaixo dos dropdowns da Navbar --}}
    <div class="bg-white border-b border-gray-300 shadow-sm px-6 py-3 flex items-center justify-between sticky top-0 z-30 shrink-0">
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

            <button @click="modoExpandido = !modoExpandido" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm" :title="modoExpandido ? 'Voltar ao Normal' : 'Tela Cheia'">
                <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline"></span>
            </button>

            <button @click="timelineAberta = !timelineAberta" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm" :class="timelineAberta ? 'bg-gray-200' : ''">
                <i class="ph-bold ph-clock-counter-clockwise"></i> <span class="hidden sm:inline">Histórico</span>
            </button>

            @if(!$isLockedGlobal)
                <button wire:click="salvarOrcamento" class="btn btn--secondary btn--small !text-emerald-700 !border-emerald-300 hover:!bg-emerald-50 shadow-sm">
                    <i class="ph-bold ph-floppy-disk"></i> Guardar
                </button>
                <button wire:click="finalizarOrcamento" class="btn btn--primary btn--small shadow-sm" onclick="confirm('Submeter para aprovação da Diretoria?') || event.stopImmediatePropagation()">
                    <i class="ph-bold ph-paper-plane-tilt"></i> <span class="hidden sm:inline">Enviar</span>
                </button>
            @endif
        </div>
    </div>

    {{-- ÁREA DA PLANILHA EXCEL E SIDEBAR --}}
    <div class="flex flex-1 w-full overflow-hidden relative" :style="modoExpandido ? 'height: 100%;' : 'height: 75vh; min-height: 500px;'">
        
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative bg-gray-50 border-r border-gray-200" :class="timelineAberta ? 'pr-[340px]' : ''">
            
            <div class="p-3 bg-white border-b border-gray-200 shadow-sm flex items-center gap-3 shrink-0 z-10 relative">
                <label class="text-xs font-bold text-gray-700 uppercase whitespace-nowrap shrink-0">Justificativa Global:</label>
                <input type="text" wire:model="justificativaGeral" placeholder="Motivo ou observações gerais de todo o Centro de Custo..." class="w-full text-sm border-gray-300 rounded-md bg-gray-50 focus:ring-1 focus:ring-emerald-500 px-3 py-1.5" @if($isLockedGlobal) disabled @endif>
            </div>

            <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0">
                    <thead>
                        <tr>
                            {{-- Z-Index 25 para os cantos manterem-se acima das colunas e linhas --}}
                            <th class="w-[300px] min-w-[300px] sticky left-0 top-0 z-[25] bg-gray-200 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Natureza Financeira</th>
                            <th class="w-[200px] min-w-[200px] sticky left-[300px] top-0 z-[25] bg-gray-200 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Observações da Linha</th>
                            
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                {{-- Z-Index 20 para o cabeçalho superior rolar por cima do conteúdo normal --}}
                                <th class="w-[140px] sticky top-0 z-[20] bg-gray-100 p-2 text-[10px] font-black text-gray-700 uppercase tracking-wider text-right border-b border-r border-gray-300">
                                    {{ $mes }} {!! ($orcamento->ano < $anoSimulacao) ? '<i class="ph-bold ph-lock-key ml-1 text-gray-400"></i>' : '' !!}
                                </th>
                            @endforeach
                            
                            <th class="w-[120px] sticky top-0 z-[20] bg-emerald-100 p-2 text-[10px] font-black text-emerald-800 uppercase tracking-wider text-right border-b border-r border-gray-300">Total Linha</th>
                            <th class="w-[60px] sticky top-0 z-[20] bg-gray-100 p-2 text-center border-b border-gray-300"><i class="ph-bold ph-gear"></i></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @php $totalGeral = 0; @endphp
                        @foreach($itensOrcamento as $index => $item)
                            <tr class="group hover:bg-blue-50 transition-colors">
                                
                                {{-- Z-Index 15 para colunas fixas laterais, garantindo que não passam por cima do cabeçalho superior --}}
                                <td class="sticky left-0 z-[15] bg-white group-hover:bg-blue-50 border-b border-r border-gray-200 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    @if(!empty($item['id']))
                                        <div class="flex flex-col mt-1 w-[280px]">
                                            <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item['descricao'] }}">{{ $item['descricao'] }}</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] font-mono text-gray-500">{{ $item['natureza_codigo'] ?: 'N/D' }}</span>
                                                @if($item['status'] === 'Reprovado') <span class="text-[8px] font-bold bg-red-100 text-red-700 px-1 py-0.5 uppercase rounded shadow-sm">Reprovado</span>
                                                @elseif($item['status'] === 'Aprovado com ressalvas') <span class="text-[8px] font-bold bg-yellow-100 text-yellow-700 px-1 py-0.5 uppercase rounded shadow-sm">Ressalvas</span>
                                                @elseif($item['status'] === 'Aprovado') <span class="text-[8px] font-bold bg-emerald-100 text-emerald-700 px-1 py-0.5 uppercase rounded shadow-sm">Aprovado</span>
                                                @elseif($item['status'] === 'Corrigido') <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1 py-0.5 uppercase rounded shadow-sm">Re-Analisar</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <select wire:model="itensOrcamento.{{ $index }}.natureza_codigo" class="w-[280px] text-xs font-bold text-gray-800 border-gray-300 rounded shadow-sm focus:border-emerald-500 focus:ring-emerald-500 p-1.5 mt-1 bg-white">
                                            <option value="">Selecione Natureza...</option>
                                            @foreach($todasNaturezas as $nat)
                                                <option value="{{ $nat->codigo }}">{{ $nat->codigo }} - {{ $nat->descricao }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>

                                <td class="sticky left-[300px] z-[15] bg-white group-hover:bg-blue-50 border-b border-r border-gray-200 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <textarea wire:model="itensOrcamento.{{ $index }}.descricao" rows="2" class="w-full min-w-[180px] text-[11px] text-gray-600 border-gray-300 rounded shadow-sm focus:border-emerald-500 focus:ring-emerald-500 p-1.5 resize-none bg-white" placeholder="Anotações para a diretoria..." @if($isLockedGlobal) disabled @endif></textarea>
                                </td>

                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $isDisabled = $isLockedGlobal || ($orcamento->ano < $anoSimulacao);
                                        $valorMes = (float)($item["valor_$sigla"] ?? 0);
                                        $previsto = (float)($item["previsto_$sigla"] ?? 0);
                                        $totalLinha += $valorMes;
                                        
                                        $diferenca = $valorMes - $previsto;
                                        $percentual = $previsto > 0 ? ($diferenca / $previsto) * 100 : ($valorMes > 0 ? 100 : 0);
                                        $sinal = $diferenca > 0 ? '+' : '';
                                        $corDiff = $diferenca > 0 ? 'text-red-500' : ($diferenca < 0 ? 'text-emerald-500' : 'text-gray-400');
                                    @endphp
                                    <td class="border-b border-r border-gray-200 p-2 align-top {{ $isDisabled ? 'bg-gray-50' : 'bg-transparent' }} transition-colors relative z-0">
                                        @if($previsto > 0 && $previsto != $valorMes)
                                            <div class="absolute top-0.5 right-1 text-[8px] font-bold text-gray-400 select-none">B: {{ number_format($previsto, 0, '', '') }}</div>
                                        @endif
                                        
                                        <input type="number" step="0.01" wire:model.live.debounce.500ms="itensOrcamento.{{ $index }}.valor_{{ $sigla }}" class="w-full text-xs text-right font-bold text-gray-900 border-gray-300 rounded shadow-sm focus:border-emerald-500 focus:ring-emerald-500 p-1.5 {{ $isDisabled ? 'bg-gray-100' : 'bg-white' }}" @if($isDisabled) disabled @endif>
                                        
                                        <div class="mt-2 flex flex-col items-end justify-center text-[9px] font-medium leading-tight space-y-0.5">
                                            <span class="text-gray-500">Previsto: R$ {{ number_format($previsto, 2, ',', '.') }}</span>
                                            @if($diferenca != 0)
                                                <span class="{{ $corDiff }} font-bold bg-white px-1.5 py-0.5 rounded shadow-sm border border-gray-100 flex items-center justify-end w-full">
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
                                <td class="border-b border-r border-gray-200 bg-emerald-50/30 p-2 text-right text-xs font-black text-emerald-900 align-top pt-3 z-0">
                                    {{ number_format($totalLinha, 2, ',', '.') }}
                                </td>
                                <td class="border-b border-gray-200 p-1 text-center align-top pt-3 z-0">
                                    <div class="flex items-center justify-center gap-1 flex-col">
                                        @if(!empty($item['avaliacoes']))
                                            <button type="button" @click="timelineAberta = true" class="text-orange-500 hover:text-orange-700 p-1 bg-orange-50 hover:bg-orange-100 rounded transition" title="Ver feedback da Diretoria"><i class="ph-fill ph-warning-circle text-lg"></i></button>
                                        @endif
                                        @if(!$isLockedGlobal)
                                            <button type="button" wire:click="removerItem({{ $index }})" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1 rounded transition" title="Remover"><i class="ph-bold ph-trash text-lg"></i></button>
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
                        <tr>
                            <td colspan="2" class="border-t-2 border-r border-gray-400 p-2 sticky left-0 bottom-0 z-[25] bg-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest pl-1">Totais Calculados</span>
                                    @if(!$isLockedGlobal)
                                        <button wire:click="adicionarItem" class="text-[10px] font-bold uppercase bg-white border border-gray-300 px-3 py-1 hover:bg-gray-50 text-gray-700 shadow-sm rounded transition flex items-center gap-1"><i class="ph-bold ph-plus"></i> Nova Linha</button>
                                    @endif
                                </div>
                            </td>
                            @foreach($meses as $sigla)
                                <td class="border-t-2 border-r border-gray-400 bg-gray-200 p-2 text-right text-xs font-black text-gray-900 sticky bottom-0 z-[20]">{{ number_format($totaisMensais[$sigla], 2, ',', '.') }}</td>
                            @endforeach
                            <td class="border-t-2 border-r border-gray-400 bg-emerald-200 p-2 text-right text-sm font-black text-emerald-900 sticky bottom-0 z-[20]">{{ number_format($totalGeral, 2, ',', '.') }}</td>
                            <td class="border-t-2 border-gray-400 bg-gray-200 sticky bottom-0 z-[20]"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BARRA LATERAL: TIMELINE DE APROVAÇÕES (Z-Index 35 desliza por cima da planilha mas abaixo da Navbar) --}}
        <div x-show="timelineAberta" x-transition x-cloak class="w-[340px] bg-white border-l border-gray-300 shadow-[rgba(0,0,0,0.15)_0px_0px_20px] flex flex-col absolute right-0 top-0 bottom-0 z-[35]">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Histórico / Logs</h3>
                <button @click="timelineAberta = false" class="text-gray-400 hover:text-gray-700 transition" title="Fechar Histórico"><i class="ph-bold ph-x text-lg"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar relative">
                <div class="absolute left-7 top-4 bottom-4 w-px bg-gray-200"></div>
                @php
                    $todasAvaliacoes = collect();
                    foreach($itensOrcamento as $itemLinha) {
                        if(!empty($itemLinha['avaliacoes'])) {
                            foreach($itemLinha['avaliacoes'] as $aval) {
                                $avalArray = is_array($aval) ? $aval : $aval->toArray();
                                $avalArray['natureza_codigo'] = $itemLinha['natureza_codigo'];
                                $avalArray['natureza_descricao'] = $itemLinha['descricao'];
                                $todasAvaliacoes->push($avalArray);
                            }
                        }
                    }
                    $todasAvaliacoes = $todasAvaliacoes->sortByDesc('created_at');
                @endphp

                @forelse($todasAvaliacoes as $aval)
                    @php $cor = match($aval['status_aplicado']) { 'Aprovado' => 'bg-emerald-500', 'Aprovado com ressalvas' => 'bg-yellow-500', 'Reprovado' => 'bg-red-500', default => 'bg-gray-500' }; @endphp
                    <div class="relative pl-7 mb-6">
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
                        <p class="text-xs font-bold text-gray-400 uppercase">Nenhum log registado.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- BOTÃO FLUTUANTE (NOVA NATUREZA) --}}
    @if(!$isLockedGlobal)
        <button wire:click="abrirModalNovaNatureza" class="fixed bottom-6 right-6 z-[35] flex items-center justify-center w-12 h-12 rounded-full shadow-lg bg-gray-800 hover:bg-black text-white transition-transform transform hover:scale-105" title="Criar Natureza Inexistente">
            <i class="ph-bold ph-plus text-xl"></i>
        </button>
    @endif

    {{-- MODAL CADASTRAR NOVA NATUREZA --}}
    @if($modalNovaNaturezaAberto)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200">
                <h3 class="text-base font-black text-gray-900 mb-4">Nova Natureza Financeira</h3>
                <input type="text" wire:model="novaNaturezaDescricao" class="w-full text-sm border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 mb-1 rounded-md" placeholder="Nome da despesa...">
                @error('novaNaturezaDescricao') <span class="text-red-500 text-[10px] font-bold block mb-3">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('modalNovaNaturezaAberto', false)" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded">Cancelar</button>
                    <button wire:click="salvarNovaNatureza" class="px-3 py-1.5 text-xs font-bold text-white bg-gray-800 hover:bg-black rounded">Cadastrar</button>
                </div>
            </div>
        </div>
    @endif
</div>