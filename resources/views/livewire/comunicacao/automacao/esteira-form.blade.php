<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $esteiraId ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos (Sequência)' }}" 
        subtitle="Programe múltiplas etapas de e-mail por condições combinadas, datas fixas ou intervalos automáticos de dias"
        icon="ph ph-git-merge"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrow-left"></i> Voltar
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
                            <p class="text-xs text-gray-500">Combine status, unidades, cursos ou respostas específicas do formulário.</p>
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

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase mb-1.5">Filtrar Unidades</label>
                            <div class="max-h-36 overflow-y-auto p-2.5 border border-gray-200 dark:border-gray-700 rounded-xl space-y-1.5 bg-gray-50/50 dark:bg-gray-900/50">
                                @foreach($unidades as $u)
                                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                                        <input type="checkbox" wire:model.live="unidades_ids" value="{{ $u->id }}" class="w-3.5 h-3.5 rounded text-purpura-600">
                                        <span class="truncate">{{ $u->nome }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase mb-1.5">Filtrar Cursos</label>
                            <div class="max-h-36 overflow-y-auto p-2.5 border border-gray-200 dark:border-gray-700 rounded-xl space-y-1.5 bg-gray-50/50 dark:bg-gray-900/50">
                                @foreach($cursos as $c)
                                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                                        <input type="checkbox" wire:model.live="cursos_ids" value="{{ $c->id }}" class="w-3.5 h-3.5 rounded text-purpura-600">
                                        <span class="truncate">{{ $c->nome }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase mb-1.5">Filtrar Turnos</label>
                            <div class="max-h-36 overflow-y-auto p-2.5 border border-gray-200 dark:border-gray-700 rounded-xl space-y-1.5 bg-gray-50/50 dark:bg-gray-900/50">
                                @foreach($turnos as $t)
                                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                                        <input type="checkbox" wire:model.live="turnos_ids" value="{{ $t->id }}" class="w-3.5 h-3.5 rounded text-purpura-600">
                                        <span class="truncate">{{ $t->nome }}</span>
                                    </label>
                                @endforeach
                            </div>
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
                            <!-- Marcador numérico da linha do tempo -->
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

                                <!-- CONFIGURAÇÃO DE DATA OU ATRASO -->
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

        <!-- COLUNA LATERAL DIREITA (4 COLUNAS): SIMULADOR DE ALCANCE E REGRAS DE SAÍDA -->
        <div class="lg:col-span-4 space-y-6 sticky top-6">
            
            <!-- CARD DE ALCANCE EM TEMPO REAL -->
            <div class="bg-gradient-to-br from-purpura-600 to-indigo-700 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-purpura-100">Destinatários Elegíveis Agora</span>
                    <i class="ph-fill ph-users-three text-2xl text-purpura-200"></i>
                </div>
                <div class="text-4xl font-black mt-2">{{ $this->estimativaPublicoAtual }}</div>
                <p class="text-xs text-purpura-100 mt-1 leading-relaxed">
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
                    <button type="submit" class="w-full py-3 px-5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-base"></i>
                        <span>{{ $esteiraId ? 'Salvar Alterações da Esteira' : 'Ativar e Agendar Esteira' }}</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>