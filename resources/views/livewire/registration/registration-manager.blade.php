<div class="w-full font-sans relative" 
     x-data="{ loteAberto: $wire.entangle('modalLoteAberto'), selecaoAberto: $wire.entangle('modalSelecaoAvancadaAberto'), antiSpamAberto: $wire.entangle('modalAntiSpamAberto') }" 
     x-effect="document.body.classList.toggle('overflow-hidden', loteAberto || selecaoAberto || antiSpamAberto)">  
    
    <x-page-header 
        title="Inscrições" 
        subtitle="Gerenciamento de candidatos e captação"
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        {{-- Busca Integrada ao Header --}}
        <x-slot name="search">
            <div class="relative w-full max-w-md">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Pesquisar Nome / E-mail / CPF" class="w-full pl-10 h-10 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition-colors">
            </div>
        </x-slot>

        <x-slot name="actions">
            <div x-data="{ copiado: false }" class="relative inline-block text-left">
                <button @click="
                    let code = `<iframe src='{{ route('publico.inscricao') }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                    navigator.clipboard.writeText(code); 
                    copiado = true; 
                    setTimeout(() => copiado = false, 2000);
                " class="btn btn--secondary btn--medium"
                  :class="copiado ? '!text-emerald-700 !border-emerald-300 !bg-emerald-50 dark:!bg-emerald-900/30 dark:!text-emerald-400' : ''">
                    <i class="text-lg ph" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i> 
                    <span x-text="copiado ? 'Copiado!' : 'Incorporar'"></span>
                </button>
            </div>

            <div x-data="{ openExport: false }" class="relative inline-block text-left">
                <button @click="openExport = !openExport" @click.away="openExport = false" class="btn btn--secondary btn--medium">
                    <i class="text-lg ph ph-export"></i> Exportar <i class="ph ph-caret-down text-[10px]"></i>
                </button>
                <div x-show="openExport" x-cloak class="absolute right-0 w-48 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-xl z-50 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
                    <div class="py-1">
                        <button wire:click="solicitarExportacao('csv')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium text-left gap-2 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-csv text-lg text-purpura-500"></i> Formato Padrão CSV
                        </button>
                        <button wire:click="solicitarExportacao('xlsx')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium text-left gap-2 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-fill ph-file-xls text-lg text-emerald-500"></i> Planilha Excel
                        </button>
                    </div>
                </div>
            </div>

            @if(feature('inscricao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.criar')))
                <button wire:click="abrirModal" class="btn btn--primary btn--medium">
                    <i class="ph ph-plus text-lg"></i> Novo Registro
                </button>
            @endif
        </x-slot>

        {{-- Filtros Inline Estilo Chips --}}
        <x-slot name="filters">
            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md h-9 px-1 shadow-sm shrink-0">
                <span class="pl-2 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Status</span>
                <select wire:model.live="filtroStatus" class="h-full border-none bg-transparent text-sm focus:ring-0 text-gray-800 dark:text-gray-200 font-medium py-0 pl-2 pr-7 cursor-pointer w-auto truncate">
                    <option value="">Todos</option>
                    @foreach($statusInscricoesDb as $id => $nome) 
                        <option value="{{ $id }}">{{ $nome }}</option> 
                    @endforeach                
                </select>
            </div>

            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md h-9 px-1 shadow-sm shrink-0">
                <span class="pl-2 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Ciclo</span>
                <select wire:model.live="filtroCiclo" class="h-full border-none bg-transparent text-sm focus:ring-0 text-gray-800 dark:text-gray-200 font-medium py-0 pl-2 pr-7 cursor-pointer w-auto truncate max-w-[150px]">
                    <option value="">Qualquer</option>
                    @foreach($ciclosDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                </select>
            </div>

            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md h-9 px-1 shadow-sm shrink-0 bg-purpura-50/50 dark:bg-purpura-900/10 border-purpura-100 dark:border-purpura-800">
                <span class="pl-2 text-[10px] font-bold text-purpura-600 uppercase tracking-wide">Etapa</span>
                <select wire:model.live="filtroEtapa" class="h-full border-none bg-transparent text-sm focus:ring-0 text-purpura-800 dark:text-purpura-300 font-bold py-0 pl-2 pr-7 cursor-pointer w-auto truncate max-w-[150px]">
                    <option value="">Todas</option>
                    @if(!empty($etapasDb))
                        @foreach($etapasDb as $numero => $nome)
                            <option value="{{ $numero }}">Passo {{ $numero }}</option>
                        @endforeach
                    @endif
                    <option value="Finalizado">Finalizado</option>
                </select>
            </div>

            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md h-9 px-1 shadow-sm shrink-0">
                <span class="pl-2 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Unidade</span>
                <select wire:model.live="filtroUnidade" class="h-full border-none bg-transparent text-sm focus:ring-0 text-gray-800 dark:text-gray-200 font-medium py-0 pl-2 pr-7 cursor-pointer w-auto truncate max-w-[150px]">
                    <option value="">Qualquer</option>
                    @foreach($unidadesDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                </select>
            </div>

            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md h-9 px-1 shadow-sm shrink-0">
                <span class="pl-2 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Curso</span>
                <select wire:model.live="filtroCurso" class="h-full border-none bg-transparent text-sm focus:ring-0 text-gray-800 dark:text-gray-200 font-medium py-0 pl-2 pr-7 cursor-pointer w-auto truncate max-w-[150px]">
                    <option value="">Qualquer</option>
                    @foreach($cursosDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                </select>
            </div>

            @if($filtroNome !== '' || $filtroStatus !== '' || $filtroCiclo !== '' || $filtroUnidade !== '' || $filtroTurno !== '' || $filtroCurso !== '')
                <button wire:click="limparFiltros" class="text-sm font-medium text-purpura-600 hover:text-purpura-700 dark:text-purpura-400 dark:hover:text-purpura-300 flex items-center gap-1 h-9 px-2 ml-1 transition">
                    <i class="ph-bold ph-x"></i> Limpar Filtros
                </button>
            @endif
        </x-slot>
    </x-page-header>

    @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
        <div class="flex flex-wrap items-center justify-end gap-2 mb-4 w-full">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-full sm:w-auto text-right">Selecionar rápido:</span>
            <button wire:click="selecionarQuantidade(10)" class="text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 px-2 cursor-pointer transition">Top 10</button>
            <button wire:click="selecionarQuantidade(50)" class="text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 px-2 cursor-pointer transition">Top 50</button>
            <button wire:click="abrirModalSelecaoAvancada" class="text-xs font-medium text-purpura-600 hover:text-purpura-700 px-2 cursor-pointer transition flex items-center gap-1"><i class="ph-bold ph-faders"></i> Avançado</button>
        </div>

        @if(count($selecionadas) > 0)
        <div class="bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 p-3 rounded-lg mb-4 flex flex-col lg:flex-row justify-between items-center gap-4 shadow-sm w-full transition-all">
            <div class="flex items-center shrink-0">
                <span class="font-medium text-white text-sm">{{ count($selecionadas) }} selecionadas</span>
                <button wire:click="desmarcarTodas" class="ml-4 text-xs text-gray-400 hover:text-white font-medium transition">Limpar</button>
            </div>
            
            <div class="flex flex-wrap items-center justify-end gap-3 w-full lg:w-auto">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select wire:model="novoStatusId" class="w-full sm:w-auto h-9 !py-0 text-sm">
                        <option value="">Alterar status para...</option>
                        @foreach($statusInscricoesDb as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    <button wire:click="salvarStatusEmLote" class="btn btn--primary btn--small">
                        Aplicar
                    </button>
                </div>
                
                <button wire:click="avancarSelecionadas" class="btn btn--ondark btn--small">
                    Avançar Etapa <i class="ph-bold ph-arrow-right"></i>
                </button>
                
                <button wire:click="abrirModalLote" class="btn btn--ondark btn--small border-transparent hover:bg-gray-800 px-3" title="Visualizar Lote">
                    <i class="ph-bold ph-list-dashes text-lg"></i>
                </button>
            </div>
        </div>
        @endif
    @endif

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse($registros as $inscricao)
            <!-- Padronizando espaçamentos py-2 para visual minimalista -->
            <tr wire:key="linha-inscricao-{{ $inscricao->id }}" class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800/50 transition-colors">
                
                <td class="px-4 py-2 text-center whitespace-nowrap w-12">
                    <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" wire:key="checkbox-lista-{{ $inscricao->id }}">
                </td>

                <td class="px-4 py-2 font-medium text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                    #{{ $inscricao->id }}
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($inscricao->nome) }}&background=f3f4f6&color=111827&bold=true" class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700 hidden sm:block">
                        <div>
                            <div class="font-medium text-gray-900 text-sm dark:text-white">{{ $inscricao->nome }}</div>
                            <div class="text-[11px] text-gray-500">{{ $inscricao->email ?? $inscricao->cpf }}</div>
                        </div>
                    </div>
                </td>

                <td class="px-4 py-2 whitespace-nowrap">
                    @if($inscricao->origem === 'importacao')
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5"><i class="ph-bold ph-upload-simple"></i> Importação</span>
                    @elseif($inscricao->origem === 'manual')
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5"><i class="ph-bold ph-hand-pointing"></i> Manual</span>
                    @else
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5"><i class="ph-bold ph-globe"></i> Formulário</span>
                    @endif
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap">
                    <div class="font-medium text-gray-700 text-sm dark:text-gray-300">{{ $inscricao->curso->nome ?? 'Não selecionado' }}</div>
                    <div class="text-xs text-gray-400">{{ $inscricao->unidade->nome ?? '-' }}</div>
                </td>
                
                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    @if($inscricao->etapa_atual == 99)
                        <span class="font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5"><i class="ph-bold ph-check-circle"></i> Finalizado</span>
                    @elseif($inscricao->etapa_atual == 100)
                        <span class="font-medium text-orange-500 flex items-center gap-1.5"><i class="ph-bold ph-clock"></i> Em Espera</span>
                    @else
                        Passo {{ $inscricao->etapa_atual }}
                    @endif
                </td>

                <td class="px-4 py-2 text-center whitespace-nowrap">
                    <span class="font-bold text-gray-700 dark:text-gray-300">
                        {{ $inscricao->pontuacao_total ?? 0 }} pts
                    </span>
                </td>
                
                <td class="px-4 py-2 text-center whitespace-nowrap">
                    @if($inscricao->posicao_ranking_geral)
                        <span class="tag tag--small tag--outline tag--neutral">
                            {{ $inscricao->posicao_ranking_geral }}º
                        </span>
                    @else
                        <span class="text-gray-300 dark:text-gray-600">-</span>
                    @endif
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-center">
                    @php $corHex = $inscricao->statusInscricao->cor ?? '#9CA3AF'; @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider rounded-md border" style="background-color: {{ $corHex }}10; color: {{ $corHex }}; border-color: {{ $corHex }}30;">
                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $corHex }};"></span>
                        {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                    </span>
                </td>
                
                <td class="px-4 py-2 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('inscricao.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.visualizar')))
                            <button wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700 rounded-md transition" title="Visualização Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>

                            <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700 rounded-md transition" title="Ver Perfil Completo">
                                <i class="text-lg ph ph-arrow-right"></i>
                            </a>
                        @endif

                        <button x-data="{ copiado: false }" 
                            @click="navigator.clipboard.writeText('{{ route('inscricao.retomar', encrypt($inscricao->id)) }}'); copiado = true; setTimeout(() => copiado = false, 2000)" 
                            class="p-1.5 transition rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 relative" 
                            :class="copiado ? 'text-emerald-500' : 'text-gray-400 hover:text-purpura-500'"
                            title="Copiar Link de Retomada">
                            <i class="text-lg ph" :class="copiado ? 'ph-check-circle' : 'ph-link'"></i>
                        </button>

                        @if(feature('inscricao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.excluir')))
                            <button wire:click="excluirInscricao({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 rounded-md transition" title="Excluir Aluno" onclick="confirm('Excluir permanentemente essa inscrição do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="13" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center">
                        <i class="ph ph-magnifying-glass text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="font-medium text-gray-600 dark:text-gray-300">Nenhuma inscrição encontrada.</p>
                        <p class="text-xs mt-1">Tente ajustar ou limpar os filtros de busca.</p>
                    </div>
                </td>
            </tr>   
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $inscricao)
                <div wire:key="card-inscricao-{{ $inscricao->id }}" class="card !p-4 !gap-0 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    
                    <div class="flex items-center justify-between mb-4 w-full">
                        @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded border" style="background-color: {{ $corHex }}10; color: {{ $corHex }}; border-color: {{ $corHex }}30;">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $corHex }};"></span>
                            {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" wire:key="checkbox-card-{{ $inscricao->id }}">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-4 w-full">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($inscricao->nome) }}&background=f3f4f6&color=111827&bold=true" class="w-10 h-10 rounded-full border border-gray-200 dark:border-gray-700 shrink-0">
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ $inscricao->nome }}</h4>
                            <p class="text-xs text-gray-500 truncate dark:text-gray-400">ID: {{ $inscricao->id }} • {{ $inscricao->cpf }}</p>
                        </div>
                    </div>

                    <div class="divider divider--horizontal border-dashed my-2"></div>

                    <div class="flex items-center justify-between mt-2 w-full">
                        <div class="flex items-center gap-1.5 text-[10px] font-medium text-gray-600 dark:text-gray-400">
                            <i class="text-xs ph-fill ph-{{ $inscricao->origem === 'importacao' ? 'upload-simple' : ($inscricao->origem === 'manual' ? 'hand-pointing' : 'globe') }}"></i> 
                            @if($inscricao->etapa_atual == 99)
                                <span class="text-emerald-600 dark:text-emerald-400">Finalizado</span>
                            @elseif($inscricao->etapa_atual == 100)
                                <span class="text-orange-500">Em Espera</span>
                            @else
                                Passo {{ $inscricao->etapa_atual }}
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ $inscricao->pontuacao_total ?? 0 }} pts
                            </div>
                            <div class="flex flex-wrap justify-end gap-1 mt-1">
                                @if($inscricao->posicao_ranking_geral)
                                    <span class="tag tag--small tag--outline tag--neutral" title="Ranking Geral">G: {{ $inscricao->posicao_ranking_geral }}º</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 w-full">
                        <button wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-700 rounded-md transition"><i class="text-lg ph ph-info"></i></button>
                        <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700 rounded-md transition"><i class="text-lg ph ph-arrow-right"></i></a>
                        <button wire:click="excluirInscricao({{ $inscricao->id }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 rounded-md transition" onclick="confirm('Excluir permanentemente essa inscrição?') || event.stopImmediatePropagation()"><i class="text-lg ph ph-trash"></i></button>
                    </div>

                </div>
            @endforeach
        </x-slot>

    </x-table>

    {{-- MODAL DE LOTE --}}
    @if($modalLoteAberto)
        <div class="fixed inset-0 z-[100] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-4 lg:px-8 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <button wire:click="$set('modalLoteAberto', false)" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white rounded-md transition" title="Fechar e Cancelar">
                        <i class="text-xl ph-bold ph-x"></i>
                    </button>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 tracking-tight">
                            <i class="ph-fill ph-check-square-offset text-purpura-500"></i> Alteração em Lote
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Você selecionou <strong>{{ count($selecionadas) }}</strong> inscrições para alterar simultaneamente.</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto bg-gray-50 dark:bg-gray-900 p-2 lg:px-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase hidden lg:block">Mover para:</span>
                    <select wire:model="novoStatusId" class="w-full md:w-56 font-medium text-sm border-gray-300">
                        <option value="">Selecione o novo status...</option>
                        @foreach($statusInscricoesDb as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    <button wire:click="salvarStatusEmLote" class="btn btn--primary btn--medium whitespace-nowrap">
                        Confirmar Ação
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-auto p-4 md:p-6 lg:px-8 custom-scrollbar">
                <div class="max-w-7xl mx-auto card !p-0 overflow-hidden">
                    <div class="overflow-x-auto w-full">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                                <tr>
                                    <th class="p-3 font-semibold text-xs text-gray-500 uppercase">Ação</th>
                                    <th class="p-3 font-semibold text-xs text-gray-500 uppercase">Candidato</th>
                                    <th class="p-3 font-semibold text-xs text-gray-500 uppercase">Interesse</th>
                                    <th class="p-3 font-semibold text-xs text-gray-500 uppercase text-center">Pontuação</th>
                                    <th class="p-3 font-semibold text-xs text-gray-500 uppercase">Status Atual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($this->getInscricoesModal() as $insc)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" wire:key="lote-{{ $insc->id }}">
                                        <td class="p-3 text-center">
                                            <button wire:click="desmarcarIndividual({{ $insc->id }})" class="text-gray-400 hover:text-red-500 transition">
                                                <i class="ph-bold ph-minus-circle text-lg"></i>
                                            </button>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">{{ $insc->nome }}</div>
                                            <div class="text-xs text-gray-500">{{ $insc->cpf }}</div>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ $insc->curso->nome ?? '-' }}</div>
                                            <div class="text-[11px] text-gray-400">{{ $insc->unidade->nome ?? '-' }}</div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                                {{ $insc->pontuacao_total ?? 0 }} pts
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            @php $corHexStatus = $insc->statusInscricao->cor ?? '#9CA3AF'; @endphp
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded border" style="background-color: {{ $corHexStatus }}10; color: {{ $corHexStatus }}; border-color: {{ $corHexStatus }}30;">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $corHexStatus }};"></span>
                                                {{ $insc->statusInscricao->nome ?? 'Pendente' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE SELEÇÃO AVANÇADA --}}
    @if($modalSelecaoAvancadaAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="card !w-full !max-w-2xl !p-0 shadow-2xl overflow-hidden">
                
                <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 w-full bg-gray-50/50 dark:bg-gray-800/50">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-faders text-purpura-500"></i> Seleção Inteligente</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Defina os parâmetros para capturar candidatos em lote.</p>
                    </div>
                    <button wire:click="$set('modalSelecaoAvancadaAberto', false)" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition"><i class="text-xl ph ph-x"></i></button>
                </div>
                
                <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh] w-full">
                    <label class="flex items-start gap-3 p-4 transition border rounded-xl cursor-pointer hover:bg-purpura-50 dark:hover:bg-gray-800 {{ $selecaoPreencherVagas ? 'border-purpura-500 bg-purpura-50/50 dark:bg-purpura-900/30 ring-1 ring-purpura-500' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900' }}">
                        <input type="checkbox" wire:model.live="selecaoPreencherVagas" class="mt-0.5 w-4 h-4 text-purpura-600 rounded border-gray-300">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900 text-sm dark:text-white">Preencher Vagas Automaticamente</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">O sistema lerá as matrizes de ofertas do semestre e selecionará o Top X de cada turma exatamente até o limite configurado de vagas de cada uma.</span>
                        </div>
                    </label>

                    @if(!$selecaoPreencherVagas)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Quantidade</label>
                                <input type="number" wire:model="selecaoQtd" min="1" class="w-full">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Base de Referência</label>
                                <select wire:model="selecaoBase" class="w-full">
                                    <option value="pontuacao">Pontuação</option>
                                    <option value="ranking_geral">Ranking Geral</option>
                                    <option value="ranking_turma">Ranking da Turma</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Modo de Seleção</label>
                            <select wire:model="selecaoModo" class="w-full">
                                <option value="global">Selecionar os {{$selecaoQtd}} melhores do contexto atual</option>
                                <option value="por_turma">Selecionar os {{$selecaoQtd}} melhores DE CADA Turma (Unidade + Curso + Turno)</option>
                            </select>
                        </div>
                    @endif
                </div>
                
                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800 w-full">
                    <button wire:click="$set('modalSelecaoAvancadaAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="executarSelecaoAvancada" class="btn btn--primary btn--medium">
                        <i class="ph-bold ph-magic-wand"></i> Executar Filtro
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-fab :actions="$this->fabActions"
    main-color="bg-purpura-600 hover:bg-purpura-700"
    mainIcon="ph ph-plus" />

    {{-- MODAL DE NOVA INSCRIÇÃO --}}
    @if($modalAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="card !w-full !max-w-md !p-0 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 w-full bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight">Cadastrar Nova Inscrição</h3>
                    <button wire:click="fecharModal" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition"><i class="text-xl ph ph-x"></i></button>
                </div>

                <form wire:submit.prevent="salvarNovaInscricao" class="p-6 space-y-4">
                    
                    <div class="bg-blue-50 border border-blue-100 dark:bg-blue-900/20 dark:border-blue-800 p-4 rounded-lg text-sm text-blue-800 dark:text-blue-300 font-medium mb-4 flex items-start gap-3">
                        <i class="ph-fill ph-info text-xl text-blue-500 mt-0.5"></i>
                        <p class="leading-snug">O candidato receberá o link seguro de retomada no e-mail para concluir as demais etapas acadêmicas após a efetivação deste cadastro.</p>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Nome Completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="nome" class="w-full" required>
                        @error('nome') <span class="text-red-500 text-[11px] font-medium mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">CPF <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="cpf" x-mask="999.999.999-99" placeholder="000.000.000-00" class="w-full" required>
                            @error('cpf') <span class="text-red-500 text-[11px] font-medium mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Celular / Telefone</label>
                            <input type="text" wire:model="celular" x-mask="(99) 99999-9999" placeholder="(00) 00000-0000" class="w-full">
                            @error('celular') <span class="text-red-500 text-[11px] font-medium mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">E-mail de Contato <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="email" class="w-full" required>
                        @error('email') <span class="text-red-500 text-[11px] font-medium mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Ciclo de Ingresso <span class="text-red-500">*</span></label>
                        <select wire:model="ciclo_id" class="w-full" required>
                            <option value="">Selecione o Semestre/Ciclo...</option>
                            @foreach($ciclosDb as $id => $nome)
                                <option value="{{ $id }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                        @error('ciclo_id') <span class="text-red-500 text-[11px] font-medium mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-5 mt-2 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn btn--primary btn--medium">
                            <span wire:loading.remove>Salvar e Processar</span>
                            <span wire:loading>Aguarde...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL ANTI-SPAM --}}
    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="card !w-full !max-w-2xl !p-0 shadow-2xl overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-red-50 dark:bg-red-900/20 w-full">
                    <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta de E-mail Duplicado
                    </h3>
                    <button wire:click="cancelarAntiSpam" class="text-gray-400 hover:text-red-600 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar w-full max-h-[50vh]">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 font-medium leading-relaxed">
                        O sistema detectou que <strong>{{ count($conflitosAntiSpam) }}</strong> {{ count($conflitosAntiSpam) == 1 ? 'candidato já recebeu' : 'candidatos já receberam' }} o e-mail automático configurado para a etapa <strong>{{ $acaoPendenteNomeStatus ?? 'selecionada' }}</strong>.
                    </p>

                    <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold text-xs uppercase">Candidato</th>
                                        <th class="px-4 py-3 font-semibold text-xs uppercase text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($conflitosAntiSpam as $conflito)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" wire:key="conflito-{{ $conflito['id'] }}">
                                            <td class="px-4 py-3">
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ $conflito['nome'] }}</span>
                                                <span class="text-xs text-gray-500">{{ $conflito['email'] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button wire:click="removerConflitoAntiSpam({{ $conflito['id'] }})" class="btn btn--secondary btn--small !text-red-600 !border-red-200 hover:!bg-red-50 dark:!border-red-900/50 dark:hover:!bg-red-900/20">
                                                    Remover da Fila
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row justify-end items-center gap-3 w-full">
                    <button wire:click="cancelarAntiSpam" class="btn btn--secondary btn--medium w-full sm:w-auto">
                        Cancelar
                    </button>
                    <button wire:click="prosseguirComReenvioAntiSpam" class="btn btn--primary btn--medium !bg-red-600 hover:!bg-red-700 focus:!ring-red-500 w-full sm:w-auto">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Prosseguir e Reenviar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>