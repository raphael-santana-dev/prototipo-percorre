<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
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
                    <i class="ph-bold ph-activity text-purpura-600"></i> Monitor de Envios
                </a>
                <a href="{{ route('comunicados.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                    <i class="ph-bold ph-plus"></i> Novo Disparo
                </a>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="flex flex-wrap items-center gap-2">
                
                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Template</span>
                    <select wire:model.live="filtro_template" class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 max-w-[200px] truncate cursor-pointer">
                        <option value="">Todos</option>
                        @if(isset($templatesDisponiveis))
                            @foreach($templatesDisponiveis as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="flex items-center h-9 px-2.5 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Status</span>
                    <select wire:model.live="filtro_status" class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="pendente">Agendados / Fila</option>
                        <option value="enviando">Enviando</option>
                        <option value="concluido">Concluídos</option>
                        <option value="erro">Com Erro</option>
                    </select>
                </div>

                <div class="flex items-center h-9 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-2xs px-2.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase mr-1.5">Período</span>
                    <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                    <span class="text-gray-300 dark:text-gray-600 mx-1">até</span>
                    <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-xs p-1 focus:ring-0 text-gray-700 dark:text-gray-200 bg-transparent cursor-pointer">
                </div>

                @if($filtro_status !== '' || $filtro_template !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 dark:bg-gray-800 rounded-lg transition flex items-center gap-1">
                        <i class="ph-bold ph-funnel-x"></i> Limpar
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
                <tr wire:key="comunicado-row-{{ $comunicado->id }}" class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                        #{{ str_pad($comunicado->id, 3, '0', STR_PAD_LEFT) }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 dark:text-purpura-400 border border-purpura-200 dark:border-purpura-800 flex items-center justify-center shrink-0">
                                <i class="ph-fill ph-envelope-simple-open text-lg"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $comunicado->template->nome ?? 'Template Excluído' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $comunicado->template->assunto ?? 'Sem Assunto' }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        @php 
                            $qtdDest = is_array($comunicado->destinatarios) ? count($comunicado->destinatarios) : 0;
                            $qtdAnexos = is_array($comunicado->anexos) ? count($comunicado->anexos) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                <i class="ph-fill ph-users text-purpura-500"></i> {{ $qtdDest }} destinatário(s)
                            </span>
                            @if($qtdAnexos > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-[11px] font-bold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    <i class="ph-bold ph-paperclip"></i> {{ $qtdAnexos }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($comunicado->data_agendamento)
                            <div class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                <i class="ph-fill ph-calendar-blank text-gray-400"></i>
                                {{ $comunicado->data_agendamento->format('d/m/Y') }}
                                <span class="text-gray-400 font-mono">{{ $comunicado->data_agendamento->format('H:i') }}</span>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Disparo Imediato</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider border {{ $comunicado->status_color }}">
                            @if($comunicado->status === 'pendente') <i class="ph-bold ph-clock"></i> Agendado
                            @elseif($comunicado->status === 'enviando') <i class="ph-bold ph-spinner animate-spin"></i> Enviando
                            @elseif($comunicado->status === 'concluido') <i class="ph-bold ph-check"></i> Enviado
                            @elseif($comunicado->status === 'erro') <i class="ph-bold ph-warning"></i> Falhou
                            @endif
                        </span>
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" wire:click="showQuickView({{ $comunicado->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-800" title="Ver Detalhes e Destinatários">
                                <i class="text-lg ph-bold ph-eye"></i>
                            </button>

                            @if(feature('comunicado.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.excluir')))
                                @if($comunicado->status === 'pendente')
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Cancelar Agendamento" onclick="confirm('Tem certeza que deseja cancelar este envio agendado?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph-bold ph-x-square"></i>
                                    </button>
                                @else
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir Histórico" onclick="confirm('Excluir este registro do histórico?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph-bold ph-trash"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-14 text-center text-gray-500 dark:text-gray-400 text-sm">
                        <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:border-gray-700">
                            <i class="ph-duotone ph-paper-plane-tilt text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-700 dark:text-gray-300">Nenhum disparo registrado.</p>
                        <p class="text-xs text-gray-400 mt-1">Crie um novo disparo para enviar comunicados aos candidatos ou estudantes.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>