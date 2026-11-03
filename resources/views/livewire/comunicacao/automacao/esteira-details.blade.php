<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $esteira->nome }}" 
        subtitle="Histórico completo de e-mails disparados, destinatários e progresso por etapa da esteira"
        icon="ph ph-git-merge"
        badge="{{ $esteira->status ? 'Ativa' : 'Pausada' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        <x-slot name="actions">
            <a href="{{ route('esteiras.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>

            <button type="button" wire:click="processarEsteiraAgora" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrows-clockwise text-purpura-600"></i> Processar Fila Agora
            </button>

            <button type="button" wire:click="alternarStatusEsteira" class="btn btn--secondary btn--medium {{ $esteira->status ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200' }}">
                <i class="ph-bold {{ $esteira->status ? 'ph-pause' : 'ph-play' }}"></i>
                {{ $esteira->status ? 'Pausar Esteira' : 'Ativar Esteira' }}
            </button>

            <a href="{{ route('esteiras.edit', $esteira->id) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                <i class="ph-bold ph-pencil-simple"></i> Editar Esteira
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col xl:flex-row xl:items-center justify-between gap-3 pt-1">
                <!-- Resumo das Etapas da Esteira -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @foreach($esteira->etapas as $et)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purpura-50 text-purpura-700 border border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800 font-bold">
                            <span class="w-4 h-4 rounded-full bg-purpura-600 text-white text-[10px] flex items-center justify-center">{{ $et->ordem }}</span>
                            {{ $et->nome }}
                        </span>
                        @if(!$loop->last)
                            <span class="text-gray-300 dark:text-gray-600">→</span>
                        @endif
                    @endforeach
                </div>

                <!-- Filtros de Busca, Etapa, Status e Data -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-56">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar nome ou e-mail..." class="w-full pl-9 h-9 rounded-lg border-gray-200 text-xs shadow-2xs focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <select wire:model.live="filtro_etapa" class="h-9 rounded-lg border-gray-200 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todas as Etapas</option>
                        @foreach($esteira->etapas as $et)
                            <option value="{{ $et->id }}">Etapa #{{ $et->ordem }} - {{ $et->nome }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filtro_status" class="h-9 rounded-lg border-gray-200 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos os Status</option>
                        <option value="enviado">Enviados</option>
                        <option value="ignorado_saida">Poupados (Meta Atingida)</option>
                        <option value="ignorado_condicao">Poupados (Mudou Condição)</option>
                        <option value="ignorado_engajamento">Filtrados por Engajamento</option>
                    </select>

                    <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-2xs px-2.5">
                        <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent">
                        <span class="text-gray-300 mx-1">até</span>
                        <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent">
                    </div>

                    @if($filtro_busca !== '' || $filtro_etapa !== '' || $filtro_status !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                        <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 rounded-lg transition flex items-center gap-1">
                            <i class="ph-bold ph-x"></i> Limpar
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-2xl overflow-hidden mt-4">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/40">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-users-three text-purpura-600 text-base"></i> Destinatários e E-mails Disparados na Esteira
            </h3>
            <span class="text-xs font-bold text-gray-400">{{ $historico->total() }} registro(s)</span>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-600 dark:text-gray-400">
                <thead class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60">
                    <tr>
                        <th class="px-6 py-3.5 font-bold">Data e Hora</th>
                        <th class="px-6 py-3.5 font-bold">Para Quem (Destinatário)</th>
                        <th class="px-6 py-3.5 font-bold">Etapa & Template Disparado</th>
                        <th class="px-6 py-3.5 font-bold text-center">Situação</th>
                        <th class="px-6 py-3.5 font-bold text-right">Auditoria</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($historico as $disp)
                        <tr wire:key="disp-esteira-{{ $disp->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if($disp->enviado_em)
                                    <span class="font-bold text-xs text-gray-900 dark:text-white">{{ $disp->enviado_em->format('d/m/Y') }}</span>
                                    <span class="text-gray-400 text-[11px] font-mono ml-1">{{ $disp->enviado_em->format('H:i:s') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-purpura-50 dark:bg-purpura-900/40 text-purpura-600 dark:text-purpura-300 font-bold text-xs flex items-center justify-center border border-purpura-200 dark:border-purpura-800 shrink-0">
                                        {{ strtoupper(substr($disp->destinatario_nome ?: $disp->destinatario_email, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-gray-900 dark:text-white">
                                            {{ $disp->destinatario_nome ?: 'Participante' }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono">
                                            {{ $disp->destinatario_email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-purpura-100 text-purpura-800 dark:bg-purpura-900/40 dark:text-purpura-300 text-[10px] font-extrabold">
                                        Etapa #{{ $disp->etapa->ordem ?? '-' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                        {{ $disp->etapa->nome ?? 'Etapa Removida' }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    Template: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $disp->etapa->template->nome ?? '-' }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-3.5 whitespace-nowrap text-center">
                                @if($disp->status === 'enviado')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-emerald-50 border border-emerald-200 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disparado
                                    </span>
                                @elseif($disp->status === 'ignorado_saida')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-blue-50 border border-blue-200 text-blue-700" title="{{ $disp->motivo_status }}">
                                        Meta Concluída
                                    </span>
                                @elseif($disp->status === 'ignorado_condicao')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-amber-50 border border-amber-200 text-amber-700" title="{{ $disp->motivo_status }}">
                                        Saiu da Condição
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-gray-100 border border-gray-200 text-gray-600" title="{{ $disp->motivo_status }}">
                                        Filtrado
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-3.5 whitespace-nowrap text-right">
                                <button wire:click="showQuickView({{ $disp->id }})" class="px-2.5 py-1 text-xs font-bold text-gray-600 dark:text-gray-300 hover:text-purpura-600 bg-gray-50 hover:bg-purpura-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg transition inline-flex items-center gap-1">
                                    <i class="ph-bold ph-info"></i> Detalhes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                                <i class="ph-duotone ph-paper-plane-tilt text-4xl mb-2 block mx-auto"></i>
                                <p class="font-bold text-gray-600 dark:text-gray-300 text-sm">Nenhum disparo registrado nesta esteira ainda.</p>
                                <p class="text-xs text-gray-400 mt-0.5">Assim que o horário das etapas for atingido, os destinatários aparecerão listados aqui.</p>
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