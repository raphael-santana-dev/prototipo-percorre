<div class="w-full relative">
    
    <x-page-header 
        title="Automações e Gatilhos" 
        subtitle="Disparos acionados por eventos do sistema"
        icon="ph ph-lightning"
        badge=""
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas ?? null">

        @if(feature('automacao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.criar')))
            <x-slot name="actions">
                <a href="{{ route('templates.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    Templates
                </a>
                <a href="{{ route('esteiras.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    Esteiras de Disparo
                </a>
                <a href="{{ route('esteiras.create') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                    <i class="ph ph-plus"></i> Nova Esteira
                </a>
                <a href="{{ route('automacoes.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Nova Automação
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
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="filtro_busca" placeholder="Buscar por nome ou gatilho..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
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
                    <span class="text-[11px] text-gray-400 mr-1.5">Ação:</span>
                    <select wire:model.live="filtro_acao" class="border-none bg-transparent text-xs font-medium text-gray-700 dark:text-gray-200 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="">Todas</option>
                        <option value="enviar_email">Apenas Disparar Mensagem</option>
                        <option value="criar_aluno_enviar_email">Criar Acesso + Disparar</option>
                    </select>
                </div>

                @if($filtro_busca !== '' || $filtro_status !== '' || $filtro_acao !== '')
                    <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 dark:bg-gray-800 rounded-lg transition">
                        Limpar
                    </button>
                @endif
            </div>
        </x-slot>
        
        @forelse($registros as $regra)
            <tr wire:key="auto-row-{{ $regra->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                    #{{ str_pad($regra->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $regra->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if(($regra->tipo_acao ?? 'enviar_email') === 'criar_aluno_enviar_email')
                            Cria acesso de estudante &middot; Dispara template
                        @else
                            Disparo automático de comunicação
                        @endif
                    </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <code class="px-2 py-1 text-xs font-mono rounded bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                        {{ $regra->evento_gatilho }}
                    </code>
                </td>

                <td class="px-4 py-3">
                    @if($regra->template)
                        @php $canalTpl = $regra->canal_disparo ?? ($regra->template->canal ?? 'email'); @endphp
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="previewTemplate({{ $regra->template_id }})" class="flex items-center gap-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:text-purpura-600 transition max-w-xs">
                                <i class="ph ph-eye text-gray-400 shrink-0"></i>
                                <span class="truncate underline decoration-gray-300 underline-offset-2">{{ $regra->template->nome }}</span>
                            </button>

                            <span class="px-1.5 py-0.5 text-[10px] font-medium uppercase rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                {{ $canalTpl === 'whatsapp' ? 'WPP' : ($canalTpl === 'ambos' ? 'Ambos' : 'E-mail') }}
                            </span>
                        </div>
                    @else
                        <span class="text-xs text-red-600 font-medium">
                            Template excluído
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                        <div class="inline-flex items-center gap-2">
                            <x-toggle :status="$regra->status" action="toggleStatus({{ $regra->id }})" />
                            <span class="text-[11px] font-medium {{ $regra->status ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                {{ $regra->status ? 'Ativa' : 'Pausada' }}
                            </span>
                        </div>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $regra->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                            {{ $regra->status ? 'Ativa' : 'Pausada' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('automacao.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.visualizar')))
                            <a href="{{ route('automacoes.show', $regra->id) }}" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Histórico de Disparos">
                                <i class="text-base ph ph-chart-line-up"></i>
                            </a>
                        @endif
                        @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                            <a href="{{ route('automacoes.edit', $regra->id) }}" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Editar Regra">
                                <i class="text-base ph ph-pencil-simple"></i>
                            </a>
                        @endif
                        @if(feature('automacao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.excluir')))
                            <button wire:click="excluir({{ $regra->id }})" class="p-1.5 text-gray-400 rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Excluir Regra" onclick="confirm('Excluir esta regra de automação permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-base ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                    Nenhuma regra de automação encontrada.
                </td>
            </tr>
        @endforelse
    </x-table>
</div>