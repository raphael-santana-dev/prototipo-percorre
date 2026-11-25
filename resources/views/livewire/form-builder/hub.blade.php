@php
    $u = auth()->user();
    $isDev = $u?->hasRole('dev') ?? false;
    $pode = fn(string $chave) => feature($chave) && ($isDev || ($u && $u->can($chave)));
@endphp

<div class="w-full relative space-y-6 font-sans" wire:poll.8s>
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Construtor de Formulários
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Escolha o tipo de fluxo para criar ou gerencie seus formulários publicados e em rascunho.
            </p>
        </div>

        @if($pode('instituicao.formbuilder.criar_form_geral'))
            <button type="button" wire:click="criarFormularioAvulsoRascunho" class="px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-gray-700 rounded-md text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition shrink-0">
                <i class="ph ph-plus"></i> Novo rascunho livre
            </button>
        @endif
    </div>

    {{-- 3 FLUXOS DE CRIAÇÃO (NEUTROS E DIRETOS) --}}
    @if($pode('instituicao.formbuilder.criar_form_ciclo') || $pode('instituicao.formbuilder.criar_form_aprendizagem') || $pode('instituicao.formbuilder.criar_form_geral'))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($pode('instituicao.formbuilder.criar_form_ciclo'))
                <button type="button" wire:click="$set('modalCicloAberto', true)" class="text-left bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 p-5 flex flex-col transition group">
                    <div class="flex items-center justify-between w-full mb-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Inscrição Oficial</span>
                        <i class="ph ph-calendar-check text-lg text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200"></i>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 flex-1">
                        Vinculado a um Ciclo Seletivo (Regular ou Projeto), com pontuação, ranking e ficha oficial.
                    </p>
                    <span class="text-xs font-semibold text-purpura-600 dark:text-purpura-400 group-hover:underline">Selecionar ciclo →</span>
                </button>
            @endif

            @if($pode('instituicao.formbuilder.criar_form_aprendizagem'))
                <button type="button" wire:click="$set('modalAprendizagemAberto', true)" class="text-left bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 p-5 flex flex-col transition group">
                    <div class="flex items-center justify-between w-full mb-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Avaliação de Aprendizagem</span>
                        <i class="ph ph-graduation-cap text-lg text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200"></i>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 flex-1">
                        Formulários vinculados a ciclos e fases do fluxo de Aprendizagem, ou salvos em rascunho.
                    </p>
                    <span class="text-xs font-semibold text-purpura-600 dark:text-purpura-400 group-hover:underline">Criar avaliação →</span>
                </button>
            @endif

            @if($pode('instituicao.formbuilder.criar_form_geral'))
                <a href="{{ route('formularios.create') }}" class="text-left bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 p-5 flex flex-col transition group">
                    <div class="flex items-center justify-between w-full mb-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Formulário Geral</span>
                        <i class="ph ph-list-dashes text-lg text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200"></i>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 flex-1">
                        Pesquisas, questionários públicos (/f/slug) ou modelos livres para vincular depois.
                    </p>
                    <span class="text-xs font-semibold text-purpura-600 dark:text-purpura-400 group-hover:underline">Criar formulário →</span>
                </a>
            @endif
        </div>
    @endif

    {{-- LISTAGEM DE FORMULÁRIOS POR ABA --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-4 sm:px-5 border-b border-gray-200 dark:border-gray-800 flex items-center gap-4 overflow-x-auto">
            <button type="button" 
                    wire:click="$set('abaStatus', 'ativos')" 
                    class="py-3 text-xs font-semibold border-b-2 transition-colors whitespace-nowrap {{ $abaStatus === 'ativos' ? 'border-purpura-600 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                Formulários Publicados
            </button>

            <button type="button" 
                    wire:click="$set('abaStatus', 'rascunhos')" 
                    class="py-3 text-xs font-semibold border-b-2 transition-colors whitespace-nowrap {{ $abaStatus === 'rascunhos' ? 'border-purpura-600 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                Rascunhos / Sem Vínculo
            </button>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($formulariosList ?? [] as $formItem)
                @php
                    $locksFormHub = \App\Traits\WithEditingLock::obterLocksAtivos($formItem->id, ['formulario' => 'Formulário']);
                    $sufixoForm = $formItem->tipo === 'aprendizagem'
                        ? 'form_aprendizagem'
                        : (($formItem->tipo === 'ciclo' || !empty($formItem->ciclo_id)) ? 'form_ciclo' : 'form_geral');
                    $chaveConstruir = $sufixoForm === 'form_ciclo'
                        ? 'inscricao.ciclo.construir'
                        : "instituicao.formbuilder.construir_{$sufixoForm}";
                @endphp
                <div wire:key="hub-form-{{ $formItem->id }}" class="p-4 sm:px-5 flex flex-col lg:flex-row lg:items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($pode("instituicao.formbuilder.respostas_{$sufixoForm}"))
                                <a href="{{ route('formularios.show', $formItem->id) }}" class="font-semibold text-sm text-gray-900 hover:text-purpura-600 dark:text-white truncate">
                                    {{ $formItem->titulo }}
                                </a>
                            @else
                                <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                    {{ $formItem->titulo }}
                                </span>
                            @endif
                            <span class="px-2 py-0.5 text-[11px] font-medium rounded border {{ $formItem->is_rascunho ? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800' }}">
                                {{ $formItem->is_rascunho ? 'Rascunho' : 'Publicado' }}
                            </span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $formItem->tipo === 'aprendizagem' ? 'Aprendizagem' : 'Formulário Público' }}
                            </span>
                            @if(count($locksFormHub) > 0)
                                <x-editing-lock-badge
                                    :id="$formItem->id"
                                    :tipos="['formulario' => 'Formulário']" />
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            @if($formItem->tipo === 'aprendizagem')
                                Vínculo: <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $formItem->faseAprendizagem?->ciclo?->nome ? $formItem->faseAprendizagem->ciclo->nome . ' — ' . $formItem->faseAprendizagem->nome : 'Sem vínculo' }}</span>
                            @else
                                <a href="{{ url('/f/' . $formItem->slug) }}" target="_blank" class="font-mono text-gray-700 dark:text-gray-300 hover:text-purpura-600 hover:underline">/f/{{ $formItem->slug }}</a>
                                @if($formItem->cicloSeletivo)
                                    · Ciclo: <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $formItem->cicloSeletivo->nome }}</span>
                                @endif
                            @endif
                            · {{ $formItem->campos_count ?? 0 }} bloco(s)
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                        @if($pode($chaveConstruir) || $pode("instituicao.formbuilder.editar_{$sufixoForm}"))
                            <a href="{{ url('/f/' . $formItem->slug . '?modo=teste') }}" target="_blank" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md text-xs font-medium transition" title="Testar em Sandbox">
                                Testar
                            </a>
                        @endif

                        @if($pode("instituicao.formbuilder.respostas_{$sufixoForm}"))
                            <a href="{{ route('formularios.show', $formItem->id) }}" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md text-xs font-medium transition">
                                Respostas
                            </a>
                        @endif

                        @if($pode("instituicao.formbuilder.vincular_{$sufixoForm}"))
                            <button type="button" wire:click="abrirModalVincular({{ $formItem->id }})" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md text-xs font-medium transition">
                                Vincular
                            </button>
                        @endif

                        @if($pode("instituicao.formbuilder.editar_{$sufixoForm}"))
                            <a href="{{ route('formularios.edit', $formItem->id) }}" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md text-xs font-medium transition" title="Configurações">
                                <i class="ph ph-gear text-sm align-middle"></i>
                            </a>
                        @endif

                        @if($pode("instituicao.formbuilder.publicar_{$sufixoForm}"))
                            <button type="button" wire:click="alternarStatusRascunho({{ $formItem->id }})" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md text-xs font-medium transition">
                                {{ $formItem->is_rascunho ? 'Publicar' : 'Despublicar' }}
                            </button>
                        @endif

                        @if($pode($chaveConstruir))
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formItem->id]) }}" class="px-3 py-1.5 bg-purpura-600 hover:bg-purpura-700 text-white rounded-md text-xs font-semibold transition">
                                Construtor
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-gray-500">
                    Nenhum formulário encontrado em <strong>{{ $abaStatus === 'rascunhos' ? 'Rascunhos' : 'Publicados' }}</strong>.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL 1: SELECIONAR CICLO SELETIVO --}}
    @if($modalCicloAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-md overflow-hidden flex flex-col max-h-[85vh]" @click.outside="$wire.set('modalCicloAberto', false)">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center shrink-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Selecione o Ciclo Seletivo</h3>
                    <button type="button" wire:click="$set('modalCicloAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1"><i class="ph ph-x text-base"></i></button>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 overflow-y-auto">
                    @forelse($ciclos as $ciclo)
                        @php
                            $locksCicloHub = \App\Traits\WithEditingLock::obterLocksAtivos($ciclo->id, [
                                'ciclo'        => 'Formulário',
                                'ciclo_dados'  => 'Ciclo',
                                'ciclo_regras' => 'Regras',
                            ]);
                        @endphp
                        <button type="button" wire:click="selecionarCicloSeletivo({{ $ciclo->id }})" class="w-full text-left px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition flex justify-between items-center gap-3">
                            <div class="min-w-0 space-y-1">
                                <div class="font-medium text-sm text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">
                                    <span class="truncate">{{ $ciclo->nome }}</span>
                                    @if(($ciclo->tipo_ciclo ?? 'regular') === 'customizado')
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded shrink-0">Projeto</span>
                                    @endif
                                    @if(count($locksCicloHub) > 0)
                                        <x-editing-lock-badge
                                            :id="$ciclo->id"
                                            :tipos="[
                                                'ciclo'        => 'Formulário',
                                                'ciclo_dados'  => 'Ciclo',
                                                'ciclo_regras' => 'Regras',
                                            ]" />
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                            </div>
                            <i class="ph ph-caret-right text-gray-400 shrink-0"></i>
                        </button>
                    @empty
                        <div class="p-6 text-center text-xs text-gray-500">Nenhum ciclo cadastrado.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 2: AVALIAÇÃO DE APRENDIZAGEM --}}
    @if($modalAprendizagemAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]" @click.outside="$wire.set('modalAprendizagemAberto', false)">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center shrink-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Novo Formulário de Aprendizagem</h3>
                    <button type="button" wire:click="$set('modalAprendizagemAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1"><i class="ph ph-x text-base"></i></button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Título do formulário</label>
                        <input type="text" wire:model="aprendizagem.titulo" placeholder="Ex: Autoavaliação Semestral" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                    </div>

                    <label class="flex items-start gap-2.5 p-3 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 cursor-pointer">
                        <input type="checkbox" wire:model.live="aprendizagem.is_rascunho" class="mt-0.5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300">
                        <div class="text-xs">
                            <span class="font-medium text-gray-900 dark:text-white block">Criar como rascunho</span>
                            <span class="text-gray-500 dark:text-gray-400">Permite montar as perguntas agora e vincular ciclo/fase depois.</span>
                        </div>
                    </label>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ciclo de Aprendizagem {{ $aprendizagem['is_rascunho'] ? '(opcional)' : '*' }}</label>
                        <select wire:model.live="aprendizagem.ciclo_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                            <option value="">Selecione o ciclo...</option>
                            @foreach($ciclosAprendizagem as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                        @error('aprendizagem.ciclo_id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($aprendizagem['ciclo_id'])
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fase do ciclo {{ $aprendizagem['is_rascunho'] ? '(opcional)' : '*' }}</label>
                            <select wire:model="aprendizagem.fase_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                                <option value="">Selecione a fase...</option>
                                @foreach($fasesAprendizagem as $fase) 
                                    <option value="{{ $fase['id'] }}">{{ $fase['nome'] }}</option> 
                                @endforeach
                            </select>
                            @error('aprendizagem.fase_id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
                <div class="px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2 shrink-0">
                    <button type="button" wire:click="$set('modalAprendizagemAberto', false)" class="px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md">
                        Cancelar
                    </button>
                    <button type="button" wire:click="criarFormularioAprendizagem" class="px-4 py-2 bg-purpura-600 hover:bg-purpura-700 text-white font-semibold rounded-md transition text-xs">
                        Criar e abrir construtor
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 3: VINCULAR DESTINO --}}
    @if($modalVincularAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]" @click.outside="$wire.set('modalVincularAberto', false)">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center shrink-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Vincular Formulário a um Destino</h3>
                    <button type="button" wire:click="$set('modalVincularAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1"><i class="ph ph-x text-base"></i></button>
                </div>

                <div class="p-5 space-y-4 overflow-y-auto">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Título do formulário</label>
                        <input type="text" wire:model="formVincularTitulo" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Destino <span class="text-red-500">*</span></label>
                        <select wire:model.live="destinoVinculo" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @if($pode('instituicao.formbuilder.vincular_form_geral'))
                                <option value="geral">Formulário Público Geral (/f/slug)</option>
                            @endif
                            @if($pode('instituicao.formbuilder.vincular_form_ciclo'))
                                <option value="ciclo_inscricao">Aplicar Campos a um Ciclo de Inscrição (/inscricao)</option>
                            @endif
                            @if($pode('instituicao.formbuilder.vincular_form_aprendizagem'))
                                <option value="aprendizagem">Vincular a Ciclo / Fase de Aprendizagem</option>
                            @endif
                        </select>
                    </div>

                    @if($destinoVinculo === 'ciclo_inscricao')
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md space-y-3">
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                Os blocos, páginas e fases serão copiados para o Ciclo de Inscrição selecionado.
                            </p>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ciclo Seletivo *</label>
                                <select wire:model="vinculoCicloInscricaoId" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="">Selecione o ciclo...</option>
                                    @foreach($ciclos as $c)
                                        <option value="{{ $c->id }}">{{ $c->nome }} ({{ ($c->tipo_ciclo ?? 'regular') === 'customizado' ? 'Projeto' : 'Regular' }})</option>
                                    @endforeach
                                </select>
                                @error('vinculoCicloInscricaoId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <label class="flex items-start gap-2 cursor-pointer pt-1">
                                <input type="checkbox" wire:model="substituirCamposCiclo" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Substituir os campos atuais do ciclo (se desmarcado, apenas adiciona ao final).</span>
                            </label>
                        </div>

                    @elseif($destinoVinculo === 'aprendizagem')
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ciclo de Aprendizagem *</label>
                                <select wire:model.live="vinculoAprendizagemCicloId" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="">Selecione o ciclo...</option>
                                    @foreach($ciclosAprendizagem as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                                </select>
                                @error('vinculoAprendizagemCicloId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            @if($vinculoAprendizagemCicloId)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fase do Ciclo *</label>
                                    <select wire:model="vinculoAprendizagemFaseId" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="">Selecione a fase...</option>
                                        @foreach($fasesVinculoDisponiveis as $fase) <option value="{{ $fase['id'] }}">{{ $fase['nome'] }}</option> @endforeach
                                    </select>
                                    @error('vinculoAprendizagemFaseId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                    @else
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md space-y-3">
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                O formulário responderá na URL pública <code class="font-mono">/f/{slug}</code>.
                            </p>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ciclo Seletivo associado (opcional)</label>
                                <select wire:model="vinculoCicloInscricaoId" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="">Nenhum (formulário avulso)</option>
                                    @foreach($ciclos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    @if($destinoVinculo !== 'ciclo_inscricao')
                        <label class="flex items-center gap-2 pt-1 cursor-pointer">
                            <input type="checkbox" wire:model="publicarAoVincular" class="rounded text-purpura-600 border-gray-300">
                            <span class="text-xs font-medium text-gray-800 dark:text-gray-200">Publicar e ativar imediatamente após salvar</span>
                        </label>
                    @endif
                </div>

                <div class="px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2 shrink-0">
                    <button type="button" wire:click="$set('modalVincularAberto', false)" class="px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md">Cancelar</button>
                    <button type="button" wire:click="salvarVinculoFormulario" class="px-4 py-2 text-xs font-semibold text-white bg-purpura-600 hover:bg-purpura-700 rounded-md transition">
                        Confirmar vínculo
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>