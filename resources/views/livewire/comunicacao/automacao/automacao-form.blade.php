<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <x-page-header 
        title="{{ $automacaoId ? 'Editar Regra de Automação' : 'Nova Regra de Automação' }}" 
        subtitle="Defina o gatilho, a ação e o modelo de mensagem que será disparado"
        icon="ph ph-lightning"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-4">
        
        <!-- COLUNA ESQUERDA: FORMULÁRIO UNIFICADO -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
            
            <!-- SEÇÃO 1: IDENTIFICAÇÃO E STATUS -->
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Identificação da Regra</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Nome interno para localizar esta automação na listagem.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="status" class="w-4 h-4 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 dark:border-gray-600">
                        <span class="text-xs font-medium {{ $status ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">
                            {{ $status ? 'Regra ativa' : 'Regra pausada' }}
                        </span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nome da Automação <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="nome" type="text" placeholder="Ex: Boas-vindas para Candidato Aprovado" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                    @error('nome') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- SEÇÃO 2: EVENTO GATILHO -->
            <div class="p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Evento Gatilho</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Momento em que o sistema executará esta regra.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Evento do Sistema <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="evento_gatilho" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Selecione um evento...</option>
                        @foreach($eventosDisponiveis as $chave => $label)
                            <option value="{{ $chave }}">{{ $label }} ({{ $chave }})</option>
                        @endforeach
                    </select>
                    @error('evento_gatilho') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($this->exigeParametroDias)
                    <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 space-y-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            @if($evento_gatilho === 'inscricao.fim_proximo')
                                Quantos dias antes do encerramento da inscrição disparar? <span class="text-red-500">*</span>
                            @else
                                Disparar após quantos dias sem finalizar o formulário? <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <div class="flex items-center gap-2.5">
                            <input type="number" min="1" max="365" wire:model="parametro_dias" class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm text-center py-1.5">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $evento_gatilho === 'inscricao.fim_proximo' ? 'dia(s) antes do fim do ciclo' : 'dia(s) de inatividade (com todas as fases liberadas)' }}
                            </span>
                        </div>
                        @error('parametro_dias') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <!-- SEÇÃO 3: AÇÃO, CANAL E TEMPLATE -->
            <div class="p-6 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Ação e Mensagem</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Defina a operação, o canal de envio e o template utilizado.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tipo de Ação -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Ação a executar <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition {{ $tipo_acao === 'enviar_email' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50/50' }}">
                                <input type="radio" wire:model.live="tipo_acao" value="enviar_email" class="mt-0.5 text-gray-900 focus:ring-gray-900">
                                <div>
                                    <span class="text-xs font-semibold text-gray-900 dark:text-white block">Apenas enviar comunicação</span>
                                    <span class="text-[11px] text-gray-500 block">Dispara o template para o destinatário.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition {{ $tipo_acao === 'criar_aluno_enviar_email' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50/50' }}">
                                <input type="radio" wire:model.live="tipo_acao" value="criar_aluno_enviar_email" class="mt-0.5 text-gray-900 focus:ring-gray-900">
                                <div>
                                    <span class="text-xs font-semibold text-gray-900 dark:text-white block">Criar acesso de estudante + enviar</span>
                                    <span class="text-[11px] text-gray-500 block">Gera conta no portal com senha provisória.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Canal de Disparo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Canal de envio <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <button type="button" wire:click="$set('canal_disparo', 'email')" class="w-full p-2.5 rounded-lg border text-left transition flex items-center justify-between {{ $canal_disparo === 'email' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50/50' }}">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="ph ph-envelope-simple text-gray-500"></i> Apenas E-mail
                                </span>
                                @if($canal_disparo === 'email') <i class="ph-bold ph-check text-xs text-gray-900 dark:text-white"></i> @endif
                            </button>

                            <button type="button" wire:click="$set('canal_disparo', 'whatsapp')" class="w-full p-2.5 rounded-lg border text-left transition flex items-center justify-between {{ $canal_disparo === 'whatsapp' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50/50' }}">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="ph ph-whatsapp-logo text-gray-500"></i> Apenas WhatsApp (Suri)
                                </span>
                                @if($canal_disparo === 'whatsapp') <i class="ph-bold ph-check text-xs text-gray-900 dark:text-white"></i> @endif
                            </button>

                            <button type="button" wire:click="$set('canal_disparo', 'ambos')" class="w-full p-2.5 rounded-lg border text-left transition flex items-center justify-between {{ $canal_disparo === 'ambos' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50/50' }}">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="ph ph-intersect text-gray-500"></i> E-mail + WhatsApp
                                </span>
                                @if($canal_disparo === 'ambos') <i class="ph-bold ph-check text-xs text-gray-900 dark:text-white"></i> @endif
                            </button>
                        </div>
                    </div>
                </div>

                @if($tipo_acao === 'criar_aluno_enviar_email')
                    <div class="p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                        Variáveis disponíveis para credenciais: <code class="font-mono text-gray-900 dark:text-white">@{{senha_provisoria}}</code> e <code class="font-mono text-gray-900 dark:text-white">@{{link_login}}</code>.
                    </div>
                @endif

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Template ({{ count($templates) }} compatível/is) <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            @if($this->templateSelecionado)
                                <button type="button" wire:click="abrirQuickViewTemplate" class="text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white underline">
                                    Expandir preview
                                </button>
                            @endif
                            <a href="{{ route('templates.create') }}" target="_blank" class="text-xs font-medium text-purpura-600 hover:underline">
                                + Novo template
                            </a>
                        </div>
                    </div>
                    <select wire:model.live="template_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Selecione o template...</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                [{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }} {{ $tpl->suri_template_id ? '• Suri: ' . $tpl->suri_template_id : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if(count($variaveisTemplateMap) > 0)
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 space-y-3">
                        <div class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                            Mapeamento de Variáveis do Template ({{ count($variaveisTemplateMap) }})
                        </div>
                        <div class="space-y-2">
                            @foreach($variaveisTemplateMap as $varNome => $colSelecionada)
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                    <div class="sm:col-span-4">
                                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2 py-1 rounded border border-gray-200 dark:border-gray-700 inline-block">
                                            #{{ $loop->iteration }} &#123;&#123;{{ $varNome }}&#125;&#125;
                                        </span>
                                    </div>
                                    <div class="sm:col-span-8">
                                        <select wire:model="variaveisTemplateMap.{{ $varNome }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1.5 px-2.5">
                                            @foreach($colunasBanco as $grupoCol => $listaCols)
                                                <optgroup label="{{ $grupoCol }}">
                                                    @foreach($listaCols as $colKey => $colLabel)
                                                        <option value="{{ $colKey }}">{{ $colLabel }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium">Cancelar</a>
                    @if(feature('automacao.editar') || feature('automacao.criar'))
                        <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                            {{ $automacaoId ? 'Salvar Alterações' : 'Salvar Automação' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA: PREVIEW DO TEMPLATE -->
        <div class="lg:col-span-5 xl:col-span-4 sticky top-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Pré-visualização
                    </span>
                    @if($this->templateSelecionado)
                        <a href="{{ route('templates.edit', $this->templateSelecionado->id) }}" target="_blank" class="text-xs text-purpura-600 hover:underline">
                            Editar template ↗
                        </a>
                    @endif
                </div>

                @if($this->templateSelecionado)
                    @php
                        $tpl = $this->templateSelecionado;
                        $canalPreview = $tpl->canal ?? 'email';
                        $botoesPreview = is_array($tpl->botoes) ? $tpl->botoes : [];
                    @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="text-[11px] text-gray-400 block">Assunto / Título</span>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">{{ $tpl->assunto ?: $tpl->nome }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shrink-0">
                                {{ $canalPreview }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 prose prose-sm max-w-none text-xs text-gray-700 dark:text-gray-200 max-h-[380px] overflow-y-auto custom-scrollbar">
                            {!! $tpl->corpo !!}
                        </div>

                        @if(!empty($botoesPreview))
                            <div class="space-y-1.5 pt-1">
                                <span class="text-[11px] text-gray-400 block">Botões configurados:</span>
                                @foreach($botoesPreview as $b)
                                    @continue(empty($b['texto']))
                                    <div class="px-3 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between text-xs">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $b['texto'] }}</span>
                                        <span class="text-[10px] font-mono text-gray-400 truncate max-w-[150px]">{{ $b['url'] ?? 'Resposta rápida' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nenhum template selecionado</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Selecione um modelo ao lado para visualizar o conteúdo.</p>
                    </div>
                @endif
            </div>
        </div>

    </form>
</div>