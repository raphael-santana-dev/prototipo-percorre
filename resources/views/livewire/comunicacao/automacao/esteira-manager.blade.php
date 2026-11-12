<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <x-page-header 
        title="Esteiras de Disparo" 
        subtitle="Gerencie réguas de comunicação automatizadas em múltiplas etapas"
        icon="ph ph-git-merge"
        badge=""
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                Gatilhos Simples
            </a>
            <button type="button" wire:click="processarAgora" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-play"></i> Executar Fila Agora
            </button>
            <a href="{{ route('esteiras.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                <i class="ph ph-plus"></i> Nova Esteira
            </a>
        </x-slot>
    </x-page-header>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        <x-slot name="search">
            <div class="relative w-full sm:w-72 shrink-0">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="filtro_busca" placeholder="Buscar esteira por nome..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        <x-slot name="filters">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Status:</span>
                    <select wire:model.live="filtro_status" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="1">Ativas</option>
                        <option value="0">Pausadas</option>
                    </select>
                </div>

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Público:</span>
                    <select wire:model.live="filtro_publico" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="inscricoes">Inscrições (Ciclo)</option>
                        <option value="formulario_publico">Formulários Gerais</option>
                        <option value="estudantes">Estudantes</option>
                        <option value="usuarios">Usuários Internos</option>
                        <option value="lista_manual">Lista Manual</option>
                    </select>
                </div>

                @if($filtro_busca !== '' || $filtro_status !== '' || $filtro_publico !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 rounded-lg transition">
                        Limpar
                    </button>
                @endif
            </div>
        </x-slot>

        @forelse($registros as $esteira)
            <tr wire:key="esteira-row-{{ $esteira->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ str_pad($esteira->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-3">
                    <a href="{{ route('esteiras.show', $esteira->id) }}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-purpura-600 transition">
                        {{ $esteira->nome }}
                    </a>
                    <div class="text-xs text-gray-500 mt-0.5">
                        @if($esteira->publico_alvo === 'inscricoes')
                            Inscrições: {{ $esteira->ciclo->nome ?? 'Todos os Ciclos' }}
                        @elseif($esteira->publico_alvo === 'formulario_publico')
                            Formulário: {{ $esteira->formulario->titulo ?? 'Removido' }}
                        @elseif($esteira->publico_alvo === 'estudantes')
                            Estudantes Matriculados
                        @elseif($esteira->publico_alvo === 'usuarios')
                            Usuários Internos
                        @else
                            Lista Manual
                        @endif
                    </div>
                </td>

                <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($esteira->etapas as $et)
                            <button type="button"
                                    wire:click="previewTemplate({{ $et->template_id }})"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-gray-400 transition"
                                    title="{{ $et->nome }} ({{ $et->template->nome ?? 'Sem template' }})">
                                <span class="font-mono text-[10px] text-gray-400">#{{ $et->ordem }}</span>
                                @if($et->tipo_agendamento === 'data_fixa' && $et->data_disparo)
                                    <span>{{ $et->data_disparo->format('d/m H:i') }}</span>
                                @elseif($et->tipo_agendamento === 'apos_etapa_anterior')
                                    <span>+{{ $et->atraso_valor }} {{ $et->atraso_unidade }}</span>
                                @else
                                    <span>Imediato</span>
                                @endif
                            </button>
                            @if(!$loop->last)
                                <span class="text-gray-300 text-xs">→</span>
                            @endif
                        @endforeach
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @php
                        $progresso = \App\Modules\Comunicacao\Services\EsteiraComunicacaoService::obterResumoProgressoEsteira($esteira);
                    @endphp
                    <div class="inline-flex flex-col items-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-semibold border {{ $progresso['cor'] }}">
                            <span>{{ $progresso['label'] }}</span>
                        </span>
                        <span class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[150px]" title="{{ $progresso['detalhe'] }}">
                            {{ $progresso['detalhe'] }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <a href="{{ route('esteiras.show', $esteira->id) }}" class="text-xs font-semibold text-gray-700 dark:text-gray-200 hover:text-purpura-600 underline decoration-gray-300 underline-offset-2">
                        {{ $esteira->disparos_enviados_count }}
                    </a>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <div class="inline-flex items-center gap-2">
                        <x-toggle :status="$esteira->status" action="toggleStatus({{ $esteira->id }})" />
                        <span class="text-[11px] font-medium {{ $esteira->status ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                            {{ $esteira->status ? 'Ativa' : 'Pausada' }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('esteiras.show', $esteira->id) }}" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Ver Disparos">
                            <i class="text-base ph ph-chart-line-up"></i>
                        </a>
                        <a href="{{ route('esteiras.edit', $esteira->id) }}" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Editar Esteira">
                            <i class="text-base ph ph-pencil-simple"></i>
                        </a>
                        <button wire:click="excluir({{ $esteira->id }})" class="p-1.5 text-gray-400 rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Excluir Esteira" onclick="confirm('Excluir esta esteira e todo o seu histórico?') || event.stopImmediatePropagation()">
                            <i class="text-base ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm">
                    Nenhuma esteira de disparos cadastrada.
                </td>
            </tr>
        @endforelse
    </x-table>
</div>