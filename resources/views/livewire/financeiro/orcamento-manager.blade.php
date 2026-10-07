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
        <!-- BARRA DO SIMULADOR DE ANO -->
        @if(auth()->user()->hasRole('dev|admin'))
            <div class="mb-6 bg-gradient-to-r from-gray-900 to-purpura-900 rounded-xl p-5 shadow-lg border border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/20 shrink-0">
                        <i class="ph-bold ph-calendar-forward text-2xl text-yellow-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black tracking-wide">Simulador de Tempo Ativo</h3>
                        <p class="text-xs text-gray-300 mt-0.5">Altere o ano vigente para testar a renovação e bloqueio de orçamentos.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
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
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroNatureza" placeholder="Buscar Natureza" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
                <input type="text" wire:model.live.debounce.500ms="filtroCentroCusto" placeholder="Buscar Centro de Custo" class="rounded-lg border-gray-200 shadow-sm text-sm w-full dark:bg-gray-700 dark:text-white">
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
                            <span class="text-[10px] text-gray-500 truncate block max-w-[200px]">{{ $orcamento->descricao_despesa ?: 'Sem justificativa geral' }}</span>
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
                        <td class="px-4 py-3 t-body-14-semibold text-right text-gray-900 dark:text-white">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
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
        @php
            $isLockedGlobal = in_array($orcamentoSelecionado->status, ['Aprovado', 'Aprovado com ressalvas', 'Finalizado']);
            if (!auth()->user()->hasRole('dev|admin') && $orcamentoSelecionado->status === 'Finalizado') {
                $isLockedGlobal = true;
            }
        @endphp

        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900 w-full h-full max-w-[1600px] rounded-2xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-800 overflow-hidden" x-data @keydown.escape.window="$wire.fecharModal()">
                
                {{-- HEADER DO MODAL --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 shrink-0">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-microsoft-excel-logo text-emerald-600 text-2xl"></i> Orçamento Interativo (Itens)</h2>
                        <p class="text-xs text-gray-500 mt-1">Modifique os itens e valores para prever o orçamento do seu Centro de Custo.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-red-500 shadow-sm transition"><i class="ph-bold ph-x text-lg"></i></button>
                    </div>
                </div>

                {{-- CORPO PRINCIPAL (SCROLL Y) --}}
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-50 dark:bg-gray-900">
                    
                    {{-- CARDS DE RESUMO SUPERIORES --}}
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filial / Ano</span>
                            <span class="text-base font-black text-gray-900 dark:text-white">{{ $orcamentoSelecionado->filial ?: '-' }} / {{ $orcamentoSelecionado->ano }}</span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Natureza</span>
                            <span class="text-sm font-mono font-bold text-purpura-600">{{ $orcamentoSelecionado->natureza ?: '-' }}</span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Centro de Custo</span>
                            <span class="text-sm font-mono font-bold text-gray-900 dark:text-gray-200">{{ $orcamentoSelecionado->ccusto ?: '-' }}</span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Justificativa da Despesa</span>
                            <input type="text" wire:model="justificativaGeral" class="w-full text-xs p-1 border-b border-gray-300 bg-transparent focus:ring-0 focus:border-purpura-500 text-gray-800 dark:text-gray-200" placeholder="Motivo geral..." @if($isLockedGlobal) disabled @endif>
                        </div>

                        @php
                            // Calcula o total geral dinâmico do Livewire Array
                            $totalGeralLivewire = 0;
                            foreach($itensOrcamento as $item) {
                                $totalGeralLivewire += (float)($item['valor_jan'] ?? 0) + (float)($item['valor_fev'] ?? 0) + (float)($item['valor_mar'] ?? 0) + 
                                                       (float)($item['valor_abr'] ?? 0) + (float)($item['valor_mai'] ?? 0) + (float)($item['valor_jun'] ?? 0) + 
                                                       (float)($item['valor_jul'] ?? 0) + (float)($item['valor_ago'] ?? 0) + (float)($item['valor_set'] ?? 0) + 
                                                       (float)($item['valor_out'] ?? 0) + (float)($item['valor_nov'] ?? 0) + (float)($item['valor_dez'] ?? 0);
                            }
                        @endphp
                        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm dark:bg-emerald-900/20 dark:border-emerald-800">
                            <span class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Total Previsto Geral</span>
                            <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">R$ {{ number_format($totalGeralLivewire, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- PLANILHA INTERATIVA (SCROLL HORIZONTAL) --}}
                    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                        <div class="overflow-x-auto custom-scrollbar pb-2">
                            <table class="w-full text-left border-collapse min-w-[1500px]">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900/50 dark:border-gray-700">
                                        <th class="p-3 text-[10px] font-bold uppercase text-gray-500 w-[250px] sticky left-0 z-10 bg-gray-50 dark:bg-gray-900/50 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Item / Descrição</th>
                                        
                                        @php
                                            $mesesKeys = [
                                                1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun',
                                                7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'
                                            ];
                                        @endphp
                                        
                                        @foreach($mesesKeys as $num => $sigla)
                                            @php
                                                // O bloqueio inteligente passa a analisar apenas se o Ano do orçamento é antigo
                                                $isPassado = ($orcamentoSelecionado->ano < $anoSimulacao);
                                            @endphp
                                            <th class="p-3 text-[10px] font-bold uppercase w-[120px] {{ $isPassado ? 'text-gray-400 bg-gray-100/50 dark:bg-gray-800/80' : 'text-gray-600 dark:text-gray-300' }}">
                                                {{ ucfirst($sigla) }} {!! $isPassado ? '<i class="ph-fill ph-lock-key"></i>' : '' !!}
                                            </th>
                                        @endforeach
                                        
                                        <th class="p-3 text-[10px] font-bold uppercase text-gray-500 w-[60px] text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($itensOrcamento as $index => $item)
                                        <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50/30 transition-colors">
                                            <td class="p-2 sticky left-0 z-10 bg-white dark:bg-gray-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                                <input type="text" wire:model="itensOrcamento.{{ $index }}.descricao" placeholder="Nome do item..." class="w-full text-xs font-bold text-gray-800 dark:text-white rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600" @if($isLockedGlobal) disabled @endif>
                                            </td>
                                            
                                            @foreach($mesesKeys as $num => $sigla)
                                                @php
                                                    $isPassado = ($orcamentoSelecionado->ano < $anoSimulacao);
                                                    $isDisabled = $isLockedGlobal || $isPassado;
                                                @endphp
                                                <td class="p-2 {{ $isDisabled ? 'bg-gray-50/50 dark:bg-gray-900/30 opacity-60' : '' }}">
                                                    <input type="number" step="0.01" wire:model.live.debounce.500ms="itensOrcamento.{{ $index }}.valor_{{ $sigla }}" class="w-full text-xs font-medium text-right text-gray-900 dark:text-gray-200 rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600" @if($isDisabled) disabled @endif>
                                                </td>
                                            @endforeach

                                            <td class="p-2 text-center">
                                                @if(!$isLockedGlobal)
                                                    <button type="button" wire:click="removerItem({{ $index }})" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition" title="Remover Item"><i class="ph-bold ph-trash text-base"></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    {{-- CÁLCULO DE TOTAIS E VARIAÇÕES --}}
                                    @php
                                        $totaisMensais = [];
                                        foreach($mesesKeys as $num => $sigla) {
                                            $totaisMensais[$num] = collect($itensOrcamento)->sum("valor_$sigla");
                                        }
                                    @endphp

                                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                                        <td class="p-4 sticky left-0 z-10 bg-gray-50 dark:bg-gray-900/50 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-300">Total Mensal</span>
                                                @if(!$isLockedGlobal)
                                                    <button type="button" wire:click="adicionarItem" class="px-3 py-1 bg-purpura-100 text-purpura-700 hover:bg-purpura-200 text-[10px] font-bold uppercase rounded-lg border border-purpura-200 shadow-sm transition"><i class="ph-bold ph-plus"></i> Linha</button>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        @php $valorAnterior = 0; @endphp
                                        @foreach($mesesKeys as $num => $sigla)
                                            @php
                                                $valorAtual = $totaisMensais[$num];
                                                $variacao = $num === 1 ? 0 : $valorAtual - $valorAnterior;
                                                $percVariacao = $valorAnterior > 0 ? ($variacao / $valorAnterior) * 100 : ($variacao > 0 ? 100 : 0);
                                                $valorAnterior = $valorAtual;
                                            @endphp
                                            <td class="p-3 text-right">
                                                <div class="text-sm font-black text-gray-900 dark:text-white mb-1">R$ {{ number_format($valorAtual, 2, ',', '.') }}</div>
                                                
                                                @if($num > 1 && $variacao !== 0)
                                                    <div class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded {{ $variacao > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}" title="Variação em relação ao mês anterior">
                                                        {!! $variacao > 0 ? '<i class="ph-bold ph-trend-up"></i>' : '<i class="ph-bold ph-trend-down"></i>' !!}
                                                        R$ {{ number_format(abs($variacao), 2, ',', '.') }} ({{ number_format($percVariacao, 1, ',', '.') }}%)
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RODAPÉ DE AÇÕES GERAIS --}}
                <div class="p-5 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 flex justify-between items-center gap-4 shrink-0">
                    <span class="text-xs text-gray-500 font-medium hidden sm:block">Ações disponíveis de acordo com a sua permissão.</span>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        @if(!$isLockedGlobal)
                            <button wire:click="salvarOrcamento" class="btn btn--secondary btn--medium flex-1 sm:flex-auto border-gray-300">Gravar Edição</button>
                            <button wire:click="finalizarOrcamento" class="btn btn--primary btn--medium bg-blue-600 border-none hover:bg-blue-700 flex-1 sm:flex-auto shadow-sm" onclick="confirm('Ao submeter para aprovação, este orçamento será trancado para novas edições. Deseja continuar?') || event.stopImmediatePropagation()">
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
</div>