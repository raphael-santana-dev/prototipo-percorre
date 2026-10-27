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
        $itemAtual = $itensOrcamento[$itemSelecionadoIndex] ?? null;
        $podeEditarItemAtual = $itemAtual ? $this->itemPodeSerEditado($itemAtual) : false;

        $corStatusOrcamento = match($orcamento->status) {
            'Importado', 'Criado' => 'bg-gray-100 text-gray-700 border-gray-300',
            'Em revisão/atualização', 'Em elaboração' => 'bg-blue-50 text-blue-700 border-blue-200',
            'Finalizado – Aguardando Aprovação', 'Finalizado' => 'bg-amber-50 text-amber-800 border-amber-200',
            'Aprovação – Aguardando Revisão pelo Responsável', 'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
            'Finalizado – Aprovado', 'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Finalizado – Integrado ao Protheus' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-gray-100 text-gray-700 border-gray-300'
        };
    @endphp

    {{-- 1. BARRA DE TÍTULO E AÇÕES GLOBAIS --}}
    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <button type="button" wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-100 transition" title="Voltar">
                <i class="ph-bold ph-arrow-left"></i>
            </button>
            <div>
                <h1 class="text-base font-semibold text-gray-900">Orçamento — Elaboração e Revisão</h1>
                <p class="text-xs text-gray-500">Clique em uma despesa na tabela à esquerda para revisar seus valores mensais à direita e liberar para aprovação.</p>
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
                <i class="ph-bold ph-chat-text"></i> Histórico / Comentários
            </button>

            @if(!$isLockedGlobal && $orcamento->status !== 'Aprovação – Aguardando Revisão pelo Responsável')
                <button type="button" wire:click="adicionarItem" class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1">
                    <i class="ph-bold ph-plus"></i> Nova Despesa
                </button>
            @endif

            @if(!$isLockedGlobal)
                <button type="button" wire:click="salvarOrcamento" wire:loading.attr="disabled" class="px-3.5 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 transition flex items-center gap-1.5">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar
                </button>

                <button type="button" wire:click="finalizarOrcamento" wire:loading.attr="disabled" class="px-4 py-1.5 text-xs font-medium rounded bg-gray-900 hover:bg-black text-white transition flex items-center gap-1.5">
                    <i class="ph-bold ph-paper-plane-tilt"></i> Submeter Aprovação
                </button>
            @else
                @if(in_array($orcamento->status, ['Finalizado – Aprovado', 'Aprovado'], true))
                    <button type="button"
                            wire:click="integrarComProtheus"
                            onclick="confirm('Finalizar definitivamente este orçamento e integrá-lo à Tabela Intermediária do Protheus? Após esta ação o orçamento ficará bloqueado para alterações.') || event.stopImmediatePropagation()"
                            class="px-4 py-1.5 text-xs font-medium rounded bg-emerald-700 hover:bg-emerald-800 text-white transition flex items-center gap-1.5">
                        <i class="ph-bold ph-cloud-arrow-up"></i> Integrar ao Protheus
                    </button>
                @elseif($orcamento->status === 'Finalizado – Integrado ao Protheus')
                    <span class="px-3 py-1.5 text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 rounded flex items-center gap-1">
                        <i class="ph-bold ph-check-circle"></i> Integrado ao Protheus
                    </span>
                @elseif($orcamento->reabertura_solicitada)
                    <span class="px-3 py-1.5 text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200 rounded">
                        Reabertura Solicitada
                    </span>
                @else
                    <button type="button" wire:click="$set('modalReaberturaAberto', true)" class="px-3 py-1.5 text-xs font-medium bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100 rounded transition">
                        Solicitar Reabertura
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- 2. CAMPOS DO CABEÇALHO DO ORÇAMENTO --}}
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

    {{-- BARRA DE AÇÕES EM LOTE QUANDO HOUVER DESPESAS MARCADAS --}}
    @if(count($itensSelecionados) > 0 && !$isLockedGlobal)
        <div class="px-5 py-2 bg-gray-900 text-white flex flex-wrap items-center justify-between gap-2 shrink-0">
            <span class="text-xs font-medium">{{ count($itensSelecionados) }} despesa(s) selecionada(s)</span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="liberarSelecionadosParaAprovacao" class="px-3 py-1 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded transition">
                    Liberar Selecionadas para Aprovação
                </button>
                <button type="button" wire:click="removerItensSelecionados" onclick="confirm('Remover as despesas selecionadas?') || event.stopImmediatePropagation()" class="px-3 py-1 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded transition">
                    Remover Selecionadas
                </button>
            </div>
        </div>
    @endif

    {{-- 3. ÁREA CENTRAL DIVIDIDA: ESQUERDA (DESPESAS) | DIREITA (DESPESA SELECIONADA - MESES) --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 overflow-hidden min-h-0">
        
        {{-- COLUNA ESQUERDA (8 COLUNAS): TABELA DE DESPESAS --}}
        <div class="lg:col-span-8 min-w-0 flex flex-col overflow-hidden bg-white min-h-0">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0">
                <span class="text-xs font-semibold text-gray-800">Despesas ({{ count($itensOrcamento) }})</span>
                <span class="text-[11px] text-gray-500">
                    Liberadas: {{ collect($itensOrcamento)->whereIn('status', ['Aprovação Pendente', 'Aprovada', 'Aprovado', 'Aprovada Parcialmente'])->count() }} / {{ count($itensOrcamento) }}
                </span>
            </div>

            <div class="flex-1 overflow-x-auto overflow-y-auto custom-scrollbar min-h-0">
                <table class="w-full min-w-[1220px] text-left border-collapse text-xs whitespace-nowrap">
                    <thead class="bg-gray-100 border-b border-gray-200 text-gray-600 sticky top-0 z-10">
                        <tr>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium w-20 min-w-[80px]">
                                <div class="flex items-center gap-1.5">
                                    @if(!$isLockedGlobal)
                                        <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                    @endif
                                    <span>ID Desp</span>
                                </div>
                            </th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium min-w-[230px]">Desc Desp</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium text-center min-w-[90px]">Comentário</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[220px]">Natureza</th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium min-w-[200px]">Descrição Natureza</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[165px]">Cenário</th>
                            <th class="py-2 px-3 border-r border-gray-200 font-medium text-right min-w-[120px]">Total Item</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium text-center min-w-[150px]">Status</th>
                            <th class="py-2 px-3 font-medium text-center min-w-[175px]">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($itensOrcamento as $index => $item)
                            @php
                                $isSelecionado = ($itemSelecionadoIndex === $index);
                                $tempId = (string) $item['temp_id'];
                                $foiClicado = in_array($tempId, $itensClicados, true) || (!empty($item['id']) && in_array('db_' . $item['id'], $itensClicados, true));
                                $podeEditarLinha = $this->itemPodeSerEditado($item);
                                $statusItem = $item['status'] ?? 'Importado';

                                $somaItem = 0;
                                foreach(array_keys($mesesConfig) as $mKey) {
                                    $somaItem += (float) ($item["valor_{$mKey}"] ?? 0);
                                }

                                $qtdComentariosItem = is_array($item['avaliacoes'] ?? null) ? count($item['avaliacoes']) : 0;

                                $badgeStatusItem = match($statusItem) {
                                    'Aprovada', 'Aprovado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Aprovada Parcialmente' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'Não Aprovada', 'Não Aprovado', 'Reprovado' => 'bg-red-50 text-red-700 border-red-200',
                                    'Aguardando Revisão pelo Responsável', 'Reprovado parcialmente' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'Aprovação Pendente' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'Em revisão/atualização' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    default => 'bg-gray-100 text-gray-600 border-gray-200',
                                };
                            @endphp

                            <tr wire:key="desp-row-{{ $tempId }}"
                                wire:click="selecionarItem({{ $index }})"
                                class="cursor-pointer transition-colors {{ $isSelecionado ? 'bg-sky-100/80 text-gray-900' : 'hover:bg-gray-50' }}">
                                
                                <td class="py-2 px-2.5 border-r border-gray-200 font-mono text-gray-700 min-w-[80px] align-middle" @click.stop>
                                    <div class="flex items-center gap-1.5">
                                        @if($podeEditarLinha)
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $tempId }}" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                        @endif
                                        <span wire:click="selecionarItem({{ $index }})">{{ $item['id_despesa'] ?? str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 min-w-[230px] align-middle">
                                    @if($podeEditarLinha)
                                        <input type="text"
                                               wire:key="desc-input-{{ $tempId }}"
                                               value="{{ $item['descricao'] }}"
                                               wire:change="atualizarCampoItem('{{ $tempId }}', 'descricao', $event.target.value)"
                                               placeholder="Descreva a despesa..."
                                               class="w-full min-w-[210px] py-1 px-2 text-xs leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                    @else
                                        <span class="block truncate max-w-[240px]" title="{{ $item['descricao'] }}">{{ $item['descricao'] ?: '-' }}</span>
                                    @endif
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 text-center min-w-[90px] align-middle" @click.stop>
                                    <button type="button"
                                            wire:click="abrirComentariosDoItem({{ $index }})"
                                            class="px-2 py-1 text-[11px] rounded border {{ $qtdComentariosItem > 0 ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                        Memo {{ $qtdComentariosItem > 0 ? "({$qtdComentariosItem})" : '' }}
                                    </button>
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 font-mono min-w-[220px] align-middle">
                                    @if($podeEditarLinha)
                                        <select wire:key="nat-select-{{ $tempId }}"
                                                wire:change="atualizarCampoItem('{{ $tempId }}', 'natureza_codigo', $event.target.value)"
                                                class="w-full min-w-[205px] py-1 pl-2.5 pr-7 text-xs leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                            <option value="">Selecione...</option>
                                            @foreach($todasNaturezas as $nat)
                                                <option value="{{ $nat->codigo }}" @selected(($item['natureza_codigo'] ?? '') === $nat->codigo)>
                                                    {{ $nat->codigo }} - {{ $nat->descricao }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{ $item['natureza_codigo'] ?: '-' }}
                                    @endif
                                </td>

                                <td class="py-2 px-3 border-r border-gray-200 min-w-[200px] truncate max-w-[240px] align-middle" title="{{ $item['natureza_nome'] }}">
                                    {{ $item['natureza_nome'] ?: '-' }}
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 min-w-[165px] align-middle">
                                    @if($podeEditarLinha)
                                        <select wire:key="cen-select-{{ $tempId }}"
                                                wire:change="atualizarCampoItem('{{ $tempId }}', 'cenario', $event.target.value)"
                                                class="w-full min-w-[150px] py-1 pl-2.5 pr-7 text-xs leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                            @foreach($cenariosDisponiveis as $codCen => $labelCen)
                                                <option value="{{ $codCen }}" @selected(($item['cenario'] ?? '1') == $codCen)>{{ $labelCen }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span>{{ $cenariosDisponiveis[$item['cenario'] ?? '1'] ?? '1 - Conservador' }}</span>
                                    @endif
                                </td>

                                <td class="py-2 px-3 border-r border-gray-200 text-right font-mono text-gray-900 min-w-[120px] align-middle">
                                    {{ number_format($somaItem, 2, ',', '.') }}
                                </td>

                                <td class="py-1.5 px-2 border-r border-gray-200 text-center min-w-[150px] align-middle">
                                    <span class="inline-block px-2 py-0.5 text-[10px] rounded border {{ $badgeStatusItem }}">
                                        {{ $statusItem }}
                                    </span>
                                </td>

                                <td class="py-1.5 px-2 text-center min-w-[175px] align-middle" @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        @if(in_array($statusItem, ['Aprovada', 'Aprovado'], true))
                                            <span class="text-[11px] text-emerald-700 flex items-center gap-1">
                                                <i class="ph-bold ph-lock-key"></i> Aprovada
                                            </span>
                                        @elseif($statusItem === 'Aprovação Pendente')
                                            <span class="px-2 py-0.5 text-[11px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded flex items-center gap-1">
                                                <i class="ph-bold ph-check"></i> Liberado
                                            </span>
                                            @if(!$isLockedGlobal)
                                                <button type="button"
                                                        wire:click="reabrirItemParaEdicao({{ $index }})"
                                                        class="p-1 text-gray-400 hover:text-gray-700"
                                                        title="Voltar para edição">
                                                    <i class="ph-bold ph-pencil-simple"></i>
                                                </button>
                                            @endif
                                        @else
                                            <button type="button"
                                                    wire:click="liberarItemParaAprovacao({{ $index }})"
                                                    @disabled(!$foiClicado || $isLockedGlobal)
                                                    title="{{ $foiClicado ? 'Liberar este item para aprovação' : 'Clique primeiro na linha do item para revisar os meses' }}"
                                                    class="px-2.5 py-1 text-[11px] rounded border transition {{ ($foiClicado && !$isLockedGlobal) ? 'bg-gray-900 text-white border-gray-900 hover:bg-black cursor-pointer' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}">
                                                Liberar para Aprovação
                                            </button>
                                        @endif

                                        @if(empty($item['id']) && !$isLockedGlobal)
                                            <button type="button" wire:click="removerItem({{ $index }})" class="p-1 text-gray-400 hover:text-red-600" title="Remover linha">
                                                <i class="ph-bold ph-trash"></i>
                                            </button>
                                        @endif
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
             wire:key="painel-direito-item-{{ $itemAtual['temp_id'] ?? 'vazio' }}">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between shrink-0">
                <span class="text-xs font-semibold text-gray-800">Despesa selecionada</span>
                @if($itemAtual)
                    <span class="text-[11px] text-gray-500 truncate max-w-[190px]" title="{{ $itemAtual['descricao'] }}">
                        #{{ $itemAtual['id_despesa'] ?? '001' }} — {{ $itemAtual['descricao'] ?: ($itemAtual['natureza_nome'] ?: 'Nova Despesa') }}
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
                                <th class="py-2 px-3 font-medium text-right w-36">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php $totalDespesaSelecionada = 0; @endphp
                            @foreach($mesesConfig as $sigla => $nomeMes)
                                @php
                                    $valPrevisto = (float) ($itemAtual["previsto_{$sigla}"] ?? 0);
                                    $valAtual    = (float) ($itemAtual["valor_{$sigla}"] ?? 0);
                                    $totalDespesaSelecionada += $valAtual;
                                @endphp
                                <tr wire:key="tr-mes-{{ $itemAtual['temp_id'] }}-{{ $sigla }}" class="hover:bg-gray-50">
                                    <td class="py-1.5 px-3 border-r border-gray-200 text-gray-700">{{ $nomeMes }}</td>
                                    <td class="py-1.5 px-3 border-r border-gray-200 text-right font-mono text-gray-400">
                                        {{ number_format($valPrevisto, 2, ',', '.') }}
                                    </td>
                                    <td class="py-1 px-2 text-right">
                                        @if($podeEditarItemAtual)
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   wire:key="input-mes-{{ $itemAtual['temp_id'] }}-{{ $sigla }}"
                                                   value="{{ $valAtual }}"
                                                   wire:change="atualizarValorMes('{{ $itemAtual['temp_id'] }}', '{{ $sigla }}', $event.target.value)"
                                                   class="w-full py-1 px-2 text-right font-mono text-xs leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                        @else
                                            <span class="font-mono text-gray-800 px-2">{{ number_format($valAtual, 2, ',', '.') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 border-t-2 border-gray-300 sticky bottom-0">
                            <tr>
                                <td class="py-2 px-3 border-r border-gray-200 font-semibold text-gray-900">TOTAL</td>
                                <td class="py-2 px-3 border-r border-gray-200 text-right font-mono text-gray-500">
                                    @php
                                        $somaPrevistoItem = 0;
                                        foreach(array_keys($mesesConfig) as $mK) {
                                            $somaPrevistoItem += (float) ($itemAtual["previsto_{$mK}"] ?? 0);
                                        }
                                    @endphp
                                    {{ number_format($somaPrevistoItem, 2, ',', '.') }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-semibold text-gray-900">
                                    {{ number_format($totalDespesaSelecionada, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center p-6 text-xs text-gray-400">
                    Selecione uma despesa à esquerda para visualizar ou editar os valores mensais.
                </div>
            @endif
        </div>
    </div>

    {{-- 4. PARTE DE BAIXO: TOTAIS MENSAIS GERAIS E TOTAL DO ANO --}}
    @php
        $totaisGeraisMes = [];
        $totalGeralAno = 0;
        foreach(array_keys($mesesConfig) as $sigla) {
            $somaMes = collect($itensOrcamento)->sum(fn($i) => (float) ($i["valor_{$sigla}"] ?? 0));
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

    {{-- MODAL AUTOMÁTICO QUANDO O ÚLTIMO ITEM É LIBERADO PARA APROVAÇÃO --}}
    @if($modalConfirmarEnvioAberto)
        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="ph-fill ph-check-circle text-emerald-600 text-xl"></i> Todos os Itens Liberados!
                </h3>
                <p class="text-xs text-gray-600 leading-relaxed mb-5">
                    Todas as despesas deste orçamento estão liberadas para aprovação. Deseja enviar o orçamento agora para análise dos aprovadores?
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('modalConfirmarEnvioAberto', false)" class="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                        Continuar revisando
                    </button>
                    <button type="button" wire:click="finalizarOrcamento" class="px-4 py-2 text-xs font-medium text-white bg-gray-900 hover:bg-black rounded">
                        Sim, Enviar para Aprovação
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL SOLICITAR REABERTURA --}}
    @if($modalReaberturaAberto)
        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-gray-900/50 px-4">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Solicitar Reabertura</h3>
                <p class="text-xs text-gray-500 mb-4">Informe o motivo para solicitar a reabertura deste orçamento.</p>
                <textarea wire:model="motivoReabertura" rows="3" class="w-full text-xs border-gray-300 rounded" placeholder="Motivo obrigatório..."></textarea>
                @error('motivoReabertura') <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" wire:click="$set('modalReaberturaAberto', false)" class="px-3 py-1.5 text-xs text-gray-600 bg-gray-100 rounded">Cancelar</button>
                    <button type="button" wire:click="solicitarReabertura" class="px-4 py-1.5 text-xs text-white bg-gray-900 rounded">Enviar Pedido</button>
                </div>
            </div>
        </div>
    @endif

    {{-- DRAWER DE COMENTÁRIOS DIVIDIDO: GERAIS vs POR ITEM --}}
    <div class="relative z-[170]" x-show="$wire.drawerComentariosAberto" x-cloak>
        <div class="fixed inset-0 bg-gray-900/40" @click="$wire.set('drawerComentariosAberto', false)"></div>
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="w-screen max-w-md bg-white shadow-xl border-l border-gray-200 flex flex-col h-full">
                
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Histórico e Comentários</h2>
                    <button type="button" @click="$wire.set('drawerComentariosAberto', false)" class="text-gray-400 hover:text-gray-700">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-2 border-b border-gray-200 bg-gray-50 text-xs font-medium">
                    <button type="button"
                            wire:click="$set('abaComentario', 'geral')"
                            class="py-2.5 text-center border-b-2 transition {{ $abaComentario === 'geral' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Comentários Gerais ({{ count($comentariosGerais) }})
                    </button>
                    <button type="button"
                            wire:click="$set('abaComentario', 'item')"
                            class="py-2.5 text-center border-b-2 transition {{ $abaComentario === 'item' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Comentários por Item
                    </button>
                </div>

                @if($abaComentario === 'geral')
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse($comentariosGerais as $com)
                            <div class="bg-white border border-gray-200 rounded p-3 text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-900">{{ $com->user_nome ?? 'Usuário' }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $com->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($com->tipo_evento !== 'comentario_chat')
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-700 rounded mb-1">{{ $com->status_aplicado }}</span>
                                @endif
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $com->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-8">Nenhum comentário geral registrado.</p>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form wire:submit.prevent="adicionarComentarioGlobal" class="space-y-2">
                            <textarea wire:model="novoComentarioGeral" rows="2" class="w-full text-xs border-gray-300 rounded p-2" placeholder="Escreva um comentário geral sobre o orçamento..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-xs rounded hover:bg-black">Enviar Comentário Geral</button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="p-3 bg-white border-b border-gray-200">
                        <label class="block text-[10px] text-gray-500 uppercase mb-1">Despesa Selecionada</label>
                        <select wire:model.live="itemComentarioId" class="w-full py-1.5 pl-2.5 pr-7 text-xs leading-normal border-gray-300 rounded">
                            <option value="">Selecione um item...</option>
                            @foreach($itensOrcamento as $it)
                                @if(!empty($it['id']))
                                    <option value="{{ $it['id'] }}">#{{ $it['id_despesa'] }} - {{ $it['descricao'] ?: $it['natureza_nome'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse($comentariosDoItemSelecionado as $comItem)
                            <div class="bg-white border border-gray-200 rounded p-3 text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-900">{{ $comItem->user_nome ?? 'Usuário' }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $comItem->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if(!empty($comItem->status_aplicado))
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] rounded mb-1 {{ $comItem->tipo_evento === 'motivo_revisao' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $comItem->status_aplicado }}
                                    </span>
                                @endif
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $comItem->comentario }}</p>
                            </div>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-8">Nenhum comentário ou motivo de revisão registrado para este item.</p>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form wire:submit.prevent="adicionarComentarioPorItem" class="space-y-2">
                            <textarea wire:model="novoComentarioItem" rows="2" class="w-full text-xs border-gray-300 rounded p-2" placeholder="Escreva um comentário ou resposta de revisão para este item..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-xs rounded hover:bg-black">Adicionar ao Item</button>
                            </div>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>