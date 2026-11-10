<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Esteiras de Disparo (Sequências)" 
        subtitle="Gerencie réguas de relacionamento automatizadas em múltiplas etapas"
        icon="ph ph-git-merge"
        badge=""
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-lightning text-amber-500"></i> Gatilhos Simples
            </a>
            <button type="button" wire:click="processarAgora" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-play text-emerald-600"></i> Executar Fila Agora
            </button>
            <a href="{{ route('esteiras.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                <i class="ph-bold ph-plus"></i> Nova Esteira
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
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.300ms="filtro_busca" placeholder="Buscar esteira por nome..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        <x-slot name="filters">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Status</span>
                    <select wire:model.live="filtro_status" class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos</option>
                        <option value="1">Ativas</option>
                        <option value="0">Pausadas</option>
                    </select>
                </div>

                <div class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Público</span>
                    <select wire:model.live="filtro_publico" class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todos os públicos</option>
                        <option value="inscricoes">Inscrições (Ciclo)</option>
                        <option value="formulario_publico">Formulários Gerais</option>
                        <option value="estudantes">Estudantes</option>
                        <option value="usuarios">Usuários Internos</option>
                        <option value="lista_manual">Lista Manual</option>
                    </select>
                </div>

                @if($filtro_busca !== '' || $filtro_status !== '' || $filtro_publico !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 rounded-lg transition flex items-center gap-1">
                        <i class="ph-bold ph-funnel-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

        @forelse($registros as $esteira)
            <tr wire:key="esteira-row-{{ $esteira->id }}" class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                    #{{ str_pad($esteira->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border {{ $esteira->status ? 'bg-purpura-50 text-purpura-600 border-purpura-200 dark:bg-purpura-900/30 dark:border-purpura-800' : 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700' }}">
                            <i class="ph-fill ph-git-merge text-lg"></i>
                        </div>
                        <div>
                            <a href="{{ route('esteiras.show', $esteira->id) }}" class="text-sm font-bold text-gray-900 dark:text-white hover:text-purpura-600 transition">
                                {{ $esteira->nome }}
                            </a>
                            <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                @if($esteira->publico_alvo === 'inscricoes')
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Inscrições:</span> {{ $esteira->ciclo->nome ?? 'Todos os Ciclos' }}
                                @elseif($esteira->publico_alvo === 'formulario_publico')
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Formulário:</span> {{ $esteira->formulario->titulo ?? 'Removido' }}
                                @elseif($esteira->publico_alvo === 'estudantes')
                                    <span class="font-semibold text-blue-600">Estudantes Matriculados</span>
                                @elseif($esteira->publico_alvo === 'usuarios')
                                    <span class="font-semibold text-amber-600">Usuários Internos</span>
                                @else
                                    <span class="font-semibold text-gray-600">Lista Manual</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>

                <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($esteira->etapas as $et)
                            <span wire:click="previewTemplate({{ $et->template_id }})" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700"
                                  title="{{ $et->nome }} ({{ $et->template->nome ?? 'Sem template' }})">
                                <span class="w-4 h-4 rounded-full bg-purpura-600 text-white text-[9px] flex items-center justify-center font-black">{{ $et->ordem }}</span>
                                @if($et->tipo_agendamento === 'data_fixa' && $et->data_disparo)
                                    {{ $et->data_disparo->format('d/m H:i') }}
                                @elseif($et->tipo_agendamento === 'apos_etapa_anterior')
                                    +{{ $et->atraso_valor }} {{ $et->atraso_unidade }}
                                @else
                                    Imediato
                                @endif
                            </span>
                            @if(!$loop->last)
                                <i class="ph-bold ph-arrow-right text-[10px] text-gray-400"></i>
                            @endif
                        @endforeach
                    </div>
                </td>

                {{-- NOVA COLUNA: BADGE DE PROGRESSO / ETAPA ATUAL OU FINALIZADA --}}
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @php
                        $progresso = \App\Modules\Comunicacao\Services\EsteiraComunicacaoService::obterResumoProgressoEsteira($esteira);
                    @endphp
                    <div class="inline-flex flex-col items-center">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold border {{ $progresso['cor'] }}">
                            <i class="ph-bold {{ $progresso['icone'] }}"></i>
                            <span>{{ $progresso['label'] }}</span>
                        </span>
                        <span class="text-[10px] text-gray-400 font-medium mt-0.5 truncate max-w-[160px]" title="{{ $progresso['detalhe'] }}">
                            {{ $progresso['detalhe'] }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <a href="{{ route('esteiras.show', $esteira->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition">
                        <i class="ph-fill ph-paper-plane-tilt"></i> {{ $esteira->disparos_enviados_count }}
                    </a>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    <div class="inline-flex items-center gap-2">
                        <x-toggle :status="$esteira->status" action="toggleStatus({{ $esteira->id }})" />
                        <span class="text-[10px] font-extrabold uppercase {{ $esteira->status ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                            {{ $esteira->status ? 'Ativa' : 'Pausada' }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('esteiras.show', $esteira->id) }}" class="p-1.5 text-gray-400 rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-800 transition" title="Ver Disparos e Destinatários">
                            <i class="text-lg ph-bold ph-chart-line-up"></i>
                        </a>
                        <a href="{{ route('esteiras.edit', $esteira->id) }}" class="p-1.5 text-gray-400 rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-800 transition" title="Editar Esteira">
                            <i class="text-lg ph-bold ph-pencil-simple"></i>
                        </a>
                        <button wire:click="excluir({{ $esteira->id }})" class="p-1.5 text-gray-400 rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Excluir Esteira" onclick="confirm('Excluir esta esteira e todo o seu histórico?') || event.stopImmediatePropagation()">
                            <i class="text-lg ph-bold ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-14 text-center text-gray-500 text-sm">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:border-gray-700">
                        <i class="ph-duotone ph-git-merge text-3xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-700 dark:text-gray-300">Nenhuma esteira de disparos cadastrada.</p>
                    <p class="text-xs text-gray-400 mt-1">Crie uma sequência automatizada de e-mails por etapa e condição.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>