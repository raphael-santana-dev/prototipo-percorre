<div class="w-full relative">
    
    <x-page-header 
        title="{{ $esteira->nome }}" 
        subtitle="Histórico de disparos, destinatários e progresso por etapa"
        icon="ph ph-git-merge"
        badge="{{ $esteira->status ? 'Ativa' : 'Pausada' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        <x-slot name="actions">
            <a href="{{ route('esteiras.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-arrow-left"></i> Voltar
            </a>

            <button type="button" wire:click="processarEsteiraAgora" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-arrows-clockwise"></i> Processar Fila Agora
            </button>

            <button type="button" wire:click="alternarStatusEsteira" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph {{ $esteira->status ? 'ph-pause' : 'ph-play' }}"></i>
                {{ $esteira->status ? 'Pausar Esteira' : 'Ativar Esteira' }}
            </button>

            <a href="{{ route('esteiras.edit', $esteira->id) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                <i class="ph ph-pencil-simple"></i> Editar Esteira
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col xl:flex-row xl:items-center justify-between gap-3 pt-1">
                <!-- Resumo das Etapas -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    @foreach($esteira->etapas as $et)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 font-medium">
                            <span class="font-mono text-[11px] text-gray-500">#{{ $et->ordem }}</span>
                            <span>{{ $et->nome }}</span>
                        </span>
                        @if(!$loop->last)
                            <span class="text-gray-300 dark:text-gray-600">→</span>
                        @endif
                    @endforeach
                </div>

                <!-- Filtros -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-52">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar destinatário..." class="w-full pl-9 h-9 rounded-lg border-gray-200 text-xs focus:ring-1 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <select wire:model.live="filtro_etapa" class="h-9 rounded-lg border-gray-200 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todas as etapas</option>
                        @foreach($esteira->etapas as $et)
                            <option value="{{ $et->id }}">Etapa #{{ $et->ordem }} - {{ $et->nome }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filtro_status" class="h-9 rounded-lg border-gray-200 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos os status</option>
                        <option value="enviado">Enviados</option>
                        <option value="ignorado_saida">Poupados (Meta Atingida)</option>
                        <option value="ignorado_condicao">Poupados (Mudou Condição)</option>
                        <option value="ignorado_engajamento">Filtrados por Engajamento</option>
                        <option value="cancelado_manual">Cancelados Manualmente</option>
                    </select>

                    <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5">
                        <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent">
                        <span class="text-gray-300 mx-1">até</span>
                        <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent">
                    </div>

                    @if($filtro_busca !== '' || $filtro_etapa !== '' || $filtro_status !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                        <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 rounded-lg transition">
                            Limpar
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mt-4">
        <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                Destinatários e Disparos da Esteira
            </h3>
            <span class="text-xs text-gray-400">{{ $historico->total() }} registro(s)</span>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-600 dark:text-gray-400">
                <thead class="text-[11px] text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-5 py-3 font-medium">Data e Hora</th>
                        <th class="px-5 py-3 font-medium">Destinatário</th>
                        <th class="px-5 py-3 font-medium">Etapa & Template</th>
                        <th class="px-5 py-3 font-medium text-center">Situação</th>
                        <th class="px-5 py-3 font-medium text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($historico as $disp)
                        <tr wire:key="disp-esteira-{{ $disp->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($disp->enviado_em)
                                    <span class="font-medium text-xs text-gray-900 dark:text-white">{{ $disp->enviado_em->format('d/m/Y') }}</span>
                                    <span class="text-gray-400 text-[11px] font-mono ml-1">{{ $disp->enviado_em->format('H:i') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ $disp->destinatario_nome ?: 'Participante' }}
                                </div>
                                <div class="text-[11px] text-gray-500 font-mono">
                                    {{ $disp->destinatario_email }}
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-mono font-semibold">
                                        #{{ $disp->etapa->ordem ?? '-' }}
                                    </span>
                                    <span class="text-xs font-medium text-gray-900 dark:text-gray-200">
                                        {{ $disp->etapa->nome ?? 'Etapa Removida' }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $disp->etapa->template->nome ?? '-' }}
                                </div>
                            </td>

                            <td class="px-5 py-3 whitespace-nowrap text-center">
                                @if($disp->status === 'enviado')
                                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-emerald-50 border-emerald-200 text-emerald-700">
                                        Disparado
                                    </span>
                                @elseif($disp->status === 'cancelado_manual')
                                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-red-50 border-red-200 text-red-700" title="{{ $disp->motivo_status }}">
                                        Cancelado
                                    </span>
                                @elseif($disp->status === 'ignorado_saida')
                                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-blue-50 border-blue-200 text-blue-700" title="{{ $disp->motivo_status }}">
                                        Meta Concluída
                                    </span>
                                @elseif($disp->status === 'ignorado_condicao')
                                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-amber-50 border-amber-200 text-amber-700" title="{{ $disp->motivo_status }}">
                                        Fora da Condição
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[11px] font-medium rounded border bg-gray-100 border-gray-200 text-gray-600" title="{{ $disp->motivo_status }}">
                                        Filtrado
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            wire:click="reenviarEtapa({{ $disp->id }})"
                                            wire:confirm="Deseja {{ $disp->status === 'enviado' ? 'reenviar' : 'forçar o envio imediato d' }}esta etapa para {{ $disp->destinatario_email }}?"
                                            class="px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 border border-gray-200 dark:border-gray-600 rounded transition">
                                        {{ $disp->status === 'enviado' ? 'Reenviar' : 'Enviar Agora' }}
                                    </button>

                                    @if($disp->status === 'cancelado_manual')
                                        <button type="button"
                                                wire:click="reativarDestinatario({{ $disp->id }})"
                                                wire:confirm="Reativar as etapas futuras para {{ $disp->destinatario_email }} nesta esteira?"
                                                class="px-2.5 py-1 text-xs font-medium text-emerald-700 bg-white hover:bg-emerald-50 border border-gray-200 rounded transition">
                                            Reativar
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="cancelarEtapasDestinatario({{ $disp->id }})"
                                                wire:confirm="Cancelar todas as próximas etapas pendentes desta esteira para {{ $disp->destinatario_email }}?"
                                                class="px-2.5 py-1 text-xs font-medium text-red-600 bg-white hover:bg-red-50 border border-gray-200 rounded transition">
                                            Cancelar Fila
                                        </button>
                                    @endif

                                    <button type="button"
                                            wire:click="showQuickView({{ $disp->id }})"
                                            class="px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded transition">
                                        Detalhes
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-400 text-xs">
                                Nenhum disparo registrado nesta esteira ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($historico->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-center">
                {{ $historico->links('components.paginacao-customizada') }}
            </div>
        @endif
    </div>
</div>