<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Central de Aprovações" icon="ph ph-check-square-offset" badge="Diretoria" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-bold border border-purple-200 flex items-center gap-2 shadow-sm">
                <i class="ph-fill ph-clock"></i> {{ $totalAguardando }} Aguardando Avaliação
            </span>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mt-6">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar Natureza" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
                    <option value="Finalizado">Aguardando Avaliação</option>
                    <option value="Aprovado">Aprovados</option>
                    <option value="Aprovado com ressalvas">Aprovados (Ressalvas)</option>
                    <option value="Reprovado">Reprovados</option>
                    <option value="">Todos Avaliados</option>
                </select>
                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white"><i class="ph-bold ph-funnel-x"></i> Limpar</button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <x-table :headers="$this->headers" :registros="$registros" :ordenacaoCampo="$ordenacaoCampo" :ordenacaoDirecao="$ordenacaoDirecao" :permiteGrid="$permiteGrid" :modoExibicao="$modoExibicao">
                @forelse($registros as $orcamento)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 t-label-12 text-gray-500">#{{ $orcamento->id }}</td>
                        <td class="px-4 py-3 t-body-14-semibold text-center">{{ $orcamento->ano }}</td>
                        <td class="px-4 py-3 t-label-12-semibold">{{ $orcamento->filial ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-bold block">{{ $orcamento->natureza ?: 'S/ NATUREZA' }}</span>
                            <span class="text-[10px] text-gray-500 truncate block max-w-[200px]">{{ $orcamento->descricao_despesa ?: 'Sem descrição' }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono font-bold text-purpura-600">{{ $orcamento->ccusto ?: '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusColor = match($orcamento->status) {
                                    'Finalizado' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'Aprovado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Aprovado com ressalvas' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Reprovado' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase border {{ $statusColor }} whitespace-nowrap">{{ $orcamento->status === 'Finalizado' ? 'Aguardando' : $orcamento->status }}</span>
                        </td>
                        <td class="px-4 py-3 t-body-14-semibold text-right">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="abrirModalDetalhes({{ $orcamento->id }})" class="p-2 text-white bg-gray-900 hover:bg-black rounded-lg shadow-sm transition" title="Analisar Orçamento"><i class="text-base ph-bold ph-magnifying-glass"></i> Analisar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">Nenhum orçamento pendente encontrado.</td></tr>
                @endforelse
            </x-table>
        </div>
    </div>

    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            <div class="bg-gray-50 w-full h-full max-w-7xl max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 overflow-hidden" x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-white">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 flex items-center gap-2"><i class="ph-fill ph-list-magnifying-glass text-purpura-500 text-2xl"></i> Avaliação do Item de Orçamento</h2>
                    </div>
                    <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-red-500 shadow-sm transition"><i class="ph-bold ph-x text-lg"></i></button>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar border-r border-gray-200 bg-white">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filial / Ano</span><span class="text-base font-black">{{ $orcamentoSelecionado->filial ?: '-' }} / {{ $orcamentoSelecionado->ano }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Natureza</span><span class="text-sm font-mono font-bold text-purpura-600">{{ $orcamentoSelecionado->natureza ?: '-' }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Centro de Custo</span><span class="text-sm font-mono font-bold">{{ $orcamentoSelecionado->ccusto ?: '-' }}</span></div>
                            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200"><span class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Total Solicitado</span><span class="text-lg font-black text-emerald-700">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span></div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Justificativa Inserida pelo Gestor</label>
                            <div class="w-full rounded-xl border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-600">{{ $orcamentoSelecionado->descricao_despesa ?: 'Sem descrição informada.' }}</div>
                        </div>

                        <h3 class="font-extrabold text-xs text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Distribuição Solicitada (Mensal)</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            @php
                                $mesesConfig = [
                                    'valor_jan' => '01 / Jan', 'valor_fev' => '02 / Fev', 'valor_mar' => '03 / Mar', 'valor_abr' => '04 / Abr', 'valor_mai' => '05 / Mai', 'valor_jun' => '06 / Jun',
                                    'valor_jul' => '07 / Jul', 'valor_ago' => '08 / Ago', 'valor_set' => '09 / Set', 'valor_out' => '10 / Out', 'valor_nov' => '11 / Nov', 'valor_dez' => '12 / Dez',
                                ];
                                $mesAnteriorValor = 0;
                            @endphp
                            @foreach($mesesConfig as $campoKey => $mesNome)
                                @php
                                    $valorAtual = (float) ($orcamentoSelecionado->$campoKey ?? 0);
                                    $variacao = $loop->first ? 0 : $valorAtual - $mesAnteriorValor;
                                    $mesAnteriorValor = $valorAtual;
                                @endphp
                                <div class="flex flex-col p-3 rounded-xl border {{ $valorAtual > 0 ? 'bg-white border-gray-200 shadow-sm' : 'bg-gray-50/50 border-dashed border-gray-200 opacity-60' }}">
                                    <div class="flex justify-between items-start mb-1">
                                        <label class="text-[10px] font-bold uppercase text-gray-500">{{ $mesNome }}</label>
                                        @if(!$loop->first && $variacao !== 0)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $variacao > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">{!! $variacao > 0 ? '<i class="ph-bold ph-trend-up"></i>' : '<i class="ph-bold ph-trend-down"></i>' !!} R$ {{ number_format(abs($variacao), 2, ',', '.') }}</span>
                                        @endif
                                    </div>
                                    <span class="text-base font-black text-gray-900">R$ {{ number_format($valorAtual, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- COLUNA DIREITA: Ações do Aprovador --}}
                    <div class="w-full md:w-96 bg-gray-50 flex flex-col border-l border-gray-200">
                        <div class="p-5 border-b border-gray-200 bg-white shrink-0"><h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Histórico</h3></div>
                        <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-6 relative">
                            <div class="absolute left-7 top-0 bottom-0 w-px bg-gray-200"></div>
                            @forelse($orcamentoSelecionado->avaliacoes ?? [] as $aval)
                                @php $corPonto = match($aval->status_aplicado) { 'Aprovado' => 'bg-emerald-500', 'Aprovado com ressalvas' => 'bg-yellow-500', 'Reprovado' => 'bg-red-500', default => 'bg-gray-500' }; @endphp
                                <div class="relative pl-8">
                                    <div class="absolute w-3 h-3 rounded-full {{ $corPonto }} border-2 border-white left-[-5px] top-1 shadow-sm"></div>
                                    <div class="bg-white p-3.5 rounded-xl shadow-sm border border-gray-100">
                                        <div class="flex justify-between items-start mb-2"><span class="text-xs font-black">{{ $aval->user_nome }}</span><span class="text-[9px] font-bold text-gray-400">{{ $aval->created_at->format('d/m H:i') }}</span></div>
                                        <span class="text-[10px] font-bold uppercase {{ str_contains($aval->status_aplicado, 'Reprovado') ? 'text-red-600' : 'text-emerald-600' }} block mb-2">{{ $aval->status_aplicado }}</span>
                                        <p class="text-sm text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100">"{{ $aval->comentario }}"</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 relative z-10"><i class="ph-fill ph-chat-slash text-4xl text-gray-300 mb-2"></i><p class="text-xs font-bold text-gray-400 uppercase">Nenhuma avaliação.</p></div>
                            @endforelse
                        </div>

                        <div class="p-4 bg-white border-t border-gray-200 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                            <p class="text-[10px] font-bold text-gray-500 uppercase text-center mb-3 tracking-widest">Painel de Decisão</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="abrirModalAvaliacao('Aprovado')" class="py-2.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold hover:bg-emerald-600 hover:text-white transition shadow-sm">Aprovar Integralmente</button>
                                <button wire:click="abrirModalAvaliacao('Reprovado')" class="py-2.5 bg-red-50 text-red-700 border border-red-200 rounded-lg text-xs font-bold hover:bg-red-600 hover:text-white transition shadow-sm">Reprovar Item</button>
                                <button wire:click="abrirModalAvaliacao('Aprovado com ressalvas')" class="col-span-2 py-2.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-xs font-bold hover:bg-yellow-500 hover:text-white transition shadow-sm">Aprovar com Ressalvas (Anotações)</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL SECUNDÁRIO: COMENTÁRIO DO APROVADOR --}}
    @if($modalAvaliacaoAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-lg font-black text-gray-900 mb-1 flex items-center gap-2"><i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Justificativa Obrigatória</h3>
                <p class="text-xs text-gray-500 mb-4">Avaliando como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>. O gestor receberá este feedback.</p>
                <textarea wire:model="comentarioAvaliacao" rows="4" class="w-full rounded-xl border-gray-300 bg-gray-50 text-sm focus:ring-purpura-500" placeholder="Digite os comentários ou diretrizes de correção..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="confirmarAvaliacao" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">Confirmar Decisão</button>
                </div>
            </div>
        </div>
    @endif
</div>