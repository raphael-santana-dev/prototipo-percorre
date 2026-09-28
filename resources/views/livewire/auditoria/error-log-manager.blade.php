<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Central de Exceções e Erros (Sistema)" 
        icon="ph ph-bug"
        badge="">
        <x-slot name="actions">
            <button wire:click="limparTodos" class="px-4 py-2 text-sm font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg shadow-sm hover:bg-red-100 transition-colors flex items-center gap-2" onclick="confirm('Tem a certeza que deseja limpar todos os logs de erros?') || event.stopImmediatePropagation()">
                <i class="ph-bold ph-trash"></i> Limpar Histórico
            </button>
        </x-slot>

        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pesquisar</label>
                    <input wire:model.live.debounce.300ms="filtroBusca" type="text" placeholder="Mensagem ou ficheiro..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo de Erro</label>
                    <select wire:model.live="filtroTipo" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos os Tipos</option>
                        <option value="Requisição HTTP">Requisição HTTP</option>
                        <option value="Background Job / CLI">Background Job / CLI</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Código HTTP</label>
                    <select wire:model.live="filtroCodigo" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos</option>
                        <option value="500">500 (Erro Interno)</option>
                        <option value="403">403 (Proibido)</option>
                        <option value="404">404 (Não Encontrado)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Estado</label>
                    <select wire:model.live="filtroResolvido" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="0">Por Resolver</option>
                        <option value="1">Resolvidos</option>
                        <option value="">Todos</option>
                    </select>
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                    <tr>
                        <th class="p-4 w-16">Código</th>
                        <th class="p-4">Mensagem / Exceção</th>
                        <th class="p-4">Ficheiro / Linha</th>
                        <th class="p-4">Utilizador</th>
                        <th class="p-4">Data / Hora</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors {{ $log->resolvido ? 'opacity-60 bg-gray-50/50' : '' }}">
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-xs font-bold font-mono {{ $log->http_code == 500 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $log->http_code ?? 'CLI' }}
                                </span>
                            </td>
                            <td class="p-4 font-medium text-gray-900 max-w-md truncate" title="{{ $log->mensagem }}">
                                {{ Str::limit($log->mensagem, 80) }}
                                @if($log->url)
                                    <div class="text-[11px] text-gray-400 truncate mt-0.5">{{ $log->metodo_http }} • {{ $log->url }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-xs font-mono text-gray-600 max-w-xs truncate" title="{{ $log->arquivo }}">
                                {{ basename($log->arquivo) }}:<span class="text-purpura-600 font-bold">{{ $log->linha }}</span>
                            </td>
                            <td class="p-4 text-xs text-gray-700 whitespace-nowrap">
                                {{ $log->user->name ?? 'Convidado / Sistema' }}
                            </td>
                            <td class="p-4 text-xs text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="p-4 text-center whitespace-nowrap">
                                @if($log->resolvido)
                                    <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-1 rounded">Resolvido</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-1 rounded">Pendente</span>
                                @endif
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">
                                <button wire:click="verDetalhes({{ $log->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Ver Stack Trace Completo">
                                    <i class="ph-bold ph-eye text-lg"></i>
                                </button>
                                <button wire:click="alternarResolvido({{ $log->id }})" class="p-1.5 text-green-600 hover:bg-green-50 rounded" title="Marcar como Resolvido/Pendente">
                                    <i class="ph-bold ph-check-circle text-lg"></i>
                                </button>
                                <button wire:click="eliminarErro({{ $log->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Eliminar Registo">
                                    <i class="ph-bold ph-trash text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400 font-medium">
                                <i class="ph ph-check-circle text-4xl text-green-500 mb-2 block"></i>
                                Nenhum erro registado no sistema. Excelente!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-center">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    @if($modalDetalhesAberto && $errorDetalhes)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            <i class="ph-bold ph-bug text-red-600"></i> Detalhes da Exceção #{{ $errorDetalhes->id }}
                        </h3>
                        <p class="text-xs text-gray-500">{{ $errorDetalhes->tipo }} • Código: {{ $errorDetalhes->http_code ?? 'N/A' }}</p>
                    </div>
                    <button wire:click="$set('modalDetalhesAberto', false)" class="text-gray-400 hover:text-gray-700"><i class="ph-bold ph-x text-xl"></i></button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 text-sm">
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg text-red-800 font-mono text-xs">
                        <span class="font-bold uppercase block mb-1">Mensagem de Erro:</span>
                        {{ $errorDetalhes->mensagem }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="bg-gray-50 p-3 rounded border border-gray-200">
                            <span class="font-bold text-gray-500 block">Ficheiro Afetado:</span>
                            <span class="font-mono text-gray-800 break-all">{{ $errorDetalhes->arquivo }} (Linha {{ $errorDetalhes->linha }})</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded border border-gray-200">
                            <span class="font-bold text-gray-500 block">URL / Endpoint:</span>
                            <span class="font-mono text-gray-800 break-all">{{ $errorDetalhes->url ?? 'Execução em Consola (CLI)' }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-bold text-gray-700 uppercase mb-1">Stack Trace (Rastreio Completo):</span>
                        <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-[11px] overflow-x-auto max-h-96 custom-scrollbar whitespace-pre">
                            {{ $errorDetalhes->stack_trace }}
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="alternarResolvido({{ $errorDetalhes->id }})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded-lg shadow-sm">
                        {{ $errorDetalhes->resolvido ? 'Marcar como Pendente' : 'Marcar como Resolvido' }}
                    </button>
                    <button wire:click="$set('modalDetalhesAberto', false)" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-lg">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>