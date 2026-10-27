<div class="font-sans flex flex-col bg-white border border-gray-200 rounded-lg overflow-hidden text-gray-800 w-full"
     x-data="{ modoExpandido: true, navbarOculta: true }"
     x-init="
        document.body.classList.toggle('modo-expandido', modoExpandido);
        document.body.classList.toggle('navbar-oculta', navbarOculta);
        $watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val));
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
     "
     :class="modoExpandido ? 'flex-1 h-full border-0 rounded-none' : 'min-h-[720px] h-[calc(100vh-140px)]'">

    <style>
        body.modo-expandido main { overflow: hidden !important; display: flex !important; flex-direction: column !important; }
        body.modo-expandido main > div { padding-top: 0 !important; padding-bottom: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; height: 100% !important; min-height: 0 !important; }
        body.modo-expandido main > div > div { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; height: 100% !important; min-height: 0 !important; }
        body.navbar-oculta .js-topnav, body.navbar-oculta header, body.navbar-oculta .js-sidebar { display: none !important; }
    </style>

    @php
        $mesesConfig = [
            'jan' => 'Janeiro', 'fev' => 'Fevereiro', 'mar' => 'Março', 'abr' => 'Abril',
            'mai' => 'Maio', 'jun' => 'Junho', 'jul' => 'Julho', 'ago' => 'Agosto',
            'set' => 'Setembro', 'out' => 'Outubro', 'nov' => 'Novembro', 'dez' => 'Dezembro'
        ];
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : ($orcamento->descricao_despesa ?: 'SEM VÍNCULO');
        $itemAtual = $itensOrdenados[$itemSelecionadoIndex] ?? $itensOrdenados->first();

        $corStatusOrcamento = match($orcamento->status) {
            'Finalizado – Aguardando Aprovação', 'Finalizado' => 'bg-amber-50 text-amber-800 border-amber-200',
            'Aprovação – Aguardando Revisão pelo Responsável', 'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
            'Finalizado – Aprovado', 'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Finalizado – Integrado ao Protheus' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-gray-100 text-gray-700 border-gray-300'
        };
    @endphp

    {{-- 1. BARRA SUPERIOR --}}
    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <button type="button" wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-100 transition">
                <i class="ph-bold ph-arrow-left"></i>
            </button>
            <div>
                <h1 class="text-base font-semibold text-gray-900">Orçamento — Análise e Aprovação</h1>
                <p class="text-xs text-gray-500">
                    Analise cada despesa (<strong>Aprovar</strong>, <strong>Revisar</strong> ou <strong>Não Aprovar</strong>) e clique em Salvar Análise.
                    @if($totalAprovadores > 1)
                        <span class="ml-1 px-2 py-0.5 bg-gray-200 text-gray-700 rounded text-[10px]">Fluxo com {{ $totalAprovadores }} aprovadores</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="modoExpandido = !modoExpandido; if (!modoExpandido) navbarOculta = false;" class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5">
                <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                <span x-text="modoExpandido ? 'Compactar' : 'Tela Cheia'"></span>
            </button>

            <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5">
                <i class="ph-bold" :class="navbarOculta ? 'ph-eye' : 'ph-eye-slash'"></i>
                <span x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
            </button>

            <button type="button" wire:click="abrirComentariosGerais" class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5">
                <i class="ph-bold ph-clock-counter-clockwise"></i> Histórico Geral
            </button>

            @if(!in_array($orcamento->status, ['Finalizado – Aprovado', 'Finalizado – Integrado ao Protheus'], true))
                <button type="button" wire:click="salvarAnaliseAprovador" wire:loading.attr="disabled" class="px-4 py-1.5 text-xs font-medium rounded bg-gray-900 hover:bg-black text-white transition flex items-center gap-1.5">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar Análise
                </button>
            @endif
        </div>
    </div>

    {{-- ALERTA DE REABERTURA --}}
    @if($orcamento->reabertura_solicitada)
        <div class="bg-amber-50 border-b border-amber-200 px-5 py-2.5 flex justify-between items-center shrink-0">
            <div class="text-xs text-amber-800">
                <strong>Pedido de Reabertura Pendente:</strong> "{{ $orcamento->reabertura_motivo }}"
            </div>
            <button type="button" wire:click="$set('modalAprovarReaberturaAberto', true)" class="px-3 py-1 bg-amber-600 text-white text-xs rounded">Analisar Pedido</button>
        </div>
    @endif

    {{-- 2. CABEÇALHO DO ORÇAMENTO --}}
    <div class="px-5 py-3 bg-white border-b border-gray-200 grid grid-cols-1 sm:grid-cols-12 gap-3 shrink-0">
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-1">Ano <span class="text-red-500">*</span></label>
            <input type="text" value="{{ $orcamento->ano }}" readonly class="w-full py-1.5 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800 font-mono">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-1">C. Custo <span class="text-red-500">*</span></label>
            <input type="text" value="{{ $orcamento->ccusto }}" readonly class="w-full py-1.5 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800 font-mono">
        </div>
        <div class="sm:col-span-5">
            <label class="block text-[11px] font-medium text-gray-600 mb-1">Nome / Centro de Custo</label>
            <input type="text" value="{{ $ccNome }}" readonly class="w-full py-1.5 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-[11px] font-medium text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
            <div class="w-full py-1.5 px-2.5 text-xs border rounded flex items-center font-medium truncate {{ $corStatusOrcamento }}">
                {{ $orcamento->status }}
            </div>
        </div>
    </div>

    {{-- BARRA DE AÇÕES EM GRUPO (LOTE) --}}
    @if(count($itensSelecionados) > 0 && !in_array($orcamento->status, ['Finalizado – Aprovado', 'Finalizado – Integrado ao Protheus'], true))
        <div class="px-5 py-2 bg-gray-900 text-white flex flex-wrap items-center justify-between gap-2 shrink-0">
            <span class="text-xs font-medium">{{ count($itensSelecionados) }} despesa(s) selecionada(s)</span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="aplicarDecisaoLote('Aprovada')" class="px-3 py-1 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded transition">
                    Aprovar Selecionadas
                </button>
                <button type="button" wire:click="aplicarDecisaoLote('Aguardando Revisão pelo Responsável')" class="px-3 py-1 text-xs font-medium bg-amber-600 hover:bg-amber-700 text-white rounded transition">
                    Solicitar Revisão nas Selecionadas
                </button>
                <button type="button" wire:click="aplicarDecisaoLote('Não Aprovada')" class="px-3 py-1 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded transition">
                    Não Aprovar Selecionadas
                </button>
            </div>
        </div>
    @endif

    {{-- 3. ÁREA CENTRAL DIVIDIDA: ESQUERDA (DESPESAS) | DIREITA (MESES DA DESPESA SELECIONADA) --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 overflow-hidden min-h-0">
        
        {{-- COLUNA ESQUERDA (8 COLUNAS): DESPESAS --}}
        <div class="lg:col-span-8 min-w-0 flex flex-col overflow-hidden bg-white min-h-0">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0">
                <span class="text-xs font-semibold text-gray-800">Despesas ({{ $itensOrdenados->count() }})</span>
                <span class="text-[11px] text-gray-500">
                    Analisadas por você: {{ count($decisoesAprovador) }} / {{ $itensOrdenados->count() }}
                </span>
            </div>

            <div class="flex-1 overflow-x-auto overflow-y-auto custom-scrollbar min-h-0">
                <table class="w-full min-w-[1240px] text-left border-collapse text-xs whitespace-nowrap">
                    <thead class="bg-gray-100 border-b border-gray-200 text-gray-600 sticky top-0 z-10">
                        <tr>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium w-20 min-w-[80px]">
                                <div class="flex items-center gap-1.5">
                                    <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                    <span>ID Desp</span>
                                </div>
                            </th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium min-w-[200px]">Desc Desp</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium text-center min-w-[90px]">Comentários</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium text-center min-w-[100px]">Motivo Revisão</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[100px]">Natureza</th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium min-w-[190px]">Descrição Natureza</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[140px]">Cenário</th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium text-right min-w-[120px]">Total Item</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium text-center min-w-[150px]">Status Atual</th>
                            <th class="py-2 px-3 font-medium text-center min-w-[240px]">Análise (Aprovar / Revisar / Não Aprovar)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($itensOrdenados as $index => $item)
                            @php
                                $isSelecionado = ($itemAtual && $itemAtual->id === $item->id);
                                $minhaDecisao = $decisoesAprovador[$item->id]['status'] ?? null;
                                $idDespFmt = $item->id_despesa ?: str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

                                $avaliacoesCollection = collect($item->avaliacoes ?? []);
                                $qtdComentarios = $avaliacoesCollection->where('tipo_evento', '!=', 'motivo_revisao')->count();
                                $qtdMotivosRevisao = $avaliacoesCollection->where('tipo_evento', 'motivo_revisao')->count();

                                $badgeStatusBanco = match($item->status) {
                                    'Aprovada', 'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Aprovada Parcialmente' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'Não Aprovada', 'Não Aprovado', 'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
                                    'Aguardando Revisão pelo Responsável', 'Reprovado parcialmente' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'Aprovação Pendente' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    default => 'bg-gray-100 text-gray-600 border-gray-200',
                                };
                            @endphp

                            <tr wire:key="aprov-row-{{ $item->id }}"
                                wire:click="selecionarItem({{ $index }})"
                                class="cursor-pointer transition-colors {{ $isSelecionado ? 'bg-sky-100/80 text-gray-900' : 'hover:bg-gray-50' }}">
                                
                                <td class="py-2 px-2.5 border-r border-gray-200 font-mono text-gray-700 min-w-[80px] align-middle" @click.stop>
                                    <div class="flex items-center gap-1.5">
                                        @if(!in_array($item->status, ['Aprovada', 'Aprovado'], true))
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ (string) $item->id }}" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                        @endif
                                        <span wire:click="selecionarItem({{ $index }})">{{ $idDespFmt }}</span>
                                    </div>
                                </td>

                                <td class="py-2 px-3 border-r border-gray-200 min-w-[200px] truncate max-w-[240px] align-middle" title="{{ $item->descricao }}">
                                    {{ $item->descricao ?: '-' }}
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 text-center min-w-[90px] align-middle" @click.stop>
                                    <button type="button"
                                            wire:click="abrirComentariosDoItem({{ $index }}, {{ $item->id }})"
                                            class="px-2 py-1 text-[11px] rounded border {{ $qtdComentarios > 0 ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                        Memo {{ $qtdComentarios > 0 ? "({$qtdComentarios})" : '' }}
                                    </button>
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 text-center min-w-[100px] align-middle" @click.stop>
                                    <button type="button"
                                            wire:click="abrirHistoricoMotivoRevisao({{ $index }}, {{ $item->id }})"
                                            class="px-2 py-1 text-[11px] rounded border {{ ($qtdMotivosRevisao > 0 || $minhaDecisao === 'Aguardando Revisão pelo Responsável') ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                        Motivo {{ $qtdMotivosRevisao > 0 ? "({$qtdMotivosRevisao})" : '' }}
                                    </button>
                                </td>

                                <td class="py-2 px-2.5 border-r border-gray-200 font-mono min-w-[100px] align-middle">
                                    {{ $item->natureza_codigo ?: '-' }}
                                </td>

                                <td class="py-2 px-3 border-r border-gray-200 min-w-[190px] truncate max-w-[230px] align-middle" title="{{ $item->natureza ? $item->natureza->descricao : '-' }}">
                                    {{ $item->natureza ? $item->natureza->descricao : '-' }}
                                </td>

                                <td class="py-2 px-2.5 border-r border-gray-200 text-gray-700 min-w-[140px] align-middle">
                                    {{ $cenariosDisponiveis[$item->cenario ?? '1'] ?? '1 - Conservador' }}
                                </td>

                                <td class="py-2 px-3 border-r border-gray-200 text-right font-mono text-gray-900 min-w-[120px] align-middle">
                                    {{ number_format($item->valor_total, 2, ',', '.') }}
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 text-center min-w-[150px] align-middle">
                                    <span class="inline-block px-2 py-0.5 text-[10px] rounded border {{ $badgeStatusBanco }}">
                                        {{ $item->status }}
                                    </span>
                                </td>

                                <td class="py-1.5 px-2 text-center min-w-[240px] align-middle" @click.stop>
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button"
                                                wire:click="definirStatusItem({{ $item->id }}, 'Aprovada')"
                                                class="px-2.5 py-1 text-[11px] rounded border transition {{ in_array($minhaDecisao, ['Aprovada', 'Aprovado'], true) ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-emerald-300 hover:bg-emerald-50' }}">
                                            Aprovar
                                        </button>

                                        <button type="button"
                                                wire:click="definirStatusItem({{ $item->id }}, 'Aguardando Revisão pelo Responsável')"
                                                class="px-2.5 py-1 text-[11px] rounded border transition {{ $minhaDecisao === 'Aguardando Revisão pelo Responsável' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-50' }}">
                                            Revisar
                                        </button>

                                        <button type="button"
                                                wire:click="definirStatusItem({{ $item->id }}, 'Não Aprovada')"
                                                class="px-2.5 py-1 text-[11px] rounded border transition {{ in_array($minhaDecisao, ['Não Aprovada', 'Não Aprovado'], true) ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-700 border-red-300 hover:bg-red-50' }}">
                                            Não Aprovar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- COLUNA DIREITA (4 COLUNAS): DESPESA SELECIONADA --}}
        <div class="lg:col-span-4 min-w-0 flex flex-col overflow-hidden bg-white min-h-0"
             wire:key="aprov-painel-direito-{{ $itemAtual->id ?? 'vazio' }}">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0">
                <span class="text-xs font-semibold text-gray-800">Despesa selecionada</span>
                @if($itemAtual)
                    <span class="text-[11px] text-gray-500 truncate max-w-[180px]" title="{{ $itemAtual->descricao }}">
                        #{{ $itemAtual->id_despesa ?: str_pad((string) $itemAtual->id, 3, '0', STR_PAD_LEFT) }} — {{ $itemAtual->descricao }}
                    </span>
                @endif
            </div>

            @if($itemAtual)
                <div class="flex-1 overflow-auto custom-scrollbar min-h-0">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-gray-100 border-b border-gray-200 text-gray-600 sticky top-0 z-10">
                            <tr>
                                <th class="py-2 px-3 border-r border-gray-200 font-medium">Mês</th>
                                <th class="py-2 px-3 border-r border-gray-200 font-medium text-right">Previsto</th>
                                <th class="py-2 px-3 font-medium text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $totalItemDir = 0;
                                $totalPrevistoDir = 0;
                            @endphp
                            @foreach($mesesConfig as $sigla => $nomeMes)
                                @php
                                    $vPrev = (float) ($itemAtual->{"previsto_{$sigla}"} ?? 0);
                                    $vMes  = (float) ($itemAtual->{"valor_{$sigla}"} ?? 0);
                                    $totalPrevistoDir += $vPrev;
                                    $totalItemDir += $vMes;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-1.5 px-3 border-r border-gray-200 text-gray-700">{{ $nomeMes }}</td>
                                    <td class="py-1.5 px-3 border-r border-gray-200 text-right font-mono text-gray-400">
                                        {{ number_format($vPrev, 2, ',', '.') }}
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono text-gray-900">
                                        {{ number_format($vMes, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 border-t-2 border-gray-300 sticky bottom-0">
                            <tr>
                                <td class="py-2 px-3 border-r border-gray-200 font-semibold text-gray-900">TOTAL</td>
                                <td class="py-2 px-3 border-r border-gray-200 text-right font-mono text-gray-500">
                                    {{ number_format($totalPrevistoDir, 2, ',', '.') }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-semibold text-gray-900">
                                    {{ number_format($totalItemDir, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- 4. PARTE DE BAIXO: TOTAIS MENSAIS GERAIS E TOTAL DO ANO --}}
    @php
        $totaisGeraisMes = [];
        $totalGeralAno = 0;
        foreach(array_keys($mesesConfig) as $sigla) {
            $somaMes = $itensOrdenados->sum("valor_{$sigla}");
            $totaisGeraisMes[$sigla] = $somaMes;
            $totalGeralAno += $somaMes;
        }
    @endphp
    <div class="bg-gray-50 border-t border-gray-300 px-5 py-3 shrink-0">
        <div class="text-xs font-semibold text-gray-800 border-b border-gray-200 pb-1.5 mb-2.5">
            Totais (Soma Mensal de Todas as Despesas do Orçamento)
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-2">
            @foreach($mesesConfig as $sigla => $nomeMes)
                <div>
                    <label class="block text-[10px] text-gray-500 mb-0.5">{{ $nomeMes }}</label>
                    <div class="w-full py-1.5 px-2.5 bg-white border border-gray-300 rounded flex items-center justify-end font-mono text-xs text-gray-800">
                        {{ number_format($totaisGeraisMes[$sigla], 2, ',', '.') }}
                    </div>
                </div>
            @endforeach

            <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                <label class="block text-[10px] font-semibold text-gray-700 mb-0.5">Total Ano (R$)</label>
                <div class="w-full py-1.5 px-3 bg-gray-900 text-white border border-gray-900 rounded flex items-center justify-end font-mono text-xs font-semibold">
                    {{ number_format($totalGeralAno, 2, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL OBRIGATÓRIO DO CAMPO "MOTIVO DA REVISÃO" --}}
    @if($modalMotivoRevisaoAberto)
        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Motivo da Revisão</h3>
                <p class="text-xs text-gray-500 mb-3">
                    Detalhe os motivos que levaram a colocar o status <strong>Aguardando Revisão pelo Responsável</strong> nesta(s) despesa(s):
                </p>
                <textarea wire:model="textoMotivoRevisao" rows="4" class="w-full text-xs border-gray-300 rounded p-2.5" placeholder="Informe os esclarecimentos ou ajustes necessários..."></textarea>
                @error('textoMotivoRevisao') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" wire:click="$set('modalMotivoRevisaoAberto', false)" class="px-3 py-1.5 text-xs text-gray-600 bg-gray-100 rounded">Cancelar</button>
                    <button type="button" wire:click="confirmarMotivoRevisaoModal" class="px-4 py-1.5 text-xs text-white bg-gray-900 hover:bg-black rounded">Confirmar Motivo</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE ANÁLISE DE REABERTURA --}}
    @if($modalAprovarReaberturaAberto)
        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Avaliar Pedido de Reabertura</h3>
                <p class="text-xs text-gray-600 mb-4">Defina opcionalmente um prazo limite para o responsável concluir a edição:</p>
                <input type="datetime-local" wire:model="prazoReabertura" class="w-full text-xs border-gray-300 rounded p-2 mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="negarReabertura" class="px-3 py-1.5 text-xs text-red-700 bg-red-50 border border-red-200 rounded">Negar</button>
                    <button type="button" wire:click="aprovarReabertura" class="px-4 py-1.5 text-xs text-white bg-gray-900 rounded">Aprovar Reabertura</button>
                </div>
            </div>
        </div>
    @endif

    {{-- DRAWER DIVIDIDO EM 3 ABAS: HISTÓRICO GERAL | COMENTÁRIOS (MEMO) | MOTIVO DA REVISÃO --}}
    <div class="relative z-[170]" x-show="$wire.drawerComentariosAberto" x-cloak>
        <div class="fixed inset-0 bg-gray-900/40" @click="$wire.set('drawerComentariosAberto', false)"></div>
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="w-screen max-w-md bg-white shadow-xl border-l border-gray-200 flex flex-col h-full">
                
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Históricos, Comentários e Revisões</h2>
                    <button type="button" @click="$wire.set('drawerComentariosAberto', false)" class="text-gray-400 hover:text-gray-700">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-3 border-b border-gray-200 bg-gray-50 text-[11px] font-medium">
                    <button type="button"
                            wire:click="$set('abaComentario', 'geral')"
                            class="py-2.5 text-center border-b-2 transition {{ $abaComentario === 'geral' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Geral ({{ count($comentariosGerais) }})
                    </button>
                    <button type="button"
                            wire:click="$set('abaComentario', 'item')"
                            class="py-2.5 text-center border-b-2 transition {{ $abaComentario === 'item' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Comentários Item
                    </button>
                    <button type="button"
                            wire:click="$set('abaComentario', 'revisao')"
                            class="py-2.5 text-center border-b-2 transition {{ $abaComentario === 'revisao' ? 'border-amber-600 text-amber-800 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Motivo da Revisão
                    </button>
                </div>

                @if($abaComentario === 'geral')
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse($comentariosGerais as $com)
                            <div class="bg-white border border-gray-200 rounded p-3 text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-900">{{ $com->user_nome ?? 'Usuário' }} (ID #{{ $com->user_id ?? '-' }})</span>
                                    <span class="text-[10px] text-gray-400">{{ $com->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($com->tipo_evento !== 'comentario_chat')
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-700 rounded mb-1">{{ $com->status_aplicado }}</span>
                                @endif
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $com->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-8">Nenhum histórico geral registado.</p>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form wire:submit.prevent="adicionarComentarioGlobal" class="space-y-2">
                            <textarea wire:model="novoComentarioGeral" rows="2" class="w-full text-xs border-gray-300 rounded p-2" placeholder="Escreva um comentário geral..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-xs rounded hover:bg-black">Enviar Comentário Geral</button>
                            </div>
                        </form>
                    </div>

                @elseif($abaComentario === 'item')
                    <div class="p-3 bg-white border-b border-gray-200">
                        <label class="block text-[10px] text-gray-500 uppercase mb-1">Despesa Selecionada</label>
                        <select wire:model.live="itemComentarioId" class="w-full py-1.5 pl-2.5 pr-7 text-xs leading-normal border-gray-300 rounded">
                            @foreach($itensOrdenados as $idxSel => $it)
                                <option value="{{ $it->id }}">#{{ $it->id_despesa ?: str_pad((string) ($idxSel + 1), 3, '0', STR_PAD_LEFT) }} - {{ $it->descricao }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse($comentariosDoItemSelecionado as $comItem)
                            <div class="bg-white border border-gray-200 rounded p-3 text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-900">{{ $comItem->user_nome ?? 'Usuário' }} (ID #{{ $comItem->user_id ?? '-' }})</span>
                                    <span class="text-[10px] text-gray-400">{{ $comItem->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if(!empty($comItem->status_aplicado))
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-700 rounded mb-1">{{ $comItem->status_aplicado }}</span>
                                @endif
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $comItem->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-8">Nenhum comentário registado para este item.</p>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form wire:submit.prevent="adicionarComentarioPorItem" class="space-y-2">
                            <textarea wire:model="novoComentarioItem" rows="2" class="w-full text-xs border-gray-300 rounded p-2" placeholder="Escreva um comentário para este item..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-xs rounded hover:bg-black">Adicionar Comentário</button>
                            </div>
                        </form>
                    </div>

                @else
                    {{-- ABA: MOTIVO DA REVISÃO --}}
                    <div class="p-3 bg-white border-b border-gray-200">
                        <label class="block text-[10px] text-gray-500 uppercase mb-1">Despesa em Análise</label>
                        <select wire:model.live="itemComentarioId" class="w-full py-1.5 pl-2.5 pr-7 text-xs leading-normal border-gray-300 rounded">
                            @foreach($itensOrdenados as $idxSel => $it)
                                <option value="{{ $it->id }}">#{{ $it->id_despesa ?: str_pad((string) ($idxSel + 1), 3, '0', STR_PAD_LEFT) }} - {{ $it->descricao }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @if(!empty($decisoesAprovador[$itemComentarioId]['motivo_revisao']))
                            <div class="bg-amber-50 border border-amber-200 rounded p-3 text-xs">
                                <span class="block text-[10px] font-semibold text-amber-800 uppercase mb-1">Motivo pendente de gravação nesta sessão:</span>
                                <p class="text-amber-900 whitespace-pre-wrap">{{ $decisoesAprovador[$itemComentarioId]['motivo_revisao'] }}</p>
                            </div>
                        @endif

                        @forelse($motivosRevisaoDoItem as $revItem)
                            <div class="bg-white border border-amber-200 rounded p-3 text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-900">{{ $revItem->user_nome ?? 'Usuário' }} (ID #{{ $revItem->user_id ?? '-' }})</span>
                                    <span class="text-[10px] text-gray-400">{{ $revItem->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <span class="inline-block px-1.5 py-0.5 text-[10px] bg-amber-50 text-amber-800 border border-amber-200 rounded mb-1">
                                    {{ $revItem->status_aplicado }}
                                </span>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $revItem->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-8">Nenhum motivo de revisão registado no histórico desta despesa.</p>
                        @endforelse
                    </div>

                    @php
                        $statusItemFoco = $decisoesAprovador[$itemComentarioId]['status'] ?? ($itensOrdenados->firstWhere('id', $itemComentarioId)->status ?? '');
                    @endphp
                    <div class="p-4 border-t border-gray-200 bg-white">
                        @if($statusItemFoco === 'Aguardando Revisão pelo Responsável')
                            <form wire:submit.prevent="adicionarMotivoRevisaoNoDrawer" class="space-y-2">
                                <textarea wire:model="novoMotivoRevisaoDrawer" rows="2" class="w-full text-xs border-gray-300 rounded p-2" placeholder="Adicionar novo motivo de revisão..."></textarea>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-1.5 bg-amber-600 text-white text-xs rounded hover:bg-amber-700">Registar Motivo da Revisão</button>
                                </div>
                            </form>
                        @else
                            <p class="text-[11px] text-gray-500 text-center">
                                O campo <strong>Motivo da Revisão</strong> fica disponível para preenchimento apenas quando a despesa é marcada com o status <strong>Aguardando Revisão pelo Responsável</strong> (botão <em>Revisar</em>).
                            </p>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>