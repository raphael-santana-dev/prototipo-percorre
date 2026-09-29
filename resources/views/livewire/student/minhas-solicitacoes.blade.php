<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Minhas Solicitações</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Acompanhe o histórico e o status dos seus pedidos à secretaria.</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 shadow-sm transition flex items-center gap-2">
            <i class="ph-bold ph-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Data / Hora</th>
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Tipo do Pedido</th>
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider">O seu pedido</th>
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($solicitacoes as $solic)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                            {{ $solic->created_at->format('d/m/Y') }} <span class="text-xs text-gray-400">{{ $solic->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-900 dark:text-slate-100">
                            {{ $solic->tema === 'alteracao_academica' ? 'Alteração Acadêmica' : str_replace('_', ' ', $solic->tema) }}
                        </td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $solic->justificativa }}">
                            "{{ $solic->justificativa }}"
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($solic->status === 'aprovada')
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] font-bold uppercase tracking-wider rounded-md">Aprovada</span>
                            @elseif($solic->status === 'rejeitada')
                                <span class="px-3 py-1 bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider rounded-md">Reprovada</span>
                            @else
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-[10px] font-bold uppercase tracking-wider rounded-md">Em Análise</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="abrirDetalhes({{ $solic->id }})" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-purpura-600 rounded-lg text-xs font-bold transition shadow-sm">
                                Ver Detalhes
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                            <i class="ph-fill ph-tray text-4xl mb-2 text-gray-300 block"></i>
                            Nenhuma solicitação encontrada no seu histórico.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($solicitacoes->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $solicitacoes->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL DE DETALHES -->
    @if($modalDetalhes && $solicitacaoAtiva)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200 dark:border-gray-700 my-8">
                
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 rounded-t-2xl">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-clock-counter-clockwise text-purpura-500 text-xl"></i> Histórico do Pedido
                    </h3>
                    <button wire:click="$set('modalDetalhes', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="space-y-4">
                        <!-- Pedido Inicial -->
                        <div class="relative pl-6 border-l-2 border-gray-200 dark:border-gray-700 pb-2">
                            <span class="absolute -left-2 top-0 bg-white dark:bg-gray-800 p-1"><i class="ph-fill ph-user text-gray-400"></i></span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">O seu pedido • {{ $solicitacaoAtiva->created_at->format('d/m/Y H:i') }}</span>
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 font-medium italic">
                                "{{ $solicitacaoAtiva->justificativa }}"
                            </div>
                        </div>

                        <!-- Resposta da Coordenação -->
                        <div class="relative pl-6 border-l-2 border-gray-200 dark:border-gray-700">
                            <span class="absolute -left-2 top-0 bg-white dark:bg-gray-800 p-1">
                                @if($solicitacaoAtiva->status === 'aprovada')
                                    <i class="ph-fill ph-check-circle text-green-500"></i>
                                @elseif($solicitacaoAtiva->status === 'rejeitada')
                                    <i class="ph-fill ph-x-circle text-red-500"></i>
                                @else
                                    <i class="ph-fill ph-hourglass text-yellow-500"></i>
                                @endif
                            </span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Resposta da Coordenação • {{ $solicitacaoAtiva->updated_at->format('d/m/Y H:i') }}</span>
                            
                            @if($solicitacaoAtiva->status === 'pendente')
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl border border-yellow-100 dark:border-yellow-800 text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                                    A sua solicitação encontra-se na fila de análise. Aguarde o retorno da nossa equipa.
                                </div>
                            @else
                                <div class="bg-purpura-50/50 dark:bg-purpura-900/20 p-4 rounded-xl border border-purpura-100 dark:border-purpura-800 text-sm text-gray-800 dark:text-gray-200 font-medium">
                                    {!! nl2br(e($solicitacaoAtiva->resposta_admin ?: 'Nenhum feedback adicional foi registado.')) !!}
                                </div>
                                <span class="text-[10px] text-gray-400 block mt-2 font-bold">Analisado por: {{ $solicitacaoAtiva->responsavel->name ?? 'Equipa de Atendimento' }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 rounded-b-2xl">
                    <button wire:click="$set('modalDetalhes', false)" class="px-6 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 shadow-sm transition">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>