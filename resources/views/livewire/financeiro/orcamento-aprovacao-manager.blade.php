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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" wire:model.live.debounce.500ms="filtroAno" placeholder="Buscar Ano" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
                <input type="text" wire:model.live.debounce.500ms="filtroFilial" placeholder="Buscar Filial" class="rounded-lg border-gray-200 shadow-sm text-sm w-full">
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
                            <span class="text-sm font-bold block">{{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}</span>
                            <span class="text-[10px] text-purpura-600 font-mono block max-w-[200px]">C.C. {{ $orcamento->ccusto ?: '-' }}</span>
                        </td>
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
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Nenhum orçamento pendente encontrado.</td></tr>
                @endforelse
            </x-table>
        </div>
    </div>

    @if($modalAberto && $orcamentoSelecionado)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4 md:p-6 overflow-hidden">
            <div class="bg-gray-50 w-full h-full max-w-[1700px] max-h-full rounded-2xl shadow-2xl flex flex-col border border-gray-200 overflow-hidden" x-data @keydown.escape.window="$wire.fecharModal()">
                
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-white shrink-0">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 flex items-center gap-2"><i class="ph-fill ph-list-magnifying-glass text-purpura-500 text-2xl"></i> Análise de Orçamento - Centro de Custo</h2>
                        <p class="text-xs text-gray-500 mt-1">Aprove ou reprove cada Natureza Financeira individualmente.</p>
                    </div>
                    <button wire:click="fecharModal" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-red-500 shadow-sm transition"><i class="ph-bold ph-x text-lg"></i></button>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    
                    {{-- COLUNA ESQUERDA: Planilha de Valores --}}
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar bg-white">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filial / Ano</span><span class="text-base font-black">{{ $orcamentoSelecionado->filial ?: '-' }} / {{ $orcamentoSelecionado->ano }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Centro de Custo</span><span class="text-sm font-black">{{ $orcamentoSelecionado->centroCusto ? $orcamentoSelecionado->centroCusto->nome : 'Sem Vínculo' }}</span><span class="text-[10px] font-mono text-purpura-600 block">C.C. {{ $orcamentoSelecionado->ccusto ?: '-' }}</span></div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200"><span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Observações do Gestor</span><span class="text-sm font-medium text-gray-600">{{ $orcamentoSelecionado->descricao_despesa ?: 'Sem observações' }}</span></div>
                            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 flex flex-col justify-center"><span class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Total Solicitado</span><span class="text-xl font-black text-emerald-700">R$ {{ number_format($orcamentoSelecionado->valor_total, 2, ',', '.') }}</span></div>
                        </div>

                        <h3 class="font-extrabold text-xs text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Distribuição Solicitada por Natureza Financeira</h3>
                        
                        {{-- TABELA DE NATUREZAS COM APROVAÇÃO POR LINHA --}}
                        <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                            <div class="overflow-x-auto custom-scrollbar pb-2">
                                <table class="w-full text-left border-collapse min-w-[1500px]">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="p-3 text-[10px] font-bold uppercase text-gray-500 w-[250px] sticky left-0 z-20 bg-gray-50 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Natureza Financeira</th>
                                            @php
                                                $mesesKeys = [1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'];
                                            @endphp
                                            @foreach($mesesKeys as $num => $sigla)
                                                <th class="p-3 text-[10px] font-bold uppercase w-[100px] text-gray-600">{{ ucfirst($sigla) }}</th>
                                            @endforeach
                                            <th class="p-3 text-[10px] font-bold uppercase text-gray-900 w-[100px] text-right bg-emerald-50/50">Total</th>
                                            <th class="p-3 text-[10px] font-bold uppercase text-gray-500 w-[150px] text-center">Decisão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orcamentoSelecionado->itens as $item)
                                            <tr class="border-b border-gray-100 hover:bg-blue-50/30 transition-colors">
                                                <td class="p-3 sticky left-0 z-10 bg-white border-r border-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] w-[280px]">
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-bold text-gray-900 truncate w-full" title="{{ $item->descricao }}">{{ $item->descricao }}</span>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-[10px] font-mono text-purpura-600">{{ $item->natureza_codigo ?: 'N/D' }}</span>
                                                            @if($item->status === 'Reprovado')
                                                                <span class="text-[8px] font-bold bg-red-100 text-red-700 px-1.5 py-0.5 rounded uppercase">Reprovado</span>
                                                            @elseif($item->status === 'Aprovado com ressalvas')
                                                                <span class="text-[8px] font-bold bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded uppercase">Ressalvas</span>
                                                            @elseif($item->status === 'Aprovado')
                                                                <span class="text-[8px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded uppercase">Aprovado</span>
                                                            @elseif($item->status === 'Corrigido')
                                                                <span class="text-[8px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded uppercase">Re-Analisar</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                @php $totalLinha = 0; @endphp
                                                @foreach($mesesKeys as $num => $sigla)
                                                    @php 
                                                        $valorAtual = (float)($item->{"valor_$sigla"} ?? 0);
                                                        $totalLinha += $valorAtual;
                                                    @endphp
                                                    <td class="p-3 border-r border-gray-50 text-right">
                                                        <span class="text-xs font-medium {{ $valorAtual == 0 ? 'text-gray-300' : 'text-gray-900' }}">{{ number_format($valorAtual, 2, ',', '.') }}</span>
                                                    </td>
                                                @endforeach
                                                <td class="p-3 text-right text-xs font-black bg-emerald-50/30 text-gray-900">{{ number_format($totalLinha, 2, ',', '.') }}</td>
                                                
                                                {{-- BOTÕES DE APROVAÇÃO POR LINHA --}}
                                                <td class="p-2 border-l border-gray-200 bg-gray-50">
                                                    <div class="flex items-center gap-1 justify-center">
                                                        <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 rounded shadow-sm border border-emerald-200 bg-white transition" title="Aprovar Item"><i class="ph-bold ph-check text-base"></i></button>
                                                        <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Aprovado com ressalvas')" class="p-1.5 text-yellow-600 hover:bg-yellow-50 hover:text-yellow-700 rounded shadow-sm border border-yellow-200 bg-white transition" title="Aprovar com Ressalvas"><i class="ph-bold ph-warning text-base"></i></button>
                                                        <button wire:click="abrirModalAvaliacaoItem({{ $item->id }}, 'Reprovado')" class="p-1.5 text-red-600 hover:bg-red-50 hover:text-red-700 rounded shadow-sm border border-red-200 bg-white transition" title="Reprovar Item"><i class="ph-bold ph-x text-base"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE COMENTÁRIO DO APROVADOR PARA O ITEM --}}
    @if($modalAvaliacaoAberto ?? false)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200">
                <h3 class="text-lg font-black text-gray-900 mb-1 flex items-center gap-2"><i class="ph-fill ph-chat-centered-text text-purpura-500"></i> Justificativa de Decisão</h3>
                <p class="text-xs text-gray-500 mb-4">Avaliando a Natureza como: <strong class="uppercase text-purpura-600">{{ $statusAvaliacao }}</strong>. O gestor receberá este feedback.</p>
                <textarea wire:model="comentarioAvaliacao" rows="4" class="w-full rounded-xl border-gray-300 bg-gray-50 text-sm focus:ring-purpura-500" placeholder="Digite as diretrizes de correção para este item..."></textarea>
                @error('comentarioAvaliacao') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('modalAvaliacaoAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="confirmarAvaliacaoItem" class="btn btn--primary btn--medium bg-gray-900 hover:bg-black border-none shadow-sm">Confirmar Decisão da Linha</button>
                </div>
            </div>
        </div>
    @endif
</div>