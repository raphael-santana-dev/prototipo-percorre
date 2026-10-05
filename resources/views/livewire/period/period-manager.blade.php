<div class="w-full font-sans relative" x-data="{ modalAberto: @entangle('modalAberto') }" x-effect="document.body.classList.toggle('overflow-hidden', modalAberto)">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <x-page-header 
        title="Ciclos de Inscrições" 
        subtitle="Gerenciamento de Semestres e Vagas"
        icon="ph ph-calendar-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="actions">
            
            {{-- BOTÃO E MODAL: INCORPORAR INSCRIÇÃO --}}
            <div x-data="{ showEmbedInscricao: false }" class="relative inline-block text-left">
                <button @click="showEmbedInscricao = true" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700">
                    <i class="ph-bold ph-code text-purpura-600"></i> Incorporar / Link
                </button>

                <!-- MODAL EMBED ALPINE -->
                <div x-show="showEmbedInscricao" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4" x-transition.opacity>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-2xl max-w-lg w-full" @click.away="showEmbedInscricao = false" x-transition>
                        
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="ph-fill ph-code text-purpura-600"></i> Integrar Inscrição Oficial
                            </h3>
                            <button @click="showEmbedInscricao = false" class="text-gray-400 hover:text-red-500 transition-colors"><i class="ph-bold ph-x text-xl"></i></button>
                        </div>
                        
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 text-left whitespace-normal">
                            O link oficial redireciona o candidato para o <b>Ciclo Ativo</b> de forma automática. Copie o iframe para embutir no seu site, ou use o Link Público para enviar via WhatsApp/E-mail.
                        </p>
                        
                        <div class="space-y-4 text-left">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Código Iframe (Embed)</label>
                                <textarea id="embedCodeInscricaoOficial" readonly class="w-full text-xs font-mono p-3 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 focus:ring-purpura-500 focus:border-purpura-500" rows="3">&lt;iframe src=&quot;{{ url('/inscricao?embed=true') }}&quot; width=&quot;100%&quot; height=&quot;800&quot; frameborder=&quot;0&quot; style=&quot;border:none; background:transparent;&quot;&gt;&lt;/iframe&gt;</textarea>
                                <div class="flex justify-end mt-1">
                                    <button @click="navigator.clipboard.writeText(document.getElementById('embedCodeInscricaoOficial').value); $dispatch('sucesso', {msg: 'Código Iframe copiado!'});" class="text-xs font-bold text-purpura-600 hover:text-purpura-800 dark:text-purpura-400 transition-colors">
                                        Copiar Iframe
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Link Público Direto</label>
                                <input type="text" id="linkPublicoInscricao" readonly value="{{ url('/inscricao') }}" class="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 focus:ring-purpura-500 focus:border-purpura-500">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-5">
                            <a href="{{ url('/inscricao') }}" target="_blank" class="text-sm font-bold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center gap-1 transition-colors">
                                Testar Formulário <i class="ph-bold ph-arrow-up-right"></i>
                            </a>
                            <button @click="navigator.clipboard.writeText(document.getElementById('linkPublicoInscricao').value); $dispatch('sucesso', {msg: 'Link Público copiado!'}); showEmbedInscricao = false;" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                                <i class="ph-bold ph-copy"></i> Copiar Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                <button wire:click="abrirModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-none">
                    <i class="ph-bold ph-plus"></i> Novo Ciclo
                </button>
            @endif
        </x-slot>
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        {{-- CAMPO DE BUSCA INLINE NA TABELA (Ficará na mesma linha da paginação/grid) --}}
        <x-slot name="search">
            <div class="relative w-full sm:w-72 shrink-0">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Pesquisar por nome do ciclo..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition-colors dark:bg-gray-800 dark:border-gray-700">
            </div>
        </x-slot>

        {{-- FILTROS EM LINHA SEPARADA E AÇÕES EM LOTE --}}
        <x-slot name="filters">
            <div class="w-full flex flex-col gap-4 mt-2">
                
                {{-- Linha de Chips e Ordenação --}}
                <div class="flex flex-wrap items-center gap-2 w-full">
                    
                    {{-- Ordenação Movida para junto dos Chips --}}
                    <div class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0 mr-1">
                        <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Ordenar</span>
                        <select wire:model.live="ordenacao" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 dark:text-gray-200 cursor-pointer font-medium max-w-[170px] truncate">
                            <option value="recentes">Mais Recentes</option>
                            <option value="mais_inscritos">Mais Inscritos</option>
                            <option value="menos_inscritos">Menos Inscritos</option>
                            <option value="nome_asc">Nome (A-Z)</option>
                            <option value="nome_desc">Nome (Z-A)</option>
                        </select>
                    </div>

                    <div wire:ignore.self x-data="{
                        visible: [],
                        allFilters: ['ano', 'semestre', 'status'],
                        init() {
                            if ($wire.filtro_ano && !this.visible.includes('ano')) this.visible.push('ano');
                            if ($wire.filtro_semestre && !this.visible.includes('semestre')) this.visible.push('semestre');
                            if ($wire.filtro_status !== '' && !this.visible.includes('status')) this.visible.push('status');
                        },
                        add(f) { if(!this.visible.includes(f)) this.visible.push(f); },
                        remove(f) {
                            $wire.set('filtro_' + f, '');
                            this.visible = this.visible.filter(i => i !== f);
                        },
                        get canAddMore() { return this.visible.length < this.allFilters.length; }
                    }" class="flex flex-wrap items-center gap-2">
                        
                        <!-- Chip: Ano -->
                        <div x-show="visible.includes('ano')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                            <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Ano</span>
                            <select wire:model.live="filtro_ano" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                                <option value="">Todos</option>
                                @if(isset($anosDisponiveis))
                                    @foreach($anosDisponiveis as $ano) <option value="{{ $ano }}">{{ $ano }}</option> @endforeach
                                @endif
                            </select>
                            <button @click="remove('ano')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                        </div>

                        <!-- Chip: Semestre -->
                        <div x-show="visible.includes('semestre')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                            <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Semestre</span>
                            <select wire:model.live="filtro_semestre" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                                <option value="">Todos</option>
                                <option value="1">1º Semestre</option>
                                <option value="2">2º Semestre</option>
                            </select>
                            <button @click="remove('semestre')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                        </div>

                        <!-- Chip: Status -->
                        <div x-show="visible.includes('status')" x-cloak class="flex items-center h-9 px-1.5 rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                            <span class="pl-1.5 text-[10px] font-bold uppercase tracking-widest text-indigo-600">Status</span>
                            <select wire:model.live="filtro_status" class="border-none shadow-none bg-transparent text-sm focus:ring-0 py-0 pl-2 pr-7 text-gray-800 cursor-pointer font-medium max-w-[150px] truncate">
                                <option value="">Todos</option>
                                <option value="1">Ativos</option>
                                <option value="0">Inativos</option>
                            </select>
                            <button @click="remove('status')" class="pr-1 text-indigo-400 hover:text-indigo-600 flex items-center justify-center transition-colors"><i class="ph-bold ph-x text-sm"></i></button>
                        </div>

                        <div x-show="canAddMore" x-data="{ open: false }" class="relative ml-1 shrink-0" x-cloak>
                            <button @click="open = !open" class="text-sm font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors h-9 px-2 focus:outline-none">
                                + Adicionar Filtro
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute left-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-xl shadow-xl z-50 py-2">
                                <button x-show="!visible.includes('ano')" @click="add('ano'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-calendar-blank text-gray-400 text-lg"></i> Ano</button>
                                <button x-show="!visible.includes('semestre')" @click="add('semestre'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-list-numbers text-gray-400 text-lg"></i> Semestre</button>
                                <button x-show="!visible.includes('status')" @click="add('status'); open = false" class="w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="ph-bold ph-toggle-left text-gray-400 text-lg"></i> Status Ativo/Inativo</button>
                            </div>
                        </div>

                        <button x-show="$wire.filtro_ano || $wire.filtro_semestre || $wire.filtro_status !== '' || $wire.filtroNome" x-cloak wire:click="limparFiltros" @click="visible = []" class="text-sm font-medium text-gray-400 hover:text-red-500 flex items-center gap-1 h-9 px-2 ml-1 transition">
                            Limpar Todos
                        </button>
                    </div>
                </div>

                {{-- Barra Escura de Ações em Lote --}}
                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    @if(count($selecionadas) > 0)
                        <div class="bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 p-3 rounded-lg flex flex-col lg:flex-row justify-between items-center gap-4 shadow-sm w-full transition-all mt-2">
                            <div class="flex items-center shrink-0">
                                <span class="font-medium text-white text-sm">{{ count($selecionadas) }} ciclos selecionados</span>
                                <button wire:click="desmarcarTodas" class="ml-4 text-xs text-gray-400 hover:text-white font-medium transition">Limpar Seleção</button>
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-end gap-3 w-full lg:w-auto">
                                <button class="btn btn--secondary btn--small !text-red-500 !border-red-500 hover:!bg-red-500 hover:!text-white" onclick="confirm('Não implementado')">Deletar Selecionados</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </x-slot>

        @forelse ($registros as $ciclo)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="linha-ciclo-{{ $ciclo->id }}">
                
                <td class="px-4 py-1.5 text-center whitespace-nowrap w-10">
                    <input type="checkbox" wire:model.live="selecionadas" value="{{ $ciclo->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer" wire:key="checkbox-lista-{{ $ciclo->id }}">
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">#{{ str_pad($ciclo->id, 3, '0', STR_PAD_LEFT) }}</td>
                
                <td class="px-4 py-1.5 whitespace-nowrap min-w-[200px]">
                    <div class="font-bold text-gray-900 text-[13px] leading-tight dark:text-white">{{ $ciclo->nome }}</div>
                    <div class="text-xs text-gray-500 leading-tight">Ano {{ $ciclo->ano }} • {{ $ciclo->semestre }}º Sem.</div>
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap text-[13px] text-gray-600 dark:text-gray-300">
                    <i class="ph-fill ph-calendar text-gray-400"></i> {{ $ciclo->data_inicio->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-1.5 whitespace-nowrap text-[13px] text-gray-600 dark:text-gray-300">
                    <i class="ph-fill ph-calendar text-gray-400"></i> {{ $ciclo->data_fim->format('d/m/Y H:i') }}
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap text-center">
                    <span class="px-2.5 py-1 text-[11px] font-bold text-purpura-700 bg-purpura-100 rounded border border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400 uppercase tracking-wider">
                        {{ $ciclo->inscricoes_count ?? 0 }} Registros
                    </span>
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap">
                    @php
                        $totalMeta = $ciclo->total_meta ?? 0;
                        $inscritos = $ciclo->inscricoes_count ?? 0;
                        $percentualMeta = $totalMeta > 0 ? round(($inscritos / $totalMeta) * 100, 1) : ($inscritos > 0 ? 100 : 0);
                        $corBarraMeta = $percentualMeta >= 100 ? 'bg-emerald-500' : ($percentualMeta >= 50 ? 'bg-blue-500' : 'bg-purpura-500');
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full min-w-[120px]">
                        <div class="flex justify-between w-full text-[10px] font-bold mb-1">
                            <span class="text-gray-500 dark:text-gray-400">{{ $inscritos }} captados</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $totalMeta }} meta</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden flex">
                            <div class="{{ $corBarraMeta }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($percentualMeta, 100) }}%"></div>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap">
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                            <span class="text-[10px] font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-400' }}">{{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}</span>
                        </div>
                    @else
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded border {{ $ciclo->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">{{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}</span>
                    @endif
                </td>
                
                <td class="px-4 py-1.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button x-data="{ copiado: false }" 
                                @click="
                                    let code = `<iframe src='{{ route('publico.inscricao') }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                                    navigator.clipboard.writeText(code); 
                                    copiado = true; 
                                    setTimeout(() => copiado = false, 2000);
                                " 
                                class="p-1.5 transition-colors rounded-lg relative" 
                                :class="copiado ? 'text-green-600 bg-green-50 dark:bg-green-900/30' : 'text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-600'"
                                title="Copiar Código de Incorporação (Iframe)">
                            <i class="text-lg ph" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i>
                        </button>
                        <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver CRM"><i class="text-lg ph-fill ph-kanban"></i></a>
                        <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida"><i class="text-lg ph ph-info"></i></button>
                        <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Ver Detalhes"><i class="text-lg ph ph-eye"></i></a>
                        <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar"><i class="text-lg ph ph-copy"></i></button>
                        <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Completo"><i class="text-lg ph ph-pencil-simple"></i></a>
                        <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor Formulário Público"><i class="text-lg ph ph-list-dashes"></i></a>
                        <a href="{{ route('ciclos.regras', $ciclo->id) }}" class="p-1.5 text-yellow-600 transition-colors rounded hover:bg-yellow-50 dark:hover:bg-gray-600" title="Regras de Pontuação"><i class="text-lg ph ph-star"></i></a>
                        <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir permanentemente?')"><i class="text-lg ph ph-trash"></i></button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center">
                        <i class="ph ph-magnifying-glass text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="font-medium text-gray-600 dark:text-gray-300">Nenhum ciclo encontrado.</p>
                        <p class="text-xs mt-1">Tente ajustar ou limpar os filtros de busca.</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $ciclo)
                <div wire:key="card-ciclo-{{ $ciclo->id }}" class="card !p-4 !gap-0 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    
                    <div class="flex items-center justify-between mb-4 w-full">
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded border {{ $ciclo->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $ciclo->status ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            {{ $ciclo->status ? 'Ativo' : 'Inativo' }}
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="selecionadas" value="{{ $ciclo->id }}" wire:key="checkbox-card-{{ $ciclo->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex items-start gap-3 mb-4 w-full">
                        <div class="w-10 h-10 rounded-lg bg-purpura-50 text-purpura-600 flex items-center justify-center shrink-0 border border-purpura-100">
                            <i class="ph-bold ph-calendar text-xl"></i>
                        </div>
                        <div class="overflow-hidden w-full">
                            <h4 class="text-sm font-bold text-gray-900 truncate dark:text-white" title="{{ $ciclo->nome }}">{{ $ciclo->nome }}</h4>
                            <p class="text-[11px] font-medium text-gray-500 uppercase tracking-widest mt-0.5">Semestre {{ $ciclo->ano }}.{{ $ciclo->semestre }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-2 space-y-2 border border-gray-100 dark:border-gray-700 mb-3">
                        <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                            <span>Abertura</span>
                            <span class="text-gray-900">{{ $ciclo->data_inicio->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                            <span>Encerramento</span>
                            <span class="text-gray-900">{{ $ciclo->data_fim->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    @php
                        $totalVagas = $ciclo->total_vagas ?? 0;
                        $preenchidas = $ciclo->vagas_preenchidas ?? 0;
                        $percentual = $totalVagas > 0 ? round(($preenchidas / $totalVagas) * 100, 1) : 0;
                        $corBarra = $percentual >= 100 ? 'bg-red-500' : ($percentual >= 80 ? 'bg-orange-500' : 'bg-emerald-500');
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full mb-3 px-1">
                        <div class="flex justify-between w-full text-[10px] font-bold mb-1">
                            <span class="text-gray-500 uppercase tracking-wider">Ocupação</span>
                            <span class="text-gray-700">{{ $preenchidas }} / {{ $totalVagas }} vagas</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden flex">
                            <div class="{{ $corBarra }} h-1.5 rounded-full transition-all" style="width: {{ min($percentual, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700 w-full">
                        <span class="text-xs font-bold text-purpura-600">{{ $ciclo->inscricoes_count ?? 0 }} inscritos</span>
                        
                        <div class="flex items-center gap-1">
                            
                            <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="p-1.5 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 rounded-md transition" title="CRM Kanban"><i class="text-lg ph-bold ph-kanban"></i></a>
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 rounded-md transition" title="Info"><i class="text-lg ph-bold ph-info"></i></button>
                            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-md transition"><i class="text-lg ph-bold ph-arrow-right"></i></a>
                        </div>
                    </div>

                </div>
            @endforeach
        </x-slot>

    </x-table>
    
    <!-- MODAL DE CADASTRO EM TELA CHEIA (STEPPER WIZARD) -->
    @if($modalAberto)
        <div class="fixed inset-0 z-[100] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden">
            
            <!-- HEADER DO MODAL -->
            <div class="flex justify-between items-center px-8 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
                <div class="flex items-center gap-3">
                    <span class="p-2 bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 rounded-lg text-xl"><i class="ph-fill ph-calendar-plus"></i></span>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Assistente de Configuração de Ciclo</h2>
                        <p class="text-xs text-gray-500">Preencha os passos sequenciais para estruturar o processo seletivo.</p>
                    </div>
                </div>
                <button wire:click="$set('modalAberto', false)" class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"><i class="text-2xl ph-bold ph-x"></i></button>
            </div>

            <!-- CORPO DO MODAL (ESTILO MAC OS / STEPPER) -->
            <div class="flex flex-1 overflow-hidden">
                
                <!-- MENU LATERAL ESQUERDO (PASSOS) -->
                <div class="w-80 bg-white dark:bg-gray-800/60 border-r border-gray-200 dark:border-gray-700 p-6 flex flex-col gap-2 shrink-0 overflow-y-auto">
                    @php
                        $passosLista = [
                            1 => ['titulo' => 'Informações Gerais', 'desc' => 'Nome, datas e período'],
                            2 => ['titulo' => 'Estrutura Académica', 'desc' => 'Unidades, cursos e turnos'],
                            3 => ['titulo' => 'Distribuição de Vagas', 'desc' => 'Capacidade e faixa etária'],
                            4 => ['titulo' => 'Etapas do Ciclo', 'desc' => 'Funil de status (CRM)'],
                            5 => ['titulo' => 'Documentos Exigidos', 'desc' => 'Exigências de matrícula'],
                            6 => ['titulo' => 'Conclusão e Sucesso', 'desc' => 'Formulários e liberação']
                        ];
                    @endphp

                    @foreach($passosLista as $num => $info)
                        <div wire:click="irParaPasso({{ $num }})" 
                             class="flex items-start gap-3.5 p-3 rounded-xl transition cursor-pointer {{ $passoAtual === $num ? 'bg-purpura-50 dark:bg-purpura-900/40 border border-purpura-200 dark:border-purpura-800 shadow-sm' : ($cicloIdEmEdicao && $num < $passoAtual ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50' : 'opacity-60 pointer-events-none') }}">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0 {{ $passoAtual === $num ? 'bg-purpura-600 text-white shadow' : ($cicloIdEmEdicao && $num < $passoAtual ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                                @if($cicloIdEmEdicao && $num < $passoAtual)
                                    <i class="ph-bold ph-check"></i>
                                @else
                                    {{ $num }}
                                @endif
                            </div>
                            <div>
                                <h4 class="text-xs font-bold {{ $passoAtual === $num ? 'text-purpura-700 dark:text-purpura-300' : 'text-gray-800 dark:text-gray-200' }}">{{ $info['titulo'] }}</h4>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight mt-0.5">{{ $info['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- CONTEÚDO PRINCIPAL DO PASSO -->
                <div class="flex-1 overflow-y-auto p-8 bg-gray-50/50 dark:bg-gray-900 custom-scrollbar flex flex-col justify-between">
                    <div class="max-w-4xl mx-auto w-full">
                        
                        <!-- PASSO 1: DADOS BÁSICOS -->
                        @if($passoAtual === 1)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl  space-y-6">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-calendar text-purpura-600"></i> Passo 1: Informações Básicas</h3>
                                    <p class="text-xs text-gray-500 mt-1">Defina o nome de exibição, ano, semestre e as datas de abertura e encerramento das inscrições.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome do Ciclo</label>
                                        <input type="text" wire:model="nome" placeholder="Ex: Processo Seletivo 2026.2" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm font-bold shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ano *</label>
                                        <input type="number" wire:model="ano" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm font-bold shadow-sm">
                                        @error('ano') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Semestre *</label>
                                        <select wire:model="semestre" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm font-bold shadow-sm">
                                            <option value="1">1º Semestre</option>
                                            <option value="2">2º Semestre</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Data e Hora de Abertura *</label>
                                        <input type="datetime-local" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm shadow-sm">
                                        @error('data_inicio') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Data e Hora de Encerramento *</label>
                                        <input type="datetime-local" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm shadow-sm">
                                        @error('data_fim') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                                    <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300 cursor-pointer">Ativar este ciclo imediatamente</label>
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 2: UNIDADE / CURSO / TURNO (ESTILO MAC OS EXPLORER) -->
                        @if($passoAtual === 2)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl  space-y-6">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-tree-structure text-purpura-600"></i> Passo 2: Estrutura Académica</h3>
                                    <p class="text-xs text-gray-500 mt-1">Selecione as Unidades, Cursos e Turnos disponíveis neste processo seletivo.</p>
                                </div>

                                <div class="flex flex-col md:flex-row h-[380px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500">1. Unidades</div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                            @foreach($unidadesDb as $u)
                                                <div wire:key="unidade-{{ $u->id }}" wire:click="setActiveUnidade({{ $u->id }})" class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                        <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $u->id }}" class="w-4 h-4 rounded text-purpura-600">
                                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $u->nome }}</span>
                                                    </label>
                                                    <i class="ph ph-caret-right text-lg {{ $activeUnidadeId == $u->id ? 'text-purpura-500' : 'text-gray-300' }}"></i>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500">2. Cursos</div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                            @if($activeUnidadeId)
                                                @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                                    <div wire:key="curso-{{ $activeUnidadeId }}-{{ $c->id }}" wire:click="setActiveCurso({{ $c->id }})" class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                            <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $activeUnidadeId }}-{{ $c->id }}" class="w-4 h-4 rounded text-purpura-600">
                                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $c->nome }}</span>
                                                        </label>
                                                        <i class="ph ph-caret-right text-lg {{ $activeCursoId == $c->id ? 'text-purpura-500' : 'text-gray-300' }}"></i>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="h-full flex flex-col items-center justify-center text-gray-400 text-xs font-bold uppercase">Selecione uma Unidade</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900/30">
                                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500">3. Turnos</div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                            @if($activeCursoId)
                                                @php $cs = $cursosDb->firstWhere('id', $activeCursoId); @endphp
                                                @if($cs)
                                                    @foreach($cs->turnosVinculados as $t)
                                                        <div wire:key="turno-{{ $activeUnidadeId }}-{{ $activeCursoId }}-{{ $t->id }}" class="flex items-center p-2.5 rounded-lg hover:bg-white dark:hover:bg-gray-700 transition">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                                <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $activeUnidadeId }}-{{ $activeCursoId }}-{{ $t->id }}" class="w-4 h-4 rounded text-purpura-600">
                                                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t->nome }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @else
                                                <div class="h-full flex flex-col items-center justify-center text-gray-400 text-xs font-bold uppercase">Selecione um Curso</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 3: DISTRIBUIÇÃO DE VAGAS -->
                        @if($passoAtual === 3)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl  space-y-6">
                                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <div>
                                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-users-three text-purpura-600"></i> Passo 3: Distribuição de Vagas</h3>
                                        <p class="text-xs text-gray-500 mt-1">Preencha o limite de vagas e faixas etárias para cada combinação académica selecionada.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @forelse($ofertasVagas as $index => $oferta)
                                        <div wire:key="oferta-{{ $index }}" class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col xl:flex-row gap-3 items-end">
                                            
                                            <div class="flex-1 w-full">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade</label>
                                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700">{{ $oferta['unidade_nome'] ?? '' }}</div>
                                            </div>
                                            <div class="flex-1 w-full">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso</label>
                                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 truncate" title="{{ $oferta['curso_nome'] ?? '' }}">{{ $oferta['curso_nome'] ?? '' }}</div>
                                            </div>
                                            <div class="w-full xl:w-28">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno</label>
                                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700">{{ $oferta['turno_nome'] ?? '' }}</div>
                                            </div>
                                            
                                            <div class="w-full xl:w-24 shrink-0">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Vagas *</label>
                                                <input type="number" wire:model.live="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500 font-black text-purpura-600 dark:text-purpura-400 text-center">
                                            </div>

                                            <div class="w-full xl:w-24 shrink-0">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center text-ponkan-600">Meta</label>
                                                <input type="number" wire:model="ofertasVagas.{{ $index }}.meta" placeholder="-" class="w-full text-sm rounded-lg border-ponkan-200 dark:border-ponkan-800 dark:bg-gray-700 dark:text-white py-2 focus:ring-ponkan-500 font-bold text-center">
                                            </div>

                                            <div class="w-full xl:w-16 shrink-0">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Mín</label>
                                                <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_min" placeholder="Livre" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 text-center font-medium focus:ring-purpura-500">
                                            </div>

                                            <div class="w-full xl:w-16 shrink-0">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Máx</label>
                                                <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_max" placeholder="Livre" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 text-center font-medium focus:ring-purpura-500">
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30 text-gray-400 text-xs font-bold">Volte ao Passo 2 e selecione as combinações académicas.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 4: ETAPAS DO CICLO (PIPELINE) -->
                        @if($passoAtual === 4)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl space-y-6">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-funnel text-purpura-600"></i> Passo 4: Etapas do Ciclo (Pipeline / Kanban)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Organize as colunas de status pelas quais os candidatos passarão no funil seletivo.</p>
                                </div>

                                <div class="w-full" x-data="{
                                     initSortable() {
                                         // Aguarda o objeto global estar disponível para evitar erros
                                         if (typeof Sortable === 'undefined') {
                                             setTimeout(() => this.initSortable(), 200);
                                             return;
                                         }
                                         new Sortable(this.$refs.statusList, {
                                             animation: 150, 
                                             handle: '.drag-handle', 
                                             ghostClass: 'opacity-50',
                                             onEnd: () => {
                                                 let items = Array.from(this.$refs.statusList.children).map(el => el.dataset.id);
                                                 $wire.atualizarOrdemStatus(items);
                                             }
                                         });
                                     }
                                 }" x-init="$nextTick(() => { initSortable() })">
                                 
                                    <div class="flex gap-2 mb-4">
                                        <select wire:model="novoStatusSelecionado" class="flex-1 text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                                            <option value="">Adicionar etapa ao funil...</option>
                                            @foreach($statusDisponiveis as $st)
                                                @if(!in_array($st->id, $statusSelecionados))
                                                    <option value="{{ $st->id }}">{{ $st->nome }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="adicionarStatusPipeline" class="bg-purpura-600 text-white px-4 py-2 rounded-lg font-bold text-xs hover:bg-purpura-700 transition shadow-sm flex items-center gap-1">
                                            <i class="ph-bold ph-plus"></i> Inserir
                                        </button>
                                    </div>

                                    <div x-ref="statusList" class="flex flex-col gap-2">
                                        @foreach($statusSelecionados as $index => $statusId)
                                            @php $stObj = $statusDisponiveis->firstWhere('id', $statusId); @endphp
                                            @if($stObj)
                                                <div data-id="{{ $statusId }}" wire:key="st-{{ $statusId }}" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg group transition-colors hover:border-purpura-300">
                                                    <div class="flex items-center gap-3">
                                                        <i class="ph-bold ph-dots-six-vertical text-gray-400 cursor-grab active:cursor-grabbing drag-handle text-xl hover:text-gray-700 dark:hover:text-gray-200"></i>
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-black">{{ $index + 1 }}</span>
                                                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $stObj->nome }}</span>
                                                    </div>
                                                    <button type="button" wire:click="removerStatusPipeline('{{ $statusId }}')" class="text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 p-1.5 rounded-lg transition">
                                                        <i class="ph-bold ph-trash text-base"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 5: DOCUMENTOS EXIGIDOS -->
                        @if($passoAtual === 5)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl  space-y-6">
                                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <div>
                                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-files text-purpura-600"></i> Passo 5: Documentos Exigidos</h3>
                                        <p class="text-xs text-gray-500 mt-1">Especifique as exigências documentais para a efetivação da matrícula digital.</p>
                                    </div>
                                    <button type="button" wire:click="addDocumento" class="px-3.5 py-2 bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-300 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                        <i class="ph-bold ph-plus text-sm"></i> Novo Documento
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    @forelse($documentosExigidos as $index => $doc)
                                        <div wire:key="doc-{{ $index }}" class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col md:flex-row gap-4 items-end">
                                            <div class="flex-1 w-full">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome do Documento *</label>
                                                <input type="text" wire:model="documentosExigidos.{{ $index }}.nome" placeholder="Ex: Histórico Escolar" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 font-bold">
                                            </div>
                                            <div class="flex-1 w-full">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Instruções</label>
                                                <input type="text" wire:model="documentosExigidos.{{ $index }}.descricao" placeholder="Ex: Assinado e carimbado" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2">
                                            </div>
                                            <div class="w-full md:w-auto flex items-center justify-between gap-4 pb-1.5">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" wire:model="documentosExigidos.{{ $index }}.is_obrigatorio" class="w-4 h-4 rounded text-purpura-600">
                                                    <span class="ml-2 text-xs font-bold text-gray-700 dark:text-gray-300">Obrigatório</span>
                                                </label>
                                                <button type="button" wire:click="removeDocumento({{ $index }})" class="p-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 rounded-lg shadow-sm hover:bg-red-500 hover:text-white transition"><i class="ph-bold ph-trash"></i></button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30 text-gray-400 text-xs font-bold">Nenhum documento exigido cadastrado.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 6: SUCESSO E FORM BUILDER -->
                        @if($passoAtual === 6)
                            <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl  text-center space-y-6">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full dark:bg-green-900/30 dark:text-green-400 mx-auto text-4xl shadow-inner">
                                    <i class="ph-bold ph-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Ciclo Criado e Configurado!</h3>
                                    <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Todas as etapas, vagas e regras académicas foram guardadas com sucesso. Agora pode avançar para o Construtor de Formulários para montar as perguntas públicas da inscrição.</p>
                                </div>
                                
                                @if($cicloCriadoId)
                                    <div class="pt-4 flex flex-wrap justify-center gap-4">
                                        <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $cicloCriadoId]) }}" class="px-6 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-lg transition flex items-center gap-2">
                                            <i class="ph-bold ph-list-dashes text-base"></i> Ir para o Construtor de Formulário
                                        </a>
                                        <button wire:click="$set('modalAberto', false)" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                                            Concluir e Fechar
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>

                    <!-- BOTÕES DE RODAPÉ DO WIZARD -->
                    @if($passoAtual < 6)
                        <div class="max-w-4xl mx-auto w-full pt-6 mt-8 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center shrink-0">
                            <button type="button" wire:click="passoAnterior" @if($passoAtual === 1) disabled @endif class="px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 disabled:opacity-40 transition shadow-sm">
                                <i class="ph-bold ph-arrow-left"></i> Anterior
                            </button>
                            <button type="button" wire:click="proximoPasso" class="px-8 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-md transition flex items-center gap-2">
                                {{ $passoAtual === 5 ? 'Finalizar Configuração' : 'Próximo Passo' }} <i class="ph-bold ph-arrow-right"></i>
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif
</div>