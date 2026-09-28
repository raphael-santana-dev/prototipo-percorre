<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Central de Solicitações" icon="ph ph-envelope-open" badge="Helpdesk">
        <x-slot name="filters">
            <div class="w-full md:w-1/4">
                <select wire:model.live="filtroStatus" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white font-bold">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes (Em Análise)</option>
                    <option value="aprovada">Aprovadas</option>
                    <option value="rejeitada">Rejeitadas</option>
                    <option value="auto_aprovada">Auto-Aprovadas (Log)</option>
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Solicitante</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tema / Motivo</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status e Data</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @forelse($solicitacoes as $req)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="ph-fill ph-user-circle text-gray-400 text-lg"></i> {{ $req->solicitante->name ?? $req->solicitante->nome ?? 'Usuário' }}
                                </div>
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5 ml-6">{{ class_basename($req->solicitante_type) }}</div>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-purpura-700 dark:text-purpura-400 text-sm block mb-1">
                                    {{ $req->tema === 'alteracao_academica' ? 'Alteração de Unidade/Curso/Turno' : str_replace('_', ' ', $req->tema) }}
                                </span>
                                <span class="text-xs text-gray-500 block max-w-sm truncate" title="{{ $req->justificativa }}">
                                    "{{ $req->justificativa }}"
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @if($req->status === 'pendente') <span class="px-2.5 py-1 bg-yellow-50 text-yellow-700 text-[10px] font-bold uppercase rounded border border-yellow-200">Pendente</span>
                                @elseif($req->status === 'aprovada') <span class="px-2.5 py-1 bg-green-50 text-green-700 text-[10px] font-bold uppercase rounded border border-green-200">Aprovada</span>
                                @elseif($req->status === 'auto_aprovada') <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold uppercase rounded border border-blue-200">Auto-Aprovada</span>
                                @else <span class="px-2.5 py-1 bg-red-50 text-red-700 text-[10px] font-bold uppercase rounded border border-red-200">Recusada</span>
                                @endif
                                <div class="text-[10px] font-bold text-gray-400 mt-1.5">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="p-4 text-right">
                                @if($req->status === 'pendente' && auth()->user()->hasRole('dev|admin|professor'))
                                    <button wire:click="abrirResposta({{ $req->id }}, 'aprovar')" class="px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-600 hover:text-white rounded text-xs font-bold transition border border-green-200 shadow-sm mr-2">Aprovar</button>
                                    <button wire:click="abrirResposta({{ $req->id }}, 'rejeitar')" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white rounded text-xs font-bold transition border border-red-200 shadow-sm">Recusar</button>
                                @else
                                    <button wire:click="abrirResposta({{ $req->id }}, 'visualizar')" class="px-3 py-1.5 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded text-xs font-bold transition border border-gray-200 shadow-sm">
                                        Ver Histórico
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-10 text-center text-gray-500 italic"><i class="ph-fill ph-envelope-open text-3xl mb-2 text-gray-300 block"></i> Nenhuma solicitação pendente no momento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($solicitacoes->hasPages())
            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">{{ $solicitacoes->links() }}</div>
        @endif
    </div>

    @if($modalResposta && $solicitacaoAtiva)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200 dark:border-gray-700 my-8">
                
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 rounded-t-2xl">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($acaoResposta === 'visualizar') <i class="ph-fill ph-clock-counter-clockwise text-blue-500 text-xl"></i> Histórico da Solicitação
                        @elseif($acaoResposta === 'aprovar') <i class="ph-fill ph-check-circle text-green-500 text-xl"></i> Confirmar Aprovação
                        @else <i class="ph-fill ph-x-circle text-red-500 text-xl"></i> Confirmar Recusa @endif
                    </h3>
                    <button wire:click="$set('modalResposta', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Resumo do Solicitante -->
                    @if(!empty($detalhesEstudante))
                        <div class="bg-blue-50/50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800 flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <span class="block text-[10px] font-bold text-blue-500 uppercase mb-1">Candidato</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $detalhesEstudante['nome'] }}</span>
                                <span class="text-xs text-gray-500">{{ $detalhesEstudante['email'] }}</span>
                            </div>
                            <div class="flex-1 border-t sm:border-t-0 sm:border-l border-blue-200 dark:border-blue-700 pt-3 sm:pt-0 sm:pl-4">
                                <span class="block text-[10px] font-bold text-blue-500 uppercase mb-1">Combinação Solicitada</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white block truncate" title="{{ $detalhesEstudante['novo_curso'] }}">{{ $detalhesEstudante['novo_curso'] }}</span>
                                <span class="text-xs text-gray-500 truncate block">{{ $detalhesEstudante['nova_unidade'] }} • {{ $detalhesEstudante['novo_turno'] }}</span>
                            </div>
                        </div>

                        <!-- Alertas Dinâmicos -->
                        @if($acaoResposta !== 'visualizar')
                            @if($alertaUF)
                                <div class="bg-orange-50 border border-orange-200 p-3 rounded-lg flex items-start gap-3">
                                    <i class="ph-fill ph-warning text-orange-500 text-xl mt-0.5 shrink-0"></i>
                                    <div>
                                        <h4 class="text-xs font-bold text-orange-800 uppercase">Atenção Logística (Divergência de Estado)</h4>
                                        <p class="text-[11px] text-orange-700 font-medium mt-0.5">O estado atual do aluno ({{ $detalhesEstudante['estado_atual'] }}) é diferente do estado da Unidade solicitada ({{ $detalhesEstudante['estado_unidade'] }}).</p>
                                    </div>
                                </div>
                            @endif

                            @if($alertaVagas)
                                <div class="bg-red-50 border border-red-200 p-3 rounded-lg flex items-start gap-3">
                                    <i class="ph-fill ph-prohibit text-red-500 text-xl mt-0.5 shrink-0"></i>
                                    <div>
                                        <h4 class="text-xs font-bold text-red-800 uppercase">Capacidade Excedida</h4>
                                        <p class="text-[11px] text-red-700 font-medium mt-0.5">Não há vagas ociosas para essa turma no momento. {{ $detalhesVaga }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="bg-green-50 border border-green-200 p-2.5 rounded-lg flex items-center gap-2">
                                    <i class="ph-fill ph-check-circle text-green-500 text-lg"></i>
                                    <span class="text-[11px] text-green-700 font-bold uppercase">{{ $detalhesVaga }}</span>
                                </div>
                            @endif
                        @endif
                    @endif

                    <!-- Histórico da Conversa -->
                    <div class="space-y-4">
                        <div class="relative pl-6 border-l-2 border-gray-200 dark:border-gray-700 pb-2">
                            <span class="absolute -left-2 top-0 bg-white dark:bg-gray-800 p-1"><i class="ph-fill ph-user text-gray-400"></i></span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Pedido Inicial • {{ $solicitacaoAtiva->created_at->format('d/m/Y H:i') }}</span>
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 font-medium italic">
                                "{{ $solicitacaoAtiva->justificativa }}"
                            </div>
                        </div>

                        @if($acaoResposta === 'visualizar')
                            <div class="relative pl-6 border-l-2 border-gray-200 dark:border-gray-700">
                                <span class="absolute -left-2 top-0 bg-white dark:bg-gray-800 p-1"><i class="ph-fill ph-shield-check text-purpura-500"></i></span>
                                <span class="text-[10px] font-bold text-purpura-600 uppercase tracking-wider block mb-1">Resposta Oficial • {{ $solicitacaoAtiva->updated_at->format('d/m/Y H:i') }}</span>
                                <div class="bg-purpura-50/50 dark:bg-purpura-900/20 p-4 rounded-xl border border-purpura-100 dark:border-purpura-800 text-sm text-gray-800 dark:text-gray-200 font-medium">
                                    {!! nl2br(e($solicitacaoAtiva->resposta_admin ?: 'Nenhum feedback foi registrado.')) !!}
                                </div>
                                <span class="text-[10px] text-gray-400 block mt-2 font-bold">Analisado por: {{ $solicitacaoAtiva->responsavel->name ?? 'Sistema' }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Formulário de Resposta -->
                    @if($acaoResposta !== 'visualizar')
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">O seu feedback para o aluno <span class="text-red-500">*</span></label>
                            <textarea wire:model="textoResposta" rows="4" placeholder="Escreva aqui a justificativa ou orientações..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-purpura-500 text-sm shadow-sm"></textarea>
                            @error('textoResposta') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 rounded-b-2xl">
                    <button wire:click="$set('modalResposta', false)" class="px-5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 shadow-sm transition">
                        Fechar
                    </button>
                    @if($acaoResposta !== 'visualizar')
                        <button wire:click="confirmarResposta" class="px-6 py-2.5 {{ $acaoResposta === 'aprovar' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 transition hover:-translate-y-0.5">
                            <i class="ph-bold ph-paper-plane-tilt"></i> {{ $acaoResposta === 'aprovar' ? 'Aprovar Pedido' : 'Recusar Pedido' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>