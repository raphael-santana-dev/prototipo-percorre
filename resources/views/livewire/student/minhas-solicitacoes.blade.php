<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 font-sans">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Minhas Solicitações</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Acompanhe o histórico e o status dos seus pedidos de alteração junto à secretaria.
            </p>
        </div>
        <a href="{{ route('student.dashboard') }}" wire:navigate class="px-3.5 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition inline-flex items-center gap-1.5 w-max">
            <i class="ph ph-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Data / Hora</th>
                        <th class="px-5 py-3">Tipo da Solicitação</th>
                        <th class="px-5 py-3">Unidade / Curso / Turno Solicitado</th>
                        <th class="px-5 py-3">Justificativa</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($solicitacoes as $solic)
                        @php $payload = is_array($solic->payload) ? $solic->payload : []; @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition">
                            <td class="px-5 py-3.5 font-mono text-gray-600 dark:text-gray-300">
                                {{ $solic->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900 dark:text-white">
                                {{ $solic->tema === 'alteracao_academica' ? 'Troca de Unidade / Curso / Turno' : ucfirst(str_replace('_', ' ', $solic->tema)) }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300">
                                @if(!empty($payload['nova_unidade_nome']) || !empty($payload['novo_curso_nome']))
                                    <span class="font-semibold">{{ $payload['nova_unidade_nome'] ?? '-' }}</span> • {{ $payload['novo_curso_nome'] ?? '-' }} ({{ $payload['novo_turno_nome'] ?? '-' }})
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 max-w-xs truncate" title="{{ $solic->justificativa }}">
                                "{{ $solic->justificativa }}"
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($solic->status === 'aprovada')
                                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded">Aprovada</span>
                                @elseif($solic->status === 'rejeitada')
                                    <span class="px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold uppercase rounded">Reprovada</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase rounded">Em Análise</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <button type="button" wire:click="abrirDetalhes({{ $solic->id }})" class="px-3 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded text-xs font-semibold transition">
                                    Detalhes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400 text-xs">
                                Nenhuma solicitação encontrada no seu histórico.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($solicitacoes->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                {{ $solicitacoes->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL MINIMALISTA DE DETALHES DA SOLICITAÇÃO --}}
    @if($modalDetalhes && $solicitacaoAtiva)
        @php $payAtivo = is_array($solicitacaoAtiva->payload) ? $solicitacaoAtiva->payload : []; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-xl border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        Detalhes da Solicitação #{{ $solicitacaoAtiva->id }}
                    </h3>
                    <button type="button" wire:click="$set('modalDetalhes', false)" class="text-gray-400 hover:text-gray-700 p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    @if(!empty($payAtivo['nova_unidade_nome']))
                        <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 space-y-1.5">
                            <div>
                                <span class="text-gray-400 uppercase font-semibold text-[10px] block">Vínculo Anterior</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $payAtivo['unidade_anterior_nome'] ?? '-' }} • {{ $payAtivo['curso_anterior_nome'] ?? '-' }} ({{ $payAtivo['turno_anterior_nome'] ?? '-' }})
                                </span>
                            </div>
                            <div class="pt-1.5 border-t border-gray-200 dark:border-gray-800">
                                <span class="text-purpura-600 uppercase font-semibold text-[10px] block">Novo Vínculo Solicitado</span>
                                <span class="font-bold text-gray-900 dark:text-white">
                                    {{ $payAtivo['nova_unidade_nome'] ?? '-' }} • {{ $payAtivo['novo_curso_nome'] ?? '-' }} ({{ $payAtivo['novo_turno_nome'] ?? '-' }})
                                </span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">
                            Sua Justificativa ({{ $solicitacaoAtiva->created_at->format('d/m/Y H:i') }})
                        </span>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300">
                            "{{ $solicitacaoAtiva->justificativa }}"
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">
                            Parecer da Equipe ({{ $solicitacaoAtiva->updated_at->format('d/m/Y H:i') }})
                        </span>
                        @if($solicitacaoAtiva->status === 'pendente')
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">
                                Sua solicitação está em análise pela equipe responsável.
                            </div>
                        @else
                            <div class="p-3 rounded-lg border {{ $solicitacaoAtiva->status === 'aprovada' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900' }}">
                                {!! nl2br(e($solicitacaoAtiva->resposta_admin ?: 'Solicitação processada pela equipe de atendimento.')) !!}
                            </div>
                            <span class="text-[11px] text-gray-400 block mt-1.5">
                                Analisado por: {{ $solicitacaoAtiva->responsavel->name ?? 'Equipe de Atendimento' }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="px-5 py-3.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end">
                    <button type="button" wire:click="$set('modalDetalhes', false)" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>