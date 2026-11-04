<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $esteiraId ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos (Sequência)' }}" 
        subtitle="Programe múltiplas etapas de e-mail por condições combinadas, datas fixas ou intervalos automáticos de dias"
        icon="ph ph-git-merge"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('esteiras.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrow-left"></i> Voltar para Lista
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-4">
        
        <!-- COLUNA PRINCIPAL (8 COLUNAS) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- BLOCO 1: IDENTIFICAÇÃO E PÚBLICO-ALVO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-purpura-50 dark:bg-purpura-900/40 text-purpura-600 font-black text-xs flex items-center justify-center border border-purpura-200">1</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Identificação e Origem do Público</h3>
                            <p class="text-xs text-gray-500">Defina para qual formulário, ciclo seletivo ou grupo esta fila será executada.</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer bg-gray-50 dark:bg-gray-900/60 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700">
                        <input type="checkbox" wire:model.live="status" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="text-xs font-extrabold uppercase {{ $status ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $status ? 'Esteira Ativa' : 'Esteira Pausada' }}
                        </span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">Nome da Esteira / Campanha <span class="text-red-500">*</span></label>
                        <input wire:model="nome" type="text" placeholder="Ex: Régua de Cobrança de Documentos - Aprovados 2º Semestre" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-semibold focus:ring-purpura-500">
                        @error('nome') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">Fonte de Destinatários <span class="text-red-500">*</span></label>
                        <select wire:model.live="publico_alvo" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 text-sm font-semibold">
                            <option value="inscricoes">Inscrições (Processo Seletivo / Projeto)</option>
                            <option value="formulario_publico">Formulário Geral / Pré-Inscrição / Avaliação</option>
                            <option value="estudantes">Estudantes Matriculados (Portal do Aluno)</option>
                            <option value="usuarios">Usuários Internos do Sistema</option>
                            <option value="lista_manual">Lista Manual de E-mails</option>
                        </select>
                    </div>

                    @if($publico_alvo === 'inscricoes')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">Ciclo / Processo Seletivo</label>
                            <select wire:model.live="ciclo_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 text-sm font-semibold">
                                <option value="">Todos os Ciclos</option>
                                @foreach($ciclos as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($publico_alvo === 'formulario_publico')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">Selecione o Formulário <span class="text-red-500">*</span></label>
                            <select wire:model.live="formulario_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 text-sm font-semibold">
                                <option value="">-- Escolha o Formulário --</option>
                                @foreach($formularios as $f)
                                    <option value="{{ $f->id }}">{{ $f->titulo }} ({{ strtoupper($f->tipo) }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <!-- BLOCO 2: CONDIÇÕES E FILTROS COMBINADOS -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 font-black text-xs flex items-center justify-center border border-blue-200">2</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Condições para Receber os Disparos</h3>
                            <p class="text-xs text-gray-500">Combine status, vínculo hierárquico de unidades/cursos/turnos ou respostas do formulário.</p>
                        </div>
                    </div>
                </div>

                @if($publico_alvo === 'inscricoes')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">
                            Status da Inscrição (Pode marcar 1 ou mais, ex: Aprovado + Documentação Pendente)
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($statusInscricoes as $st)
                                @php $marcado = in_array((string) $st->id, $status_ids, true); @endphp
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-bold cursor-pointer transition {{ $marcado ? 'bg-purpura-50 border-purpura-400 text-purpura-800 dark:bg-purpura-900/40 dark:text-purpura-200' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">
                                    <input type="checkbox" wire:model.live="status_ids" value="{{ $st->id }}" class="w-3.5 h-3.5 rounded text-purpura-600">
                                    <span>{{ $st->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Se nenhum status for marcado, considera inscritos em qualquer status.</p>
                    </div>
                @endif

                @if(in_array($publico_alvo, ['inscricoes', 'estudantes']))
                    @php
                        $qtdUniCfg = count($unidades_ids);
                        $qtdCurCfg = count($cursos_ids);
                        $qtdTurCfg = count($turnos_ids);
                    @endphp
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="p-4 bg-gray-50/70 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <i class="ph-fill ph-tree-structure text-purpura-600 text-base"></i>
                                    <span class="text-xs font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Filtro de Unidades, Cursos e Turnos</span>
                                </div>
                                @if($qtdUniCfg > 0 || $qtdCurCfg > 0 || $qtdTurCfg > 0)
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1 text-[11px] font-bold">
                                        <span class="px-2 py-0.5 rounded bg-purpura-50 text-purpura-700 border border-purpura-200">{{ $qtdUniCfg }} Unidade(s)</span>
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $qtdCurCfg }} Vínculo(s) de Curso</span>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $qtdTurCfg }} Vínculo(s) de Turno</span>
                                        <button type="button" wire:click="limparRestricaoComboAcademico" class="text-xs font-bold text-red-500 hover:underline ml-2">
                                            Limpar filtro (Usar Todos)
                                        </button>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500">
                                        Enviando para todas as Unidades, Cursos e Turnos. Clique ao lado para restringir combinações específicas.
                                    </p>
                                @endif
                            </div>

                            <button type="button"
                                    wire:click="abrirModalConfigCombo"
                                    class="shrink-0 py-2.5 px-4 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 shadow-xs">
                                <i class="ph-bold ph-tree-structure text-sm"></i> Configurar Unidades / Cursos / Turnos
                            </button>
                        </div>
                    </div>
                @endif

                @if(in_array($publico_alvo, ['inscricoes', 'formulario_publico']))
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1.5">Situação de Preenchimento do Formulário</label>
                        <select wire:model.live="progresso_preenchimento" class="w-full md:w-80 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs font-semibold py-2 px-3">
                            <option value="todos">Todos (Iniciados + Finalizados)</option>
                            <option value="incompleto">Apenas Preenchimento Incompleto (Em Andamento)</option>
                            <option value="finalizado">Apenas Preenchimento Concluído (Finalizados)</option>
                        </select>
                    </div>

                    <!-- CONDIÇÕES SOBRE RESPOSTAS DE CAMPOS DINÂMICOS -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Condições por Resposta do Formulário (Opcional)</span>
                            <button type="button" wire:click="adicionarRegraDinamica" class="text-xs font-bold text-purpura-600 hover:underline flex items-center gap-1">
                                <i class="ph-bold ph-plus"></i> Adicionar Condição de Campo
                            </button>
                        </div>

                        @foreach($regras_dinamicas as $rIdx => $regra)
                            <div wire:key="regra-din-{{ $rIdx }}" class="grid grid-cols-12 gap-2 items-center bg-gray-50 dark:bg-gray-900/50 p-2.5 rounded-xl border border-gray-200 dark:border-gray-700">
                                <div class="col-span-5">
                                    <select wire:model.live="regras_dinamicas.{{ $rIdx }}.campo" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white">
                                        <option value="">Escolha o campo...</option>
                                        @foreach($this->camposDinamicosDisponiveis as $cDin)
                                            <option value="{{ $cDin->name }}">{{ strip_tags($cDin->label) }} ({{ $cDin->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <select wire:model.live="regras_dinamicas.{{ $rIdx }}.operador" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white">
                                        <option value="=">Igual a (=)</option>
                                        <option value="!=">Diferente de (!=)</option>
                                        <option value="contem">Contém o texto</option>
                                        <option value="preenchido">Está preenchido</option>
                                        <option value="vazio">Está em branco</option>
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    @if(!in_array($regra['operador'] ?? '=', ['preenchido', 'vazio']))
                                        <input type="text" wire:model.live.debounce.400ms="regras_dinamicas.{{ $rIdx }}.valor" placeholder="Valor esperado..." class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white">
                                    @endif
                                </div>
                                <div class="col-span-1 text-right">
                                    <button type="button" wire:click="removerRegraDinamica({{ $rIdx }})" class="text-gray-400 hover:text-red-500 p-1">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($publico_alvo === 'lista_manual')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">Lista de E-mails (1 por linha ou separados por vírgula)</label>
                        <textarea wire:model.live.debounce.500ms="emails_manuais_texto" rows="4" placeholder="candidato1@email.com&#10;candidato2@email.com" class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:text-white text-xs font-mono p-3"></textarea>
                    </div>
                @endif
            </div>

            <!-- BLOCO 3: LINHA DO TEMPO DAS ETAPAS DE DISPARO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 font-black text-xs flex items-center justify-center border border-emerald-200">3</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Etapas da Esteira (Sequência de E-mails)</h3>
                            <p class="text-xs text-gray-500">Defina o template e o momento exato de disparo de cada etapa.</p>
                        </div>
                    </div>

                    <button type="button" wire:click="adicionarEtapa" class="btn btn--secondary btn--medium flex items-center gap-1.5">
                        <i class="ph-bold ph-plus text-purpura-600"></i> Adicionar Etapa
                    </button>
                </div>

                <div class="space-y-4 relative before:absolute before:top-6 before:bottom-6 before:left-5 before:w-0.5 before:bg-purpura-200 dark:before:bg-gray-700">
                    @foreach($etapas as $index => $etapa)
                        <div wire:key="esteira-etapa-{{ $index }}" class="relative pl-12">
                            <div class="absolute left-1.5 top-5 w-7 h-7 rounded-full bg-purpura-600 text-white text-xs font-black flex items-center justify-center shadow-sm ring-4 ring-white dark:ring-gray-800">
                                {{ $index + 1 }}
                            </div>

                            <div class="p-5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/50 space-y-4 hover:border-purpura-300 transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Identificação da Etapa #{{ $index + 1 }}</label>
                                        <input type="text" wire:model="etapas.{{ $index }}.nome" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-bold px-3 py-2">
                                    </div>

                                    @if(count($etapas) > 1)
                                        <button type="button" wire:click="removerEtapa({{ $index }})" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition" title="Remover Etapa">
                                            <i class="ph-bold ph-trash text-base"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Template de E-mail <span class="text-red-500">*</span></label>
                                        <select wire:model="etapas.{{ $index }}.template_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-semibold py-2 px-3">
                                            <option value="">-- Selecione o Template --</option>
                                            @foreach($templates as $tpl)
                                                <option value="{{ $tpl->id }}">[{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error("etapas.{$index}.template_id") <span class="text-[11px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Quando Disparar?</label>
                                        <select wire:model.live="etapas.{{ $index }}.tipo_agendamento" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-semibold py-2 px-3">
                                            <option value="data_fixa">Em uma Data e Hora específica (dd/mm/yyyy HH:mm)</option>
                                            @if($index > 0)
                                                <option value="apos_etapa_anterior">Após X dias/horas da Etapa #{{ $index }}</option>
                                            @endif
                                            <option value="imediato">Imediatamente ao entrar na condição</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-200/70 dark:border-gray-700">
                                    @if(($etapa['tipo_agendamento'] ?? 'data_fixa') === 'data_fixa')
                                        <div>
                                            <label class="block text-[10px] font-bold text-purpura-700 dark:text-purpura-300 uppercase mb-1">
                                                <i class="ph-bold ph-calendar-blank"></i> Data e Hora do Disparo
                                            </label>
                                            <input type="datetime-local" wire:model="etapas.{{ $index }}.data_disparo" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-semibold py-2 px-3">
                                            @error("etapas.{$index}.data_disparo") <span class="text-[11px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif(($etapa['tipo_agendamento'] ?? '') === 'apos_etapa_anterior')
                                        <div>
                                            <label class="block text-[10px] font-bold text-indigo-700 dark:text-indigo-300 uppercase mb-1">
                                                <i class="ph-bold ph-clock-countdown"></i> Aguardar quanto tempo após a Etapa #{{ $index }}?
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" min="1" max="365" wire:model="etapas.{{ $index }}.atraso_valor" class="w-24 rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-bold text-center py-2">
                                                <select wire:model="etapas.{{ $index }}.atraso_unidade" class="rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-semibold py-2 px-3">
                                                    <option value="dias">Dia(s) após a Etapa #{{ $index }}</option>
                                                    <option value="horas">Hora(s) após a Etapa #{{ $index }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    @endif

                                    @if($index > 0)
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">
                                                <i class="ph-bold ph-funnel"></i> Condição de Engajamento da Etapa #{{ $index }}
                                            </label>
                                            <select wire:model="etapas.{{ $index }}.filtro_engajamento_anterior" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white text-xs font-semibold py-2 px-3">
                                                <option value="todos">Enviar para todos que receberam a Etapa #{{ $index }}</option>
                                                <option value="nao_abriu">Enviar APENAS para quem NÃO ABRIU a Etapa #{{ $index }}</option>
                                                <option value="nao_clicou">Enviar APENAS para quem NÃO CLICOU em links da Etapa #{{ $index }}</option>
                                                <option value="abriu">Enviar APENAS para quem ABRIU a Etapa #{{ $index }}</option>
                                                <option value="clicou">Enviar APENAS para quem CLICOU na Etapa #{{ $index }}</option>
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- COLUNA LATERAL DIREITA (4 COLUNAS): SIMULADOR DE ALCANCE (SEM GRADIENTE) E REGRAS DE SAÍDA -->
        <div class="lg:col-span-4 space-y-6 sticky top-6">
            
            <!-- CARD DE ALCANCE EM TEMPO REAL (SÓLIDO, SEM GRADIENTE) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 border-l-4 border-l-purpura-600">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Destinatários Elegíveis Agora
                    </span>
                    <div class="w-9 h-9 rounded-xl bg-purpura-50 dark:bg-purpura-900/30 border border-purpura-200 dark:border-purpura-800 flex items-center justify-center text-purpura-600 dark:text-purpura-400">
                        <i class="ph-fill ph-users-three text-lg"></i>
                    </div>
                </div>
                <div class="text-3xl font-black text-gray-900 dark:text-white mt-2">
                    {{ $this->estimativaPublicoAtual }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                    Pessoas que cumprem todas as condições selecionadas neste exato momento. Novos inscritos que entrarem na condição também serão incluídos automaticamente.
                </p>
            </div>

            <!-- REGRAS INTELIGENTES DE SAÍDA E REVALIDAÇÃO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-1.5 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <i class="ph-fill ph-shield-check text-emerald-600 text-base"></i> Proteção Anti-Spam e Saída
                </h4>

                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" wire:model.live="revalidar_condicoes_no_envio" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                    <div class="text-xs">
                        <span class="font-bold text-gray-900 dark:text-white block">Revalidar condições antes de cada etapa</span>
                        <span class="text-[11px] text-gray-500">Se na data da Etapa 2 o inscrito já tiver mudado de status (ex: entregou a documentação), ele não receberá cobranças desnecessárias.</span>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 cursor-pointer pt-2 border-t border-gray-100 dark:border-gray-700">
                    <input type="checkbox" wire:model.live="sair_ao_concluir_formulario" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                    <div class="text-xs">
                        <span class="font-bold text-gray-900 dark:text-white block">Remover da fila ao concluir formulário</span>
                        <span class="text-[11px] text-gray-500">Interrompe os lembretes seguintes assim que o candidato finalizar a última etapa do formulário.</span>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 cursor-pointer pt-2 border-t border-gray-100 dark:border-gray-700">
                    <input type="checkbox" wire:model="apenas_horario_comercial" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                    <div class="text-xs">
                        <span class="font-bold text-gray-900 dark:text-white block">Disparar apenas em horário comercial</span>
                        <span class="text-[11px] text-gray-500">Garante que os envios ocorram somente entre 08:00 e 20:00.</span>
                    </div>
                </label>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="w-full py-3 px-5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center justify-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-base"></i>
                        <span>{{ $esteiraId ? 'Salvar Alterações da Esteira' : 'Ativar e Agendar Esteira' }}</span>
                    </button>
                </div>
            </div>
        </div>

    </form>

    {{-- MODAL DE CONFIGURAÇÃO DE UNIDADES / CURSOS / TURNOS EM ÁRVORE (PADRÃO CONSTRUTOR DE FORMULÁRIOS) --}}
    @if($modalConfigComboAberto)
        <div class="fixed inset-0 z-[260] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-tree-structure text-purpura-600 text-lg"></i> Configurar Filtro: Unidades, Cursos e Turnos
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Selecione uma Unidade à esquerda para marcar apenas os Cursos daquela Unidade e os Turnos específicos daquele Curso.
                        </p>
                    </div>
                    <button type="button" wire:click="$set('modalConfigComboAberto', false)" class="text-gray-400 hover:text-red-500 p-1">
                        <i class="ph-bold ph-x text-xl"></i>
                    </button>
                </div>

                <div class="flex flex-col md:flex-row h-[420px] bg-white dark:bg-gray-800 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                    {{-- COLUNA 1: UNIDADES --}}
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase text-gray-500 tracking-wider">1. Unidades</span>
                            <span class="text-[10px] font-bold text-purpura-600">{{ count($comboUnidadesSelecionadas) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @foreach($unidadesDb as $u)
                                <div wire:key="esteira-uni-{{ $u->id }}"
                                     wire:click="setComboActiveUnidade({{ $u->id }})"
                                     class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $comboActiveUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                        <input type="checkbox" wire:model.live="comboUnidadesSelecionadas" value="{{ $u->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                                        <span class="text-xs font-bold {{ $comboActiveUnidadeId == $u->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $u->nome }}</span>
                                    </label>
                                    <i class="ph ph-caret-right text-base {{ $comboActiveUnidadeId == $u->id ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- COLUNA 2: CURSOS DA UNIDADE SELECIONADA --}}
                    <div class="flex-1 flex flex-col bg-gray-50/40 dark:bg-gray-800/80 overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase text-gray-500 tracking-wider">2. Cursos da Unidade</span>
                            <span class="text-[10px] font-bold text-purpura-600">{{ count($comboCursosSelecionados) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @if($comboActiveUnidadeId)
                                @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $comboActiveUnidadeId)) as $c)
                                    @php $chaveCursoUnidade = $comboActiveUnidadeId . '-' . $c->id; @endphp
                                    <div wire:key="esteira-cur-{{ $chaveCursoUnidade }}"
                                         wire:click="setComboActiveCurso({{ $c->id }})"
                                         class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition {{ $comboActiveCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/30 ring-1 ring-purpura-300' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                            <input type="checkbox" wire:model.live="comboCursosSelecionados" value="{{ $chaveCursoUnidade }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                                            <span class="text-xs font-bold {{ $comboActiveCursoId == $c->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $c->nome }}</span>
                                        </label>
                                        <i class="ph ph-caret-right text-base {{ $comboActiveCursoId == $c->id ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                    </div>
                                @endforeach
                            @else
                                <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60 p-4 text-center">
                                    <i class="ph ph-buildings text-3xl mb-2"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Selecione uma Unidade à esquerda</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- COLUNA 3: TURNOS DO CURSO NAQUELA UNIDADE --}}
                    <div class="flex-1 flex flex-col bg-gray-50/80 dark:bg-gray-900/30 overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase text-gray-500 tracking-wider">3. Turnos Vinculados</span>
                            <span class="text-[10px] font-bold text-purpura-600">{{ count($comboTurnosSelecionados) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @if($comboActiveUnidadeId && $comboActiveCursoId)
                                @php $cursoComboObj = $cursosDb->firstWhere('id', $comboActiveCursoId); @endphp
                                @if($cursoComboObj)
                                    @foreach($cursoComboObj->turnosVinculados as $t)
                                        @php $chaveTurnoCompleta = $comboActiveUnidadeId . '-' . $comboActiveCursoId . '-' . $t->id; @endphp
                                        <div wire:key="esteira-tur-{{ $chaveTurnoCompleta }}" class="flex items-center p-2.5 rounded-lg transition hover:bg-white dark:hover:bg-gray-700">
                                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                <input type="checkbox" wire:model.live="comboTurnosSelecionados" value="{{ $chaveTurnoCompleta }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t->nome }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            @else
                                <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60 p-4 text-center">
                                    <i class="ph ph-graduation-cap text-3xl mb-2"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Selecione um Curso ao centro</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/60 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-[11px] text-gray-500">
                        Se deixar tudo desmarcado, a esteira considerará todas as Unidades, Cursos e Turnos.
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('modalConfigComboAberto', false)" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmarConfigComboAcademico" class="px-5 py-2 text-xs font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow-sm flex items-center gap-1.5">
                            <i class="ph-bold ph-check"></i> Aplicar Filtro Acadêmico
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>