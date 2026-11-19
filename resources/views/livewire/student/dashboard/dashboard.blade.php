<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 font-sans">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Meu Painel</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Olá, {{ explode(' ', $student->name)[0] }}. Acompanhe sua inscrição, documentos, solicitações e formulários.
            </p>
        </div>

        @if($inscricao && $uploadLiberado)
            <a href="{{ route('student.student.documentos') }}" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold bg-purpura-600 hover:bg-purpura-700 text-white transition">
                <i class="ph ph-folder-open text-sm"></i> Enviar Documentação / Laudo
            </a>
        @endif
    </div>

    {{-- ALERTA DE ENVIO DE DOCUMENTOS / LAUDO DISPONÍVEL --}}
    @if($documentosPendentes && $uploadLiberado)
        <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <i class="ph-fill ph-warning-circle text-xl text-amber-600 shrink-0 mt-0.5"></i>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200">
                        Envio de Documentação Liberado
                    </h2>
                    <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">
                        Sua inscrição está na etapa de entrega documental. Envie seus documentos obrigatórios e/ou laudo médico pelo portal.
                    </p>
                </div>
            </div>
            <a href="{{ route('student.student.documentos') }}" wire:navigate class="shrink-0 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition inline-flex items-center gap-1.5">
                <i class="ph ph-upload-simple"></i> Enviar Agora
            </a>
        </div>
    @endif

    {{-- AVALIAÇÕES DE APRENDIZAGEM PENDENTES --}}
    @if(count($formulariosPendentes) > 0)
        <div class="space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Avaliações Pendentes
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($formulariosPendentes as $av)
                    @foreach($av->faseAtual->formularios as $form)
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4">
                            <div>
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-semibold uppercase rounded">
                                    {{ $av->ciclo->nome }}
                                </span>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mt-1.5">{{ $form->titulo }}</h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Prazo: {{ $av->data_prazo ? \Carbon\Carbon::parse($av->data_prazo)->format('d/m/Y') : 'Não definido' }}
                                </p>
                            </div>
                            <a href="{{ route('student.formulario-aprendizagem.responder', ['slug' => $form->slug, 'aluno_id' => $student->id]) }}" class="px-3.5 py-2 bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold rounded-lg transition shrink-0">
                                Responder
                            </a>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- COLUNA ESQUERDA (8 COLUNAS): INSCRIÇÃO ATUAL, SOLICITAÇÕES E FORMULÁRIOS GERAIS --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD: INSCRIÇÃO ATUAL --}}
            @if($inscricao)
                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Inscrição Atual (#{{ $inscricao->id }})
                                </h3>
                                <span class="text-xs text-gray-400">• {{ $inscricao->ciclo->nome ?? 'Ciclo Atual' }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Registrada em {{ $inscricao->created_at->format('d/m/Y \à\s H:i') }}
                            </p>
                        </div>

                        @php
                            $corHex = $inscricao->statusInscricao->cor ?? '#6B7280';
                            $statusExibido = ($inscricao->statusInscricao && ($inscricao->statusInscricao->visivel_estudante ?? true))
                                ? ($inscricao->statusInscricao->titulo_amigavel ?: $inscricao->statusInscricao->nome)
                                : ($inscricao->statusInscricao->nome ?? 'Em Análise');
                        @endphp
                        <span class="px-2.5 py-1 text-[11px] font-semibold rounded border uppercase tracking-wider w-max"
                              style="background-color: {{ $corHex }}12; color: {{ $corHex }}; border-color: {{ $corHex }}35;">
                            {{ $statusExibido }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="block text-[10px] uppercase font-semibold text-gray-400 mb-0.5">Unidade / Sede</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ $inscricao->unidade->nome ?? 'Não informada' }}
                                @if($inscricao->estado) ({{ $inscricao->estado }}) @endif
                            </span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-semibold text-gray-400 mb-0.5">Curso Escolhido</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $inscricao->curso->nome ?? 'Não informado' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-semibold text-gray-400 mb-0.5">Turno</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $inscricao->turno->nome ?? 'Não informado' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button"
                                wire:click="abrirDetalhesInscricao({{ $inscricao->id }})"
                                class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                            <i class="ph ph-eye"></i> Ver Respostas
                        </button>

                        <button type="button"
                                wire:click="baixarFichaInscricaoPdf({{ $inscricao->id }})"
                                wire:loading.attr="disabled"
                                class="px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 text-purpura-700 dark:text-purpura-300 border border-purpura-200 dark:border-purpura-800 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                            <i class="ph ph-file-pdf"></i> Baixar Ficha (PDF)
                        </button>

                        @if($uploadLiberado)
                            <a href="{{ route('student.student.documentos') }}"
                               wire:navigate
                               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                                <i class="ph ph-upload-simple"></i> Enviar Documentos / Laudo
                            </a>
                        @else
                            <span class="px-3 py-2 bg-gray-50 dark:bg-gray-900 text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg text-xs inline-flex items-center gap-1.5"
                                  title="O envio de documentos será habilitado quando sua inscrição atingir a etapa documental">
                                <i class="ph ph-lock-key"></i> Upload de Documentos Aguardando Etapa
                            </span>
                        @endif

                        <button type="button"
                                wire:click="abrirModalSolicitacao"
                                class="px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 ml-auto">
                            <i class="ph ph-arrows-left-right"></i> Solicitar Troca de Curso/Unidade
                        </button>
                    </div>
                </div>
            @else
                <div class="p-8 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-sm font-bold text-gray-800 dark:text-white">Nenhuma inscrição encontrada</p>
                    <p class="text-xs text-gray-500 mt-1">Não localizamos inscrições vinculadas ao seu perfil até o momento.</p>
                </div>
            @endif

            {{-- CARD: FORMULÁRIOS GERAIS PREENCHIDOS E FASE ATUAL --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                            Formulários Preenchidos e Fase Atual
                        </h3>
                        <p class="text-[11px] text-gray-500">Histórico de formulários gerais respondidos por você.</p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">{{ count($formulariosGerais) }} registro(s)</span>
                </div>

                @if(count($formulariosGerais) > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($formulariosGerais as $fItem)
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $fItem['titulo'] }}</span>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                                            {{ $fItem['fase_nome'] }} (Etapa {{ $fItem['etapa_atual'] }}/{{ $fItem['total_etapas'] }})
                                        </span>
                                        @if($fItem['concluido'])
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Concluído
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                Em Preenchimento
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Última atualização em {{ $fItem['data_envio'] }}</p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            wire:click="baixarFichaRespostaPdf({{ $fItem['resposta_id'] }})"
                                            class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 transition inline-flex items-center gap-1">
                                        <i class="ph ph-file-pdf text-purpura-600"></i> PDF
                                    </button>
                                    @if(!$fItem['concluido'])
                                        <a href="{{ url('/f/' . $fItem['slug']) }}" target="_blank" class="px-2.5 py-1.5 rounded bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold transition">
                                            Continuar ↗
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-xs text-gray-400">
                        Nenhum formulário complementar respondido até o momento.
                    </div>
                @endif
            </div>

            {{-- CARD: RESUMO DAS SOLICITAÇÕES DE TROCA / HELPDESK --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                        Minhas Solicitações Recentes
                    </h3>
                    <a href="{{ route('student.solicitacoes') }}" wire:navigate class="text-xs font-semibold text-purpura-600 hover:underline">
                        Ver todas ({{ $minhasSolicitacoes->count() }}) →
                    </a>
                </div>

                @if($minhasSolicitacoes->count() > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($minhasSolicitacoes->take(3) as $solic)
                            @php $pay = is_array($solic->payload) ? $solic->payload : []; @endphp
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">
                                            {{ $solic->tema === 'alteracao_academica' ? 'Solicitação de Troca de Unidade/Curso/Turno' : ucfirst(str_replace('_', ' ', $solic->tema)) }}
                                        </span>
                                        <span class="text-[11px] text-gray-400">• {{ $solic->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    @if(!empty($pay['nova_unidade_nome']) || !empty($pay['novo_curso_nome']))
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                            Destino solicitado: <strong>{{ $pay['nova_unidade_nome'] ?? '-' }}</strong> — <strong>{{ $pay['novo_curso_nome'] ?? '-' }}</strong> ({{ $pay['novo_turno_nome'] ?? '-' }})
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-500 truncate mt-0.5">Motivo: "{{ $solic->justificativa }}"</p>
                                </div>

                                <div class="shrink-0">
                                    @if($solic->status === 'aprovada')
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded">Aprovada</span>
                                    @elseif($solic->status === 'rejeitada')
                                        <span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold uppercase rounded">Reprovada</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase rounded">Em Análise</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-xs text-gray-400">
                        Nenhuma solicitação registrada.
                    </div>
                @endif
            </div>

        </div>

        {{-- COLUNA DIREITA (4 COLUNAS): HISTÓRICO DE TODAS AS INSCRIÇÕES --}}
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                        Histórico de Inscrições
                    </h3>
                    <span class="text-xs text-gray-400 font-mono">{{ $historicoInscricoes->count() }}</span>
                </div>

                @if($historicoInscricoes->count() > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($historicoInscricoes as $hist)
                            <div class="p-4 space-y-2 hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition" wire:key="hist-{{ $hist->id }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-mono text-gray-400">
                                        #{{ $hist->id }} • {{ $hist->created_at->format('d/m/Y') }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                                        {{ $hist->statusInscricao->nome ?? 'Registrada' }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $hist->curso->nome ?? 'Curso não definido' }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $hist->unidade->nome ?? '-' }} • {{ $hist->turno->nome ?? '-' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Ciclo: {{ $hist->ciclo->nome ?? '-' }}</p>
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <button type="button"
                                            wire:click="abrirDetalhesInscricao({{ $hist->id }})"
                                            class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded text-[11px] font-semibold transition">
                                        Ver Ficha
                                    </button>
                                    <button type="button"
                                            wire:click="baixarFichaInscricaoPdf({{ $hist->id }})"
                                            class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purpura-600 dark:text-purpura-400 hover:bg-gray-50 rounded text-[11px] font-semibold transition inline-flex items-center gap-1">
                                        <i class="ph ph-file-pdf"></i> Baixar PDF
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-xs text-gray-400">
                        Nenhum histórico de inscrição encontrado.
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- MODAL MINIMALISTA: SOLICITAR TROCA DE UNIDADE / CURSO / TURNO --}}
    @if($modalSolicitacaoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Solicitar Troca de Unidade / Curso / Turno
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Escolha uma combinação disponível no ciclo atual ({{ $inscricao->ciclo->nome ?? '' }}).
                        </p>
                    </div>
                    <button type="button" wire:click="$set('modalSolicitacaoAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="salvarSolicitacao" class="p-5 space-y-4">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                        <div class="text-xs">
                            <span class="font-semibold text-gray-800 dark:text-gray-200 block">
                                UF da sua inscrição: {{ $inscricao->estado ?: 'Não informada' }}
                            </span>
                            <span class="text-[11px] text-gray-500">
                                Marque ao lado caso deseje visualizar unidades de outros estados.
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                            <input type="checkbox" wire:model.live="queroOutroEstado" class="w-4 h-4 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Quero em outro estado</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Nova Unidade Desejada <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="novaUnidadeId" class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Selecione a unidade...</option>
                            @foreach($this->unidadesDisponiveisTroca as $u)
                                <option value="{{ $u->id }}">{{ $u->nome }} {{ $u->estado ? "({$u->estado})" : '' }}</option>
                            @endforeach
                        </select>
                        @error('novaUnidadeId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Novo Curso Desejado <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="novoCursoId" @disabled(empty($novaUnidadeId)) class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white disabled:opacity-50">
                            <option value="">{{ empty($novaUnidadeId) ? 'Selecione a unidade primeiro...' : 'Selecione o curso...' }}</option>
                            @foreach($this->cursosDisponiveisTroca as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                        @error('novoCursoId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Novo Turno Desejado <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="novoTurnoId" @disabled(empty($novoCursoId)) class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white disabled:opacity-50">
                            <option value="">{{ empty($novoCursoId) ? 'Selecione o curso primeiro...' : 'Selecione o turno...' }}</option>
                            @foreach($this->turnosDisponiveisTroca as $t)
                                <option value="{{ $t->id }}">{{ $t->nome }}</option>
                            @endforeach
                        </select>
                        @error('novoTurnoId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Motivo da Solicitação de Troca <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="motivoSolicitacao" rows="3" placeholder="Descreva o motivo para a troca de unidade, curso ou turno..." class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
                        @error('motivoSolicitacao') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('modalSolicitacaoAberto', false)" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-xs font-semibold transition">
                            Enviar Solicitação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL MINIMALISTA: DETALHES E RESPOSTAS DA INSCRIÇÃO --}}
    @if($modalInscricaoAberto && $inscricaoDetalhe)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-3xl border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Ficha da Inscrição #{{ $inscricaoDetalhe->id }}
                        </h3>
                        <p class="text-xs text-gray-500">Registrada em {{ $inscricaoDetalhe->created_at->format('d/m/Y \à\s H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="baixarFichaInscricaoPdf({{ $inscricaoDetalhe->id }})"
                                class="px-3 py-1.5 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                            <i class="ph ph-file-pdf"></i> Baixar PDF
                        </button>
                        <button type="button" wire:click="$set('modalInscricaoAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="p-5 overflow-y-auto max-h-[70vh] space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs">
                        <div>
                            <span class="text-[10px] uppercase text-gray-400 font-semibold block">Unidade</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $inscricaoDetalhe->unidade->nome ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-gray-400 font-semibold block">Curso</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $inscricaoDetalhe->curso->nome ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-gray-400 font-semibold block">Turno</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $inscricaoDetalhe->turno->nome ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Respostas Registradas</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @php
                                $dinDetalhe = is_array($inscricaoDetalhe->dados_dinamicos)
                                    ? $inscricaoDetalhe->dados_dinamicos
                                    : (json_decode($inscricaoDetalhe->dados_dinamicos ?? '{}', true) ?: []);
                            @endphp
                            @forelse($dinDetalhe as $chave => $valor)
                                @continue(str_contains(strtolower($chave), 'form_config') || str_starts_with($chave, '_'))
                                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs">
                                    <span class="block text-[10px] font-semibold uppercase text-gray-400 mb-0.5">{{ str_replace('_', ' ', $chave) }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white break-words">
                                        {{ is_array($valor) ? implode(', ', $valor) : ($valor ?: '-') }}
                                    </span>
                                </div>
                            @empty
                                <div class="col-span-2 p-4 text-center text-xs text-gray-400 border border-dashed border-gray-200 rounded-lg">
                                    Nenhum campo complementar preenchido.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end">
                    <button type="button" wire:click="$set('modalInscricaoAberto', false)" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>