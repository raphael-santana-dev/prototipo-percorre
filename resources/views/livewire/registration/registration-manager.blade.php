<!-- Baseado no arquivo original[cite: 4] -->
<div class="w-full font-sans relative" 
     x-data="{ loteAberto: $wire.entangle('modalLoteAberto'), selecaoAberto: $wire.entangle('modalSelecaoAvancadaAberto'), antiSpamAberto: $wire.entangle('modalAntiSpamAberto') }" 
     x-effect="document.body.classList.toggle('overflow-hidden', loteAberto || selecaoAberto || antiSpamAberto)">  
    
    <x-page-header 
        title="Inscrições" 
        icon="ph ph-clipboard-text"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            <div class="flex flex-wrap items-center justify-end gap-2 w-full">
                <div x-data="{ copiado: false }" class="relative inline-block text-left">
                    <button @click="
                        let code = `<iframe src='{{ route('publico.inscricao') }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                        navigator.clipboard.writeText(code); 
                        copiado = true; 
                        setTimeout(() => copiado = false, 2000);
                    " class="btn btn--secondary btn--medium"
                      :class="copiado ? '!text-pistache-700 !border-pistache-300 !bg-pistache-50 dark:!bg-pistache-900/30 dark:!text-pistache-400' : ''">
                        <i class="text-lg ph" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i> 
                        <span x-text="copiado ? 'Copiado!' : 'Incorporar'"></span>
                    </button>
                </div>

                <div x-data="{ openExport: false }" class="relative inline-block text-left">
                    <button @click="openExport = !openExport" @click.away="openExport = false" class="btn btn--secondary btn--medium">
                        <i class="text-lg ph ph-export"></i> Exportar <i class="ph ph-caret-down"></i>
                    </button>
                    <div x-show="openExport" x-cloak class="absolute right-0 w-48 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg z-50 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
                        <div class="py-1">
                            <button wire:click="solicitarExportacao('xlsx')" class="flex items-center w-full px-4 py-2 text-sm text-pistache-700 hover:bg-pistache-50 font-bold text-left gap-2 dark:text-pistache-400 dark:hover:bg-gray-700">
                                <i class="ph-fill ph-file-xls text-lg"></i> Formato Excel (.xlsx)
                            </button>
                            <button wire:click="solicitarExportacao('csv')" class="flex items-center w-full px-4 py-2 text-sm text-purpura-700 hover:bg-purpura-50 font-bold text-left gap-2 dark:text-purpura-400 dark:hover:bg-gray-700">
                                <i class="ph-fill ph-file-csv text-lg"></i> Formato CSV (.csv)
                            </button>
                        </div>
                    </div>
                </div>

                @if(feature('inscricao.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.criar')))
                    <button wire:click="abrirModal" class="btn btn--primary btn--medium">
                        <i class="ph ph-plus text-lg"></i> Nova Inscrição
                    </button>
                @endif
            </div>
        </x-slot>

        <x-slot name="filters">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full">
                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-magnifying-glass text-purpura-500"></i> Buscar
                    </label>
                    <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Nome ou CPF..." class="w-full">
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-tag text-purpura-500"></i> Status
                    </label>
                    <select wire:model.live="filtroStatus" class="w-full">
                        <option value="">Todos os Status</option>
                        @foreach($statusInscricoesDb as $id => $nome) 
                            <option value="{{ $id }}">{{ $nome }}</option> 
                        @endforeach                
                    </select>
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-calendar-check text-purpura-500"></i> Ciclo
                    </label>
                    <select wire:model.live="filtroCiclo" class="w-full">
                        <option value="">Todos os Semestres</option>
                        @foreach($ciclosDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                    </select>
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-steps text-purpura-500"></i> Etapa
                    </label>
                    <select wire:model.live="filtroEtapa" class="w-full">
                        <option value="">Todas as Etapas</option>
                        @if(!empty($etapasDb))
                            @foreach($etapasDb as $numero => $nome)
                                <option value="{{ $numero }}">Passo {{ $numero }} - {{ $nome ?? 'Formulário' }}</option>
                            @endforeach
                        @endif
                        <option value="Finalizado">Finalizado (Concluído)</option>
                    </select>
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-buildings text-purpura-500"></i> Unidade
                    </label>
                    <select wire:model.live="filtroUnidade" class="w-full">
                        <option value="">Todas as Unidades</option>
                        @foreach($unidadesDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                    </select>
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-1 mb-1 text-xs font-bold text-gray-500 uppercase dark:text-gray-400">
                        <i class="ph ph-graduation-cap text-purpura-500"></i> Curso
                    </label>
                    <select wire:model.live="filtroCurso" class="w-full">
                        <option value="">Todos os Cursos</option>
                        @foreach($cursosDb as $id => $nome) <option value="{{ $id }}">{{ $nome }}</option> @endforeach
                    </select>
                </div>

                @if($filtroNome !== '' || $filtroStatus !== '' || $filtroCiclo !== '' || $filtroUnidade !== '' || $filtroTurno !== '' || $filtroCurso !== '')
                    <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-6 flex justify-end mt-2 border-t border-gray-100 dark:border-gray-700 pt-4 w-full">
                        <button wire:click="limparFiltros" class="btn btn--secondary btn--small">
                            <i class="ph-bold ph-x"></i> Limpar Filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
        <div class="flex flex-wrap items-center justify-end gap-2 mb-4 w-full">
            <span class="text-xs font-bold text-gray-500 uppercase dark:text-gray-400 w-full sm:w-auto text-right">Selecionar rápido:</span>
            <button wire:click="selecionarQuantidade(10)" class="tag tag--medium tag--outline tag--neutral cursor-pointer hover:bg-gray-100">Top 10</button>
            <button wire:click="selecionarQuantidade(50)" class="tag tag--medium tag--outline tag--neutral cursor-pointer hover:bg-gray-100">Top 50</button>
            <button wire:click="abrirModalSelecaoAvancada" class="btn btn--secondary btn--small !bg-purpura-50 !border-purpura-200 !text-purpura-700 hover:!bg-purpura-100"><i class="ph-bold ph-faders"></i> Avançado</button>
        </div>

        @if(count($selecionadas) > 0)
        <div class="bg-purpura-50 dark:bg-purpura-900/30 border border-purpura-200 dark:border-purpura-800 p-4 rounded-xl mb-6 flex flex-col lg:flex-row justify-between items-center gap-4 shadow-sm w-full">
            <div class="flex items-center shrink-0">
                <span class="font-bold text-purpura-800 dark:text-purpura-300 text-lg">{{ count($selecionadas) }} selecionadas</span>
                <button wire:click="desmarcarTodas" class="ml-4 text-sm text-purpura-600 dark:text-purpura-400 hover:text-purpura-900 hover:underline font-medium">Limpar seleção</button>
            </div>
            
            <div class="flex flex-wrap items-center justify-end gap-3 w-full lg:w-auto">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-xs font-bold text-purpura-800 dark:text-purpura-300 uppercase hidden sm:block">Alterar para:</span>
                    <select wire:model="novoStatusId" class="w-full sm:w-auto !py-1.5 !px-3 !text-xs font-bold">
                        <option value="">Selecione o status...</option>
                        @foreach($statusInscricoesDb as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    <button wire:click="salvarStatusEmLote" class="btn btn--primary btn--small">
                        Aplicar
                    </button>
                </div>

                <div class="w-px h-6 bg-purpura-200 dark:bg-purpura-700 hidden sm:block"></div>
                
                <button wire:click="avancarSelecionadas" class="btn btn--cta btn--small">
                    Avançar Etapa <i class="ph-bold ph-arrow-right"></i>
                </button>

                <div class="w-px h-6 bg-purpura-200 dark:bg-purpura-700 hidden sm:block"></div>
                
                <button wire:click="abrirModalLote" class="btn btn--secondary btn--small !bg-white">
                    Ver no Modal
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
            <tr wire:key="linha-inscricao-{{ $inscricao->id }}" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                
                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" wire:key="checkbox-lista-{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                </td>

                <td class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                    #{{ $inscricao->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 text-sm dark:text-white">{{ $inscricao->nome }}</div>
                    <div class="text-[11px] text-gray-400 dark:text-gray-500">{{ $inscricao->cpf }}</div>
                </td>

                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    @if($inscricao->origem === 'importacao')
                        <span class="tag tag--small tag--filled tag--purpura inline-flex items-center gap-1"><i class="ph-bold ph-upload-simple"></i> Importação</span>
                    @elseif($inscricao->origem === 'manual')
                        <span class="tag tag--small tag--filled tag--ponkan inline-flex items-center gap-1"><i class="ph-bold ph-hand-pointing"></i> Manual</span>
                    @else
                        <span class="tag tag--small tag--filled tag--petunia inline-flex items-center gap-1"><i class="ph-bold ph-globe"></i> Formulário</span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-semibold text-gray-700 text-sm dark:text-gray-300">{{ $inscricao->curso->nome ?? 'Não selecionado' }}</div>
                    <div class="text-[11px] text-gray-400">{{ $inscricao->unidade->nome ?? '-' }}</div>
                </td>
                
                <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    @if($inscricao->etapa_atual == 99)
                        <span class="font-bold text-pistache-700 dark:text-pistache-400"><i class="ph-bold ph-check-circle"></i> Finalizado</span>
                    @elseif($inscricao->etapa_atual == 100)
                        <span class="font-bold text-ponkan-600 dark:text-ponkan-400"><i class="ph-bold ph-clock"></i> Em Espera</span>
                    @else
                        Passo {{ $inscricao->etapa_atual }}
                    @endif
                </td>

                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    <span class="tag tag--small {{ $inscricao->pontuacao_total > 0 ? 'tag--filled tag--pistache' : 'tag--filled tag--neutral' }}">
                        {{ $inscricao->pontuacao_total ?? 0 }} pts
                    </span>
                </td>
                
                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    @if($inscricao->posicao_ranking_geral)
                        <span class="tag tag--small tag--outline tag--neutral">
                            {{ $inscricao->posicao_ranking_geral }}º
                        </span>
                    @else
                        <span class="text-gray-300 dark:text-gray-600">-</span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                    <span class="tag tag--small border" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                        {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('inscricao.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.visualizar')))
                            <button wire:click="showQuickView({{ $inscricao->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>

                            <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                        @endif

                        <button x-data="{ copiado: false }" 
                            @click="navigator.clipboard.writeText('{{ route('inscricao.retomar', encrypt($inscricao->id)) }}'); copiado = true; setTimeout(() => copiado = false, 2000)" 
                            class="p-1.5 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 relative" 
                            :class="copiado ? 'text-pistache-500' : 'text-gray-400 hover:text-purpura-500'"
                            title="Copiar Link de Retomada">
                        <i class="text-xl ph" :class="copiado ? 'ph-check-circle' : 'ph-link'"></i>
                        </button>

                        @if(feature('inscricao.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.excluir')))
                            <button wire:click="excluirInscricao({{ $inscricao->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-pitaya-500 hover:bg-pitaya-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente essa inscrição do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-xl ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="13" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhuma inscrição encontrada.
                </td>
            </tr>   
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $inscricao)
                <div wire:key="card-inscricao-{{ $inscricao->id }}" class="card !p-4 !gap-0 hover:border-purpura-300 transition-colors">
                    
                    <div class="flex items-center justify-between mb-4 w-full">
                        @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                        <span class="tag tag--small border flex items-center gap-1.5" style="background-color: {{ $corHex }}10; color: {{ $corHex }}; border-color: {{ $corHex }}30;">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $corHex }};"></span>
                            {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="selecionadas" value="{{ $inscricao->id }}" wire:key="checkbox-card-{{ $inscricao->id }}" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-4 w-full">
                        <div class="flex items-center justify-center w-10 h-10 text-xl text-gray-400 bg-gray-50 rounded-full dark:bg-gray-700 dark:text-gray-300 shrink-0">
                            <i class="ph ph-user"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold text-gray-900 truncate dark:text-white">{{ $inscricao->nome }}</h4>
                            <p class="text-xs text-gray-500 truncate dark:text-gray-400">ID: {{ $inscricao->id }} • {{ $inscricao->cpf }}</p>
                        </div>
                    </div>

                    <div class="divider divider--horizontal border-dashed my-2"></div>

                    <div class="flex items-center justify-between mt-2 w-full">
                        <div class="flex items-center gap-1.5 text-[10px]">
                            <i class="text-xs ph-fill ph-{{ $inscricao->origem === 'importacao' ? 'upload-simple' : ($inscricao->origem === 'manual' ? 'hand-pointing' : 'globe') }}"></i> Via {{ ucfirst($inscricao->origem) }} • 
                            @if($inscricao->etapa_atual == 99)
                                <span class="font-bold text-pistache-600 dark:text-pistache-400">Finalizado</span>
                            @elseif($inscricao->etapa_atual == 100)
                                <span class="font-bold text-ponkan-600 dark:text-ponkan-400">Em Espera</span>
                            @else
                                Passo {{ $inscricao->etapa_atual }}
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div class="text-xs font-bold text-gray-600 dark:text-gray-300">
                                {{ $inscricao->pontuacao_total ?? 0 }} pts
                            </div>
                            <div class="flex flex-wrap justify-end gap-1 mt-1">
                                @if($inscricao->posicao_ranking_geral)
                                    <span class="tag tag--small tag--outline tag--neutral" title="Geral">G: {{ $inscricao->posicao_ranking_geral }}º</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1 mt-3 w-full">
                        <button wire:click="showQuickView({{ $inscricao->id }})" class="p-2 text-gray-400 hover:text-purpura-500 hover:bg-purpura-50 rounded-lg"><i class="text-xl ph ph-info"></i></button>
                        <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-2 text-gray-400 hover:text-ponkan-500 hover:bg-ponkan-50 rounded-lg"><i class="text-xl ph ph-eye"></i></a>
                        <button wire:click="excluirInscricao({{ $inscricao->id }})" class="p-2 text-gray-400 hover:text-pitaya-500 hover:bg-pitaya-50 rounded-lg" onclick="confirm('Excluir permanentemente essa inscrição?') || event.stopImmediatePropagation()"><i class="text-xl ph ph-trash"></i></button>
                    </div>

                </div>
            @endforeach
        </x-slot>

    </x-table>

    @if($modalLoteAberto)
        <div class="fixed inset-0 z-[100] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <button wire:click="$set('modalLoteAberto', false)" class="p-2 text-gray-400 hover:text-pitaya-500 hover:bg-pitaya-50 rounded-lg transition" title="Fechar e Cancelar">
                        <i class="text-2xl ph-bold ph-x"></i>
                    </button>
                    <div>
                        <h3 class="t-heading-x-small text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-check-square-offset text-purpura-500"></i> Alteração em Lote
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Você selecionou <strong>{{ count($selecionadas) }}</strong> inscrições para alterar simultaneamente.</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto bg-gray-50 dark:bg-gray-900 p-2 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase hidden lg:block ml-2">Mover para:</span>
                    <select wire:model="novoStatusId" class="w-full md:w-56 font-bold">
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
            
            <div class="flex-1 overflow-auto p-4 md:p-6 custom-scrollbar">
                <div class="max-w-7xl mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-4 font-bold text-xs text-gray-500 uppercase">Remover</th>
                                    <th class="p-4 font-bold text-xs text-gray-500 uppercase">Candidato</th>
                                    <th class="p-4 font-bold text-xs text-gray-500 uppercase">Interesse</th>
                                    <th class="p-4 font-bold text-xs text-gray-500 uppercase text-center">Pontuação</th>
                                    <th class="p-4 font-bold text-xs text-gray-500 uppercase">Status Atual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($this->getInscricoesModal() as $insc)
                                    <tr class="hover:bg-gray-50 transition-colors" wire:key="lote-{{ $insc->id }}">
                                        <td class="p-4 text-center">
                                            <button wire:click="desmarcarIndividual({{ $insc->id }})" class="text-gray-300 hover:text-pitaya-500 transition">
                                                <i class="ph-bold ph-minus-circle text-xl"></i>
                                            </button>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-sm text-gray-900">{{ $insc->nome }}</div>
                                            <div class="text-xs text-gray-500">{{ $insc->cpf }}</div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-semibold text-sm text-gray-700">{{ $insc->curso->nome ?? '-' }}</div>
                                            <div class="text-[11px] text-gray-400">{{ $insc->unidade->nome ?? '-' }}</div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="tag tag--small {{ $insc->pontuacao_total > 0 ? 'tag--filled tag--pistache' : 'tag--filled tag--neutral' }}">
                                                {{ $insc->pontuacao_total ?? 0 }} pts
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            @php $corHexStatus = $insc->statusInscricao->cor ?? '#6B7280'; @endphp
                                            <span class="tag tag--small border" style="background-color: {{ $corHexStatus }}15; color: {{ $corHexStatus }}; border-color: {{ $corHexStatus }}40;">
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

    @if($modalSelecaoAvancadaAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/40 backdrop-blur-md px-4">
            <div class="flex flex-col w-full max-w-2xl overflow-hidden bg-white shadow-2xl dark:bg-gray-800 rounded-xl">
                
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="t-heading-x-small text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-faders text-purpura-500"></i> Seleção Inteligente</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Defina os parâmetros para capturar candidatos em lote.</p>
                    </div>
                    <button wire:click="$set('modalSelecaoAvancadaAberto', false)" class="text-gray-400 transition hover:text-pitaya-500"><i class="text-2xl ph ph-x"></i></button>
                </div>
                
                <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh]">
                    <label class="flex items-start gap-3 p-3 transition border rounded-lg cursor-pointer hover:bg-purpura-50 dark:hover:bg-gray-700 {{ $selecaoPreencherVagas ? 'border-purpura-500 bg-purpura-50/50 dark:bg-purpura-900/30' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900' }}">
                        <input type="checkbox" wire:model.live="selecaoPreencherVagas" class="w-5 h-5 mt-0.5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 text-md dark:text-white">Preencher Vagas Automaticamente</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight">O sistema lerá as matrizes de ofertas do semestre e selecionará o Top X de cada turma exatamente até o limite configurado de vagas de cada uma.</span>
                        </div>
                    </label>

                    @if(!$selecaoPreencherVagas)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <label class="t-label-12-semibold uppercase text-gray-700 mb-1 block">Quantidade</label>
                                <input type="number" wire:model="selecaoQtd" min="1" class="w-full">
                            </div>
                            <div>
                                <label class="t-label-12-semibold uppercase text-gray-700 mb-1 block">Base de Referência</label>
                                <select wire:model="selecaoBase" class="w-full">
                                    <option value="pontuacao">Pontuação</option>
                                    <option value="ranking_geral">Ranking Geral</option>
                                    <option value="ranking_turma">Ranking da Turma</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="t-label-12-semibold uppercase text-gray-700 mb-1 block">Modo de Seleção</label>
                            <select wire:model="selecaoModo" class="w-full">
                                <option value="global">Selecionar os {{$selecaoQtd}} melhores do contexto atual</option>
                                <option value="por_turma">Selecionar os {{$selecaoQtd}} melhores DE CADA Turma (Unidade + Curso + Turno)</option>
                            </select>
                        </div>
                    @endif
                </div>
                
                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50 dark:bg-gray-900 border-gray-100 dark:border-gray-700">
                    <button wire:click="$set('modalSelecaoAvancadaAberto', false)" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="executarSelecaoAvancada" class="btn btn--primary btn--medium flex items-center gap-2">
                        <i class="ph-bold ph-magic-wand"></i> Executar Filtro
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-fab :actions="$this->fabActions"
    main-color="bg-purpura-600 hover:bg-purpura-800"
    mainIcon="ph ph-plus" />

    @if($modalAberto)
        <x-modal title="Cadastrar Nova Inscrição" max-width="md" close-method="fecharModal">
            <form wire:submit.prevent="salvarNovaInscricao" class="space-y-4">
                
                <div class="bg-purpura-50 border border-purpura-200 p-3 rounded-lg text-xs text-purpura-700 font-medium mb-3">
                    <i class="ph-fill ph-info"></i> O candidato receberá o link seguro de retomada no e-mail para concluir as demais etapas acadêmicas após a efetivação deste cadastro.
                </div>

                <div>
                    <label class="t-label-12-semibold uppercase text-gray-500 mb-1 block">Nome Completo <span class="text-pitaya-500">*</span></label>
                    <input type="text" wire:model="nome" class="w-full" required>
                    @error('nome') <span class="text-pitaya-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="t-label-12-semibold uppercase text-gray-500 mb-1 block">CPF <span class="text-pitaya-500">*</span></label>
                        <input type="text" wire:model="cpf" x-mask="999.999.999-99" placeholder="000.000.000-00" class="w-full" required>
                        @error('cpf') <span class="text-pitaya-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="t-label-12-semibold uppercase text-gray-500 mb-1 block">Celular / Telefone</label>
                        <input type="text" wire:model="celular" x-mask="(99) 99999-9999" placeholder="(00) 00000-0000" class="w-full">
                        @error('celular') <span class="text-pitaya-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="t-label-12-semibold uppercase text-gray-500 mb-1 block">E-mail de Contato <span class="text-pitaya-500">*</span></label>
                    <input type="email" wire:model="email" class="w-full" required>
                    @error('email') <span class="text-pitaya-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="t-label-12-semibold uppercase text-gray-500 mb-1 block">Ciclo de Ingresso <span class="text-pitaya-500">*</span></label>
                    <select wire:model="ciclo_id" class="w-full" required>
                        <option value="">Selecione o Semestre/Ciclo...</option>
                        @foreach($ciclosDb as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                    @error('ciclo_id') <span class="text-pitaya-500 text-[10px] font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-3 pt-5 mt-4 border-t border-gray-100">
                    <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn--primary btn--medium">
                        <span wire:loading.remove>Salvar e Processar</span>
                        <span wire:loading>Aguarde...</span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-pitaya-50">
                    <h3 class="text-lg font-bold text-pitaya-700 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta de E-mail Duplicado
                    </h3>
                    <button wire:click="cancelarAntiSpam" class="text-gray-400 hover:text-pitaya-600 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <p class="text-sm text-gray-700 mb-4 font-medium">
                        O sistema detectou que <strong>{{ count($conflitosAntiSpam) }}</strong> {{ count($conflitosAntiSpam) == 1 ? 'candidato já recebeu' : 'candidatos já receberam' }} o e-mail automático configurado para a etapa <strong>{{ $acaoPendenteNomeStatus ?? 'selecionada' }}</strong>.
                    </p>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2 font-bold uppercase text-[10px]">Candidato</th>
                                        <th class="px-4 py-2 font-bold uppercase text-[10px] text-right">Ação de Remoção</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($conflitosAntiSpam as $conflito)
                                        <tr class="hover:bg-gray-50 transition" wire:key="conflito-{{ $conflito['id'] }}">
                                            <td class="px-4 py-3">
                                                <span class="block font-bold text-gray-900">{{ $conflito['nome'] }}</span>
                                                <span class="text-xs text-gray-500">{{ $conflito['email'] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button wire:click="removerConflitoAntiSpam({{ $conflito['id'] }})" class="btn btn--small bg-white border border-pitaya-200 text-pitaya-600 hover:bg-pitaya-50">
                                                    Tirar da Lista
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <button wire:click="cancelarAntiSpam" class="btn btn--secondary w-full sm:w-auto btn--medium">
                        Cancelar Tudo
                    </button>
                    <button wire:click="prosseguirComReenvioAntiSpam" class="btn bg-pitaya-600 text-white hover:bg-pitaya-700 w-full sm:w-auto btn--medium flex items-center justify-center gap-2">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Prosseguir e Reenviar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>