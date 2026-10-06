<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Orçamentos das Áreas"
        icon="ph ph-wallet"
        badge="Módulo Financeiro"
        :breadcrumbs="$breadcrumbs">

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
        <!-- BARRA DO SIMULADOR (HOMOLOGAÇÃO) -->
        @if(auth()->user()->hasRole('dev|admin'))
            <div class="mb-6 bg-gradient-to-r from-gray-900 to-purpura-900 rounded-xl p-5 shadow-lg border border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/20 shrink-0">
                        <i class="ph-bold ph-calendar-forward text-2xl text-yellow-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black tracking-wide">Simulação (Mês Vigente): <span class="text-yellow-400">{{ $nomesMeses[$mesSimulacaoAtual] }}</span></h3>
                        <p class="text-xs text-gray-300 mt-0.5">Os meses anteriores a {{ $nomesMeses[$mesSimulacaoAtual] }} e orçamentos aprovados serão congelados.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="avancarMesSimulacao" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 rounded-lg text-sm font-black transition shadow-md flex items-center gap-2 whitespace-nowrap" onclick="confirm('Tem certeza? Orçamentos em elaboração serão forçados para finalizado e o sistema avançará.') || event.stopImmediatePropagation()">
                        Avançar Mês <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </div>
            </div>
        @endif

        <div class="mb-6">
            <x-summary-cards :metricas="$metricas" />
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 bg-gray-50/40 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano (Ex: 2026)" class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar Natureza..." class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                
                <select wire:model.live="filtroStatus" class="rounded-lg border-gray-200 shadow-sm text-sm focus:ring-purpura-500 w-full dark:bg-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="Criado">Criado (Importado)</option>
                    <option value="Em elaboração">Em Elaboração</option>
                    <option value="Finalizado">Finalizado (Aguardando)</option>
                    <option value="Aprovado">Aprovado</option>
                    <option value="Reprovado">Reprovado</option>
                </select>

                <button wire:click="limparFiltros" class="btn btn--secondary btn--small w-full h-[38px] bg-white">
                    <i class="ph-bold ph-funnel-x"></i> Limpar
                </button>
            </div>
        </div>

        <div class="relative z-0 bg-white dark:bg-gray-900">
            <div class="tabela-orcamento">
                <x-table 
                    :headers="$this->headers" 
                    :registros="$registros" 
                    :ordenacaoCampo="$ordenacaoCampo" 
                    :ordenacaoDirecao="$ordenacaoDirecao" 
                    :permiteGrid="$permiteGrid" 
                    :modoExibicao="$modoExibicao">

                    @forelse($registros as $orcamento)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                            <td class="px-4 py-3 t-label-12 text-gray-500 dark:text-gray-400">#{{ $orcamento->id }}</td>
                            <td class="px-4 py-3 t-body-14-semibold text-center text-gray-900 dark:text-white">{{ $orcamento->ano }}</td>
                            <td class="px-4 py-3 t-label-12-semibold text-gray-700 dark:text-gray-300">{{ $orcamento->filial ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block">{{ $orcamento->natureza ?: 'S/ NATUREZA' }}</span>
                                <span class="text-[10px] text-gray-500 truncate block max-w-[200px]" title="{{ $orcamento->descricao_despesa }}">{{ $orcamento->descricao_despesa ?: 'Sem descrição' }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono font-bold text-purpura-600 dark:text-purpura-400">{{ $orcamento->ccusto ?: '-' }}</td>
                            
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
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider border {{ $statusColor }} whitespace-nowrap">
                                    {{ $orcamento->status ?? 'Criado' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 t-body-14-semibold text-right text-gray-900 dark:text-white">
                                R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="abrirModalDetalhes({{ $orcamento->id }})" class="p-2 text-gray-400 hover:text-purpura-600 bg-white border border-gray-200 rounded-lg shadow-sm transition" title="Ver Detalhes / Aprovar">
                                    <i class="text-base ph-bold ph-pencil-simple"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <i class="ph-fill ph-wallet text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                Nenhum orçamento encontrado para o seu Centro de Custo.
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        </div>
    </div>

    {{-- MODAL PRINCIPAL: DETALHES E APROVAÇÃO --}}
    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            
            <div class="bg-gray-50 dark:bg-gray-900 w-full h-full max-w-7xl max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-800 overflow-hidden" 
                 x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-list-magnifying-glass text-purpura-500 text-2xl"></i> Detalhamento do Item de Orçamento
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Análise de variação mensal (Moeda: {{ $orcamentoSelecionado->moeda }} | C. Moeda: {{ $orcamentoSelecionado->cmoeda ?: '-' }})
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        @php
                            $statusColor = match($orcamentoSelecionado->status) {
                                'Em elaboração' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'Finalizado' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'Aprovado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'Aprovado com ressalvas' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                'Reprovado' => 'bg-red-100 text-red-700 border-red-200',
                                default => 'bg-gray-100 text-gray-700 border-gray-200'
                            };
                        @endphp
                        <span class="px-3 py-1.5 text-xs font-bold rounded-full uppercase tracking-wider border {{ $statusColor }}">
                            Status: {{ $orcamentoSelecionado->status ?? 'Criado' }}
                        </span>

                        <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-red-500 shadow-sm transition">
                            <i class="ph-bold ph-x text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    
                    {{-- COLUNA ESQUERDA: Formulário e Variação Mensal --}}
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Filial / Ano</span>
                                <span class="text-base font-black text-gray-900 dark:text-white">{{ $orcamentoSelecionado->filial ?: '-' }} / {{ $orcamentoSelecionado->ano }}</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Natureza</span>
                                <span class="text-sm font-mono font-bold text-purpura-600">{{ $orcamentoSelecionado->natureza ?: '-' }}</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Centro de Custo</span>
                                <span class="text-sm font-mono font-bold text-gray-900 dark:text-gray-200">{{ $orcamentoSelecionado->ccusto ?: '-' }}</span>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                <span class="block text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Total Previsto Anual</span>
                                <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Descrição e Justificativa da Despesa <span class="text-red-500">*</span></label>
                            <textarea wire:model="editData.descricao_despesa" rows="3" class="w-full rounded-xl border-gray-200 shadow-sm text-sm focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white disabled:opacity-60" placeholder="Descreva detalhadamente o motivo deste orçamento..." @if(in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas']) || (!auth()->user()->hasRole('dev|admin') && $orcamentoSelecionado->status === 'Finalizado')) disabled @endif></textarea>
                        </div>

                        <h3 class="font-extrabold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">Distribuição e Variação Mensal</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            @php
                                $mesesConfig = [
                                    'valor_jan' => '01 / Janeiro', 'valor_fev' => '02 / Fevereiro', 'valor_mar' => '03 / Março',
                                    'valor_abr' => '04 / Abril', 'valor_mai' => '05 / Maio', 'valor_jun' => '06 / Junho',
                                    'valor_jul' => '07 / Julho', 'valor_ago' => '08 / Agosto', 'valor_set' => '09 / Setembro',
                                    'valor_out' => '10 / Outubro', 'valor_nov' => '11 / Novembro', 'valor_dez' => '12 / Dezembro',
                                ];
                                $mesAnteriorValor = 0;
                            @endphp

                            @foreach($mesesConfig as $campoKey => $mesNome)
                                @php
                                    $valorAtual = (float) ($editData[$campoKey] ?? 0);
                                    $isPassado = $loop->iteration < $mesSimulacaoAtual;
                                    
                                    // Cálculo da variação com o mês anterior
                                    $variacao = $loop->first ? 0 : $valorAtual - $mesAnteriorValor;
                                    $percVariacao = ($mesAnteriorValor > 0) ? ($variacao / $mesAnteriorValor) * 100 : 0;
                                    
                                    // Seta para o próximo ciclo
                                    $mesAnteriorValor = $valorAtual;
                                @endphp
                                <div class="flex flex-col p-3 rounded-xl border {{ $valorAtual > 0 ? 'bg-white border-gray-200 shadow-sm' : 'bg-gray-50/50 border-dashed border-gray-200' }} focus-within:border-purpura-500 focus-within:shadow-md transition dark:bg-gray-800 dark:border-gray-700">
                                    <div class="flex justify-between items-start mb-1">
                                        <label class="text-[10px] font-bold uppercase text-gray-500">{{ $mesNome }}</label>
                                        
                                        @if(!$loop->first && $variacao !== 0)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $variacao > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}" title="Variação em relação ao mês anterior">
                                                {!! $variacao > 0 ? '<i class="ph-bold ph-trend-up"></i>' : '<i class="ph-bold ph-trend-down"></i>' !!} 
                                                R$ {{ number_format(abs($variacao), 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="relative">
                                        <span class="absolute left-2 top-0.5 text-sm text-gray-400 font-bold">R$</span>
                                        <input type="number" step="0.01" wire:model="editData.{{ $campoKey }}" 
                                               class="w-full text-base font-black text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0 pl-8 p-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                               @if(in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas']) || $isPassado || (!auth()->user()->hasRole('dev|admin') && $orcamentoSelecionado->status === 'Finalizado')) disabled title="Bloqueado para edição" @endif>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- COLUNA DIREITA: Histórico de Comentários / Timeline --}}
                    <div class="w-full md:w-96 bg-gray-50 dark:bg-gray-800/50 flex flex-col">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                                <i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Histórico de Aprovações
                            </h3>
                        </div>

                        <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-6 relative">
                            <div class="absolute left-7 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                            @forelse($orcamentoSelecionado->avaliacoes ?? [] as $aval)
                                @php
                                    $corPonto = match($aval->status_aplicado) {
                                        'Aprovado' => 'bg-emerald-500',
                                        'Aprovado com ressalvas' => 'bg-yellow-500',
                                        'Reprovado' => 'bg-red-500',
                                        default => 'bg-gray-500'
                                    };
                                @endphp
                                <div class="relative pl-8">
                                    <div class="absolute w-3 h-3 rounded-full {{ $corPonto }} border-2 border-white dark:border-gray-800 left-[-5px] top-1 shadow-sm"></div>
                                    <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-xs font-black text-gray-900 dark:text-white">{{ $aval->user_nome }}</span>
                                            <span class="text-[9px] font-bold text-gray-400">{{ $aval->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ str_contains($aval->status_aplicado, 'Reprovado') ? 'text-red-600' : 'text-emerald-600' }} block mb-2">
                                            Ação: {{ $aval->status_aplicado }}
                                        </span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-900 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                            "{{ $aval->comentario }}"
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 relative z-10">
                                    <i class="ph-fill ph-chat-slash text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Nenhuma avaliação<br>registrada ainda.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- AÇÕES DO APROVADOR (Apenas Admins/Diretoria veem quando está finalizado) --}}
                        @if(auth()->user()->hasRole('dev|admin') && in_array($orcamentoSelecionado->status, ['Finalizado', 'Reprovado', 'Em elaboração']))
                            <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shrink-0">
                                <p class="text-[10px] font-bold text-gray-500 uppercase text-center mb-2 tracking-widest">Painel do Aprovador</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button wire:click="abrirModalAvaliacao('Aprovado')" class="py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold hover:bg-emerald-600 hover:text-white transition">Aprovar</button>
                                    <button wire:click="abrirModalAvaliacao('Reprovado')" class="py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-xs font-bold hover:bg-red-600 hover:text-white transition">Reprovar</button>
                                    <button wire:click="abrirModalAvaliacao('Aprovado com ressalvas')" class="col-span-2 py-2 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-xs font-bold hover:bg-yellow-500 hover:text-white transition">Aprovar com Ressalvas</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RODAPÉ GESTOR --}}
                <div class="p-5 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 flex justify-between items-center gap-4">
                    <span class="text-xs text-gray-500 font-medium hidden sm:block">Ações disponíveis de acordo com a sua permissão.</span>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        @if(!in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas']))
                            <button wire:click="salvarOrcamento" class="btn btn--secondary btn--medium flex-1 sm:flex-auto">Salvar Rascunho</button>
                            <button wire:click="finalizarOrcamento" class="btn btn--primary btn--medium bg-blue-600 border-none hover:bg-blue-700 flex-1 sm:flex-auto" onclick="confirm('Submeter para aprovação? Você não poderá mais editar.') || event.stopImmediatePropagation()">
                                <i class="ph-bold ph-paper-plane-tilt"></i> Submeter P/ Aprovação
                            </button>
                        @else
                            <button wire:click="fecharModal" class="btn btn--secondary btn--medium w-full">Fechar Visualização</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL SECUNDÁRIO: COMENTÁRIO DO APROVADOR --}}
    @if($modalAvaliacaoAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1 flex items-center gap-2">
                    <i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Justificativa de Avaliação
                </h3>
                <p class="text-xs text-gray-500 mb-4">Você está marcando este orçamento como <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>. Insira o motivo:</p>
                
                <textarea wire:model="comentarioAvaliacao" rows="4" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-purpura-500" placeholder="Digite os comentários ou diretrizes de correção..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="confirmarAvaliacao" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">Confirmar Decisão</button>
                </div>
            </div>
        </div>
    @endif
</div>