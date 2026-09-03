<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Central de Solicitações" icon="ph ph-envelope-open" badge="Helpdesk">
        <x-slot name="filters">
            <div class="w-full md:w-1/4">
                <select wire:model.live="filtroStatus" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes</option>
                    <option value="aprovada">Aprovadas</option>
                    <option value="rejeitada">Rejeitadas</option>
                    <option value="auto_aprovada">Auto-Aprovadas (Log)</option>
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase">Solicitante</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase">Tema / Ação Requerida</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase text-center">Status</th>
                        <th class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Análise</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($solicitacoes as $req)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-4">
                                <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $req->solicitante->name ?? $req->solicitante->nome ?? 'Usuário' }}</div>
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">{{ class_basename($req->solicitante_type) }}</div>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-gray-700 dark:text-gray-300 text-sm block">
                                    {{ str_replace('_', ' ', $req->tema) }}
                                </span>
                                <span class="text-xs text-gray-500 block mt-1 max-w-xs truncate" title="{{ $req->justificativa }}">
                                    "{{ $req->justificativa }}"
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @if($req->status === 'pendente') <span class="px-2.5 py-1 bg-yellow-50 text-yellow-700 text-[10px] font-bold uppercase rounded border border-yellow-200">Pendente</span>
                                @elseif($req->status === 'aprovada') <span class="px-2.5 py-1 bg-green-50 text-green-700 text-[10px] font-bold uppercase rounded border border-green-200">Aprovada</span>
                                @elseif($req->status === 'auto_aprovada') <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold uppercase rounded border border-blue-200">Aprovada (Sistema)</span>
                                @else <span class="px-2.5 py-1 bg-red-50 text-red-700 text-[10px] font-bold uppercase rounded border border-red-200">Recusada</span>
                                @endif
                                <div class="text-[10px] text-gray-400 mt-1">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="p-4 text-right">
                                @if($req->status === 'pendente' && auth()->user()->hasRole('dev|admin|professor'))
                                    <button wire:click="abrirResposta({{ $req->id }}, 'aprovar')" class="px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-600 hover:text-white rounded text-xs font-bold transition border border-green-200 mr-2">Aprovar</button>
                                    <button wire:click="abrirResposta({{ $req->id }}, 'rejeitar')" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white rounded text-xs font-bold transition border border-red-200">Recusar</button>
                                @else
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Analisado por ID {{ $req->responsavel_id ?? 'Sistema' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">Nenhuma solicitação encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">{{ $solicitacoes->links() }}</div>
    </div>

    <!-- MODAL DE RESPOSTA -->
    @if($modalResposta)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">
                    {{ $acaoResposta === 'aprovar' ? 'Confirmar Aprovação' : 'Confirmar Recusa' }}
                </h3>
                <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg mb-4 text-sm text-gray-600 dark:text-gray-400 italic border border-gray-200 dark:border-gray-700">
                    "{{ $solicitacaoAtiva->justificativa }}"
                </div>
                
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Feedback ao solicitante (obrigatório):</label>
                <textarea wire:model="textoResposta" rows="3" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 text-sm mb-4"></textarea>
                @error('textoResposta') <span class="text-red-500 text-xs font-bold -mt-2 mb-4 block">{{ $message }}</span> @enderror

                <div class="flex justify-end gap-2">
                    <button wire:click="$set('modalResposta', false)" class="px-4 py-2 border rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                    <button wire:click="confirmarResposta" class="px-4 py-2 {{ $acaoResposta === 'aprovar' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white rounded-lg text-xs font-bold shadow-sm">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>