<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $automacaoId ? 'Editar Regra de Automação' : 'Nova Regra de Automação' }}" 
        subtitle="Configure o evento gatilho, a ação do sistema e o template que será disparado"
        icon="ph ph-lightning"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-arrow-left"></i> Voltar para Lista
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-4">
        
        <!-- COLUNA ESQUERDA (8 COLUNAS): CONFIGURAÇÃO DO FLUXO DA REGRA -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            
            <!-- PASSO 1: IDENTIFICAÇÃO E STATUS -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-purpura-50 dark:bg-purpura-900/40 text-purpura-600 dark:text-purpura-400 font-black text-xs flex items-center justify-center border border-purpura-200 dark:border-purpura-800">1</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Identificação da Regra</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Defina um apelido claro para identificar este fluxo na listagem.</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer bg-gray-50 dark:bg-gray-900/60 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700">
                        <input type="checkbox" wire:model.live="status" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="text-xs font-extrabold uppercase {{ $status ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                            {{ $status ? 'Regra Ativa' : 'Regra Pausada' }}
                        </span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Nome / Apelido da Automação <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="nome" type="text" placeholder="Ex: Boas-vindas e Acesso para Candidato Aprovado" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-semibold focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                    @error('nome') <span class="text-xs text-red-500 font-bold block mt-1.5">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- PASSO 2: QUANDO ACONTECER (GATILHO) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 font-black text-xs flex items-center justify-center border border-blue-200 dark:border-blue-800">2</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Evento Gatilho (Quando ocorrer...)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Escolha o momento exato em que o sistema deve disparar esta automação.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Selecione o Evento do Sistema <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="evento_gatilho" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-semibold focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                        <option value="">-- Escolha quando a regra será acionada --</option>
                        @foreach($eventosDisponiveis as $chave => $label)
                            <option value="{{ $chave }}">{{ $label }} ({{ $chave }})</option>
                        @endforeach
                    </select>
                    @error('evento_gatilho') <span class="text-xs text-red-500 font-bold block mt-1.5">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- PASSO 3: AÇÃO EXECUTADA E TEMPLATE -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 font-black text-xs flex items-center justify-center border border-emerald-200 dark:border-emerald-800">3</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Ação e Template de Mensagem</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Defina o que o sistema fará e qual modelo de comunicação será enviado.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        O que o sistema deve executar? <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition {{ $tipo_acao === 'enviar_email' ? 'border-purpura-500 bg-purpura-50/40 dark:bg-purpura-900/20 ring-1 ring-purpura-500' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                            <input type="radio" wire:model.live="tipo_acao" value="enviar_email" class="mt-0.5 text-purpura-600 focus:ring-purpura-500">
                            <div>
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white block">Apenas Disparar Comunicação</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight block mt-0.5">Envia o template selecionado para o candidato, aluno ou equipe.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition {{ $tipo_acao === 'criar_aluno_enviar_email' ? 'border-purpura-500 bg-purpura-50/40 dark:bg-purpura-900/20 ring-1 ring-purpura-500' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                            <input type="radio" wire:model.live="tipo_acao" value="criar_aluno_enviar_email" class="mt-0.5 text-purpura-600 focus:ring-purpura-500">
                            <div>
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white block">Criar Acesso (Student) + Disparar</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight block mt-0.5">Gera conta no portal do aluno com senha provisória e envia o template.</span>
                            </div>
                        </label>
                    </div>

                    @if($tipo_acao === 'criar_aluno_enviar_email')
                        <div class="mt-3 p-3 bg-indigo-50/70 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs text-indigo-800 dark:text-indigo-300 flex items-center gap-2">
                            <i class="ph-fill ph-info text-base text-indigo-600 shrink-0"></i>
                            <span>Dica: Utilize as variáveis <code class="font-mono font-bold bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded">@{{senha_provisoria}}</code> e <code class="font-mono font-bold bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded">@{{link_login}}</code> no seu template.</span>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Template de Comunicação Alvo <span class="text-red-500">*</span>
                        </label>
                        <a href="{{ route('templates.create') }}" target="_blank" class="text-xs font-bold text-purpura-600 hover:underline flex items-center gap-1">
                            <i class="ph-bold ph-plus"></i> Criar novo template
                        </a>
                    </div>
                    <select wire:model.live="template_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-semibold focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                        <option value="">-- Selecione o Template que será enviado --</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                [{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1.5">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('automacoes.index') }}" class="btn btn--secondary btn--medium">Cancelar</a>
                    @if(feature('automacao.editar') || feature('automacao.criar'))
                        <button type="submit" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm flex items-center gap-2">
                            <i class="ph-bold ph-floppy-disk text-base"></i>
                            <span>{{ $automacaoId ? 'Salvar Alterações' : 'Ativar e Salvar Automação' }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA (4 COLUNAS): LIVE PREVIEW DO TEMPLATE SELECIONADO -->
        <div class="lg:col-span-5 xl:col-span-4 sticky top-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/50 flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                        <i class="ph-fill ph-eye text-purpura-600 text-base"></i> Pré-visualização do Disparo
                    </span>
                    @if($this->templateSelecionado)
                        <a href="{{ route('templates.edit', $this->templateSelecionado->id) }}" target="_blank" class="text-[11px] font-bold text-purpura-600 hover:underline flex items-center gap-1">
                            Editar Template <i class="ph-bold ph-arrow-square-out"></i>
                        </a>
                    @endif
                </div>

                @if($this->templateSelecionado)
                    @php
                        $tpl = $this->templateSelecionado;
                        $canalPreview = $tpl->canal ?? 'email';
                        $botoesPreview = is_array($tpl->botoes) ? $tpl->botoes : [];
                    @endphp
                    <div class="p-5 space-y-4">
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase block">Assunto / Título</span>
                                <p class="text-xs font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $tpl->assunto ?: $tpl->nome }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded bg-purpura-50 text-purpura-700 border border-purpura-200 shrink-0">
                                {{ $canalPreview }}
                            </span>
                        </div>

                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 prose prose-sm max-w-none text-xs text-gray-700 dark:text-gray-200 max-h-[380px] overflow-y-auto custom-scrollbar">
                            {!! $tpl->corpo !!}
                        </div>

                        @if(!empty($botoesPreview))
                            <div class="space-y-1.5 pt-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Botões / Opções Interativas:</span>
                                @foreach($botoesPreview as $b)
                                    @continue(empty($b['texto']))
                                    <div class="px-3 py-2 rounded-lg bg-purpura-50/60 dark:bg-purpura-900/20 border border-purpura-200 dark:border-purpura-800 flex items-center justify-between text-xs">
                                        <span class="font-bold text-purpura-700 dark:text-purpura-300">{{ $b['texto'] }}</span>
                                        <span class="text-[10px] font-mono text-gray-500 truncate max-w-[150px]">{{ $b['url'] ?? 'Opção rápida' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-10 text-center text-gray-400">
                        <i class="ph-duotone ph-envelope-open text-4xl mb-2 block mx-auto"></i>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Nenhum template selecionado</p>
                        <p class="text-[11px] text-gray-400 mt-1">Escolha um template no Passo 3 ao lado para visualizar o conteúdo que será enviado.</p>
                    </div>
                @endif
            </div>
        </div>

    </form>
</div>