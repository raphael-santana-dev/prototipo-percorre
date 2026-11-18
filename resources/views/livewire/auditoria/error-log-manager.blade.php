<div class="w-full relative">
    
    <x-page-header 
        title="Central de Exceções e Erros" 
        subtitle="Monitoramento agrupado de falhas, recorrências e stack traces do sistema"
        icon="ph ph-bug"
        badge="">
        <x-slot name="actions">
            <button type="button"
                    wire:click="limparTodos"
                    class="btn btn--secondary btn--medium !text-red-600 !border-red-200 hover:!bg-red-50"
                    onclick="confirm('Tem a certeza que deseja limpar todos os logs de erros?') || event.stopImmediatePropagation()">
                <i class="ph ph-trash"></i> Limpar Histórico
            </button>
        </x-slot>

        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 w-full">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Pesquisar</label>
                    <input wire:model.live.debounce.300ms="filtroBusca" type="text" placeholder="Mensagem ou arquivo..." class="w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tipo de Erro</label>
                    <select wire:model.live="filtroTipo" class="w-full">
                        <option value="">Todos os Tipos</option>
                        <option value="Requisição HTTP">Requisição HTTP</option>
                        <option value="Background Job / CLI">Background Job / CLI</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Código HTTP</label>
                    <select wire:model.live="filtroCodigo" class="w-full">
                        <option value="">Todos</option>
                        <option value="500">500 (Erro Interno)</option>
                        <option value="403">403 (Proibido)</option>
                        <option value="404">404 (Não Encontrado)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Estado</label>
                    <select wire:model.live="filtroResolvido" class="w-full">
                        <option value="0">Por Resolver (Pendentes + Reabertos)</option>
                        <option value="reabertos">Apenas Reabertos</option>
                        <option value="1">Resolvidos</option>
                        <option value="">Todos</option>
                    </select>
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mt-4">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700 text-[11px] uppercase text-gray-500 font-medium">
                    <tr>
                        <th class="px-4 py-3 w-16">Código</th>
                        <th class="px-4 py-3">Mensagem / Exceção</th>
                        <th class="px-4 py-3">Arquivo / Linha</th>
                        <th class="px-4 py-3 text-center">Ocorrências</th>
                        <th class="px-4 py-3">Última Ocorrência</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($logs as $log)
                        @php
                            $qtdOcorrencias = max(1, (int) ($log->ocorrencias_count ?? 1));
                            $dataUltima = $log->ultima_ocorrencia_em ?? $log->updated_at ?? $log->created_at;
                        @endphp
                        <tr wire:key="err-row-{{ $log->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors {{ $log->resolvido ? 'opacity-60 bg-gray-50/40 dark:bg-gray-900/20' : '' }}">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold font-mono border {{ $log->http_code == 500 ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                    {{ $log->http_code ?? 'CLI' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white max-w-md truncate" title="{{ $log->mensagem }}">
                                {{ Str::limit($log->mensagem, 75) }}
                                @if($log->url)
                                    <div class="text-[11px] text-gray-400 font-mono truncate mt-0.5">{{ $log->metodo_http }} • {{ $log->url }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-xs font-mono text-gray-600 dark:text-gray-300 max-w-xs truncate" title="{{ $log->arquivo }}">
                                {{ basename($log->arquivo) }}:<span class="text-purpura-600 dark:text-purpura-400 font-semibold">{{ $log->linha }}</span>
                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <button type="button"
                                        wire:click="verOcorrencias({{ $log->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border transition {{ $qtdOcorrencias > 1 ? 'bg-purpura-50 text-purpura-700 border-purpura-200 hover:bg-purpura-100 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}"
                                        title="Clique para ver data/hora, IP, usuário e navegador de cada ocorrência">
                                    <i class="ph ph-clock-counter-clockwise"></i>
                                    <span>{{ $qtdOcorrencias }}x</span>
                                </button>
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <div class="font-mono text-gray-900 dark:text-white">{{ $dataUltima ? $dataUltima->format('d/m/Y H:i:s') : '-' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $log->user->name ?? 'Convidado / Sistema' }}</div>
                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($log->resolvido)
                                    <span class="px-2 py-0.5 rounded border text-[11px] font-medium bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                        Resolvido
                                    </span>
                                @elseif($log->reaberto ?? false)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-semibold bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700"
                                          title="Este erro havia sido resolvido anteriormente, mas voltou a ocorrer">
                                        <i class="ph-bold ph-arrow-counter-clockwise"></i> Reaberto
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded border text-[11px] font-medium bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
                                        Pendente
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <button type="button" wire:click="verOcorrencias({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700 rounded transition" title="Ver Histórico de Ocorrências">
                                        <i class="ph ph-list-bullets text-base"></i>
                                    </button>
                                    <button type="button" wire:click="verDetalhes({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700 rounded transition" title="Ver Stack Trace Completo">
                                        <i class="ph ph-eye text-base"></i>
                                    </button>
                                    <button type="button" wire:click="alternarResolvido({{ $log->id }})" class="p-1.5 {{ $log->resolvido ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded transition" title="{{ $log->resolvido ? 'Reabrir Erro' : 'Marcar como Resolvido' }}">
                                        <i class="ph {{ $log->resolvido ? 'ph-arrow-counter-clockwise' : 'ph-check-circle' }} text-base"></i>
                                    </button>
                                    <button type="button" wire:click="eliminarErro({{ $log->id }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition" title="Eliminar Registo">
                                        <i class="ph ph-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                                Nenhum erro registado para os filtros selecionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-center sm:justify-between items-center flex-col sm:flex-row gap-3">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span>Exibir</span>
                    <select wire:model.live="porPagina" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-xs py-1 pl-2 pr-6">
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>registros por página</span>
                </div>
                
                {{ $logs->links('components.paginacao-customizada') }}
            </div>
        @endif
    </div>

    {{-- MODAL 1: HISTÓRICO DE OCORRÊNCIAS (DATA/HORA, IP, USUÁRIO, NAVEGADOR) --}}
    @if($modalOcorrenciasAberto && $erroOcorrencias)
        <x-modal
            title="Histórico de Ocorrências do Erro #{{ $erroOcorrencias->id }}"
            subtitle="{{ Str::limit($erroOcorrencias->mensagem, 90) }}"
            max-width="4xl"
            close-method="fecharModalOcorrencias">

            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs">
                    <div class="font-mono text-gray-700 dark:text-gray-300 truncate">
                        <strong class="font-sans text-gray-500">Local:</strong> {{ basename($erroOcorrencias->arquivo) }}:{{ $erroOcorrencias->linha }}
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-200">
                            Total: {{ max(1, (int) ($erroOcorrencias->ocorrencias_count ?? 1)) }} ocorrência(s)
                        </span>
                        @if($erroOcorrencias->reaberto && !$erroOcorrencias->resolvido)
                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-300 font-semibold">
                                Reaberto
                            </span>
                        @endif
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto max-h-[55vh] custom-scrollbar">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-900 text-[11px] font-medium uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-3.5 py-2.5">Data / Hora</th>
                                    <th class="px-3.5 py-2.5">Evento</th>
                                    <th class="px-3.5 py-2.5">Usuário Logado</th>
                                    <th class="px-3.5 py-2.5">IP</th>
                                    <th class="px-3.5 py-2.5">URL / Método</th>
                                    <th class="px-3.5 py-2.5">Navegador / Dispositivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($listaOcorrenciasModal as $oc)
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                        <td class="px-3.5 py-2.5 whitespace-nowrap font-mono text-gray-900 dark:text-white">
                                            {{ $oc['data_hora'] ?? '-' }}
                                        </td>
                                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                                            @php $evTipo = $oc['evento'] ?? 'Recorrência'; @endphp
                                            <span class="px-2 py-0.5 rounded text-[10px] font-medium border {{ $evTipo === 'Erro Reaberto' ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                                {{ $evTipo }}
                                            </span>
                                        </td>
                                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $oc['user_nome'] ?? 'Convidado / Sistema' }}</div>
                                            @if(!empty($oc['user_email']) && $oc['user_email'] !== '-')
                                                <div class="text-[10px] font-mono text-gray-400">{{ $oc['user_email'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3.5 py-2.5 whitespace-nowrap font-mono text-gray-600 dark:text-gray-300">
                                            {{ $oc['ip'] ?? '-' }}
                                        </td>
                                        <td class="px-3.5 py-2.5 max-w-[200px] truncate font-mono text-[11px] text-gray-600 dark:text-gray-300" title="{{ ($oc['metodo_http'] ?? '') . ' ' . ($oc['url'] ?? '') }}">
                                            <span class="font-semibold">{{ $oc['metodo_http'] ?? 'GET' }}</span> {{ $oc['url'] ?? '-' }}
                                        </td>
                                        <td class="px-3.5 py-2.5 max-w-[240px] truncate text-gray-500 dark:text-gray-400" title="{{ $oc['navegador'] ?? '-' }}">
                                            {{ $oc['navegador'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="fecharModalOcorrencias" class="btn btn--secondary btn--small">
                        Fechar
                    </button>
                </div>
            </div>
        </x-modal>
    @endif

    {{-- MODAL 2: DETALHES E STACK TRACE COMPLETO --}}
    @if($modalDetalhesAberto && $errorDetalhes)
        <x-modal
            title="Detalhes da Exceção #{{ $errorDetalhes->id }}"
            subtitle="{{ $errorDetalhes->tipo }} • Código HTTP: {{ $errorDetalhes->http_code ?? 'CLI' }} • {{ max(1, (int) ($errorDetalhes->ocorrencias_count ?? 1)) }} ocorrência(s)"
            max-width="4xl"
            close-method="fecharModalDetalhes">

            <div class="space-y-4 text-sm">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3.5 rounded-lg text-red-800 dark:text-red-300 font-mono text-xs">
                    <span class="font-semibold uppercase block mb-1 font-sans">Mensagem de Erro:</span>
                    {{ $errorDetalhes->mensagem }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        <span class="font-medium text-gray-500 block mb-0.5">Arquivo Afetado:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200 break-all">{{ $errorDetalhes->arquivo }} (Linha {{ $errorDetalhes->linha }})</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        <span class="font-medium text-gray-500 block mb-0.5">URL / Endpoint:</span>
                        <span class="font-mono text-gray-800 dark:text-gray-200 break-all">{{ $errorDetalhes->url ?? 'Execução em Consola (CLI)' }}</span>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Stack Trace (Rastreio Completo):</span>
                    <div class="bg-gray-950 text-gray-200 p-4 rounded-lg font-mono text-[11px] overflow-x-auto max-h-96 custom-scrollbar whitespace-pre leading-relaxed">
                        {{ $errorDetalhes->stack_trace }}
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2">
                    <button type="button" wire:click="alternarResolvido({{ $errorDetalhes->id }})" class="btn btn--primary btn--small {{ $errorDetalhes->resolvido ? '!bg-amber-600 hover:!bg-amber-700' : '!bg-emerald-600 hover:!bg-emerald-700' }} border-none">
                        {{ $errorDetalhes->resolvido ? 'Reabrir Erro' : 'Marcar como Resolvido' }}
                    </button>
                    <button type="button" wire:click="fecharModalDetalhes" class="btn btn--secondary btn--small">
                        Fechar
                    </button>
                </div>
            </div>
        </x-modal>
    @endif
</div>