<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
            <i class="ph-fill ph-magic-wand text-purpura-600"></i> Construtor de Formulários
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Escolha abaixo o tipo de fluxo. O sistema adaptará as regras automaticamente.</p>
    </div>

    <!-- CARDS DE OPÇÕES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- PROCESSO SELETIVO (Padrão) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative group cursor-pointer" wire:click="$set('modalCicloAberto', true)">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 mb-4">
                <i class="ph-bold ph-calendar-check text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Inscrição Oficial</h3>
            <p class="text-[11px] text-gray-500 leading-tight mb-4 flex-1">
                Vinculado a um Ciclo Seletivo. Gera ranqueamento e pontuação.
            </p>
            <span class="text-xs font-bold text-indigo-600 group-hover:underline">Criar Inscrição →</span>
        </div>

        <!-- PRÉ-INSCRIÇÃO (Leads) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative group cursor-pointer" wire:click="$set('modalPreInscricaoAberto', true)">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-4">
                <i class="ph-bold ph-users-three text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Pré-Inscrição (Leads)</h3>
            <p class="text-[11px] text-gray-500 leading-tight mb-4 flex-1">
                Formulário rápido para captação. Pode ser filtrado por unidade ou curso.
            </p>
            <span class="text-xs font-bold text-emerald-600 group-hover:underline">Criar Captação →</span>
        </div>

        <!-- AVALIAÇÃO DE APRENDIZAGEM -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative group cursor-pointer" wire:click="$set('modalAprendizagemAberto', true)">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 mb-4">
                <i class="ph-bold ph-graduation-cap text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Avaliação de Aprendiz.</h3>
            <p class="text-[11px] text-gray-500 leading-tight mb-4 flex-1">
                Formulários vinculados a um Workflow (Aluno responde, Gestor avalia).
            </p>
            <span class="text-xs font-bold text-orange-600 group-hover:underline">Criar Avaliação →</span>
        </div>

        <!-- FORMULÁRIO GERAL -->
        <a href="{{ route('formularios.create') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative group">
            <div class="w-12 h-12 bg-purpura-100 rounded-xl flex items-center justify-center text-purpura-600 mb-4">
                <i class="ph-bold ph-list-dashes text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Formulário Avulso</h3>
            <p class="text-[11px] text-gray-500 leading-tight mb-4 flex-1">
                Pesquisas e questionários sem vínculo direto com processos acadêmicos.
            </p>
            <span class="text-xs font-bold text-purpura-600 group-hover:underline">Criar Avulso →</span>
        </a>
    </div>

    <!-- LISTAGEM DE FORMULÁRIOS POR ABA (PUBLICADOS VS RASCUNHOS) -->
    <div class="mt-10 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-6 pt-4 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/40">
            <div class="flex items-center gap-2">
                <button type="button" 
                        wire:click="$set('abaStatus', 'ativos')" 
                        class="px-4 py-2.5 text-xs font-extrabold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2 {{ $abaStatus === 'ativos' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                    <i class="ph-fill ph-check-circle text-base"></i> Formulários Publicados
                </button>

                <button type="button" 
                        wire:click="$set('abaStatus', 'rascunhos')" 
                        class="px-4 py-2.5 text-xs font-extrabold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2 {{ $abaStatus === 'rascunhos' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                    <i class="ph-fill ph-file-dashed text-base"></i> Rascunhos / Sem Vínculo
                </button>
            </div>

            <button type="button" wire:click="criarFormularioAvulsoRascunho" class="mb-2 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-bold flex items-center gap-1.5 transition">
                <i class="ph-bold ph-plus"></i> Novo Rascunho Rápido
            </button>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($formulariosList ?? [] as $formItem)
                <div wire:key="hub-form-{{ $formItem->id }}" class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $formItem->titulo }}</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded border {{ $formItem->is_rascunho ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                {{ $formItem->is_rascunho ? 'Rascunho' : 'Publicado' }}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $formItem->tipo }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">
                            @if($formItem->tipo === 'aprendizagem')
                                Vínculo: <strong>{{ $formItem->faseAprendizagem?->ciclo?->nome ? $formItem->faseAprendizagem->ciclo->nome . ' — ' . $formItem->faseAprendizagem->nome : 'Sem Ciclo/Fase vinculado' }}</strong>
                            @elseif($formItem->tipo === 'pre_inscricao')
                                Captação: <strong>{{ $formItem->unidade->nome ?? 'Todas Unidades' }} / {{ $formItem->curso->nome ?? 'Todos Cursos' }}</strong>
                            @else
                                Slug: <span class="font-mono">/f/{{ $formItem->slug }}</span>
                            @endif
                            &middot; {{ $formItem->campos_count ?? 0 }} bloco(s)
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @php
                            $rotaTeste = $formItem->tipo === 'pre_inscricao'
                                ? url('/pre-inscricao/' . $formItem->slug . '?modo=teste')
                                : url('/f/' . $formItem->slug . '?modo=teste');
                        @endphp
                        @if(auth()->user()->hasAnyRole(['dev', 'admin']) || auth()->user()->can('formulario.testar') || auth()->user()->can('inscricao.criar'))
                            <a href="{{ $rotaTeste }}" target="_blank" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-bold flex items-center gap-1 transition" title="Testar em modo Sandbox">
                                <i class="ph-fill ph-flask"></i> Testar
                            </a>
                        @endif

                        <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formItem->id]) }}" class="px-3 py-1.5 bg-purpura-50 hover:bg-purpura-100 text-purpura-700 border border-purpura-200 rounded-lg text-xs font-bold flex items-center gap-1 transition">
                            <i class="ph-bold ph-pencil-simple"></i> Editar Campos
                        </a>

                        <button type="button" wire:click="alternarStatusRascunho({{ $formItem->id }})" class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $formItem->is_rascunho ? 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300' }}">
                            {{ $formItem->is_rascunho ? 'Publicar' : 'Mover p/ Rascunho' }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">
                    Nenhum formulário encontrado na aba de <strong>{{ $abaStatus === 'rascunhos' ? 'Rascunhos' : 'Publicados' }}</strong>.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal 4: VINCULAR FASE AO PUBLICAR RASCUNHO DE APRENDIZAGEM -->
    @if($modalVincularAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-orange-50">
                    <h3 class="font-bold text-orange-900 text-sm flex items-center gap-2"><i class="ph-fill ph-link"></i> Vincular Ciclo/Fase para Publicar</h3>
                    <button wire:click="$set('modalVincularAberto', false)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-xs text-gray-600">Este formulário de aprendizagem estava em rascunho sem vínculo. Escolha o Ciclo e a Fase para publicá-lo:</p>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">1. Ciclo de Aprendizagem</label>
                        <select wire:model.live="aprendizagem.ciclo_id" class="w-full text-sm rounded-lg border-gray-300">
                            <option value="">-- Selecione o Ciclo --</option>
                            @foreach($ciclosAprendizagem as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                        @error('aprendizagem.ciclo_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($aprendizagem['ciclo_id'])
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">2. Fase do Ciclo</label>
                            <select wire:model="aprendizagem.fase_id" class="w-full text-sm rounded-lg border-gray-300">
                                <option value="">-- Selecione a Fase --</option>
                                @foreach($fasesAprendizagem as $fase) <option value="{{ $fase['id'] }}">{{ $fase['nome'] }}</option> @endforeach
                            </select>
                            @error('aprendizagem.fase_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                    <button wire:click="$set('modalVincularAberto', false)" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-300 rounded-lg">Cancelar</button>
                    <button wire:click="confirmarVinculoEPublicar" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Vincular e Publicar</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAIS DE CONTEXTO -->

    <!-- Modal 1: INSCRIÇÃO OFICIAL (Ciclo Seletivo) -->
    @if($modalCicloAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="$wire.set('modalCicloAberto', false)">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-900 text-sm">Selecione o Ciclo Seletivo</h3>
                    <button wire:click="$set('modalCicloAberto', false)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <div class="p-2 max-h-80 overflow-y-auto">
                    @forelse($ciclos as $ciclo)
                        <button wire:click="selecionarCicloSeletivo({{ $ciclo->id }})" class="w-full text-left px-4 py-3 hover:bg-indigo-50 transition border-b border-gray-50 flex justify-between items-center group">
                            <div>
                                <div class="font-bold text-sm text-gray-800 group-hover:text-indigo-700">{{ $ciclo->nome }}</div>
                                <div class="text-[10px] text-gray-500">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                            </div>
                            <i class="ph-bold ph-caret-right text-gray-300 group-hover:text-indigo-500"></i>
                        </button>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">Nenhum ciclo cadastrado no sistema.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Modal 2: PRÉ-INSCRIÇÃO (Leads) -->
    @if($modalPreInscricaoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="$wire.set('modalPreInscricaoAberto', false)">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-emerald-50">
                    <h3 class="font-bold text-emerald-900 text-sm flex items-center gap-2"><i class="ph-fill ph-funnel"></i> Contexto da Pré-Inscrição</h3>
                    <button wire:click="$set('modalPreInscricaoAberto', false)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-xs text-gray-500 leading-tight mb-2">Você pode restringir esta captação selecionando as opções abaixo. Se deixar em branco, o formulário será de captação geral.</p>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Para qual Ciclo Seletivo?</label>
                        <select wire:model="preInscricao.ciclo_id" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-emerald-500">
                            <option value="">-- Todos (Geral) --</option>
                            @foreach($ciclos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Para qual Unidade?</label>
                        <select wire:model="preInscricao.unidade_id" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-emerald-500">
                            <option value="">-- Qualquer Unidade --</option>
                            @foreach($unidades as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Para qual Curso?</label>
                        <select wire:model="preInscricao.curso_id" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-emerald-500">
                            <option value="">-- Qualquer Curso --</option>
                            @foreach($cursos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                    </div>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <button wire:click="criarFormularioPreInscricao" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition shadow-sm text-sm">
                        Criar e Abrir Construtor
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal 3: AVALIAÇÃO DE APRENDIZAGEM -->
    @if($modalAprendizagemAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="$wire.set('modalAprendizagemAberto', false)">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-orange-50">
                    <h3 class="font-bold text-orange-900 text-sm flex items-center gap-2"><i class="ph-fill ph-tree-structure"></i> Vínculo no Workflow</h3>
                    <button wire:click="$set('modalAprendizagemAberto', false)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-xs text-gray-500 leading-tight mb-2">Para criar as perguntas deste formulário, você precisa dizer a qual Ciclo e Fase de aprendizagem ele pertence.</p>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Título do Formulário (Opcional)</label>
                        <input type="text" wire:model="aprendizagem.titulo" placeholder="Ex: Autoavaliação Semestral" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-orange-500">
                    </div>

                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-amber-200 bg-amber-50/60 cursor-pointer">
                        <input type="checkbox" wire:model.live="aprendizagem.is_rascunho" class="mt-0.5 rounded text-orange-600 focus:ring-orange-500 border-gray-300">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-amber-900">Criar como Rascunho (Vincular Ciclo/Fase depois)</span>
                            <span class="text-[10px] text-amber-700">Você poderá montar todas as páginas e perguntas agora sem escolher a fase.</span>
                        </div>
                    </label>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">1. Escolha o Ciclo de Aprendizagem</label>
                        <select wire:model.live="aprendizagem.ciclo_id" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-orange-500">
                            <option value="">-- Selecione o Ciclo --</option>
                            @foreach($ciclosAprendizagem as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                    </div>

                    @if($aprendizagem['ciclo_id'])
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">2. Em qual Fase este formulário será preenchido? <span class="text-red-500">*</span></label>
                            <select wire:model="aprendizagem.fase_id" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-orange-500">
                                <option value="">-- Selecione a Fase --</option>
                                @foreach($fasesAprendizagem as $fase) 
                                    <option value="{{ $fase['id'] }}">{{ $fase['nome'] }}</option> 
                                @endforeach
                            </select>
                            @error('aprendizagem.fase_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="p-4 border border-dashed border-gray-300 rounded-lg text-center bg-gray-50">
                            <span class="text-xs text-gray-500">Selecione um ciclo acima para carregar as fases.</span>
                        </div>
                    @endif
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <button wire:click="criarFormularioAprendizagem" class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-lg transition shadow-sm text-sm">
                        Criar e Abrir Construtor
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>