<div class="w-full relative font-sans">
    
    <x-page-header title="Central de Solicitações" icon="ph ph-envelope-open" badge="Atendimento">
        <x-slot name="filters">
            <div class="w-full md:w-64">
                <select wire:model.live="filtroStatus" class="w-full text-xs border-gray-300 rounded-lg focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white font-semibold py-2">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes (Em Análise)</option>
                    <option value="aprovada">Aprovadas</option>
                    <option value="rejeitada">Reprovadas</option>
                    <option value="auto_aprovada">Auto-Aprovadas (Log)</option>
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mt-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Solicitante</th>
                        <th class="px-5 py-3">Tema / Detalhes do Pedido</th>
                        <th class="px-5 py-3 text-center">Status e Data</th>
                        <th class="px-5 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($solicitacoes as $req)
                        @php
                            $payReq = is_array($req->payload) ? $req->payload : [];
                            $podeAvaliarLinha = $this->usuarioPodeAvaliar($req);
                        @endphp
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $req->solicitante->name ?? $req->solicitante->nome ?? 'Usuário' }}
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $req->solicitante->email ?? class_basename($req->solicitante_type) }}
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-gray-900 dark:text-white block">
                                    {{ $req->tema === 'alteracao_academica' ? 'Troca de Unidade / Curso / Turno' : ucfirst(str_replace('_', ' ', $req->tema)) }}
                                </span>
                                @if($req->tema === 'alteracao_academica' && !empty($payReq['nova_unidade_nome']))
                                    <span class="text-[11px] text-purpura-600 dark:text-purpura-400 font-semibold block mt-0.5">
                                        Para: {{ $payReq['nova_unidade_nome'] }} • {{ $payReq['novo_curso_nome'] ?? '-' }} ({{ $payReq['novo_turno_nome'] ?? '-' }})
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500 block max-w-md truncate mt-0.5" title="{{ $req->justificativa }}">
                                    Motivo: "{{ $req->justificativa }}"
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($req->status === 'pendente')
                                    <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold uppercase rounded border border-amber-200">Pendente</span>
                                @elseif($req->status === 'aprovada')
                                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase rounded border border-emerald-200">Aprovada</span>
                                @elseif($req->status === 'auto_aprovada')
                                    <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold uppercase rounded border border-blue-200">Auto-Aprovada</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold uppercase rounded border border-red-200">Reprovada</span>
                                @endif
                                <div class="text-[11px] font-mono text-gray-400 mt-1">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if($req->status === 'pendente' && $podeAvaliarLinha)
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" wire:click="abrirResposta({{ $req->id }}, 'aprovar')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition">
                                            Aprovar
                                        </button>
                                        <button type="button" wire:click="abrirResposta({{ $req->id }}, 'rejeitar')" class="px-3 py-1.5 bg-white dark:bg-gray-900 text-red-600 hover:bg-red-50 border border-red-200 dark:border-red-800 rounded-lg text-xs font-semibold transition">
                                            Reprovar
                                        </button>
                                    </div>
                                @else
                                    <button type="button" wire:click="abrirResposta({{ $req->id }}, 'visualizar')" class="px-3 py-1.5 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg text-xs font-semibold transition border border-gray-200 dark:border-gray-700">
                                        Detalhes
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-gray-400 text-xs">
                                Nenhuma solicitação encontrada para o filtro selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($solicitacoes->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">{{ $solicitacoes->links() }}</div>
        @endif
    </div>

    {{-- MODAL MINIMALISTA DE APROVAÇÃO / REPROVAÇÃO / DETALHES --}}
    @if($modalResposta && $solicitacaoAtiva)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl border border-gray-200 dark:border-gray-700 my-8 overflow-hidden">
                
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        @if($acaoResposta === 'visualizar')
                            Detalhes da Solicitação #{{ $solicitacaoAtiva->id }}
                        @elseif($acaoResposta === 'aprovar')
                            Aprovar Solicitação de Troca #{{ $solicitacaoAtiva->id }}
                        @else
                            Reprovar Solicitação #{{ $solicitacaoAtiva->id }}
                        @endif
                    </h3>
                    <button type="button" wire:click="$set('modalResposta', false)" class="text-gray-400 hover:text-gray-700 p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>
                
                <div class="p-5 space-y-4 text-xs">
                    @if(!empty($detalhesEstudante))
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-[10px] font-semibold text-gray-400 uppercase mb-0.5">Estudante (Inscrição #{{ $detalhesEstudante['inscricao_id'] }})</span>
                                <span class="font-bold text-gray-900 dark:text-white block">{{ $detalhesEstudante['nome'] }}</span>
                                <span class="text-gray-500 block">{{ $detalhesEstudante['email'] }}</span>
                                <span class="text-[11px] text-gray-500 mt-1 block">
                                    UF da Inscrição: <strong>{{ $detalhesEstudante['estado_atual'] }}</strong>
                                    @if($detalhesEstudante['quero_outro_estado'])
                                        <span class="ml-1 px-1.5 py-0.2 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[10px] font-semibold">Marcou "Quero em outro estado"</span>
                                    @endif
                                </span>
                            </div>

                            <div class="sm:border-l sm:border-gray-200 sm:dark:border-gray-700 sm:pl-4 space-y-1.5">
                                <div>
                                    <span class="block text-[10px] font-semibold text-gray-400 uppercase">Vínculo Atual</span>
                                    <span class="text-gray-600 dark:text-gray-300">
                                        {{ $detalhesEstudante['unidade_atual'] }} • {{ $detalhesEstudante['curso_atual'] }} ({{ $detalhesEstudante['turno_atual'] }})
                                    </span>
                                </div>
                                <div class="pt-1 border-t border-gray-200 dark:border-gray-800">
                                    <span class="block text-[10px] font-semibold text-purpura-600 uppercase">Novo Vínculo Solicitado</span>
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ $detalhesEstudante['nova_unidade'] }} ({{ $detalhesEstudante['estado_unidade'] }}) • {{ $detalhesEstudante['novo_curso'] }} ({{ $detalhesEstudante['novo_turno'] }})
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($acaoResposta !== 'visualizar')
                            @if($alertaUF)
                                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-2.5">
                                    <i class="ph-fill ph-warning text-amber-600 text-base shrink-0 mt-0.5"></i>
                                    <div>
                                        <span class="font-bold block">Divergência de Estado (UF)</span>
                                        A UF de residência do estudante ({{ $detalhesEstudante['estado_atual'] }}) é diferente da UF da unidade solicitada ({{ $detalhesEstudante['estado_unidade'] }}).
                                    </div>
                                </div>
                            @endif

                            <div class="p-3 rounded-lg border {{ $alertaVagas ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' }} flex items-center gap-2">
                                <i class="ph-fill {{ $alertaVagas ? 'ph-prohibit text-red-600' : 'ph-check-circle text-emerald-600' }} text-base"></i>
                                <span class="font-semibold">{{ $detalhesVaga }}</span>
                            </div>
                        @endif
                    @endif

                    <div>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">
                            Motivo Informado pelo Estudante ({{ $solicitacaoAtiva->created_at->format('d/m/Y H:i') }})
                        </span>
                        <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300">
                            "{{ $solicitacaoAtiva->justificativa }}"
                        </div>
                    </div>

                    @if($acaoResposta === 'visualizar')
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">
                                Parecer da Equipe ({{ $solicitacaoAtiva->updated_at->format('d/m/Y H:i') }})
                            </span>
                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">
                                {!! nl2br(e($solicitacaoAtiva->resposta_admin ?: 'Nenhum parecer registrado.')) !!}
                            </div>
                            <span class="text-[11px] text-gray-400 block mt-1">
                                Analisado por: {{ $solicitacaoAtiva->responsavel->name ?? 'Sistema' }}
                            </span>
                        </div>
                    @else
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Parecer / Feedback para o Estudante <span class="text-red-500">*</span>
                            </label>
                            <textarea wire:model="textoResposta" rows="3" placeholder="Informe a justificativa ou orientações sobre a decisão..." class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg focus:ring-purpura-500"></textarea>
                            @error('textoResposta') <span class="text-red-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <div class="px-5 py-3.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-2">
                    <button type="button" wire:click="$set('modalResposta', false)" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Fechar
                    </button>
                    @if($acaoResposta !== 'visualizar')
                        <button type="button" wire:click="confirmarResposta" class="px-4 py-2 {{ $acaoResposta === 'aprovar' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }} text-white rounded-lg text-xs font-semibold transition">
                            {{ $acaoResposta === 'aprovar' ? 'Confirmar Aprovação e Trocar Vínculo' : 'Confirmar Reprovação' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>