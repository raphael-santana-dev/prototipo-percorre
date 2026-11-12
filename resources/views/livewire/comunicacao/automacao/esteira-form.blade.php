<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <x-page-header 
        title="{{ $esteiraId ? 'Editar Esteira de Disparos' : 'Nova Esteira de Disparos' }}" 
        subtitle="Configure sequências de comunicação baseadas em condições, datas fixas ou intervalos"
        icon="ph ph-git-merge"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('esteiras.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-4">
        
        <!-- COLUNA PRINCIPAL -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- SEÇÃO 1: IDENTIFICAÇÃO E PÚBLICO-ALVO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">1. Identificação e Público-Alvo</h3>
                        <p class="text-xs text-gray-500">Defina a origem dos destinatários desta sequência.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="status" class="w-4 h-4 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300">
                        <span class="text-xs font-medium {{ $status ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">
                            {{ $status ? 'Esteira ativa' : 'Esteira pausada' }}
                        </span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da Esteira <span class="text-red-500">*</span></label>
                        <input wire:model="nome" type="text" placeholder="Ex: Régua de Cobrança de Documentos - Aprovados" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm focus:ring-1 focus:ring-purpura-500">
                        @error('nome') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fonte de Destinatários <span class="text-red-500">*</span></label>
                        <select wire:model.live="publico_alvo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm">
                            <option value="inscricoes">Inscrições (Processo Seletivo / Projeto)</option>
                            <option value="formulario_publico">Formulário Geral / Pré-Inscrição / Avaliação</option>
                            <option value="estudantes">Estudantes Matriculados (Portal do Aluno)</option>
                            <option value="usuarios">Usuários Internos do Sistema</option>
                            <option value="lista_manual">Lista Manual de E-mails</option>
                        </select>
                    </div>

                    @if($publico_alvo === 'inscricoes')
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ciclo / Processo Seletivo</label>
                            <select wire:model.live="ciclo_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm">
                                <option value="">Todos os Ciclos</option>
                                @foreach($ciclos as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($publico_alvo === 'formulario_publico')
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Formulário <span class="text-red-500">*</span></label>
                            <select wire:model.live="formulario_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm">
                                <option value="">Selecione o formulário...</option>
                                @foreach($formularios as $f)
                                    <option value="{{ $f->id }}">{{ $f->titulo }} ({{ strtoupper($f->tipo) }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SEÇÃO 2: CONDIÇÕES DE SEGMENTAÇÃO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">2. Condições de Segmentação</h3>
                    <p class="text-xs text-gray-500">Filtre quem deve entrar nesta esteira de comunicação.</p>
                </div>

                @if($publico_alvo === 'inscricoes')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status da Inscrição
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($statusInscricoes as $st)
                                @php $marcado = in_array((string) $st->id, $status_ids, true); @endphp
                                <label class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition {{ $marcado ? 'bg-gray-900 border-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50' }}">
                                    <input type="checkbox" wire:model.live="status_ids" value="{{ $st->id }}" class="sr-only">
                                    <span>{{ $st->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">Se nenhum status for selecionado, considera inscritos em qualquer status.</p>
                    </div>
                @endif

                @if(in_array($publico_alvo, ['inscricoes', 'estudantes']))
                    @php
                        $qtdUniCfg = count($unidades_ids);
                        $qtdCurCfg = count($cursos_ids);
                        $qtdTurCfg = count($turnos_ids);
                    @endphp
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 block">Restrição por Unidade, Curso e Turno</span>
                            @if($qtdUniCfg > 0 || $qtdCurCfg > 0 || $qtdTurCfg > 0)
                                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-600 dark:text-gray-300">
                                    <span>{{ $qtdUniCfg }} unidade(s) • {{ $qtdCurCfg }} curso(s) • {{ $qtdTurCfg }} turno(s)</span>
                                    <button type="button" wire:click="limparRestricaoComboAcademico" class="text-xs text-red-600 hover:underline">
                                        Limpar
                                    </button>
                                </div>
                            @else
                                <span class="text-xs text-gray-500">Aplicável a todas as unidades, cursos e turnos.</span>
                            @endif
                        </div>

                        <button type="button" wire:click="abrirModalConfigCombo" class="btn btn--secondary btn--medium shrink-0">
                            Filtrar Unidades / Cursos / Turnos
                        </button>
                    </div>
                @endif

                @if(in_array($publico_alvo, ['inscricoes', 'formulario_publico']))
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Progresso do Preenchimento</label>
                        <select wire:model.live="progresso_preenchimento" class="w-full md:w-80 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-2 px-3">
                            <option value="todos">Todos (Iniciados e Finalizados)</option>
                            <option value="incompleto">Apenas Incompletos (Em Andamento)</option>
                            <option value="finalizado">Apenas Concluídos (Finalizados)</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Condições por Resposta do Formulário</span>
                            <button type="button" wire:click="adicionarRegraDinamica" class="text-xs font-medium text-purpura-600 hover:underline">
                                + Adicionar condição
                            </button>
                        </div>

                        @foreach($regras_dinamicas as $rIdx => $regra)
                            <div wire:key="regra-din-{{ $rIdx }}" class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-5">
                                    <select wire:model.live="regras_dinamicas.{{ $rIdx }}.campo" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-1.5">
                                        <option value="">Campo...</option>
                                        @foreach($this->camposDinamicosDisponiveis as $cDin)
                                            <option value="{{ $cDin->name }}">{{ strip_tags($cDin->label) }} ({{ $cDin->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <select wire:model.live="regras_dinamicas.{{ $rIdx }}.operador" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-1.5">
                                        <option value="=">Igual a (=)</option>
                                        <option value="!=">Diferente (!=)</option>
                                        <option value="contem">Contém</option>
                                        <option value="preenchido">Preenchido</option>
                                        <option value="vazio">Em branco</option>
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    @if(!in_array($regra['operador'] ?? '=', ['preenchido', 'vazio']))
                                        <input type="text" wire:model.live.debounce.400ms="regras_dinamicas.{{ $rIdx }}.valor" placeholder="Valor..." class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-1.5">
                                    @endif
                                </div>
                                <div class="col-span-1 text-right">
                                    <button type="button" wire:click="removerRegraDinamica({{ $rIdx }})" class="text-gray-400 hover:text-red-600 p-1">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($publico_alvo === 'lista_manual')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Lista de E-mails (1 por linha ou separados por vírgula)</label>
                        <textarea wire:model.live.debounce.500ms="emails_manuais_texto" rows="4" placeholder="candidato1@email.com&#10;candidato2@email.com" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs font-mono p-3"></textarea>
                    </div>
                @endif
            </div>

            <!-- SEÇÃO 3: ETAPAS DE DISPARO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">3. Etapas de Disparo</h3>
                        <p class="text-xs text-gray-500">Defina a ordem, canal, template e agendamento de cada etapa.</p>
                    </div>

                    <button type="button" wire:click="adicionarEtapa" class="btn btn--secondary btn--small">
                        <i class="ph ph-plus"></i> Adicionar Etapa
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($etapas as $index => $etapa)
                        <div wire:key="esteira-etapa-{{ $index }}" class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/40 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-mono font-semibold">
                                        #{{ $index + 1 }}
                                    </span>
                                    <input type="text" wire:model="etapas.{{ $index }}.nome" placeholder="Título da etapa" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs font-medium px-3 py-1.5">
                                </div>

                                @if(count($etapas) > 1)
                                    <button type="button" wire:click="removerEtapa({{ $index }})" class="text-gray-400 hover:text-red-600 p-1.5 rounded transition" title="Remover Etapa">
                                        <i class="ph ph-trash text-sm"></i>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                <div class="md:col-span-3">
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Canal *</label>
                                    <select wire:model.live="etapas.{{ $index }}.canal_disparo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                        <option value="email">Apenas E-mail</option>
                                        <option value="whatsapp">Apenas WhatsApp</option>
                                        <option value="ambos">E-mail + WhatsApp</option>
                                    </select>
                                </div>

                                <div class="md:col-span-5">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[11px] font-medium text-gray-500">Template *</label>
                                        @if(!empty($etapa['template_id']))
                                            <button type="button" wire:click="previewTemplate({{ (int) $etapa['template_id'] }})" class="text-[11px] text-purpura-600 hover:underline">
                                                Ver template
                                            </button>
                                        @endif
                                    </div>
                                    @php
                                        $canalEtapaAtual = $etapa['canal_disparo'] ?? 'email';
                                        $templatesEtapa = $templates->filter(function($t) use ($canalEtapaAtual) {
                                            $ct = $t->canal ?? 'email';
                                            return match($canalEtapaAtual) {
                                                'email'    => in_array($ct, ['email', 'ambos'], true),
                                                'whatsapp' => in_array($ct, ['whatsapp', 'ambos'], true),
                                                'ambos'    => $ct === 'ambos',
                                                default    => true,
                                            };
                                        });
                                    @endphp
                                    <select wire:model.live="etapas.{{ $index }}.template_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                        <option value="">Selecione o template...</option>
                                        @foreach($templatesEtapa as $tpl)
                                            <option value="{{ $tpl->id }}">[{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }}</option>
                                        @endforeach
                                    </select>
                                    @error("etapas.{$index}.template_id") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-4">
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Agendamento</label>
                                    <select wire:model.live="etapas.{{ $index }}.tipo_agendamento" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                        <option value="data_fixa">Data e hora fixa</option>
                                        @if($index > 0)
                                            <option value="apos_etapa_anterior">Após Etapa #{{ $index }}</option>
                                        @endif
                                        <option value="imediato">Imediato</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-gray-200/70 dark:border-gray-700">
                                @if(($etapa['tipo_agendamento'] ?? 'data_fixa') === 'data_fixa')
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Data e Hora do Disparo</label>
                                        <input type="datetime-local" wire:model="etapas.{{ $index }}.data_disparo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                        @error("etapas.{$index}.data_disparo") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                @elseif(($etapa['tipo_agendamento'] ?? '') === 'apos_etapa_anterior')
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Intervalo após a Etapa #{{ $index }}</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="1" max="365" wire:model="etapas.{{ $index }}.atraso_valor" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs text-center py-1.5">
                                            <select wire:model="etapas.{{ $index }}.atraso_unidade" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                                <option value="dias">Dia(s)</option>
                                                <option value="horas">Hora(s)</option>
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                @if($index > 0)
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Filtro de Engajamento da Etapa #{{ $index }}</label>
                                        <select wire:model="etapas.{{ $index }}.filtro_engajamento_anterior" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5">
                                            <option value="todos">Todos que receberam a Etapa #{{ $index }}</option>
                                            <option value="nao_abriu">Apenas quem NÃO ABRIU a Etapa #{{ $index }}</option>
                                            <option value="nao_clicou">Apenas quem NÃO CLICOU na Etapa #{{ $index }}</option>
                                            <option value="abriu">Apenas quem ABRIU a Etapa #{{ $index }}</option>
                                            <option value="clicou">Apenas quem CLICOU na Etapa #{{ $index }}</option>
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- COLUNA LATERAL DIREITA -->
        <div class="lg:col-span-4 space-y-6 sticky top-6">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="pb-4 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">
                        Destinatários Elegíveis Agora
                    </span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $this->estimativaPublicoAtual }}
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Pessoas que atendem aos filtros selecionados neste momento.
                    </p>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                        Regras de Saída e Cadência
                    </h4>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model.live="revalidar_condicoes_no_envio" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                        <div class="text-xs">
                            <span class="font-medium text-gray-900 dark:text-white block">Revalidar condições antes de cada etapa</span>
                            <span class="text-[11px] text-gray-500">Interrompe o envio se o inscrito mudar de status.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model.live="sair_ao_concluir_formulario" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                        <div class="text-xs">
                            <span class="font-medium text-gray-900 dark:text-white block">Sair ao concluir formulário</span>
                            <span class="text-[11px] text-gray-500">Remove da fila quando finalizar a inscrição.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="apenas_horario_comercial" class="mt-0.5 rounded text-purpura-600 border-gray-300">
                        <div class="text-xs">
                            <span class="font-medium text-gray-900 dark:text-white block">Apenas horário comercial (08h às 20h)</span>
                        </div>
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="w-full py-2.5 px-4 bg-purpura-600 hover:bg-purpura-700 text-white font-semibold text-xs rounded-lg transition">
                        {{ $esteiraId ? 'Salvar Alterações da Esteira' : 'Salvar e Agendar Esteira' }}
                    </button>
                </div>
            </div>
        </div>

    </form>

    {{-- MODAL DE UNIDADES / CURSOS / TURNOS --}}
    @if($modalConfigComboAberto)
        <div class="fixed inset-0 z-[260] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Filtrar Unidades, Cursos e Turnos
                        </h3>
                        <p class="text-xs text-gray-500">
                            Selecione a Unidade, os Cursos daquela Unidade e os Turnos correspondentes.
                        </p>
                    </div>
                    <button type="button" wire:click="$set('modalConfigComboAberto', false)" class="text-gray-400 hover:text-gray-700 p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <div class="flex flex-col md:flex-row h-[400px] bg-white dark:bg-gray-800 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                    {{-- COLUNA 1: UNIDADES --}}
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase text-gray-500">1. Unidades</span>
                            <span class="text-[11px] text-gray-500">{{ count($comboUnidadesSelecionadas) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @foreach($unidadesDb as $u)
                                <div wire:key="esteira-uni-{{ $u->id }}"
                                     wire:click="setComboActiveUnidade({{ $u->id }})"
                                     class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $comboActiveUnidadeId == $u->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                        <input type="checkbox" wire:model.live="comboUnidadesSelecionadas" value="{{ $u->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600">
                                        <span class="text-xs text-gray-800 dark:text-gray-200">{{ $u->nome }}</span>
                                    </label>
                                    <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- COLUNA 2: CURSOS DA UNIDADE --}}
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase text-gray-500">2. Cursos da Unidade</span>
                            <span class="text-[11px] text-gray-500">{{ count($comboCursosSelecionados) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @if($comboActiveUnidadeId)
                                @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $comboActiveUnidadeId)) as $c)
                                    @php $chaveCursoUnidade = $comboActiveUnidadeId . '-' . $c->id; @endphp
                                    <div wire:key="esteira-cur-{{ $chaveCursoUnidade }}"
                                         wire:click="setComboActiveCurso({{ $c->id }})"
                                         class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $comboActiveCursoId == $c->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                            <input type="checkbox" wire:model.live="comboCursosSelecionados" value="{{ $chaveCursoUnidade }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600">
                                            <span class="text-xs text-gray-800 dark:text-gray-200">{{ $c->nome }}</span>
                                        </label>
                                        <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                    </div>
                                @endforeach
                            @else
                                <div class="h-full flex items-center justify-center text-gray-400 text-xs p-4 text-center">
                                    Selecione uma Unidade à esquerda
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- COLUNA 3: TURNOS --}}
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase text-gray-500">3. Turnos</span>
                            <span class="text-[11px] text-gray-500">{{ count($comboTurnosSelecionados) }} sel.</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar space-y-1">
                            @if($comboActiveUnidadeId && $comboActiveCursoId)
                                @php
                                    $cursoComboObj = $cursosDb->firstWhere('id', $comboActiveCursoId);
                                    $turnosComboList = $cursoComboObj
                                        ? (method_exists($cursoComboObj, 'turnosNaUnidade') ? $cursoComboObj->turnosNaUnidade((int) $comboActiveUnidadeId) : $cursoComboObj->turnosVinculados)
                                        : collect();
                                @endphp
                                @foreach($turnosComboList as $t)
                                    @php $chaveTurnoCompleta = $comboActiveUnidadeId . '-' . $comboActiveCursoId . '-' . $t->id; @endphp
                                    <div wire:key="esteira-tur-{{ $chaveTurnoCompleta }}" class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <label class="flex items-center gap-2 cursor-pointer flex-1">
                                            <input type="checkbox" wire:model.live="comboTurnosSelecionados" value="{{ $chaveTurnoCompleta }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600">
                                            <span class="text-xs text-gray-800 dark:text-gray-200">{{ $t->nome }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            @else
                                <div class="h-full flex items-center justify-center text-gray-400 text-xs p-4 text-center">
                                    Selecione um Curso ao centro
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Sem seleção = considera todas as opções.
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('modalConfigComboAberto', false)" class="btn btn--secondary btn--small">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmarConfigComboAcademico" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                            Aplicar Filtro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>