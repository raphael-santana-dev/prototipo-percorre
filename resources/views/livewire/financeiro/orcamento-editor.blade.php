<div class="font-sans flex flex-col transition-all duration-300 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden" 
     x-data="{ modoExpandido: false, navbarOculta: false }" 
     x-init="$watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val)); $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val))"
     @item-adicionado.window="setTimeout(() => { if($refs.tableContainer) $refs.tableContainer.scrollTo({top: $refs.tableContainer.scrollHeight, behavior: 'smooth'}) }, 100)"
     @comentario-adicionado.window="setTimeout(() => { if($refs.chatContainer) $refs.chatContainer.scrollTo({top: $refs.chatContainer.scrollHeight, behavior: 'smooth'}) }, 100)"
     :class="modoExpandido ? 'h-full border-none rounded-none' : 'min-h-[600px] h-[calc(100vh-180px)]'">

    <!-- Inclusão do jQuery e Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        /* ESTILOS BLINDADOS PARA O SELECT2 E CORREÇÃO DE LAYOUT */
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single {
            background-color: #ffffff !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; height: 34px !important; display: flex !important; align-items: center !important; outline: none !important; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important; font-size: 11px !important; font-weight: 700 !important; line-height: 32px !important; padding-left: 8px !important; padding-right: 24px !important; text-align: left !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; width: 100% !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 32px !important; position: absolute !important; top: 1px !important; right: 1px !important; width: 20px !important;
        }
        .select2-custom-dropdown {
            min-width: 380px !important; width: auto !important; border: 1px solid #d1d5db !important; border-radius: 0.5rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important; background-color: #ffffff !important; z-index: 99999 !important; overflow: hidden !important;
        }
        .select2-results__option {
            padding: 8px 12px !important; font-size: 11px !important; font-weight: 600 !important; color: #374151 !important; white-space: nowrap !important; 
        }
        .select2-results__option--highlighted[aria-selected] { background-color: #10b981 !important; color: #ffffff !important; }
        .select2-search--dropdown { padding: 8px !important; background-color: #f9fafb !important; border-bottom: 1px solid #f3f4f6 !important; }
        .select2-search__field { border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; padding: 6px 12px !important; font-size: 12px !important; outline: none !important; width: 100% !important; box-sizing: border-box !important; }
        .select2-search__field:focus { border-color: #111827 !important; box-shadow: 0 0 0 1px #111827 !important; }

        body.modo-expandido main > div > div.max-w-7xl { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
        .cursor-unlock { cursor: cell !important; }
    </style>

    {{-- BARRA SUPERIOR (HEADER FIXO) --}}
    <div class="bg-white border-b border-gray-300 px-6 py-3 flex items-center justify-between z-[60] shrink-0">
        <div class="flex items-center gap-4">
            <button wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition"><i class="ph-bold ph-arrow-left"></i></button>
            <div class="border-l border-gray-300 pl-4">
                <h1 class="text-lg font-black text-gray-900 flex items-center gap-2 tracking-tight">
                    <i class="ph-fill ph-microsoft-excel-logo text-emerald-600"></i> Planilha de Orçamento
                </h1>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-widest mt-0.5">
                    C. Custo: <span class="text-purpura-600 font-bold">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'S/ Vínculo' }} ({{ $orcamento->ccusto }})</span> | Ano: {{ $orcamento->ano }}
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
                <span class="text-sm font-black uppercase {{ $statusColor }} flex flex-col items-end leading-tight">
                    {{ $orcamento->status }}
                    @if($orcamento->prazo_edicao && !$isLockedGlobal)
                        <span class="text-[9px] font-bold text-orange-600">Reaberto até {{ \Carbon\Carbon::parse($orcamento->prazo_edicao)->format('d/m/y H:i') }}</span>
                    @endif
                </span>
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

            @if(!$isLockedGlobal)
                <button wire:click="salvarOrcamento" wire:loading.attr="disabled" wire:target="salvarOrcamento" class="btn btn--secondary btn--small !text-emerald-700 !border-emerald-300 hover:!bg-emerald-50 disabled:opacity-50">
                    <i class="ph-bold ph-floppy-disk" wire:loading.class="hidden" wire:target="salvarOrcamento"></i>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="salvarOrcamento"></i>
                    Salvar
                </button>
                <button wire:click="finalizarOrcamento" wire:loading.attr="disabled" wire:target="finalizarOrcamento" class="btn btn--primary btn--small shadow-sm disabled:opacity-50" onclick="confirm('Submeter para aprovação da Diretoria?') || event.stopImmediatePropagation()">
                    <i class="ph-bold ph-paper-plane-tilt" wire:loading.class="hidden" wire:target="finalizarOrcamento"></i>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="finalizarOrcamento"></i>
                    <span class="hidden sm:inline">Enviar</span>
                </button>
            @else
                @if($orcamento->reabertura_solicitada)
                    <span class="btn btn--small bg-orange-100 text-orange-700 border border-orange-200 cursor-not-allowed opacity-80" title="Aguardando a resposta da Diretoria">
                        <i class="ph-bold ph-hourglass-high animate-pulse"></i> Reabertura Solicitada
                    </span>
                @else
                    <button wire:click="$set('modalReaberturaAberto', true)" class="btn btn--secondary btn--small !bg-orange-50 !text-orange-700 !border-orange-300 hover:!bg-orange-100 shadow-sm" title="Pedir autorização para editar o orçamento fechado">
                        <i class="ph-bold ph-lock-key-open"></i> Solicitar Reabertura
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- ÁREA DINÂMICA: PLANILHA --}}
    <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50">
        <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300 relative">
            
            {{-- BARRA DE AÇÕES DA PLANILHA (Excluir Lote e Comentários) --}}
            <div class="p-3 bg-white border-b border-gray-300 shadow-sm flex items-center justify-between shrink-0 z-10 relative" x-data="{ temSelecao: @entangle('itensSelecionados').live }">
                <div class="flex items-center gap-3">
                    <template x-if="temSelecao && Object.keys(temSelecao).length > 0">
                        <button wire:click="removerItensSelecionados" wire:loading.attr="disabled" wire:target="removerItensSelecionados" class="btn btn--small !bg-red-50 !text-red-600 border border-red-200 hover:!bg-red-100 shadow-sm flex items-center gap-2 transition disabled:opacity-50 cursor-pointer" onclick="confirm('Tem certeza que deseja remover os itens selecionados?') || event.stopImmediatePropagation()">
                            <i class="ph-bold ph-trash" wire:loading.class="hidden" wire:target="removerItensSelecionados"></i>
                            <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="removerItensSelecionados"></i>
                            Remover Selecionados (<span x-text="Object.keys(temSelecao).length"></span>)
                        </button>
                    </template>
                </div>
                
                <button @click="$wire.set('drawerComentariosAberto', true)" class="btn btn--secondary btn--small !text-gray-700 !border-gray-300 hover:!bg-gray-100 shadow-sm flex items-center gap-2">
                    <i class="ph-bold ph-chat-centered-text"></i> Histórico / Comentários
                </button>
            </div>

            <div x-ref="tableContainer" class="flex-1 overflow-auto custom-scrollbar relative bg-white">
                <table class="w-full text-left whitespace-nowrap min-w-[2100px] border-separate border-spacing-0 border-t border-l border-gray-300">
                    <thead>
                        <tr>
                            <th class="w-[60px] min-w-[60px] sticky left-0 top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] text-center">ID</th>
                            <th class="w-[100px] min-w-[100px] sticky left-[60px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] text-center">Código</th>
                            <th class="w-[250px] min-w-[250px] sticky left-[160px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Nome da Natureza</th>
                            <th class="w-[200px] min-w-[200px] sticky left-[410px] top-0 z-[50] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider border-b border-r border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Observações</th>
                            
                            @php $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; @endphp
                            @foreach($meses as $mes)
                                <th class="w-[140px] sticky top-0 z-[40] bg-gray-100 p-3 text-[10px] font-black text-gray-700 uppercase tracking-wider text-right border-b border-r border-gray-300">
                                    {{ $mes }} {!! ($orcamento->ano < $anoSimulacao) ? '<i class="ph-bold ph-lock-key ml-1 text-gray-400"></i>' : '' !!}
                                </th>
                            @endforeach
                            
                            <th class="w-[120px] sticky top-0 z-[40] bg-emerald-100 p-3 text-[10px] font-black text-emerald-800 uppercase tracking-wider text-right border-b border-r border-gray-300">Total Linha</th>
                            <th class="w-[60px] sticky top-0 z-[40] bg-gray-100 p-3 text-center border-b border-r border-gray-300"><i class="ph-bold ph-gear"></i></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @php $totalGeral = 0; @endphp
                        @foreach($itensOrcamento as $index => $item)
                            @php 
                                $statusItem = $item['status'] ?? 'Criado';
                                $itemID = $item['id'] ?? null;
                                $tempID = $item['temp_id'] ?? ($itemID ? 'db_'.$itemID : 'novo_'.$index);
                                $itemNatCod = $item['natureza_codigo'] ?? '';
                                $itemNatNome = $item['natureza_nome'] ?? 'Aguardando seleção...';
                                
                                $isLinhaAprovada = ($statusItem === 'Aprovado');
                                $eventoDuploClique = ($isLinhaAprovada && !$isLockedGlobal) ? "wire:dblclick=solicitarDesbloqueio($index)" : "";
                                $classeCursor = ($isLinhaAprovada && !$isLockedGlobal) ? "cursor-unlock" : "";
                                $tooltipMsg = ($isLinhaAprovada && !$isLockedGlobal) ? "Duplo clique para solicitar desbloqueio desta linha" : "";
                                
                                $bgRowSolid = match($statusItem) {
                                    'Aprovado' => 'bg-[#eefcf5]',
                                    'Reprovado' => 'bg-[#fff5f5]',
                                    'Aprovado com ressalvas' => 'bg-[#fffbeb]',
                                    default => 'bg-white'
                                };
                            @endphp
                            
                            <tr wire:key="row-orcamento-{{ $tempID }}" class="group transition-colors {{ $bgRowSolid }} hover:bg-blue-50 {{ $classeCursor }}" {!! $eventoDuploClique !!} title="{{ $tooltipMsg }}">                                
                                <td class="sticky left-0 z-[30] bg-white group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 text-center align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex items-center justify-center gap-2 mt-1 px-1">
                                        @if(in_array($statusItem, ['Criado', 'Corrigido']) && !$isLockedGlobal)
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $tempID }}" class="rounded border-gray-400 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                        @endif
                                        <span class="text-xs font-bold text-gray-600">{{ $itemID ?? 'NOVO' }}</span>
                                    </div>
                                </td>

                                <td class="sticky left-[60px] z-[30] bg-white group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 text-center align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    @if(empty($itemID))
                                        <div wire:ignore wire:key="select-wrapper-{{ $tempID }}" class="w-full">
                                            <select x-data x-init="
                                                        $($el).select2({ 
                                                            placeholder: 'Pesquisar...', 
                                                            width: '100%',
                                                            dropdownCssClass: 'select2-custom-dropdown'
                                                        });
                                                        $($el).on('change', function() {
                                                            $wire.atualizarNatureza('{{ $tempID }}', $(this).val());
                                                        });
                                                        if (!$($el).val()) {
                                                            setTimeout(() => $($el).select2('open'), 100);
                                                        }
                                                    "
                                                    class="w-full text-[10px] font-bold text-gray-800 border-gray-300 rounded shadow-sm focus:border-emerald-500 bg-white">
                                                <option value="">Pesquisar...</option>
                                                @foreach($todasNaturezas as $nat)
                                                    <option value="{{ $nat->codigo }}" @if($itemNatCod == $nat->codigo) selected @endif>{{ $nat->codigo }} - {{ $nat->descricao }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @else
                                        <span class="text-xs font-mono font-bold text-gray-800 mt-1 block">{{ $itemNatCod ?: 'N/D' }}</span>
                                    @endif
                                </td>

                                <td class="sticky left-[160px] z-[30] bg-white group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <div class="flex flex-col mt-1 w-full overflow-hidden">
                                        <span class="text-xs font-bold text-gray-900 truncate" title="{{ $itemNatNome }}">{{ $itemNatNome ?: 'Aguardando seleção...' }}</span>
                                        <div class="flex items-center gap-1 mt-1 flex-wrap">
                                            @if($statusItem === 'Reprovado') <span class="text-[8px] font-bold bg-red-100 text-red-700 px-1.5 py-0.5 uppercase rounded shadow-sm">Reprovado</span>
                                            @elseif($statusItem === 'Aprovado com ressalvas') <span class="text-[8px] font-bold bg-yellow-100 text-yellow-700 px-1.5 py-0.5 uppercase rounded shadow-sm">Ressalvas</span>
                                            @elseif($statusItem === 'Aprovado') <span class="text-[8px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 uppercase rounded shadow-sm" title="Aprovado pela Diretoria. Duplo clique na linha para desbloquear."><i class="ph-bold ph-lock-key mr-0.5"></i> Aprovado</span>
                                            @elseif($statusItem === 'Corrigido') <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 uppercase flex items-center gap-1 rounded shadow-sm"><i class="ph-bold ph-arrows-clockwise"></i> Corrigido</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="sticky left-[410px] z-[30] bg-white group-hover:bg-blue-50 border-b border-r border-gray-300 p-2 align-top transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <textarea wire:model="itensOrcamento.{{ $index }}.descricao" rows="2" class="w-full text-[11px] text-gray-600 border-gray-300 rounded shadow-sm focus:border-gray-500 focus:ring-gray-500 p-1.5 resize-none bg-white {{ $classeCursor }}" placeholder="Anotações da linha..." @if($isLockedGlobal || $isLinhaAprovada) disabled @endif></textarea>
                                </td>

                                @php $totalLinha = 0; @endphp
                                @foreach($meses as $sigla)
                                    @php
                                        $isDisabled = $isLockedGlobal || ($orcamento->ano < $anoSimulacao) || $isLinhaAprovada;
                                        $valorMes = (float)($item["valor_$sigla"] ?? 0);
                                        $previsto = (float)($item["previsto_$sigla"] ?? 0);
                                        $totalLinha += $valorMes;
                                        
                                        $diferenca = $valorMes - $previsto;
                                        $percentual = $previsto > 0 ? ($diferenca / $previsto) * 100 : ($valorMes > 0 ? 100 : 0);
                                        $sinal = $diferenca > 0 ? '+' : '';
                                        $corDiff = $diferenca > 0 ? 'text-red-500' : ($diferenca < 0 ? 'text-emerald-500' : 'text-gray-400');
                                    @endphp
                                    <td class="border-b border-r border-gray-200 p-2 align-top {{ $isDisabled ? 'bg-gray-50' : 'bg-transparent' }} transition-colors z-0 relative">
                                        @if($previsto > 0 && $previsto != $valorMes)
                                            <div class="absolute -top-1.5 right-1 bg-white px-1 text-[8px] font-bold text-gray-400 rounded-sm">Base: {{ number_format($previsto, 0, '', '') }}</div>
                                        @endif
                                        
                                        <input type="number" step="0.01" wire:model.live.debounce.500ms="itensOrcamento.{{ $index }}.valor_{{ $sigla }}" class="w-full text-xs text-right font-bold text-gray-900 border-gray-300 rounded shadow-sm focus:border-gray-500 focus:ring-gray-500 p-1.5 {{ $isDisabled ? 'bg-gray-100' : 'bg-white' }} {{ $classeCursor }}" @if($isDisabled) disabled @endif>
                                        
                                        <div class="mt-2 flex flex-col items-end justify-center text-[9px] font-medium leading-tight space-y-0.5 px-1">
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
                                            <button type="button" @click="$wire.set('drawerComentariosAberto', true)" class="text-gray-500 hover:text-gray-900 p-1 bg-gray-100 hover:bg-gray-200 rounded transition" title="Ver feedback da Diretoria"><i class="ph-fill ph-chat-text text-lg"></i></button>
                                        @endif
                                        @if(!$isLockedGlobal && !$isLinhaAprovada)
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
                            <td colspan="4" class="border-t-2 border-b border-r border-gray-400 p-3 sticky left-0 bottom-0 z-[50] bg-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest pl-1">Totais Calculados</span>
                                    @if(!$isLockedGlobal)
                                        <button wire:click="adicionarItem" class="text-[10px] font-bold uppercase bg-white border border-gray-300 px-3 py-1 hover:bg-gray-50 text-gray-700 shadow-sm rounded transition flex items-center gap-1"><i class="ph-bold ph-plus"></i> Nova Linha</button>
                                    @endif
                                </div>
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

    {{-- MODAIS --}}
    @if($modalNovaNaturezaAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200">
                <h3 class="text-base font-black text-gray-900 mb-4">Nova Natureza Financeira</h3>
                <input type="text" wire:model="novaNaturezaDescricao" class="w-full text-sm border-gray-300 focus:border-gray-900 focus:ring-gray-900 mb-1 rounded-md" placeholder="Nome da despesa...">
                @error('novaNaturezaDescricao') <span class="text-red-500 text-[10px] font-bold block mb-3">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('modalNovaNaturezaAberto', false)" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition">Cancelar</button>
                    <button wire:click="salvarNovaNatureza" class="px-3 py-1.5 text-xs font-bold text-white bg-gray-900 hover:bg-black rounded shadow-sm transition">Salvar</button>
                </div>
            </div>
        </div>
    @endif

    @if($modalDesbloqueioAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-black text-gray-900 mb-2 flex items-center gap-2"><i class="ph-fill ph-lock-key-open text-blue-500"></i> Desbloquear Edição</h3>
                <p class="text-xs text-gray-500 mb-4">Esta linha já foi aprovada. Para editá-la, pode informar o motivo do desbloqueio (opcional). O status da linha voltará para "Corrigido".</p>
                <textarea wire:model="justificativaDesbloqueio" rows="3" class="w-full text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md" placeholder="Motivo da alteração (Opcional)..."></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('modalDesbloqueioAberto', false)" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded">Cancelar</button>
                    <button wire:click="confirmarDesbloqueio" class="px-3 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded shadow-sm">Confirmar e Desbloquear</button>
                </div>
            </div>
        </div>
    @endif

    @if($modalReaberturaAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-black text-gray-900 mb-2 flex items-center gap-2"><i class="ph-fill ph-warning-circle text-orange-500"></i> Solicitar Reabertura</h3>
                <p class="text-xs text-gray-500 mb-4">Este orçamento encontra-se bloqueado pela Diretoria. Indique o motivo para solicitar a reabertura dos campos para nova edição.</p>
                <textarea wire:model="motivoReabertura" rows="3" class="w-full text-sm border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md" placeholder="Motivo obrigatório..."></textarea>
                @error('motivoReabertura') <span class="text-red-500 text-[10px] font-bold block mt-1">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('modalReaberturaAberto', false)" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded">Cancelar</button>
                    <button wire:click="solicitarReabertura" class="px-3 py-1.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded shadow-sm">Enviar Pedido</button>
                </div>
            </div>
        </div>
    @endif

    @if(!$isLockedGlobal)
        <button wire:click="salvarOrcamento" wire:loading.attr="disabled" wire:target="salvarOrcamento" class="fixed bottom-6 right-6 z-[40] flex items-center justify-center w-14 h-14 rounded-full shadow-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-transform transform hover:scale-105 disabled:opacity-50 disabled:scale-100" title="Salvar Orçamento">
            <i class="ph-bold ph-floppy-disk text-2xl" wire:loading.class="hidden" wire:target="salvarOrcamento"></i>
            <i class="ph-bold ph-spinner animate-spin text-2xl" wire:loading wire:target="salvarOrcamento"></i>
        </button>
    @endif

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
                                    foreach($itensOrcamento as $itemLinha) {
                                        if(!empty($itemLinha['avaliacoes'])) {
                                            foreach($itemLinha['avaliacoes'] as $aval) {
                                                $avalArray = is_array($aval) ? $aval : $aval->toArray();
                                                $avalArray['natureza_codigo'] = $itemLinha['natureza_codigo'] ?? '';
                                                $avalArray['natureza_descricao'] = $itemLinha['natureza_nome'] ?? '';
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
                                        $nomeDisplay = $isMeu ? 'Você' : ($aval['user_nome'] ?? 'Usuário');
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
</div>