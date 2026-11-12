<div class="w-full font-sans relative" wire:poll.8s x-data="{ modalAberto: @entangle('modalAberto') }" x-effect="document.body.classList.toggle('overflow-hidden', modalAberto)">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <x-page-header 
        title="Ciclos de Inscrições" 
        subtitle="Gerenciamento de Semestres e Vagas"
        icon="ph ph-calendar-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="actions">
            
            {{-- BOTÃO E MODAL: INCORPORAR INSCRIÇÃO (COM X-TELEPORT E Z-[999]) --}}
            <div x-data="{ showEmbedInscricao: false }" @keydown.escape.window="showEmbedInscricao = false" class="relative inline-block text-left">
                <button type="button" @click="showEmbedInscricao = true" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700">
                    <i class="ph ph-code text-purpura-600"></i> Incorporar / Link
                </button>

                <template x-teleport="body">
                    <div x-show="showEmbedInscricao"
                         x-cloak
                         class="fixed inset-0 z-[999] flex items-center justify-center p-4 font-sans"
                         role="dialog"
                         aria-modal="true"
                         x-transition.opacity>
                        
                        {{-- Backdrop --}}
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs" @click="showEmbedInscricao = false"></div>

                        {{-- Painel do Modal --}}
                        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Integrar Inscrição Oficial
                                </h3>
                                <button type="button" @click="showEmbedInscricao = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1 rounded-lg transition">
                                    <i class="ph ph-x text-lg"></i>
                                </button>
                            </div>
                            
                            <div class="p-6 space-y-4 text-left">
                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                    O link oficial redireciona o candidato para o <b>Ciclo Regular Ativo</b> automaticamente. Para projetos customizados, copie o link diretamente no ícone da linha do ciclo na tabela.
                                </p>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Código Iframe (Embed)</label>
                                        <button type="button" @click="navigator.clipboard.writeText(document.getElementById('embedCodeInscricaoOficial').value); $dispatch('sucesso', {msg: 'Código Iframe copiado!'});" class="text-xs font-medium text-purpura-600 hover:underline">
                                            Copiar código
                                        </button>
                                    </div>
                                    <textarea id="embedCodeInscricaoOficial" readonly class="w-full text-xs font-mono p-3 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-purpura-500" rows="3">&lt;iframe src=&quot;{{ url('/inscricao?embed=true') }}&quot; width=&quot;100%&quot; height=&quot;800&quot; frameborder=&quot;0&quot; style=&quot;border:none; background:transparent;&quot;&gt;&lt;/iframe&gt;</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Link Público Direto</label>
                                    <input type="text" id="linkPublicoInscricao" readonly value="{{ url('/inscricao') }}" class="w-full text-xs font-mono p-2.5 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                                </div>
                            </div>
                            
                            <div class="px-6 py-3.5 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                @if(auth()->user()->hasAnyRole(['dev', 'admin']) || auth()->user()->can('formulario.testar') || auth()->user()->can('inscricao.criar'))
                                    <a href="{{ url('/inscricao?modo=teste') }}" target="_blank" class="text-xs font-medium text-amber-700 hover:text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors">
                                        <i class="ph ph-flask"></i> Testar em Sandbox ↗
                                    </a>
                                @else
                                    <span></span>
                                @endif
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="showEmbedInscricao = false" class="btn btn--secondary btn--small">
                                        Fechar
                                    </button>
                                    <button type="button" @click="navigator.clipboard.writeText(document.getElementById('linkPublicoInscricao').value); $dispatch('sucesso', {msg: 'Link Público copiado!'}); showEmbedInscricao = false;" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                        <i class="ph ph-copy"></i> Copiar Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                <button wire:click="abrirModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Novo Ciclo
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

        {{-- CAMPO DE BUSCA INLINE NA TABELA --}}
        <x-slot name="search">
            <div class="relative w-full sm:w-72 shrink-0">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.500ms="filtroNome" placeholder="Pesquisar por nome do ciclo..." class="w-full pl-9 h-9 bg-white border-gray-200 text-sm rounded-lg focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            </div>
        </x-slot>

        {{-- FILTROS E AÇÕES EM LOTE --}}
        <x-slot name="filters">
            <div class="w-full flex flex-col gap-4 mt-2">
                <div class="flex flex-wrap items-center gap-2 w-full">
                    <div class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shrink-0">
                        <span class="text-[11px] text-gray-400 mr-1.5">Ordenar:</span>
                        <select wire:model.live="ordenacao" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-700 dark:text-gray-200 cursor-pointer font-medium">
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
                        
                        <div x-show="visible.includes('ano')" x-cloak class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 shrink-0">
                            <span class="text-[11px] text-gray-400 mr-1">Ano:</span>
                            <select wire:model.live="filtro_ano" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-700 dark:text-gray-200 cursor-pointer font-medium">
                                <option value="">Todos</option>
                                @if(isset($anosDisponiveis))
                                    @foreach($anosDisponiveis as $ano) <option value="{{ $ano }}">{{ $ano }}</option> @endforeach
                                @endif
                            </select>
                            <button type="button" @click="remove('ano')" class="text-gray-400 hover:text-gray-700"><i class="ph ph-x text-xs"></i></button>
                        </div>

                        <div x-show="visible.includes('semestre')" x-cloak class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 shrink-0">
                            <span class="text-[11px] text-gray-400 mr-1">Semestre:</span>
                            <select wire:model.live="filtro_semestre" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-700 dark:text-gray-200 cursor-pointer font-medium">
                                <option value="">Todos</option>
                                <option value="1">1º Semestre</option>
                                <option value="2">2º Semestre</option>
                            </select>
                            <button type="button" @click="remove('semestre')" class="text-gray-400 hover:text-gray-700"><i class="ph ph-x text-xs"></i></button>
                        </div>

                        <div x-show="visible.includes('status')" x-cloak class="flex items-center h-9 px-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 shrink-0">
                            <span class="text-[11px] text-gray-400 mr-1">Status:</span>
                            <select wire:model.live="filtro_status" class="border-none shadow-none bg-transparent text-xs focus:ring-0 py-0 pl-1 pr-6 text-gray-700 dark:text-gray-200 cursor-pointer font-medium">
                                <option value="">Todos</option>
                                <option value="1">Ativos</option>
                                <option value="0">Inativos</option>
                            </select>
                            <button type="button" @click="remove('status')" class="text-gray-400 hover:text-gray-700"><i class="ph ph-x text-xs"></i></button>
                        </div>

                        <div x-show="canAddMore" x-data="{ open: false }" class="relative shrink-0" x-cloak>
                            <button type="button" @click="open = !open" class="text-xs font-medium text-purpura-600 hover:underline flex items-center gap-1 h-9 px-2">
                                + Adicionar Filtro
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute left-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 py-1">
                                <button type="button" x-show="!visible.includes('ano')" @click="add('ano'); open = false" class="w-full text-left px-3.5 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"><i class="ph ph-calendar-blank text-gray-400"></i> Ano</button>
                                <button type="button" x-show="!visible.includes('semestre')" @click="add('semestre'); open = false" class="w-full text-left px-3.5 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"><i class="ph ph-list-numbers text-gray-400"></i> Semestre</button>
                                <button type="button" x-show="!visible.includes('status')" @click="add('status'); open = false" class="w-full text-left px-3.5 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"><i class="ph ph-toggle-left text-gray-400"></i> Status</button>
                            </div>
                        </div>

                        <button type="button" x-show="$wire.filtro_ano || $wire.filtro_semestre || $wire.filtro_status !== '' || $wire.filtroNome" x-cloak wire:click="limparFiltros" @click="visible = []" class="text-xs font-medium text-gray-500 hover:text-gray-900 h-9 px-2 transition">
                            Limpar
                        </button>
                    </div>
                </div>

                @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                    @if(count($selecionadas) > 0)
                        <div class="bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 p-3 rounded-lg flex flex-col lg:flex-row justify-between items-center gap-4 w-full">
                            <div class="flex items-center shrink-0">
                                <span class="font-medium text-white text-xs">{{ count($selecionadas) }} ciclo(s) selecionado(s)</span>
                                <button type="button" wire:click="desmarcarTodas" class="ml-4 text-xs text-gray-400 hover:text-white transition">Limpar Seleção</button>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2 w-full lg:w-auto">
                                <button type="button" class="btn btn--secondary btn--small !text-red-500 !border-red-500 hover:!bg-red-500 hover:!text-white" onclick="confirm('Não implementado')">Deletar Selecionados</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </x-slot>

        @forelse ($registros as $ciclo)
            @php
                $urlPublicaCiclo = (($ciclo->tipo_ciclo ?? 'regular') === 'customizado' && !empty($ciclo->slug_projeto))
                    ? url('/inscricao/' . $ciclo->slug_projeto)
                    : url('/inscricao');
            @endphp
            <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-800/40" wire:key="linha-ciclo-{{ $ciclo->id }}">
                
                <td class="px-4 py-2 text-center whitespace-nowrap w-10">
                    <input type="checkbox" wire:model.live="selecionadas" value="{{ $ciclo->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer" wire:key="checkbox-lista-{{ $ciclo->id }}">
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-xs font-mono text-gray-400">#{{ str_pad($ciclo->id, 3, '0', STR_PAD_LEFT) }}</td>
                
                <td class="px-4 py-2 whitespace-nowrap min-w-[220px]">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900 text-sm dark:text-white">{{ $ciclo->nome }}</span>
                        @if(($ciclo->tipo_ciclo ?? 'regular') === 'customizado')
                            <span class="px-1.5 py-0.5 text-[10px] font-medium uppercase bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 rounded" title="URL: /inscricao/{{ $ciclo->slug_projeto }}">
                                Projeto
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                        <span>Ano {{ $ciclo->ano }} • {{ $ciclo->semestre }}º Sem.</span>
                        <span class="text-purpura-600 dark:text-purpura-400 font-medium">• {{ $ciclo->fase_atual_nome }}</span>
                    </div>
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                    {{ $ciclo->data_inicio->format('d/m/Y H:i') }}
                </td>

                <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                    {{ $ciclo->data_fim->format('d/m/Y H:i') }}
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-center">
                    <span class="px-2 py-0.5 text-xs font-medium text-gray-700 bg-gray-100 rounded border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                        {{ $ciclo->inscricoes_count ?? 0 }} inscritos
                    </span>
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap">
                    @php
                        $totalMeta = $ciclo->total_meta ?? 0;
                        $inscritos = $ciclo->inscricoes_count ?? 0;
                        $percentualMeta = $totalMeta > 0 ? round(($inscritos / $totalMeta) * 100, 1) : ($inscritos > 0 ? 100 : 0);
                        $corBarraMeta = $percentualMeta >= 100 ? 'bg-emerald-500' : ($percentualMeta >= 50 ? 'bg-blue-500' : 'bg-purpura-500');
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full min-w-[120px]">
                        <div class="flex justify-between w-full text-[10px] font-medium mb-1">
                            <span class="text-gray-500">{{ $inscritos }} captados</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $totalMeta }} meta</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden flex">
                            <div class="{{ $corBarraMeta }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($percentualMeta, 100) }}%"></div>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-center">
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <button type="button" wire:click="toggleBloqueio({{ $ciclo->id }})" class="p-1.5 rounded border transition-colors {{ $ciclo->bloqueado ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400' : 'bg-white text-gray-400 border-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700' }}" title="{{ $ciclo->bloqueado ? 'Ciclo Bloqueado (Clique para desbloquear)' : 'Ciclo Desbloqueado (Clique para bloquear)' }}">
                            <i class="text-base ph {{ $ciclo->bloqueado ? 'ph-lock-key' : 'ph-lock-key-open' }}"></i>
                        </button>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium uppercase {{ $ciclo->bloqueado ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-gray-100 text-gray-500' }}">
                            {{ $ciclo->bloqueado ? 'Bloqueado' : 'Aberto' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-2 whitespace-nowrap text-center">
                    <x-editing-lock-badge
                        :id="$ciclo->id"
                        :tipos="[
                            'ciclo_dados'  => 'Ciclo',
                            'ciclo'        => 'Formulário',
                            'ciclo_regras' => 'Regras',
                        ]" />
                </td>

                <td class="px-4 py-2 whitespace-nowrap">
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                            <span class="text-[11px] font-medium {{ $ciclo->status ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">{{ $ciclo->status ? 'Ativo' : 'Inativo' }}</span>
                        </div>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $ciclo->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">{{ $ciclo->status ? 'Ativo' : 'Inativo' }}</span>
                    @endif
                </td>
                
                <td class="px-4 py-2 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button x-data="{ copiado: false }" 
                                @click="
                                    let code = `<iframe src='{{ $urlPublicaCiclo }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                                    navigator.clipboard.writeText(code); 
                                    copiado = true; 
                                    setTimeout(() => copiado = false, 2000);
                                " 
                                class="p-1.5 transition-colors rounded relative" 
                                :class="copiado ? 'text-emerald-600 bg-emerald-50' : 'text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800'"
                                title="Copiar Código de Incorporação (Iframe) - {{ $urlPublicaCiclo }}">
                            <i class="text-base ph" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i>
                        </button>
                        <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Ver CRM"><i class="text-base ph ph-kanban"></i></a>
                        <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Visualização Rápida"><i class="text-base ph ph-info"></i></button>
                        <a href="{{ $urlPublicaCiclo . '?modo=teste&ciclo_id=' . $ciclo->id }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Testar Formulário em Sandbox">
                            <i class="text-base ph ph-flask"></i>
                        </a>
                        <a href="{{ $urlPublicaCiclo . '?preview=true&ciclo_id=' . $ciclo->id }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Pré-visualizar Formulário de Inscrição">
                            <i class="text-base ph ph-arrow-square-out"></i>
                        </a>
                        <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Ver Detalhes"><i class="text-base ph ph-eye"></i></a>
                        <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Duplicar"><i class="text-base ph ph-copy"></i></button>
                        <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Editar Completo"><i class="text-base ph ph-pencil-simple"></i></a>
                        <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Construtor Formulário Público"><i class="text-base ph ph-list-dashes"></i></a>
                        <a href="{{ route('ciclos.regras', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800" title="Regras de Pontuação"><i class="text-base ph ph-star"></i></a>
                        <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800" title="Excluir" onclick="confirm('Excluir permanentemente?') || event.stopImmediatePropagation()"><i class="text-base ph ph-trash"></i></button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                    Nenhum ciclo encontrado.
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $ciclo)
                <div wire:key="card-ciclo-{{ $ciclo->id }}" class="card !p-4 !gap-0 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    <x-editing-lock-badge :id="$ciclo->id" :tipos="['ciclo_dados' => 'Ciclo', 'ciclo' => 'Formulário', 'ciclo_regras' => 'Regras']" />
                    <div class="flex items-center justify-between mb-3 w-full">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 text-[10px] font-medium uppercase rounded border {{ $ciclo->status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                {{ $ciclo->status ? 'Ativo' : 'Inativo' }}
                            </span>
                            @if(($ciclo->tipo_ciclo ?? 'regular') === 'customizado')
                                <span class="px-2 py-0.5 text-[10px] font-medium uppercase bg-gray-100 text-gray-700 border border-gray-200 rounded">Projeto</span>
                            @endif
                        </div>
                        <input type="checkbox" wire:model.live="selecionadas" value="{{ $ciclo->id }}" wire:key="checkbox-card-{{ $ciclo->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer">
                    </div>

                    <div class="mb-3 w-full">
                        <h4 class="text-sm font-bold text-gray-900 truncate dark:text-white" title="{{ $ciclo->nome }}">{{ $ciclo->nome }}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Semestre {{ $ciclo->ano }}.{{ $ciclo->semestre }} • {{ $ciclo->fase_atual_nome }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-2.5 space-y-1.5 border border-gray-100 dark:border-gray-700 mb-3 text-xs">
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Abertura</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200">{{ $ciclo->data_inicio->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Encerramento</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200">{{ $ciclo->data_fim->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    @php
                        $totalVagas = $ciclo->total_vagas ?? 0;
                        $preenchidas = $ciclo->vagas_preenchidas ?? 0;
                        $percentual = $totalVagas > 0 ? round(($preenchidas / $totalVagas) * 100, 1) : 0;
                        $corBarra = $percentual >= 100 ? 'bg-red-500' : ($percentual >= 80 ? 'bg-orange-500' : 'bg-emerald-500');
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full mb-3">
                        <div class="flex justify-between w-full text-[11px] font-medium mb-1">
                            <span class="text-gray-500">Ocupação</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $preenchidas }} / {{ $totalVagas }} vagas</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden flex">
                            <div class="{{ $corBarra }} h-1.5 rounded-full transition-all" style="width: {{ min($percentual, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700 w-full">
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $ciclo->inscricoes_count ?? 0 }} inscritos</span>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="p-1.5 text-gray-400 hover:text-gray-900 rounded transition" title="CRM Kanban"><i class="text-base ph ph-kanban"></i></a>
                            <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 hover:text-gray-900 rounded transition" title="Info"><i class="text-base ph ph-info"></i></button>
                            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 hover:text-gray-900 rounded transition"><i class="text-base ph ph-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>

    </x-table>
    
    <!-- MODAL DE CADASTRO EM TELA CHEIA (STEPPER WIZARD COM X-TELEPORT E Z-[999]) -->
    @if($modalAberto)
        <template x-teleport="body">
            <div class="fixed inset-0 z-[999] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden font-sans"
                 @keydown.escape.window="$wire.set('modalAberto', false)"
                 role="dialog"
                 aria-modal="true">
                
                <!-- HEADER DO MODAL -->
                <div class="flex justify-between items-center px-6 py-3.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white">Assistente de Configuração de Ciclo</h2>
                        <p class="text-xs text-gray-500">Preencha os passos sequenciais para estruturar o processo seletivo.</p>
                    </div>
                    <button type="button" wire:click="$set('modalAberto', false)" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="text-xl ph ph-x"></i>
                    </button>
                </div>

                <!-- CORPO DO MODAL -->
                <div class="flex flex-1 overflow-hidden">
                    
                    <!-- MENU LATERAL ESQUERDO (PASSOS) -->
                    <div class="w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-5 flex flex-col gap-1.5 shrink-0 overflow-y-auto">
                        @php
                            $passosLista = [
                                1 => ['titulo' => 'Informações Gerais', 'desc' => 'Nome, modalidade e datas'],
                                2 => ['titulo' => 'Estrutura Acadêmica', 'desc' => 'Unidades, cursos e turnos'],
                                3 => ['titulo' => 'Distribuição de Vagas', 'desc' => 'Capacidade e faixa etária'],
                                4 => ['titulo' => 'Etapas do Ciclo', 'desc' => 'Funil de status (CRM)'],
                                5 => ['titulo' => 'Documentos Exigidos', 'desc' => 'Exigências de matrícula'],
                                6 => ['titulo' => 'Conclusão', 'desc' => 'Formulários e liberação']
                            ];
                        @endphp

                        @foreach($passosLista as $num => $info)
                            <div wire:click="irParaPasso({{ $num }})" 
                                 class="flex items-start gap-3 p-2.5 rounded-lg transition cursor-pointer {{ $passoAtual === $num ? 'bg-gray-100 dark:bg-gray-700/70 font-semibold' : ($cicloIdEmEdicao && $num < $passoAtual ? 'hover:bg-gray-50 dark:hover:bg-gray-700/40' : 'opacity-50 pointer-events-none') }}">
                                <div class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold shrink-0 {{ $passoAtual === $num ? 'bg-purpura-600 text-white' : ($cicloIdEmEdicao && $num < $passoAtual ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                                    @if($cicloIdEmEdicao && $num < $passoAtual)
                                        <i class="ph ph-check"></i>
                                    @else
                                        {{ $num }}
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold {{ $passoAtual === $num ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">{{ $info['titulo'] }}</h4>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight mt-0.5">{{ $info['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- CONTEÚDO PRINCIPAL DO PASSO -->
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50 dark:bg-gray-900 custom-scrollbar flex flex-col justify-between">
                        <div class="max-w-4xl mx-auto w-full">
                            
                            <!-- PASSO 1: DADOS BÁSICOS -->
                            @if($passoAtual === 1)
                                @php
                                    $inputCicloClass = 'w-full h-10 px-3.5 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg shadow-2xs focus:outline-none focus:ring-2 focus:ring-purpura-500/20 focus:border-purpura-500 transition-colors';
                                @endphp
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 1: Informações Básicas</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Defina o nome de exibição, a modalidade do processo seletivo e as datas de abertura e encerramento.</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Ciclo</label>
                                            <input type="text" wire:model="nome" placeholder="Ex: Processo Seletivo 2026.2" class="{{ $inputCicloClass }}">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ano <span class="text-red-500">*</span></label>
                                            <input type="number" wire:model="ano" class="{{ $inputCicloClass }}">
                                            @error('ano') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Semestre <span class="text-red-500">*</span></label>
                                            <select wire:model="semestre" class="{{ $inputCicloClass }}">
                                                <option value="1">1º Semestre</option>
                                                <option value="2">2º Semestre</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Modalidade <span class="text-red-500">*</span></label>
                                            <select wire:model.live="tipo_ciclo" class="{{ $inputCicloClass }}">
                                                <option value="regular">Regular (/inscricao)</option>
                                                <option value="customizado">Projeto Customizado</option>
                                            </select>
                                        </div>
                                    </div>

                                    @if($tipo_ciclo === 'customizado')
                                        <div class="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Slug do Projeto (URL Exclusiva) <span class="text-red-500">*</span></label>
                                            <div class="flex items-center">
                                                <span class="h-10 px-3 inline-flex items-center bg-gray-100 dark:bg-gray-800 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg text-xs font-mono text-gray-500">/inscricao/</span>
                                                <input type="text" wire:model="slug_projeto" placeholder="projeto-verao-2026" class="{{ $inputCicloClass }} !rounded-l-none font-mono">
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-1">Este ciclo poderá ficar ativo simultaneamente com o ciclo regular em seu próprio endereço.</p>
                                            @error('slug_projeto') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data e Hora de Abertura <span class="text-red-500">*</span></label>
                                            <input type="datetime-local" wire:model="data_inicio" class="{{ $inputCicloClass }}">
                                            @error('data_inicio') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data e Hora de Encerramento <span class="text-red-500">*</span></label>
                                            <input type="datetime-local" wire:model="data_fim" class="{{ $inputCicloClass }}">
                                            @error('data_fim') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Início do Período de Matrícula</label>
                                            <input type="datetime-local" wire:model="data_inicio_matricula" class="{{ $inputCicloClass }}">
                                            @error('data_inicio_matricula') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fim do Período de Matrícula</label>
                                            <input type="datetime-local" wire:model="data_fim_matricula" class="{{ $inputCicloClass }}">
                                            @error('data_fim_matricula') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="flex items-center pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <input type="checkbox" wire:model="status" id="status" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                                        <label for="status" class="block ml-2 text-xs font-medium text-gray-900 dark:text-gray-300 cursor-pointer">
                                            Ativar este ciclo imediatamente {{ $tipo_ciclo === 'regular' ? '(desativará outro ciclo regular ativo)' : '(projetos customizados não desativam o ciclo regular)' }}
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <!-- PASSO 2: UNIDADE / CURSO / TURNO -->
                            @if($passoAtual === 2)
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 2: Estrutura Acadêmica</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Selecione as Unidades, os Cursos da Unidade e os Turnos disponíveis neste processo seletivo.</p>
                                    </div>

                                    <div class="flex flex-col md:flex-row h-[380px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-medium uppercase text-gray-500">1. Unidades</div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @foreach($unidadesDb as $u)
                                                    <div wire:key="unidade-{{ $u->id }}" wire:click="setActiveUnidade({{ $u->id }})" class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                            <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $u->id }}" wire:change="setActiveUnidade({{ $u->id }})" class="w-4 h-4 rounded text-purpura-600">
                                                            <span class="text-xs text-gray-800 dark:text-gray-200">{{ $u->nome }}</span>
                                                        </label>
                                                        <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-medium uppercase text-gray-500">2. Cursos</div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @if($activeUnidadeId)
                                                    @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                                        <div wire:key="curso-{{ $activeUnidadeId }}-{{ $c->id }}" wire:click="setActiveCurso({{ $c->id }})" class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                                <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $activeUnidadeId }}-{{ $c->id }}" wire:change="setActiveCurso({{ $c->id }})" class="w-4 h-4 rounded text-purpura-600">
                                                                <span class="text-xs text-gray-800 dark:text-gray-200">{{ $c->nome }}</span>
                                                            </label>
                                                            <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione uma Unidade</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex-1 flex flex-col">
                                            <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-medium uppercase text-gray-500">3. Turnos</div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @if($activeUnidadeId && $activeCursoId)
                                                    @php
                                                        $cs = $cursosDb->firstWhere('id', $activeCursoId);
                                                        $turnosComboWiz = $cs
                                                            ? (method_exists($cs, 'turnosNaUnidade') ? $cs->turnosNaUnidade((int) $activeUnidadeId) : $cs->turnosVinculados)
                                                            : collect();
                                                    @endphp
                                                    @forelse($turnosComboWiz as $t)
                                                        <div wire:key="turno-{{ $activeUnidadeId }}-{{ $activeCursoId }}-{{ $t->id }}" class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                                <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $activeUnidadeId }}-{{ $activeCursoId }}-{{ $t->id }}" class="w-4 h-4 rounded text-purpura-600">
                                                                <span class="text-xs text-gray-800 dark:text-gray-200">{{ $t->nome }}</span>
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <div class="h-full flex items-center justify-center text-gray-400 text-xs p-4 text-center">Nenhum turno vinculado nesta unidade.</div>
                                                    @endforelse
                                                @else
                                                    <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione um Curso</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- PASSO 3: DISTRIBUIÇÃO DE VAGAS -->
                            @if($passoAtual === 3)
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 3: Distribuição de Vagas</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Preencha o limite de vagas e faixas etárias para cada combinação selecionada.</p>
                                    </div>

                                    <div class="space-y-2.5">
                                        @forelse($ofertasVagas as $index => $oferta)
                                            <div wire:key="oferta-{{ $index }}" class="p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex flex-col xl:flex-row gap-3 items-end">
                                                <div class="flex-1 w-full">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1">Unidade</label>
                                                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 px-3 py-2 rounded border border-gray-200 dark:border-gray-700">{{ $oferta['unidade_nome'] ?? '' }}</div>
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1">Curso</label>
                                                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 px-3 py-2 rounded border border-gray-200 dark:border-gray-700 truncate" title="{{ $oferta['curso_nome'] ?? '' }}">{{ $oferta['curso_nome'] ?? '' }}</div>
                                                </div>
                                                <div class="w-full xl:w-28">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1">Turno</label>
                                                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 px-3 py-2 rounded border border-gray-200 dark:border-gray-700">{{ $oferta['turno_nome'] ?? '' }}</div>
                                                </div>
                                                <div class="w-full xl:w-20 shrink-0">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1 text-center">Vagas *</label>
                                                    <input type="number" wire:model.live="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5 font-bold text-center">
                                                </div>
                                                <div class="w-full xl:w-20 shrink-0">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1 text-center">Meta</label>
                                                    <input type="number" wire:model="ofertasVagas.{{ $index }}.meta" placeholder="-" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5 text-center">
                                                </div>
                                                <div class="w-full xl:w-16 shrink-0">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1 text-center">Id. Mín</label>
                                                    <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_min" placeholder="Livre" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5 text-center">
                                                </div>
                                                <div class="w-full xl:w-16 shrink-0">
                                                    <label class="block text-[10px] font-medium text-gray-500 uppercase mb-1 text-center">Id. Máx</label>
                                                    <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_max" placeholder="Livre" class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5 text-center">
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-8 text-center border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 text-xs">Volte ao Passo 2 e selecione as combinações acadêmicas.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            <!-- PASSO 4: ETAPAS DO CICLO (PIPELINE) -->
                            @if($passoAtual === 4)
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 4: Etapas do Ciclo (Pipeline / Kanban)</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Organize as colunas de status pelas quais os candidatos passarão no funil seletivo.</p>
                                    </div>

                                    <div class="w-full" x-data="{
                                         initSortable() {
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
                                            <select wire:model="novoStatusSelecionado" class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2">
                                                <option value="">Adicionar etapa ao funil...</option>
                                                @foreach($statusDisponiveis as $st)
                                                    @if(!in_array($st->id, $statusSelecionados))
                                                        <option value="{{ $st->id }}">{{ $st->nome }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button type="button" wire:click="adicionarStatusPipeline" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                                + Inserir
                                            </button>
                                        </div>

                                        <div x-ref="statusList" class="flex flex-col gap-2">
                                            @foreach($statusSelecionados as $index => $statusId)
                                                @php $stObj = $statusDisponiveis->firstWhere('id', $statusId); @endphp
                                                @if($stObj)
                                                    <div data-id="{{ $statusId }}" wire:key="st-{{ $statusId }}" class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                        <div class="flex items-center gap-3">
                                                            <i class="ph ph-dots-six-vertical text-gray-400 cursor-grab active:cursor-grabbing drag-handle text-lg"></i>
                                                            <span class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-mono font-semibold">#{{ $index + 1 }}</span>
                                                            <span class="font-medium text-xs text-gray-800 dark:text-gray-200">{{ $stObj->nome }}</span>
                                                        </div>
                                                        <button type="button" wire:click="removerStatusPipeline('{{ $statusId }}')" class="text-gray-400 hover:text-red-600 p-1 rounded transition">
                                                            <i class="ph ph-trash text-sm"></i>
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
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 5: Documentos Exigidos</h3>
                                            <p class="text-xs text-gray-500 mt-0.5">Especifique as exigências documentais para a efetivação da matrícula digital.</p>
                                        </div>
                                        <button type="button" wire:click="addDocumento" class="btn btn--secondary btn--small">
                                            + Novo Documento
                                        </button>
                                    </div>

                                    <div class="space-y-2.5">
                                        @forelse($documentosExigidos as $index => $doc)
                                            <div wire:key="doc-{{ $index }}" class="p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex flex-col md:flex-row gap-3 items-end">
                                                <div class="flex-1 w-full">
                                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Nome do Documento *</label>
                                                    <input type="text" wire:model="documentosExigidos.{{ $index }}.nome" placeholder="Ex: Histórico Escolar" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5">
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Instruções</label>
                                                    <input type="text" wire:model="documentosExigidos.{{ $index }}.descricao" placeholder="Ex: Assinado e carimbado" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5">
                                                </div>
                                                <div class="w-full md:w-auto flex items-center justify-between gap-4 pb-1">
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="checkbox" wire:model="documentosExigidos.{{ $index }}.is_obrigatorio" class="w-4 h-4 rounded text-purpura-600">
                                                        <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">Obrigatório</span>
                                                    </label>
                                                    <button type="button" wire:click="removeDocumento({{ $index }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition"><i class="ph ph-trash"></i></button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-8 text-center border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 text-xs">Nenhum documento exigido cadastrado.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            <!-- PASSO 6: SUCESSO E FORM BUILDER -->
                            @if($passoAtual === 6)
                                <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700 text-center space-y-4">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-200 mx-auto text-2xl">
                                        <i class="ph ph-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ciclo Criado e Configurado</h3>
                                        <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">Todas as etapas, vagas e regras acadêmicas foram guardadas com sucesso. Avance para o Construtor de Formulários para configurar os campos da inscrição.</p>
                                    </div>
                                    
                                    @if($cicloCriadoId)
                                        <div class="pt-3 flex flex-wrap justify-center gap-3">
                                            <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $cicloCriadoId]) }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                                                Ir para o Construtor de Formulário
                                            </a>
                                            <button type="button" wire:click="$set('modalAberto', false)" class="btn btn--secondary btn--medium">
                                                Concluir e Fechar
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>

                        <!-- BOTÕES DE RODAPÉ DO WIZARD -->
                        @if($passoAtual < 6)
                            <div class="max-w-4xl mx-auto w-full pt-5 mt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center shrink-0">
                                <button type="button" wire:click="passoAnterior" @if($passoAtual === 1) disabled @endif class="btn btn--secondary btn--medium disabled:opacity-40">
                                    <i class="ph ph-arrow-left"></i> Anterior
                                </button>
                                <button type="button" wire:click="proximoPasso" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                                    {{ $passoAtual === 5 ? 'Finalizar Configuração' : 'Próximo Passo' }} <i class="ph ph-arrow-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </template>
    @endif
</div>