<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Automações e Gatilhos" 
        subtitle="Disparos inteligentes acionados por eventos do sistema"
        icon="ph ph-lightning"
        badge=""
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas ?? null">

        @if(feature('automacao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.criar')))
            <x-slot name="actions">
                <a href="{{ route('templates.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    <i class="ph-bold ph-layout text-purpura-600"></i> Templates
                </a>
                <a href="{{ route('automacoes.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                    <i class="ph-bold ph-plus"></i> Nova Automação
                </a>
            </x-slot>
        @endif

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
                <input type="text" wire:model.live.debounce.300ms="filtro_busca" placeholder="Buscar por nome ou gatilho..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition-colors dark:bg-gray-800 dark:border-gray-700 dark:text-white">
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
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Ação</span>
                    <select wire:model.live="filtro_acao" class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todas as ações</option>
                        <option value="enviar_email">Apenas Disparar Mensagem</option>
                        <option value="criar_aluno_enviar_email">Criar Acesso + Disparar Mensagem</option>
                    </select>
                </div>

                @if($filtro_busca !== '' || $filtro_status !== '' || $filtro_acao !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 dark:bg-gray-800 dark:hover:bg-red-900/20 rounded-lg transition flex items-center gap-1">
                        <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                    </button>
                @endif
            </div>
        </x-slot>
        
        @forelse($registros as $regra)
            <tr wire:key="auto-row-{{ $regra->id }}" class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-bold text-gray-400">
                    #{{ str_pad($regra->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border {{ $regra->status ? 'bg-purpura-50 text-purpura-600 border-purpura-200 dark:bg-purpura-900/30 dark:border-purpura-800 dark:text-purpura-400' : 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700' }}">
                            <i class="ph-fill {{ ($regra->tipo_acao ?? 'enviar_email') === 'criar_aluno_enviar_email' ? 'ph-user-plus' : 'ph-lightning' }} text-lg"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $regra->nome }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                                @if(($regra->tipo_acao ?? 'enviar_email') === 'criar_aluno_enviar_email')
                                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Cria acesso de estudante</span> &middot; Dispara template
                                @else
                                    <span>Disparo automático de comunicação</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                        <i class="ph-fill ph-broadcast text-blue-500"></i> {{ $regra->evento_gatilho }}
                    </span>
                </td>

                <td class="px-4 py-3">
                    @if($regra->template)
                        @php $canalTpl = $regra->template->canal ?? 'email'; @endphp
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="previewTemplate({{ $regra->template_id }})" class="group flex items-center gap-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 hover:text-purpura-600 dark:hover:text-purpura-400 transition outline-none bg-gray-50 dark:bg-gray-800 hover:bg-purpura-50/60 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 max-w-xs">
                                <i class="ph-bold ph-eye text-purpura-500 shrink-0"></i>
                                <span class="truncate">{{ $regra->template->nome }}</span>
                            </button>

                            @if($canalTpl === 'whatsapp')
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-emerald-50 text-emerald-700 border border-emerald-200" title="Canal WhatsApp">WPP</span>
                            @elseif($canalTpl === 'ambos')
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-purpura-50 text-purpura-700 border border-purpura-200" title="Multicanal: E-mail + WhatsApp">AMBOS</span>
                            @else
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-blue-50 text-blue-700 border border-blue-200" title="Canal E-mail">EMAIL</span>
                            @endif
                        </div>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs text-red-600 bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg font-bold">
                            <i class="ph-fill ph-warning-circle"></i> Template Excluído
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                        <div class="inline-flex items-center gap-2">
                            <x-toggle :status="$regra->status" action="toggleStatus({{ $regra->id }})" />
                            <span class="text-[10px] font-extrabold uppercase {{ $regra->status ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                {{ $regra->status ? 'Ativa' : 'Pausada' }}
                            </span>
                        </div>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full border {{ $regra->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                            {{ $regra->status ? 'Ativa' : 'Pausada' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('automacao.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.visualizar')))
                            <a href="{{ route('automacoes.show', $regra->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-800" title="Métricas e Histórico de Disparos">
                                <i class="text-lg ph-bold ph-chart-line-up"></i>
                            </a>
                        @endif
                        @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                            <a href="{{ route('automacoes.edit', $regra->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-800" title="Editar Regra">
                                <i class="text-lg ph-bold ph-pencil-simple"></i>
                            </a>
                        @endif
                        @if(feature('automacao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.excluir')))
                            <button wire:click="excluir({{ $regra->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir Regra" onclick="confirm('Excluir esta regra de automação permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph-bold ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-14 text-center text-gray-500 dark:text-gray-400 text-sm">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:border-gray-700">
                        <i class="ph-duotone ph-lightning text-3xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-700 dark:text-gray-300">Nenhuma regra de automação encontrada.</p>
                    <p class="text-xs text-gray-400 mt-1">Configure gatilhos automáticos para notificar candidatos e estudantes em tempo real.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>