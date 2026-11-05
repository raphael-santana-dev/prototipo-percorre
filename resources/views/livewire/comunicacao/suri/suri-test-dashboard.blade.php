<div class="p-6 max-w-7xl mx-auto font-sans space-y-6">

    <!-- CABEÇALHO E STATUS DO .ENV -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 shrink-0">
                <i class="ph-fill ph-whatsapp-logo text-3xl"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">Central de Testes & Homologação Suri (WhatsApp)</h1>
                    @if($configSuri['tem_token'] && $configSuri['base_url'] !== 'Não configurada')
                        <span class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Credenciais .ENV Detectadas
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase rounded-full bg-red-50 text-red-700 border border-red-200">
                            Configuração .ENV Pendente
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Simule chamadas POST de importação de contatos, disparos de templates (simples, com botões, com variáveis) e mensagens livres.
                </p>
                <div class="flex flex-wrap items-center gap-4 mt-2.5 text-[11px] font-mono text-gray-600 dark:text-gray-300">
                    <span><b>SURI_API_URL:</b> {{ $configSuri['base_url'] }}</span>
                    <span><b>SURI_CHANNEL_ID:</b> {{ $configSuri['channel_id'] }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" wire:click="abrirModal('guia_suri')" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <i class="ph-bold ph-question text-base"></i> Onde buscar os IDs na Suri?
            </button>
            <a href="{{ route('templates.index') }}" class="px-3.5 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold hover:bg-gray-50 transition flex items-center gap-1.5">
                <i class="ph-bold ph-arrow-left"></i> Voltar aos Templates
            </a>
        </div>
    </div>

    <!-- ATALHOS DOS 5 ENDPOINTS DE TESTE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: IMPORTAR CONTATO -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center">
                        <i class="ph-bold ph-user-plus text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/contacts</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">1. Importar Contato</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Registra o contato na Suri com nome, DDI+DDD+Telefone e canal, retornando o <code class="font-mono text-purpura-600">userId</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('contato')" class="w-full py-2 px-3 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-play"></i> Testar Importação
            </button>
        </div>

        <!-- CARD 2: TEMPLATE SIMPLES -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center">
                        <i class="ph-bold ph-chat-text text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/messages/send</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">2. Template Simples</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia um template sem variáveis usando o <code class="font-mono text-purpura-600">userId</code> do contato e redireciona para um <code class="font-mono">flowId</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_simples')" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-paper-plane-tilt"></i> Disparar Simples
            </button>
        </div>

        <!-- CARD 3: TEMPLATE COM BOTÕES -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-purpura-50 text-purpura-600 border border-purpura-200 flex items-center justify-center">
                        <i class="ph-bold ph-hand-tap text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/messages/send</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">3. Template c/ Botões</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia o objeto <code class="font-mono text-purpura-600">user</code> completo + <code class="font-mono">templateId</code> com botões interativos.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_botoes')" class="w-full py-2 px-3 bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-paper-plane-tilt"></i> Disparar c/ Botões
            </button>
        </div>

        <!-- CARD 4: TEMPLATE COM VARIÁVEIS -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center">
                        <i class="ph-bold ph-brackets-curly text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/messages/send</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">4. Template c/ Variáveis</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia o objeto <code class="font-mono">user</code>, o <code class="font-mono">templateId</code>, a lista <code class="font-mono text-purpura-600">bodyParameters</code> e a ação de fluxo.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_variaveis')" class="w-full py-2 px-3 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-paper-plane-tilt"></i> Disparar c/ Variáveis
            </button>
        </div>

        <!-- CARD 5: MENSAGEM SIMPLES SEM TEMPLATE -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center">
                        <i class="ph-bold ph-chat-circle-dots text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/messages/send</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">5. Mensagem Livre</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia texto livre para janelas de 24h abertas usando <code class="font-mono text-purpura-600">userId</code> e <code class="font-mono">context.msgId</code> opcional.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('msg_simples')" class="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-paper-plane-tilt"></i> Enviar Texto Livre
            </button>
        </div>
    </div>

    <!-- PAINEL INFERIOR: TEMPLATES WHATSAPP LOCAIS + CONSOLE DE INSPEÇÃO JSON -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LISTA RÁPIDA DE TEMPLATES WHATSAPP DO SISTEMA -->
        <div class="lg:col-span-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/60 dark:bg-gray-900/40">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="ph-fill ph-layout text-purpura-600 text-base"></i> Templates WhatsApp no Sistema
                </h3>
                <span class="text-[11px] font-bold text-gray-400">{{ count($templatesLocais) }} template(s)</span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[420px] overflow-y-auto custom-scrollbar">
                @forelse($templatesLocais as $tpl)
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-gray-50/70 transition">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $tpl->nome }}</p>
                            @if($tpl->suri_template_id)
                                <p class="text-[11px] font-mono text-emerald-600 mt-0.5 truncate">
                                    ID Suri: <b>{{ $tpl->suri_template_id }}</b>
                                </p>
                            @else
                                <p class="text-[11px] text-amber-600 mt-0.5">Ainda não sincronizado com a Suri</p>
                            @endif
                        </div>

                        <div class="shrink-0 flex items-center gap-1.5">
                            @if($tpl->suri_template_id)
                                <button type="button"
                                        wire:click="$set('templateId', '{{ $tpl->suri_template_id }}')"
                                        class="px-2.5 py-1 text-[11px] font-bold bg-purpura-50 text-purpura-700 border border-purpura-200 rounded-lg hover:bg-purpura-100 transition">
                                    Usar ID
                                </button>
                            @else
                                <button type="button"
                                        wire:click="sincronizarTemplateLocal({{ $tpl->id }})"
                                        class="px-2.5 py-1 text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition flex items-center gap-1">
                                    <i class="ph-bold ph-arrows-clockwise"></i> Sincronizar
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-gray-400">
                        Nenhum template com canal WhatsApp cadastrado.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- CONSOLE DE PAYLOAD E RESPOSTA DA API SURI -->
        <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-900 text-white">
                <div class="flex items-center gap-2">
                    <i class="ph-bold ph-terminal-window text-emerald-400 text-lg"></i>
                    <span class="text-xs font-extrabold uppercase tracking-wider">Console de Inspeção HTTP (Payload & Resposta Suri)</span>
                </div>
                @if($ultimoResultado)
                    <span class="px-2.5 py-0.5 text-[10px] font-mono font-bold rounded {{ $ultimoResultado['sucesso'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }}">
                        HTTP {{ $ultimoResultado['http_status'] }} • {{ $ultimoResultado['horario'] }}
                    </span>
                @endif
            </div>

            @if($ultimoResultado)
                <div class="p-5 space-y-4 bg-gray-950 text-gray-100 font-mono text-xs max-h-[420px] overflow-y-auto custom-scrollbar">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-purpura-400 block mb-1">Endpoint Acionado ({{ $ultimoResultado['acao'] }}):</span>
                        <code class="px-2.5 py-1.5 bg-gray-900 border border-gray-800 rounded block text-emerald-300 break-all">POST {{ $ultimoResultado['url'] }}</code>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-blue-400 block mb-1">Body Enviado (JSON):</span>
                            <pre class="p-3 bg-gray-900 border border-gray-800 rounded-xl overflow-x-auto text-[11px] text-blue-200">{{ json_encode($ultimoResultado['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase {{ $ultimoResultado['sucesso'] ? 'text-emerald-400' : 'text-red-400' }} block mb-1">Resposta da Suri:</span>
                            <pre class="p-3 bg-gray-900 border border-gray-800 rounded-xl overflow-x-auto text-[11px] {{ $ultimoResultado['sucesso'] ? 'text-emerald-200' : 'text-red-200' }}">{{ json_encode($ultimoResultado['resposta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-12 text-center text-gray-400 bg-gray-950 font-mono text-xs">
                    <i class="ph-duotone ph-Brackets-curly text-4xl text-gray-600 mb-2 block mx-auto"></i>
                    Clique em qualquer um dos 5 cartões acima e execute um teste para inspecionar o JSON enviado e o retorno da API da Suri.
                </div>
            @endif
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAIS DE TESTE E GUIA DA SURI                                        -->
    <!-- ===================================================================== -->
    @if($modalAtivo)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/65 backdrop-blur-sm px-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                
                <!-- Topo do Modal -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($modalAtivo === 'contato')
                            <i class="ph-fill ph-user-plus text-blue-600 text-xl"></i> 1. Importar Contato para a Suri
                        @elseif($modalAtivo === 'tpl_simples')
                            <i class="ph-fill ph-chat-text text-emerald-600 text-xl"></i> 2. Enviar Mensagem com Template Simples
                        @elseif($modalAtivo === 'tpl_botoes')
                            <i class="ph-fill ph-hand-tap text-purpura-600 text-xl"></i> 3. Enviar Mensagem com Template com Botões
                        @elseif($modalAtivo === 'tpl_variaveis')
                            <i class="ph-fill ph-brackets-curly text-amber-600 text-xl"></i> 4. Enviar Mensagem com Template com Variáveis
                        @elseif($modalAtivo === 'msg_simples')
                            <i class="ph-fill ph-chat-circle-dots text-indigo-600 text-xl"></i> 5. Enviar Mensagem Simples (Sem Template)
                        @else
                            <i class="ph-fill ph-book-open text-indigo-600 text-xl"></i> Guia: Onde encontrar cada ID no Portal da Suri
                        @endif
                    </h3>
                    <button type="button" wire:click="fecharModal" class="text-gray-400 hover:text-red-500 p-1">
                        <i class="ph-bold ph-x text-xl"></i>
                    </button>
                </div>

                <!-- Corpo do Modal -->
                <div class="p-6 space-y-4 max-h-[78vh] overflow-y-auto custom-scrollbar">

                    @if(in_array($modalAtivo, ['contato', 'tpl_botoes', 'tpl_variaveis']))
                        <!-- Autopreenchimento opcional com candidato do banco -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Preencher rápido com dados de um Candidato (Opcional)</label>
                            <select wire:model.live="inscricaoTesteId" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="">-- Digitar manualmente abaixo --</option>
                                @foreach($inscricoesRecentes as $insc)
                                    <option value="{{ $insc->id }}">#{{ $insc->id }} - {{ $insc->nome }} ({{ $insc->celular }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- MODAL 1: IMPORTAR CONTATO --}}
                    @if($modalAtivo === 'contato')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nome do Contato (name) *</label>
                                <input type="text" wire:model="name" placeholder="Ex: RonYTEdSON" class="w-full rounded-lg border-gray-300 text-xs font-semibold">
                                @error('name') <span class="text-[11px] text-red-500 font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Telefone c/ DDI e DDD (phone) *</label>
                                <input type="text" wire:model="phone" placeholder="Ex: 5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('phone') <span class="text-[11px] text-red-500 font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Channel ID (channelId) *</label>
                                <input type="text" wire:model="channelId" placeholder="Ex: wp332148629991857" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Gender</label>
                                    <select wire:model="gender" class="w-full rounded-lg border-gray-300 text-xs">
                                        <option value="0">0 (Não inf.)</option>
                                        <option value="1">1 (Masculino)</option>
                                        <option value="2">2 (Feminino)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Channel Type</label>
                                    <input type="number" wire:model="channelType" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                            <button type="button" wire:click="dispararImportarContato" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl">
                                Executar POST /api/contacts
                            </button>
                        </div>

                    {{-- MODAL 2: TEMPLATE SIMPLES --}}
                    @elseif($modalAtivo === 'tpl_simples')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">ID do Contato na Suri (userId) *</label>
                                <input type="text" wire:model="userId" placeholder="Ex: wp332148629991857-5585987755801" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('userId') <span class="text-[11px] text-red-500 font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">ID do Template na Suri (templateId) *</label>
                                <input type="text" wire:model="templateId" placeholder="Selecione ou digite o templateId..." class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('templateId') <span class="text-[11px] text-red-500 font-bold">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ou escolha um Template Sincronizado do Sistema</label>
                                <select wire:model.live="templateId" class="w-full rounded-lg border-gray-300 text-xs">
                                    <option value="">-- Selecionar template sincronizado --</option>
                                    @foreach($templatesLocais->whereNotNull('suri_template_id') as $t)
                                        <option value="{{ $t->suri_template_id }}">{{ $t->nome }} ({{ $t->suri_template_id }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Flow ID Destino (responseAction.sendTo)</label>
                                <input type="text" wire:model="flowId" placeholder="UUID do Fluxo na Suri (Opcional)" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                                    <input type="checkbox" wire:model="ignorePortfolio" class="rounded text-purpura-600">
                                    <span>ignorePortfolio (Ignorar Carteira)</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                            <button type="button" wire:click="dispararTemplateSimples" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl">
                                Disparar Template Simples
                            </button>
                        </div>

                    {{-- MODAL 3: TEMPLATE COM BOTÕES --}}
                    @elseif($modalAtivo === 'tpl_botoes')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nome (user.name) *</label>
                                <input type="text" wire:model="name" placeholder="Nome do destinatário" class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Telefone c/ DDI (user.phone) *</label>
                                <input type="text" wire:model="phone" placeholder="5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Channel ID (user.channelId) *</label>
                                <input type="text" wire:model="channelId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Default Department ID (Opcional)</label>
                                <input type="text" wire:model="defaultDepartmentId" placeholder="null ou ID do departamento" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Template ID (message.templateId) *</label>
                                <input type="text" wire:model="templateId" placeholder="ID do template com botões na Suri" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                            <button type="button" wire:click="dispararTemplateComBotoes" class="px-5 py-2 bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-bold rounded-xl">
                                Disparar Template com Botões
                            </button>
                        </div>

                    {{-- MODAL 4: TEMPLATE COM VARIÁVEIS --}}
                    @elseif($modalAtivo === 'tpl_variaveis')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nome (user.name) *</label>
                                <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Telefone c/ DDI (user.phone) *</label>
                                <input type="text" wire:model="phone" placeholder="5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Channel ID *</label>
                                <input type="text" wire:model="channelId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Template ID (message.templateId) *</label>
                                <input type="text" wire:model="templateId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>

                            <div class="sm:col-span-2 space-y-2 pt-2 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-gray-800">Parâmetros da Mensagem (bodyParameters: @{{1}}, @{{2}}...)</label>
                                    <button type="button" wire:click="adicionarParametro" class="text-xs font-bold text-purpura-600 hover:underline">+ Adicionar Variável</button>
                                </div>
                                @foreach($bodyParameters as $idx => $param)
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono font-bold text-gray-600">@{{ $idx + 1 }}</span>
                                        <input type="text" wire:model="bodyParameters.{{ $idx }}" placeholder="Valor da variável {{ $idx + 1 }}" class="flex-1 rounded-lg border-gray-300 text-xs">
                                        <button type="button" wire:click="removerParametro({{ $idx }})" class="text-gray-400 hover:text-red-500 p-1">
                                            <i class="ph-bold ph-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Flow ID (responseAction.sendTo)</label>
                                <input type="text" wire:model="flowId" placeholder="UUID do Fluxo (Opcional)" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Default Department ID (Opcional)</label>
                                <input type="text" wire:model="defaultDepartmentId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                            <button type="button" wire:click="dispararTemplateComVariaveis" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl">
                                Disparar Template com Variáveis
                            </button>
                        </div>

                    {{-- MODAL 5: MENSAGEM SIMPLES SEM TEMPLATE --}}
                    @elseif($modalAtivo === 'msg_simples')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">ID do Contato na Suri (userId) *</label>
                                <input type="text" wire:model="userId" placeholder="Ex: wp332148629991857-5585987755801" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Conteúdo da Mensagem (message.text) *</label>
                                <textarea wire:model="textoMensagem" rows="3" placeholder="Digite o texto da mensagem..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">ID da Mensagem de Contexto / Reply (context.msgId - Opcional)</label>
                                <input type="text" wire:model="contextMsgId" placeholder="Ex: wamid.HBgNNTU4NTk4Nzc1NTgwMRUCABIYFjNF..." class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                            <button type="button" wire:click="dispararMensagemSimples" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl">
                                Enviar Mensagem Simples
                            </button>
                        </div>

                    {{-- MODAL GUIA: ONDE BUSCAR OS IDS NA SURI --}}
                    @elseif($modalAtivo === 'guia_suri')
                        <div class="space-y-3 text-xs text-gray-700 dark:text-gray-200 leading-relaxed">
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">1. Onde pegar o <code class="font-mono">userId</code> (ID do Contato)?</h4>
                                <p class="mt-1">• Ao executar o cartão <b>"1. Importar Contato"</b> (`POST /api/contacts`), a Suri devolve o `id` do contato no JSON de resposta (geralmente no formato `channelId-5511999999999`).<br>• No Portal da Suri: vá em <b>Contatos</b>, clique sobre o contato desejado e veja o identificador na URL ou nos detalhes do perfil.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">2. Onde pegar o <code class="font-mono">templateId</code>?</h4>
                                <p class="mt-1">• No próprio sistema: ao salvar ou sincronizar um Template WhatsApp, o ID gerado pela Suri fica salvo na coluna `suri_template_id` (listado na tabela à esquerda).<br>• No Portal da Suri: vá em <b>Configurações (⚙️) → Templates (Mensagens HSM / WhatsApp)</b>, clique sobre o template aprovado pela Meta e copie o <b>ID do Template</b>.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">3. Onde pegar o <code class="font-mono">flowId</code> (sendTo)?</h4>
                                <p class="mt-1">• No Portal da Suri: acesse o menu <b>Fluxos de Atendimento (Automação / Chatbot)</b>, clique para editar o fluxo para onde o usuário deve ir quando responder ao template, e copie o <b>UUID que aparece no final da URL do navegador</b> (ex: `.../flows/a1b2c3d4-...`).</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">4. Onde pegar o <code class="font-mono">defaultDepartmentId</code>?</h4>
                                <p class="mt-1">• No Portal da Suri: vá em <b>Configurações (⚙️) → Departamentos</b>, clique em editar o departamento desejado e copie o ID na URL (ou via `GET {{baseUrl}}/api/departments`).</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">5. Onde pegar o <code class="font-mono">msgId</code> (`wamid.XXX...`)?</h4>
                                <p class="mt-1">• Esse campo é <b>opcional</b> e serve apenas quando você quer responder citando (reply) uma mensagem específica do usuário. Ele vem no payload de Webhook da Suri (`message.id` ou `context.id` iniciado por `wamid.`).</p>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif

</div>