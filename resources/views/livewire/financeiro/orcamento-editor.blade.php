<div class="font-sans flex flex-col bg-white border border-gray-200 rounded-lg overflow-hidden text-gray-800 w-full"
     x-data="{ modoExpandido: true, navbarOculta: true, abaMobile: 'itens' }"
     x-init="
        document.body.classList.toggle('modo-expandido', modoExpandido);
        document.body.classList.toggle('navbar-oculta', navbarOculta);
        $watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val));
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
     "
     @item-adicionado.window="
        abaMobile = 'itens';
        setTimeout(() => {
            if ($refs.listaItensContainer) {
                $refs.listaItensContainer.scrollTo({ top: $refs.listaItensContainer.scrollHeight, behavior: 'smooth' });
            }
        }, 120);
     "
     :class="modoExpandido ? 'flex-1 h-full border-0 rounded-none' : 'min-h-[720px] h-[calc(100vh-140px)]'">

    {{-- Inclusão do jQuery e Select2 --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body.modo-expandido main { overflow: hidden !important; display: flex !important; flex-direction: column !important; }
        body.modo-expandido main > div { padding-top: 0 !important; padding-bottom: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; height: 100% !important; min-height: 0 !important; }
        body.modo-expandido main > div > div { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; height: 100% !important; min-height: 0 !important; }
        body.navbar-oculta .js-topnav, body.navbar-oculta header, body.navbar-oculta .js-sidebar { display: none !important; }

        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            height: 30px !important;
            display: flex !important;
            align-items: center !important;
            outline: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1f2937 !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            line-height: 28px !important;
            padding-left: 8px !important;
            padding-right: 22px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 28px !important;
            position: absolute !important;
            top: 1px !important;
            right: 2px !important;
            width: 18px !important;
        }
        .select2-orcamento-dropdown {
            min-width: 340px !important;
            max-width: 480px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
            background-color: #ffffff !important;
            z-index: 99999 !important;
            overflow: hidden !important;
        }
        .select2-results__option {
            padding: 7px 10px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            color: #374151 !important;
        }
        .select2-results__option--highlighted[aria-selected] {
            background-color: #111827 !important;
            color: #ffffff !important;
        }
        .select2-search--dropdown {
            padding: 6px !important;
            background-color: #f9fafb !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 5px 10px !important;
            font-size: 11px !important;
            outline: none !important;
            width: 100% !important;
        }
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
    <div class="bg-gray-50 border-b border-gray-200 px-3 sm:px-5 py-2.5 flex flex-wrap items-center justify-between gap-2 shrink-0">
        <div class="flex items-center gap-2.5">
            <button type="button" wire:click="voltar" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-100 transition shrink-0" title="Voltar">
                <i class="ph-bold ph-arrow-left"></i>
            </button>
            <div>
                <h1 class="text-sm sm:text-base font-semibold text-gray-900 leading-tight">Orçamento — Elaboração e Revisão de Itens</h1>
                <p class="hidden sm:block text-[11px] text-gray-500">Selecione cada item na lista à esquerda para revisar seus valores mensais à direita e liberar para aprovação.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <button type="button" @click="modoExpandido = !modoExpandido; if (!modoExpandido) navbarOculta = false;" class="hidden md:inline-flex px-2.5 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition items-center gap-1.5">
                <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                <span x-text="modoExpandido ? 'Compactar' : 'Tela Cheia'"></span>
            </button>

            <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="hidden md:inline-flex px-2.5 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition items-center gap-1.5">
                <i class="ph-bold" :class="navbarOculta ? 'ph-eye' : 'ph-eye-slash'"></i>
                <span x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
            </button>

            <button type="button" wire:click="abrirComentariosGerais" class="px-2.5 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition flex items-center gap-1">
                <i class="ph-bold ph-chat-text"></i> <span class="hidden sm:inline">Histórico /</span> Comentários
            </button>

            @if(!$isLockedGlobal && $orcamento->status !== 'Aprovação – Aguardando Revisão pelo Responsável')
                <button type="button" wire:click="adicionarItem" class="px-3 py-1.5 text-xs font-semibold rounded border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1 shadow-2xs">
                    <i class="ph-bold ph-plus"></i> Novo Item
                </button>
            @endif

            @if(!$isLockedGlobal)
                <button type="button" wire:click="salvarOrcamento" wire:loading.attr="disabled" class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 transition flex items-center gap-1">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar
                </button>

                <button type="button" wire:click="finalizarOrcamento" wire:loading.attr="disabled" class="px-3.5 py-1.5 text-xs font-medium rounded bg-gray-900 hover:bg-black text-white transition flex items-center gap-1">
                    <i class="ph-bold ph-paper-plane-tilt"></i> Submeter Aprovação
                </button>
            @else
                @if(in_array($orcamento->status, ['Finalizado – Aprovado', 'Aprovado'], true))
                    <button type="button"
                            wire:click="integrarComProtheus"
                            onclick="confirm('Finalizar definitivamente este orçamento e integrá-lo à Tabela Intermediária do Protheus? Após esta ação o orçamento ficará bloqueado para alterações.') || event.stopImmediatePropagation()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded bg-emerald-700 hover:bg-emerald-800 text-white transition flex items-center gap-1.5">
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
    <div class="px-3 sm:px-5 py-2.5 bg-white border-b border-gray-200 grid grid-cols-2 sm:grid-cols-12 gap-2 sm:gap-3 shrink-0">
        <div class="col-span-1 sm:col-span-2">
            <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Ano <span class="text-red-500">*</span></label>
            <input type="text" value="{{ $orcamento->ano }}" readonly class="w-full py-1 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800 font-mono">
        </div>
        <div class="col-span-1 sm:col-span-2">
            <label class="block text-[10px] font-medium text-gray-500 mb-0.5">C. Custo <span class="text-red-500">*</span></label>
            <input type="text" value="{{ $orcamento->ccusto }}" readonly class="w-full py-1 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800 font-mono">
        </div>
        <div class="col-span-2 sm:col-span-5">
            <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Nome / Centro de Custo</label>
            <input type="text" value="{{ $ccNome }}" readonly class="w-full py-1 px-2.5 text-xs bg-gray-100 border border-gray-300 rounded text-gray-800 truncate">
        </div>
        <div class="col-span-2 sm:col-span-3">
            <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Status <span class="text-red-500">*</span></label>
            <div class="w-full py-1 px-2.5 text-xs border rounded flex items-center font-medium truncate {{ $corStatusOrcamento }}">
                {{ $orcamento->status }}
            </div>
        </div>
    </div>

    {{-- NAVEGAÇÃO POR ABAS NO MOBILE (< lg) --}}
    <div class="flex lg:hidden border-b border-gray-200 bg-gray-100 text-xs font-semibold shrink-0">
        <button type="button" @click="abaMobile = 'itens'" class="flex-1 py-2.5 text-center border-b-2 transition" :class="abaMobile === 'itens' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500'">
            1. Itens ({{ count($itensOrcamento) }})
        </button>
        <button type="button" @click="abaMobile = 'meses'" class="flex-1 py-2.5 text-center border-b-2 transition" :class="abaMobile === 'meses' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500'">
            2. Meses (#{{ $itemAtual['id_despesa'] ?? '001' }})
        </button>
        <button type="button" @click="abaMobile = 'totais'" class="flex-1 py-2.5 text-center border-b-2 transition" :class="abaMobile === 'totais' ? 'border-gray-900 text-gray-900 bg-white' : 'border-transparent text-gray-500'">
            3. Totais do Ano
        </button>
    </div>

    {{-- BARRA DE AÇÕES EM LOTE QUANDO HOUVER ITENS MARCADOS --}}
    @if(count($itensSelecionados) > 0 && !$isLockedGlobal)
        <div class="px-3 sm:px-5 py-2 bg-gray-900 text-white flex flex-wrap items-center justify-between gap-2 shrink-0">
            <span class="text-xs font-medium">{{ count($itensSelecionados) }} item(ns) selecionado(s)</span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="liberarSelecionadosParaAprovacao" class="px-3 py-1 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded transition flex items-center gap-1">
                    <i class="ph-bold ph-check"></i> Liberar Selecionados
                </button>
                <button type="button" wire:click="removerItensSelecionados" onclick="confirm('Tem certeza que deseja excluir os itens selecionados?') || event.stopImmediatePropagation()" class="px-3 py-1 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded transition flex items-center gap-1">
                    <i class="ph-bold ph-trash"></i> Excluir Selecionados
                </button>
            </div>
        </div>
    @endif

    {{-- 3. ÁREA CENTRAL DIVIDIDA: ESQUERDA (ITENS) | DIREITA (ITEM SELECIONADO - MESES) --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 lg:divide-x divide-gray-200 overflow-hidden min-h-0">
        
        {{-- COLUNA ESQUERDA (8 COLUNAS NO DESKTOP): LISTA DE ITENS --}}
        <div class="lg:col-span-9 min-w-0 flex-col overflow-hidden bg-white min-h-0"
             :class="abaMobile === 'itens' ? 'flex' : 'hidden lg:flex'">
            
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-gray-800">Itens do Orçamento ({{ count($itensOrcamento) }})</span>
                    <span class="text-[11px] text-gray-500">
                        Liberados: <strong>{{ collect($itensOrcamento)->whereIn('status', ['Aprovação Pendente', 'Aprovada', 'Aprovado', 'Aprovada Parcialmente'])->count() }}</strong> / {{ count($itensOrcamento) }}
                    </span>
                </div>

                @if(!$isLockedGlobal && $orcamento->status !== 'Aprovação – Aguardando Revisão pelo Responsável')
                    <button type="button" wire:click="adicionarItem" class="px-2.5 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded transition flex items-center gap-1">
                        <i class="ph-bold ph-plus"></i> Adicionar Item
                    </button>
                @endif
            </div>

            <div x-ref="listaItensContainer" class="flex-1 overflow-y-auto overflow-x-auto custom-scrollbar min-h-0">
                <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                    <thead class="hidden lg:table-header-group bg-gray-100 border-b border-gray-200 text-gray-600 sticky top-0 z-10">
                        <tr>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium min-w-[70px]">
                                <div class="flex items-center gap-1">
                                    @if(!$isLockedGlobal)
                                        <input type="checkbox" wire:model.live="selecionarTudo" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                    @endif
                                    <span>ID</span>
                                </div>
                            </th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[210px]">Descrição do Item</th>
                            <th class="py-2 px-1.5 border-r border-gray-200 font-medium text-center min-w-[85px]">Memo</th>
                            <th class="py-2 px-2.5 border-r border-gray-200 font-medium min-w-[200px]">Natureza Financeira</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium min-w-[145px]">Cenário</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium text-right min-w-[170px]">Total Item</th>
                            <th class="py-2 px-2 border-r border-gray-200 font-medium text-center min-w-[130px]">Status</th>
                            <th class="py-2 px-2 font-medium text-center min-w-[180px]">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 block lg:table-row-group p-2 lg:p-0 space-y-2.5 lg:space-y-0">
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

                            <tr wire:key="item-row-{{ $tempId }}"
                                wire:click="selecionarItem({{ $index }})"
                                class="block lg:table-row rounded-lg lg:rounded-none border lg:border-0 border-gray-200 p-3 lg:p-0 cursor-pointer transition-colors {{ $isSelecionado ? 'bg-sky-100/80 ring-1 lg:ring-0 ring-sky-400 text-gray-900' : 'bg-white hover:bg-gray-50' }}">
                                
                                {{-- ID DO ITEM + CHECKBOX --}}
                                <td class="flex lg:table-cell items-center justify-between py-1 lg:py-2 px-1 lg:px-2 lg:border-r border-gray-200 font-mono text-gray-700 align-middle" @click.stop>
                                    <div class="flex items-center gap-1.5">
                                        @if($podeEditarLinha)
                                            <input type="checkbox" wire:model.live="itensSelecionados" value="{{ $tempId }}" class="rounded border-gray-300 text-gray-900 focus:ring-0">
                                        @endif
                                        <span wire:click="selecionarItem({{ $index }})" class="font-bold lg:font-normal">#{{ $item['id_despesa'] ?? str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <button type="button"
                                            @click="abaMobile = 'meses'; $wire.selecionarItem({{ $index }})"
                                            class="lg:hidden px-2 py-0.5 text-[11px] font-semibold bg-sky-600 text-white rounded">
                                        Editar Meses &rarr;
                                    </button>
                                </td>

                                {{-- DESCRIÇÃO DO ITEM --}}
                                <td class="block lg:table-cell py-1.5 lg:px-2 border-r border-gray-200 align-middle">
                                    <span class="lg:hidden block text-[10px] font-semibold text-gray-400 uppercase mb-0.5">Descrição do Item</span>
                                    @if($podeEditarLinha)
                                        <input type="text"
                                               wire:key="desc-input-{{ $tempId }}"
                                               value="{{ $item['descricao'] }}"
                                               wire:change="atualizarCampoItem('{{ $tempId }}', 'descricao', $event.target.value)"
                                               placeholder="Descreva o item..."
                                               class="w-full min-w-[200px] py-1 px-2 text-xs leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                    @else
                                        <span class="block truncate max-w-[210px]" title="{{ $item['descricao'] }}">{{ $item['descricao'] ?: '-' }}</span>
                                    @endif
                                </td>

                                {{-- MEMO / COMENTÁRIOS DO ITEM --}}
                                <td class="inline-block lg:table-cell py-1 lg:py-1.5 lg:px-1.5 lg:border-r border-gray-200 text-center align-middle" @click.stop>
                                    <button type="button"
                                            wire:click="abrirComentariosDoItem({{ $index }})"
                                            class="px-2 py-1 text-[11px] rounded border {{ $qtdComentariosItem > 0 ? 'bg-sky-50 text-sky-700 border-sky-200 font-semibold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                        Memo {{ $qtdComentariosItem > 0 ? "({$qtdComentariosItem})" : '' }}
                                    </button>
                                </td>

                                {{-- NATUREZA FINANCEIRA COM SELECT2 --}}
                                <td class="block lg:table-cell py-1.5 lg:px-2 lg:border-r border-gray-200 align-middle" @click.stop>
                                    <span class="lg:hidden block text-[10px] font-semibold text-gray-400 uppercase mb-0.5">Natureza Financeira</span>
                                    @if($podeEditarLinha)
                                        <div wire:ignore wire:key="select2-nat-wrapper-{{ $tempId }}" class="w-full min-w-[190px]">
                                            <select x-data
                                                    x-init="
                                                        $($el).select2({
                                                            placeholder: 'Selecione a natureza...',
                                                            width: '100%',
                                                            dropdownAutoWidth: true,
                                                            dropdownCssClass: 'select2-orcamento-dropdown'
                                                        });
                                                        $($el).on('change', function () {
                                                            $wire.selecionarItem({{ $index }});
                                                            $wire.atualizarCampoItem('{{ $tempId }}', 'natureza_codigo', $(this).val());
                                                        });
                                                    "
                                                    class="w-full text-xs">
                                                <option value="">Selecione a natureza...</option>
                                                @foreach($todasNaturezas as $nat)
                                                    <option value="{{ $nat->codigo }}" @selected(($item['natureza_codigo'] ?? '') === $nat->codigo)>
                                                        {{ $nat->codigo }} - {{ $nat->descricao }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @else
                                        <div class="truncate text-[11px]" title="{{ $item['natureza_codigo'] }} - {{ $item['natureza_nome'] }}">
                                            <span class="font-mono font-semibold text-gray-800">{{ $item['natureza_codigo'] ?: '-' }}</span>
                                            @if(!empty($item['natureza_nome']))
                                                <span class="text-gray-500">— {{ $item['natureza_nome'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- CENÁRIO --}}
                                <td class="block lg:table-cell py-1.5 lg:px-1.5 lg:border-r border-gray-200 align-middle">
                                    <span class="lg:hidden block text-[10px] font-semibold text-gray-400 uppercase mb-0.5">Cenário</span>
                                    @if($podeEditarLinha)
                                        <select wire:key="cen-select-{{ $tempId }}"
                                                wire:change="atualizarCampoItem('{{ $tempId }}', 'cenario', $event.target.value)"
                                                class="w-full min-w-[130px] py-1 pl-2 pr-6 text-[11px] leading-normal bg-white border border-gray-300 rounded focus:border-sky-600 focus:ring-0">
                                            @foreach($cenariosDisponiveis as $codCen => $labelCen)
                                                <option value="{{ $codCen }}" @selected(($item['cenario'] ?? '1') == $codCen)>{{ $labelCen }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-[11px] block truncate">{{ $cenariosDisponiveis[$item['cenario'] ?? '1'] ?? '1 - Conservador' }}</span>
                                    @endif
                                </td>

                                {{-- TOTAL DO ITEM --}}
                                <td class="flex lg:table-cell items-center justify-between py-1 lg:py-2 lg:px-2 lg:border-r border-gray-200 text-right font-mono font-semibold text-gray-900 align-middle">
                                    <span class="lg:hidden text-[10px] font-sans font-semibold text-gray-400 uppercase">Total do Item:</span>
                                    <span class="min-w-[140px] inline-block">{{ number_format($somaItem, 2, ',', '.') }}</span>
                                </td>

                                {{-- STATUS DO ITEM --}}
                                <td class="flex lg:table-cell items-center justify-between py-1 lg:py-1.5 lg:px-1.5 lg:border-r border-gray-200 text-center align-middle">
                                    <span class="lg:hidden text-[10px] font-semibold text-gray-400 uppercase">Status:</span>
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] rounded border truncate max-w-full {{ $badgeStatusItem }}" title="{{ $statusItem }}">
                                        {{ $statusItem }}
                                    </span>
                                </td>

                                {{-- AÇÕES DO ITEM (LIBERAR / EDITAR / EXCLUIR) --}}
                                <td class="block lg:table-cell pt-2 lg:pt-0 py-1.5 lg:px-1.5 text-center align-middle border-t lg:border-t-0 border-gray-100 mt-1 lg:mt-0" @click.stop>
                                    <div class="flex items-center justify-end lg:justify-center gap-1.5">
                                        @if(in_array($statusItem, ['Aprovada', 'Aprovado'], true))
                                            <span class="text-[11px] text-emerald-700 font-medium flex items-center gap-1">
                                                <i class="ph-bold ph-lock-key"></i> Aprovado
                                            </span>
                                        @elseif($statusItem === 'Aprovação Pendente')
                                            <span class="px-2 py-0.5 text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded flex items-center gap-1 font-medium">
                                                <i class="ph-bold ph-check"></i> Liberado
                                            </span>
                                            @if(!$isLockedGlobal)
                                                <button type="button"
                                                        wire:click="reabrirItemParaEdicao({{ $index }})"
                                                        class="p-1 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded transition"
                                                        title="Voltar item para edição">
                                                    <i class="ph-bold ph-pencil-simple text-sm"></i>
                                                </button>
                                            @endif
                                        @else
                                            <button type="button"
                                                    wire:click="liberarItemParaAprovacao({{ $index }})"
                                                    @disabled(!$foiClicado || $isLockedGlobal)
                                                    title="{{ $foiClicado ? 'Liberar este item para aprovação' : 'Clique primeiro na linha do item para revisar os meses' }}"
                                                    class="px-2.5 py-1 text-[10px] font-medium rounded border transition {{ ($foiClicado && !$isLockedGlobal) ? 'bg-gray-900 text-white border-gray-900 hover:bg-black cursor-pointer' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}">
                                                Liberar p/ Aprov.
                                            </button>
                                        @endif

                                        @if($podeEditarLinha && !$isLockedGlobal)
                                            <button type="button"
                                                    wire:click="removerItem({{ $index }})"
                                                    onclick="confirm('Deseja remover este item da lista?') || event.stopImmediatePropagation()"
                                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition shrink-0"
                                                    title="Remover item">
                                                <i class="ph-bold ph-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- RODAPÉ RÁPIDO PARA ADICIONAR NOVO ITEM --}}
                @if(!$isLockedGlobal && $orcamento->status !== 'Aprovação – Aguardando Revisão pelo Responsável')
                    <div class="p-3 bg-gray-50/70 border-t border-gray-200 flex justify-center sticky left-0">
                        <button type="button" wire:click="adicionarItem" class="w-full sm:w-auto px-4 py-1.5 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-dashed border-gray-300 rounded-md transition flex items-center justify-center gap-1.5 shadow-2xs">
                            <i class="ph-bold ph-plus-circle text-emerald-600 text-sm"></i> Adicionar Novo Item
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- COLUNA DIREITA (4 COLUNAS NO DESKTOP): VALORES MENSAIS DO ITEM SELECIONADO --}}
        <div class="lg:col-span-3 min-w-0 flex-col overflow-hidden bg-white min-h-0"
             :class="abaMobile === 'meses' ? 'flex' : 'hidden lg:flex'"
             wire:key="painel-direito-item-{{ $itemAtual['temp_id'] ?? 'vazio' }}">
            
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button" @click="abaMobile = 'itens'" class="lg:hidden text-xs font-semibold text-sky-700 flex items-center gap-1 shrink-0">
                        <i class="ph-bold ph-arrow-left"></i> Itens
                    </button>
                    <span class="text-xs font-semibold text-gray-800 shrink-0">Item selecionado</span>
                </div>
                @if($itemAtual)
                    <span class="text-[11px] font-medium text-gray-600 truncate" title="{{ $itemAtual['descricao'] }}">
                        #{{ $itemAtual['id_despesa'] ?? '001' }} — {{ $itemAtual['descricao'] ?: ($itemAtual['natureza_nome'] ?: 'Novo Item') }}
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
                                <th class="py-2 px-3 font-medium text-right w-36 sm:w-40">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php $totalItemSelecionado = 0; @endphp
                            @foreach($mesesConfig as $sigla => $nomeMes)
                                @php
                                    $valPrevisto = (float) ($itemAtual["previsto_{$sigla}"] ?? 0);
                                    $valAtual    = (float) ($itemAtual["valor_{$sigla}"] ?? 0);
                                    $totalItemSelecionado += $valAtual;
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
                                    {{ number_format($totalItemSelecionado, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center p-6 text-xs text-gray-400">
                    Selecione um item à esquerda para visualizar ou editar os valores mensais.
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
    <div class="bg-gray-50 border-t border-gray-300 px-3 sm:px-5 py-3 shrink-0"
         :class="abaMobile === 'totais' ? 'block' : 'hidden lg:block'">
        <div class="text-xs font-semibold text-gray-800 border-b border-gray-200 pb-1.5 mb-2.5">
            Totais (Soma Mensal de Todos os Itens do Orçamento)
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
                    Todos os itens deste orçamento estão liberados para aprovação. Deseja enviar o orçamento agora para análise dos aprovadores?
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
                        <label class="block text-[10px] text-gray-500 uppercase mb-1">Item Selecionado</label>
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