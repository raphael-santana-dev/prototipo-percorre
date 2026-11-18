<div class="w-full relative">
    
    <x-page-header 
        title="Histórico de Comunicados" 
        subtitle="Acompanhamento de campanhas, avisos em lote e envios programados"
        icon="ph ph-paper-plane-tilt"
        badge=""
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas ?? null">

        @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
            <x-slot name="actions">
                <a href="{{ route('monitor.emails') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    Monitor de Envios
                </a>
                <a href="{{ route('comunicados.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Novo Disparo
                </a>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Template:</span>
                    <select wire:model.live="filtro_template" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 max-w-[200px] truncate cursor-pointer">
                        <option value="">Todos</option>
                        @if(isset($templatesDisponiveis))
                            @foreach($templatesDisponiveis as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700">
                    <span class="text-[11px] text-gray-400 mr-1.5">Status:</span>
                    <select wire:model.live="filtro_status" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="pendente">Agendados / Fila</option>
                        <option value="enviando">Enviando</option>
                        <option value="concluido">Concluídos</option>
                        <option value="erro">Com Erro</option>
                    </select>
                </div>

                <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5">
                    <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                    <span class="text-gray-300 dark:text-gray-600 mx-1">até</span>
                    <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                </div>

                @if($filtro_status !== '' || $filtro_template !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 dark:bg-gray-800 rounded-lg transition">
                        Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <div wire:poll.10s>
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $comunicado)
                <tr wire:key="comunicado-row-{{ $comunicado->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                        #{{ str_pad($comunicado->id, 3, '0', STR_PAD_LEFT) }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $comunicado->template->nome ?? 'Template Excluído' }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $comunicado->template->assunto ?? 'Sem Assunto' }}</div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        @php 
                            $qtdDest = is_array($comunicado->destinatarios) ? count($comunicado->destinatarios) : 0;
                            $qtdAnexos = is_array($comunicado->anexos) ? count($comunicado->anexos) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                {{ $qtdDest }} destinatário(s)
                            </span>
                            @if($qtdAnexos > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                    <i class="ph ph-paperclip"></i> {{ $qtdAnexos }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($comunicado->data_agendamento)
                            <span class="text-xs font-medium text-gray-900 dark:text-gray-200">{{ $comunicado->data_agendamento->format('d/m/Y') }}</span>
                            <span class="text-xs text-gray-400 font-mono ml-1">{{ $comunicado->data_agendamento->format('H:i') }}</span>
                        @else
                            <span class="text-xs text-gray-400">Imediato</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium rounded border {{ $comunicado->status_color }}">
                            @if($comunicado->status === 'pendente') Agendado
                            @elseif($comunicado->status === 'enviando') Enviando...
                            @elseif($comunicado->status === 'concluido') Concluído
                            @elseif($comunicado->status === 'erro') Falhou
                            @endif
                        </span>
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" wire:click="showQuickView({{ $comunicado->id }})" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Ver Detalhes">
                                <i class="text-base ph ph-eye"></i>
                            </button>

                            @if(feature('comunicado.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.excluir')))
                                @if($comunicado->status === 'pendente')
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Cancelar Agendamento" onclick="confirm('Tem certeza que deseja cancelar este envio agendado?') || event.stopImmediatePropagation()">
                                        <i class="text-base ph ph-x-square"></i>
                                    </button>
                                @else
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Excluir Histórico" onclick="confirm('Excluir este registro do histórico?') || event.stopImmediatePropagation()">
                                        <i class="text-base ph ph-trash"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                        Nenhum disparo registrado.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>