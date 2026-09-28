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
                        <th class="px-6 py-4 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Resposta da Coordenação</th>
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
                            {{ $solic->justificativa }}
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
                        <td class="px-6 py-4 text-slate-700 dark:text-slate-300 max-w-sm truncate font-medium" title="{{ $solic->resposta_admin }}">
                            {{ $solic->resposta_admin ?: 'Aguardando avaliação...' }}
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
</div>