<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header title="Orçamentos das Áreas" icon="ph ph-wallet" badge="Módulo Financeiro" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            @if(auth()->user()->hasRole('dev|admin'))
                <button wire:click="sincronizarProtheus" wire:loading.attr="disabled" class="btn btn--primary btn--small">
                    <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarProtheus"></i> 
                    <span wire:loading.remove wire:target="sincronizarProtheus">Importar Protheus</span>
                    <span wire:loading wire:target="sincronizarProtheus">Buscando...</span>
                </button>
            @endif
        </x-slot>
    </x-page-header>

    @if(isset($metricas))
        <!-- BARRA DO SIMULADOR DE ANO/MÊS -->
        @if(auth()->user()->hasRole('dev|admin'))
            <div class="mb-6 bg-gradient-to-r from-gray-900 to-purpura-900 rounded-xl p-5 shadow-lg border border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/20 shrink-0">
                        <i class="ph-bold ph-calendar-forward text-2xl text-yellow-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black tracking-wide">Simulador de Tempo Ativo</h3>
                        <p class="text-xs text-gray-300 mt-0.5">Altere o mês vigente para testar o bloqueio dinâmico das despesas passadas.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <select wire:model.live="mesSimulacaoAtual" class="bg-gray-800 border border-gray-700 text-white text-sm rounded-lg focus:ring-purpura-500 py-2">
                        @foreach($nomesMeses as $num => $nome)
                            <option value="{{ $num }}">{{ $nome }}</option>
                        @endforeach
                    </select>

                    <div class="bg-gray-800 border border-gray-700 text-white text-sm rounded-lg py-2 px-4 font-bold">
                        Ano: {{ $anoSimulacao }}
                    </div>

                    <button wire:click="avancarAnoSimulacao" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 rounded-lg text-sm font-black transition shadow-md flex items-center gap-2 whitespace-nowrap">
                        Avançar Ano <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </div>
            </div>
        @endif

        <div class="mb-6"><x-summary-cards :metricas="$metricas" /></div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar Natureza" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="Criado">Criado (Importado)</option>
                    <option value="Em elaboração">Em Elaboração</option>
                    <option value="Finalizado">Finalizado (Aguardando)</option>
                    <option value="Aprovado">Aprovado</option>
                    <option value="Reprovado">Reprovado</option>
                </select>
                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white"><i class="ph-bold ph-funnel-x"></i> Limpar</button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <x-table :headers="$this->headers" :registros="$registros" :ordenacaoCampo="$ordenacaoCampo" :ordenacaoDirecao="$ordenacaoDirecao" :permiteGrid="$permiteGrid" :modoExibicao="$modoExibicao">
                @forelse($registros as $orcamento)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
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
                                    'Em elaboração' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Finalizado' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'Aprovado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Aprovado com ressalvas' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Reprovado' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase border {{ $statusColor }} whitespace-nowrap">{{ $orcamento->status ?? 'Criado' }}</span>
                        </td>
                        <td class="px-4 py-3 t-body-14-semibold text-right">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="abrirModalDetalhes({{ $orcamento->id }})" class="p-2 text-gray-400 hover:text-purpura-600 bg-white border border-gray-200 rounded-lg shadow-sm transition" title="Ver / Editar Valores"><i class="text-base ph-bold ph-pencil-simple"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">Nenhum orçamento encontrado.</td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>

    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900 w-full h-full max-w-7xl max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-800 overflow-hidden" x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-list-magnifying-glass text-purpura-500 text-2xl"></i> Orçamento (Mão Dupla)</h2>
                        <p class="text-xs text-gray-500 mt-1">Análise de variação mensal (Moeda: {{ $orcamentoSelecionado->moeda }})</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-red-500 shadow-sm transition"><i class="ph-bold ph-x text-lg"></i></button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filial / Ano</span><span class="text-base font-black">{{ $orcamentoSelecionado->filial ?: '-' }} / {{ $orcamentoSelecionado->ano }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Natureza</span><span class="text-sm font-mono font-bold text-purpura-600">{{ $orcamentoSelecionado->natureza ?: '-' }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Centro de Custo</span><span class="text-sm font-mono font-bold">{{ $orcamentoSelecionado->ccusto ?: '-' }}</span></div>
                            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200"><span class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Total Previsto</span><span class="text-lg font-black text-emerald-700">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span></div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Descrição da Despesa <span class="text-red-500">*</span></label>
                            <textarea wire:model="editData.descricao_despesa" rows="3" class="w-full rounded-xl border-gray-200 shadow-sm text-sm focus:ring-purpura-500 disabled:opacity-60" placeholder="Motivo do orçamento..." @if(in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas', 'Finalizado'])) disabled @endif></textarea>
                        </div>

                        <h3 class="font-extrabold text-xs text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Distribuição e Variação Mensal</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            @php
                                $mesesConfig = [
                                    'valor_jan' => '01 / Jan', 'valor_fev' => '02 / Fev', 'valor_mar' => '03 / Mar',
                                    'valor_abr' => '04 / Abr', 'valor_mai' => '05 / Mai', 'valor_jun' => '06 / Jun',
                                    'valor_jul' => '07 / Jul', 'valor_ago' => '08 / Ago', 'valor_set' => '09 / Set',
                                    'valor_out' => '10 / Out', 'valor_nov' => '11 / Nov', 'valor_dez' => '12 / Dez',
                                ];
                                $mesAnteriorValor = 0;
                            @endphp

                            @foreach($mesesConfig as $campoKey => $mesNome)
                                @php
                                    $valorAtual = (float) ($editData[$campoKey] ?? 0);
                                    
                                    // BLOQUEIO INTELIGENTE DE MESES/ANO:
                                    // Se o ano for anterior à simulação, bloqueia. 
                                    // Se o ano for o atual da simulação, bloqueia os meses passados.
                                    $isPassado = ($orcamentoSelecionado->ano < $anoSimulacao) || ($orcamentoSelecionado->ano == $anoSimulacao && $loop->iteration < $mesSimulacaoAtual);
                                    
                                    $isDisabled = in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas', 'Finalizado']) || $isPassado;
                                    
                                    $variacao = $loop->first ? 0 : $valorAtual - $mesAnteriorValor;
                                    $mesAnteriorValor = $valorAtual;
                                @endphp
                                <div class="flex flex-col p-3 rounded-xl border {{ $valorAtual > 0 ? 'bg-white border-gray-200 shadow-sm' : 'bg-gray-50/50 border-dashed border-gray-200' }} focus-within:border-purpura-500 focus-within:shadow-md transition {{ $isDisabled ? 'opacity-60 bg-gray-100' : '' }}">
                                    <div class="flex justify-between items-start mb-1">
                                        <label class="text-[10px] font-bold uppercase text-gray-500">{{ $mesNome }} {!! $isPassado ? '<i class="ph-fill ph-lock-key"></i>' : '' !!}</label>
                                        @if(!$loop->first && $variacao !== 0)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $variacao > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
                                                {!! $variacao > 0 ? '<i class="ph-bold ph-trend-up"></i>' : '<i class="ph-bold ph-trend-down"></i>' !!} R$ {{ number_format(abs($variacao), 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="relative">
                                        <span class="absolute left-2 top-0.5 text-sm text-gray-400 font-bold">R$</span>
                                        <input type="number" step="0.01" wire:model="editData.{{ $campoKey }}" class="w-full text-base font-black text-gray-900 bg-transparent border-0 focus:ring-0 pl-8 p-0 disabled:cursor-not-allowed" @if($isDisabled) disabled @endif>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- COLUNA DIREITA: Histórico de Avaliações --}}
                    <div class="w-full md:w-96 bg-gray-50 flex flex-col border-l border-gray-200">
                        <div class="p-5 border-b border-gray-200 bg-white shrink-0"><h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 uppercase tracking-wider"><i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Avaliações</h3></div>
                        <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-6 relative">
                            <div class="absolute left-7 top-0 bottom-0 w-px bg-gray-200"></div>
                            @forelse($orcamentoSelecionado->avaliacoes ?? [] as $aval)
                                @php $corPonto = match($aval->status_aplicado) { 'Aprovado' => 'bg-emerald-500', 'Aprovado com ressalvas' => 'bg-yellow-500', 'Reprovado' => 'bg-red-500', default => 'bg-gray-500' }; @endphp
                                <div class="relative pl-8">
                                    <div class="absolute w-3 h-3 rounded-full {{ $corPonto }} border-2 border-white left-[-5px] top-1 shadow-sm"></div>
                                    <div class="bg-white p-3.5 rounded-xl shadow-sm border border-gray-100">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-xs font-black">{{ $aval->user_nome }}</span>
                                            <span class="text-[9px] font-bold text-gray-400">{{ $aval->created_at->format('d/m H:i') }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase {{ str_contains($aval->status_aplicado, 'Reprovado') ? 'text-red-600' : 'text-emerald-600' }} block mb-2">{{ $aval->status_aplicado }}</span>
                                        <p class="text-sm text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100">"{{ $aval->comentario }}"</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 relative z-10"><i class="ph-fill ph-chat-slash text-4xl text-gray-300 mb-2"></i><p class="text-xs font-bold text-gray-400 uppercase">Nenhuma avaliação.</p></div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-200 bg-white flex justify-between items-center gap-4">
                    <span class="text-xs text-gray-500 font-medium hidden sm:block">Ações disponíveis de acordo com seu acesso.</span>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        @if(!in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas', 'Finalizado']))
                            <button wire:click="salvarOrcamento" class="btn btn--secondary btn--medium flex-1 sm:flex-auto">Salvar Rascunho</button>
                            <button wire:click="finalizarOrcamento" class="btn btn--primary btn--medium bg-blue-600 border-none hover:bg-blue-700 flex-1 sm:flex-auto" onclick="confirm('Submeter para aprovação? Você não poderá mais editar os valores.') || event.stopImmediatePropagation()">
                                <i class="ph-bold ph-paper-plane-tilt"></i> Submeter P/ Aprovação
                            </button>
                        @else
                            <button wire:click="fecharModal" class="btn btn--secondary btn--medium w-full">Fechar</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>