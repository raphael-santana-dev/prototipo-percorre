<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Ciclos de Inscrições" 
        icon="ph ph-calendar-check"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        <x-slot name="actions">
            @if(feature('ciclo.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.criar')))
                <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Ciclo
                </button>
            @endif
        </x-slot>

        <x-slot name="filters">
            <div class="flex gap-2">
                <select wire:model.live="filtro_ano" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos os Anos</option>
                    @if(isset($anosDisponiveis))
                        @foreach($anosDisponiveis as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    @endif
                </select>
                
                <select wire:model.live="filtro_semestre" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semestre...</option>
                    <option value="1">1º Semestre</option>
                    <option value="2">2º Semestre</option>
                </select>

                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>

                @if($filtro_ano !== '' || $filtro_semestre !== '' || $filtro_status !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1 dark:bg-gray-800 dark:text-gray-300">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $ciclo)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">#{{ $ciclo->id }}</td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $ciclo->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $ciclo->data_inicio->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $ciclo->data_fim->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    <span class="px-3 py-1 text-[10px] font-bold text-purpura-700 bg-purpura-100 rounded-full dark:bg-purpura-900/30 dark:text-purpura-400 uppercase tracking-wider border border-purpura-200">
                        {{ $ciclo->inscricoes_count ?? 0 }} INSCRIÇÕES
                    </span>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @php
                        $totalVagas = $ciclo->total_vagas ?? 0;
                        $preenchidas = $ciclo->vagas_preenchidas ?? 0;
                        $percentual = $totalVagas > 0 ? round(($preenchidas / $totalVagas) * 100, 1) : 0;
                        $corBarra = $percentual >= 100 ? 'bg-red-500' : ($percentual >= 80 ? 'bg-orange-500' : 'bg-emerald-500');
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full min-w-[120px]">
                        <div class="flex justify-between w-full text-[10px] font-bold mb-1">
                            <span class="text-gray-500 dark:text-gray-400">{{ $preenchidas }} preench.</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $totalVagas }} total</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden flex">
                            <div class="{{ $corBarra }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($percentual, 100) }}%"></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if(feature('ciclo.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.editar')))
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$ciclo->status" action="toggleStatus({{ $ciclo->id }})" />
                            <span class="text-[10px] font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-400' }}">{{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}</span>
                        </div>
                    @else
                        <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $ciclo->status ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">{{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}</span>
                    @endif
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('ciclos.crm', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver CRM"><i class="text-lg ph-fill ph-kanban"></i></a>
                        <button wire:click="showQuickView({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Visualização Rápida"><i class="text-lg ph ph-info"></i></button>
                        <a href="{{ route('ciclos.show', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Detalhes"><i class="text-lg ph ph-eye"></i></a>
                        <button wire:click="duplicar({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar"><i class="text-lg ph ph-copy"></i></button>
                        <a href="{{ route('ciclos.edit', $ciclo->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Completo"><i class="text-lg ph ph-pencil-simple"></i></a>
                        <a href="{{ route('construtor.campos', ['tipo' => 'ciclo', 'id' => $ciclo->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor"><i class="text-lg ph ph-list-dashes"></i></a>
                        <button wire:click="delete({{ $ciclo->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir" onclick="confirm('Excluir permanentemente?')"><i class="text-lg ph ph-trash"></i></button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Nenhum ciclo encontrado.</td>
            </tr>
        @endforelse
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
                            2 => ['titulo' => 'Estrutura Acadêmica', 'desc' => 'Unidades, cursos e turnos'],
                            3 => ['titulo' => 'Distribuição de Vagas', 'desc' => 'Capacidade e faixa etária'],
                            4 => ['titulo' => 'Etapas do Ciclo', 'desc' => 'Funil de status (CRM)'],
                            5 => ['titulo' => 'Documentos Exigidos', 'desc' => 'Exigências de matrícula'],
                            6 => ['titulo' => 'Conclusão e Sucesso', 'desc' => 'Formulários e liberação']
                        ];
                    @endphp

                    @foreach($passosLista as $num => $info)
                        <div wire:click="irParaPasso({{ $num }})" 
                             class="flex items-start gap-3.5 p-3 rounded-xl transition cursor-pointer {{ $passoAtual === $num ? 'bg-purpura-50 dark:bg-purpura-900/40 border border-purpura-200 dark:border-purpura-800 shadow-sm' : ($cicloIdEmEdicao && $num < $passoAtual ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50' : 'opacity-60') }}">
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
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
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
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-tree-structure text-purpura-600"></i> Passo 2: Estrutura Acadêmica</h3>
                                    <p class="text-xs text-gray-500 mt-1">Selecione as Unidades, Cursos e Turnos disponíveis neste processo seletivo.</p>
                                </div>

                                <div class="flex flex-col md:flex-row h-[380px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-500">1. Unidades</div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                            @foreach($unidadesDb as $u)
                                                <div wire:click="setActiveUnidade({{ $u->id }})" class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
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
                                                    <div wire:click="setActiveCurso({{ $c->id }})" class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                            <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $c->id }}" class="w-4 h-4 rounded text-purpura-600">
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
                                                @foreach($cursosDb->firstWhere('id', $activeCursoId)->turnosVinculados as $t)
                                                    <div class="flex items-center p-2.5 rounded-lg hover:bg-white dark:hover:bg-gray-700 transition">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                            <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $t->id }}" class="w-4 h-4 rounded text-purpura-600">
                                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t->nome }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
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
    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
            <div>
                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-users-three text-purpura-600"></i> Passo 3: Distribuição de Vagas</h3>
                <p class="text-xs text-gray-500 mt-1">Estabeleça o limite de vagas e faixas etárias para cada cruzamento acadêmico.</p>
            </div>
            <button type="button" wire:click="addOferta" class="px-3.5 py-2 bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200 dark:bg-purpura-900/40 dark:text-purpura-300 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                <i class="ph-bold ph-plus text-sm"></i> Adicionar Oferta
            </button>
        </div>

        <div class="space-y-3">
            @forelse($ofertasVagas as $index => $oferta)
                <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col xl:flex-row gap-3 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade</label>
                        <select wire:model.live="ofertasVagas.{{ $index }}.unidade_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2">
                            <option value="">Selecione...</option>
                            @foreach($unidadesDb as $u) 
                                @if(in_array((string)$u->id, $unidadesSelecionadas)) 
                                    <option value="{{ $u->id }}">{{ $u->nome }}</option> 
                                @endif 
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso</label>
                        <select wire:model.live="ofertasVagas.{{ $index }}.curso_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2" @if(!$oferta['unidade_id']) disabled @endif>
                            <option value="">Selecione...</option>
                            @if($oferta['unidade_id'])
                                @foreach($cursosDb as $c) 
                                    @if(in_array((string)$c->id, $cursosSelecionados) && $c->unidades->contains('id', $oferta['unidade_id'])) 
                                        <option value="{{ $c->id }}">{{ $c->nome }}</option> 
                                    @endif 
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno</label>
                        <select wire:model="ofertasVagas.{{ $index }}.turno_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2" @if(!$oferta['curso_id']) disabled @endif>
                            <option value="">Selecione...</option>
                            @if($oferta['curso_id'])
                                @php $cs = $cursosDb->firstWhere('id', $oferta['curso_id']); @endphp
                                @if($cs) 
                                    @foreach($cs->turnosVinculados as $t) 
                                        @if(in_array((string)$t->id, $turnosSelecionados)) 
                                            <option value="{{ $t->id }}">{{ $t->nome }}</option> 
                                        @endif 
                                    @endforeach 
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="w-full xl:w-24">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Vagas</label>
                        <input type="number" wire:model="ofertasVagas.{{ $index }}.vagas" min="0" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 text-center font-black text-purpura-600">
                    </div>
                    <div class="w-full xl:w-20">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Mín</label>
                        <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_min" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 text-center">
                    </div>
                    <div class="w-full xl:w-20">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-center">Id. Máx</label>
                        <input type="number" wire:model="ofertasVagas.{{ $index }}.idade_max" placeholder="Livre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 text-center">
                    </div>
                    <div class="w-full xl:w-auto">
                        <button type="button" wire:click="removeOferta({{ $index }})" class="w-full xl:w-auto p-2 bg-white dark:bg-gray-800 text-red-500 border border-red-200 rounded-lg shadow-sm hover:bg-red-500 hover:text-white transition"><i class="ph-bold ph-trash"></i></button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/30 text-gray-400 text-xs font-bold">Nenhuma oferta de vaga cadastrada.</div>
            @endforelse
        </div>
    </div>
@endif

                        <!-- PASSO 4: ETAPAS DO CICLO (PIPELINE) -->
                        @if($passoAtual === 4)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-funnel text-purpura-600"></i> Passo 4: Etapas do Ciclo (Pipeline / Kanban)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Organize as colunas de status pelas quais os candidatos passarão no funil seletivo.</p>
                                </div>

                                <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

                                <div class="w-full" x-data="{
                                     initSortable() {
                                         new Sortable(this.$refs.statusList, {
                                             animation: 150, handle: '.drag-handle', ghostClass: 'opacity-50',
                                             onEnd: () => {
                                                 let items = Array.from(this.$refs.statusList.children).map(el => el.dataset.id);
                                                 $wire.atualizarOrdemStatus(items);
                                             }
                                         });
                                     }
                                 }" x-init="initSortable()">
                                    <div class="flex gap-2 mb-4">
                                        <select wire:model="novoStatusSelecionado" class="flex-1 text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2">
                                            <option value="">Adicionar etapa ao funil...</option>
                                            @foreach($statusDisponiveis as $st)
                                                @if(!in_array($st->id, $statusSelecionados))
                                                    <option value="{{ $st->id }}">{{ $st->nome }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="adicionarStatusPipeline" class="bg-purpura-600 text-white px-4 py-2 rounded-lg font-bold text-xs hover:bg-purpura-700 transition"><i class="ph-bold ph-plus"></i> Inserir</button>
                                    </div>

                                    <div x-ref="statusList" class="flex flex-col gap-2">
                                        @foreach($statusSelecionados as $index => $statusId)
                                            @php $stObj = $statusDisponiveis->firstWhere('id', $statusId); @endphp
                                            @if($stObj)
                                                <div data-id="{{ $statusId }}" wire:key="st-{{ $statusId }}" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                    <div class="flex items-center gap-3">
                                                        <i class="ph-bold ph-dots-six-vertical text-gray-400 cursor-grab drag-handle text-xl"></i>
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-black">{{ $index + 1 }}</span>
                                                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $stObj->nome }}</span>
                                                    </div>
                                                    <button type="button" wire:click="removerStatusPipeline('{{ $statusId }}')" class="text-gray-400 hover:text-red-500 p-1.5"><i class="ph-bold ph-trash text-base"></i></button>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- PASSO 5: DOCUMENTOS EXIGIDOS -->
                        @if($passoAtual === 5)
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
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
                                        <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col md:flex-row gap-4 items-end">
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
                            <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 text-center space-y-6">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full dark:bg-green-900/30 dark:text-green-400 mx-auto text-4xl shadow-inner">
                                    <i class="ph-bold ph-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Ciclo Criado e Configurado!</h3>
                                    <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Todas as etapas, vagas e regras acadêmicas foram salvas com sucesso. Agora você pode avançar para o Construtor de Formulários para montar as perguntas públicas da inscrição.</p>
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