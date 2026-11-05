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
                    Simule listagem e importação de contatos, disparos de templates e mensagens livres. Todos os testes ficam registrados no Log Exclusivo abaixo.
                </p>
                <div class="flex flex-wrap items-center gap-4 mt-2.5 text-[11px] font-mono text-gray-600 dark:text-gray-300">
                    <span><b>SURI_API_URL:</b> {{ $configSuri['base_url'] }}</span>
                    <span><b>SURI_CHANNEL_ID:</b> {{ $configSuri['channel_id'] }}</span>
                    @if($userId)
                        <span class="px-2 py-0.5 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded font-bold">
                            Contato Ativo (userId): {{ $userId }}
                        </span>
                    @endif
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

    <!-- ATALHOS DOS 6 ENDPOINTS DE TESTE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        
        <!-- CARD 0: LISTAR CONTATOS (GET) -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-teal-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 border border-teal-200 flex items-center justify-center">
                        <i class="ph-bold ph-address-book text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-teal-50 text-teal-700 border border-teal-200 rounded">GET /api/contacts</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">1. Listar Contatos</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Consulta os contatos da Suri usando <code class="font-mono text-teal-600">continuationToken</code> e <code class="font-mono text-teal-600">limit</code> para capturar o <code class="font-mono">userId</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('listar_contatos')" class="w-full py-2 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-magnifying-glass"></i> Listar Contatos
            </button>
        </div>

        <!-- CARD 1: IMPORTAR CONTATO (POST) -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between gap-4 hover:border-purpura-400 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center">
                        <i class="ph-bold ph-user-plus text-lg"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[9px] font-mono font-bold bg-gray-100 text-gray-600 rounded">POST /api/contacts</span>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">2. Importar Contato</h3>
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
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">3. Template Simples</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia um template aprovado usando o <code class="font-mono text-purpura-600">userId</code> do contato e redireciona para um <code class="font-mono">flowId</code>.
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
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">4. Template c/ Botões</h3>
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
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">5. Template c/ Variáveis</h3>
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
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">6. Mensagem Livre</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Envia texto livre para janelas de 24h abertas usando <code class="font-mono text-purpura-600">userId</code> e <code class="font-mono">context.msgId</code> opcional.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('msg_simples')" class="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                <i class="ph-bold ph-paper-plane-tilt"></i> Enviar Texto Livre
            </button>
        </div>
    </div>

    <!-- PAINEL DO MEIO: TEMPLATES WHATSAPP LOCAIS + CONSOLE DE INSPEÇÃO JSON -->
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
                    <span class="text-xs font-extrabold uppercase tracking-wider">
                        Console de Inspeção HTTP (Payload & Resposta Suri)
                        @if(!empty($ultimoResultado['id']))
                            <span class="text-gray-400 font-mono ml-1">#Log {{ $ultimoResultado['id'] }}</span>
                        @endif
                    </span>
                </div>
                @if($ultimoResultado)
                    <span class="px-2.5 py-0.5 text-[10px] font-mono font-bold rounded {{ $ultimoResultado['sucesso'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }}">
                        HTTP {{ $ultimoResultado['http_status'] }} • {{ $ultimoResultado['horario'] }}
                    </span>
                @endif
            </div>

            @if($ultimoResultado)
                <div class="p-5 space-y-4 bg-gray-950 text-gray-100 font-mono text-xs max-h-[460px] overflow-y-auto custom-scrollbar">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase text-purpura-400">
                            Endpoint Acionado ({{ $ultimoResultado['acao'] }}):
                        </span>
                        @if(!empty($ultimoResultado['user_nome']))
                            <span class="text-[10px] text-gray-400">Executado por: <b class="text-gray-200">{{ $ultimoResultado['user_nome'] }}</b></span>
                        @endif
                    </div>

                    <code class="px-2.5 py-1.5 bg-gray-900 border border-gray-800 rounded block text-emerald-300 break-all">
                        {{ $ultimoResultado['metodo'] ?? 'POST' }} {{ $ultimoResultado['url'] }}
                    </code>

                    @php
                        $erroMsgSuri = (string) data_get($ultimoResultado, 'resposta.error', '');
                    @endphp
                    @if(str_contains($erroMsgSuri, '132001'))
                        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/40 text-amber-200 font-sans text-xs space-y-1">
                            <p class="font-extrabold text-amber-300 flex items-center gap-1.5">
                                <i class="ph-fill ph-warning-circle text-base"></i> Diagnóstico do Erro (#132001 - Template name does not exist in the translation):
                            </p>
                            <p class="leading-relaxed">
                                1. Verifique no <b>Portal da Suri → Configurações → Templates</b> se este template já foi <b>Aprovado pela Meta</b>.<br>
                                2. Confira se o template pertence ao mesmo canal (<code class="font-mono">{{ $configSuri['channel_id'] }}</code>) do contato que está recebendo a mensagem.
                            </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-blue-400 block mb-1">
                                {{ ($ultimoResultado['metodo'] ?? 'POST') === 'GET' ? 'Query Params Enviados:' : 'Body Enviado (JSON):' }}
                            </span>
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
                    <i class="ph-duotone ph-brackets-curly text-4xl text-gray-600 mb-2 block mx-auto"></i>
                    Clique em qualquer um dos 6 cartões acima e execute um teste para inspecionar os parâmetros enviados e o retorno da API da Suri.
                </div>
            @endif
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TABELA DE LOGS EXCLUSIVA DO LABORATÓRIO SURI                          -->
    <!-- ===================================================================== -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-gray-50/60 dark:bg-gray-900/40">
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="ph-fill ph-clock-counter-clockwise text-purpura-600 text-base"></i>
                    Histórico de Logs Exclusivo do Laboratório Suri ({{ $totalLogsSalvos }})
                </h3>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Clique em <b>"Inspecionar no Console"</b> em qualquer registro abaixo para recarregar o Payload e a Resposta HTTP no console acima.
                </p>
            </div>

            <!-- Filtros e Limpeza do Log -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-56">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" wire:model.live.debounce.300ms="filtroBuscaLog" placeholder="Buscar no payload, resposta, URL..." class="w-full pl-8 pr-3 h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>

                <select wire:model.live="filtroAcaoLog" class="h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white font-semibold py-0 px-2.5">
                    <option value="">Todas as Ações</option>
                    @foreach($acoesDistintas as $acaoItem)
                        <option value="{{ $acaoItem }}">{{ $acaoItem }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroStatusLog" class="h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white font-semibold py-0 px-2.5">
                    <option value="">Todos os Status</option>
                    <option value="1">Sucesso (2xx)</option>
                    <option value="0">Falha / Erro</option>
                </select>

                @if($filtroBuscaLog !== '' || $filtroAcaoLog !== '' || $filtroStatusLog !== '')
                    <button type="button" wire:click="limparFiltrosLog" class="h-8 px-2.5 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 rounded-lg transition">
                        Limpar Filtro
                    </button>
                @endif

                @if($totalLogsSalvos > 0)
                    <button type="button"
                            wire:click="limparTodosOsLogs"
                            wire:confirm="Tem certeza de que deseja apagar todo o histórico de logs de testes da Suri?"
                            class="h-8 px-3 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition flex items-center gap-1">
                        <i class="ph-bold ph-trash"></i> Limpar Histórico
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/60 text-[10px] font-bold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">#ID</th>
                        <th class="px-4 py-3">Data / Hora</th>
                        <th class="px-4 py-3">Ação Executada</th>
                        <th class="px-4 py-3">Método & Endpoint</th>
                        <th class="px-4 py-3 text-center">HTTP Status</th>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($logsHistorico as $logItem)
                        <tr wire:key="suri-log-{{ $logItem->id }}"
                            class="transition-colors {{ $logSelecionadoId === $logItem->id ? 'bg-purpura-50/60 dark:bg-purpura-900/20' : 'hover:bg-gray-50/80 dark:hover:bg-gray-800/40' }}">
                            <td class="px-4 py-3 font-mono font-bold text-gray-500">
                                #{{ $logItem->id }}
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                {{ $logItem->created_at ? $logItem->created_at->format('d/m/Y H:i:s') : '-' }}
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $logItem->acao }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-gray-600 dark:text-gray-300 max-w-md truncate" title="{{ $logItem->url }}">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold {{ $logItem->metodo === 'GET' ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }} mr-1">
                                    {{ $logItem->metodo }}
                                </span>
                                {{ $logItem->url }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold {{ $logItem->sucesso ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                    HTTP {{ $logItem->http_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $logItem->user_nome ?: 'Sistema' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            wire:click="replayLog({{ $logItem->id }})"
                                            wire:confirm="Deseja reenviar (Replay) exatamente esta mesma requisição ({{ $logItem->metodo }} {{ $logItem->acao }}) para a Suri?"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition inline-flex items-center gap-1"
                                            title="Reenviar este mesmo payload para a Suri">
                                        <i class="ph-bold ph-arrows-clockwise"></i>
                                        <span>Replay</span>
                                    </button>

                                    <button type="button"
                                            wire:click="carregarLogNoConsole({{ $logItem->id }})"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 {{ $logSelecionadoId === $logItem->id ? 'bg-purpura-600 text-white' : 'bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200' }}">
                                        <i class="ph-bold ph-terminal-window"></i>
                                        <span>{{ $logSelecionadoId === $logItem->id ? 'No Console' : 'Inspecionar' }}</span>
                                    </button>

                                    <button type="button"
                                            wire:click="excluirLog({{ $logItem->id }})"
                                            class="p-1 text-gray-400 hover:text-red-600 rounded transition"
                                            title="Excluir este log">
                                        <i class="ph-bold ph-trash text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400 text-xs">
                                Nenhum teste registrado no histórico até o momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logsHistorico->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                {{ $logsHistorico->links() }}
            </div>
        @endif
    </div>

    <!-- ===================================================================== -->
    <!-- MODAIS DE TESTE E GUIA DA SURI                                        -->
    <!-- ===================================================================== -->
    @if($modalAtivo)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/65 backdrop-blur-sm px-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                
                <!-- Topo do Modal -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($modalAtivo === 'listar_contatos')
                            <i class="ph-fill ph-address-book text-teal-600 text-xl"></i> 1. Listar Contatos da Suri (GET /api/contacts)
                        @elseif($modalAtivo === 'contato')
                            <i class="ph-fill ph-user-plus text-blue-600 text-xl"></i> 2. Importar Contato para a Suri
                        @elseif($modalAtivo === 'tpl_simples')
                            <i class="ph-fill ph-chat-text text-emerald-600 text-xl"></i> 3. Enviar Mensagem com Template Simples
                        @elseif($modalAtivo === 'tpl_botoes')
                            <i class="ph-fill ph-hand-tap text-purpura-600 text-xl"></i> 4. Enviar Mensagem com Template com Botões
                        @elseif($modalAtivo === 'tpl_variaveis')
                            <i class="ph-fill ph-brackets-curly text-amber-600 text-xl"></i> 5. Enviar Mensagem com Template com Variáveis
                        @elseif($modalAtivo === 'msg_simples')
                            <i class="ph-fill ph-chat-circle-dots text-indigo-600 text-xl"></i> 6. Enviar Mensagem Simples (Sem Template)
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

                    {{-- MODAL 0: LISTAR CONTATOS --}}
                    @if($modalAtivo === 'listar_contatos')
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="sm:col-span-6">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">continuationToken (Opcional)</label>
                                <input type="text" wire:model="continuationToken" placeholder="Token de paginação da Suri..." class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">limit (Qtd. por página) *</label>
                                <input type="text" wire:model="limit" placeholder="Ex: 20" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-3 flex gap-1.5">
                                <button type="button" wire:click="dispararListarContatos(false)" class="w-full py-2 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-lg transition flex items-center justify-center gap-1">
                                    <i class="ph-bold ph-magnifying-glass"></i> Buscar
                                </button>
                            </div>
                        </div>

                        @if($proximoContinuationToken)
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-teal-50 border border-teal-200 text-xs text-teal-800">
                                <span class="truncate font-mono text-[11px]"><b>Próximo continuationToken:</b> {{ $proximoContinuationToken }}</span>
                                <button type="button" wire:click="dispararListarContatos(true)" class="shrink-0 ml-2 px-3 py-1 bg-teal-600 text-white font-bold rounded-md text-[11px] hover:bg-teal-700">
                                    Carregar Próxima Página →
                                </button>
                            </div>
                        @endif

                        @if(!empty($contatosListadosSuri))
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 dark:bg-gray-900 text-[10px] font-bold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-3.5 py-2.5">ID do Contato (userId)</th>
                                            <th class="px-3.5 py-2.5">Nome</th>
                                            <th class="px-3.5 py-2.5">Telefone</th>
                                            <th class="px-3.5 py-2.5 text-right">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($contatosListadosSuri as $cSuri)
                                            @php
                                                $cId = (string) ($cSuri['id'] ?? $cSuri['userId'] ?? '');
                                                $cNome = (string) ($cSuri['name'] ?? 'Sem nome');
                                                $cFone = (string) ($cSuri['phone'] ?? '');
                                                $cCanal = (string) ($cSuri['channelId'] ?? '');
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="px-3.5 py-2.5 font-mono text-[11px] text-purpura-700 dark:text-purpura-300 font-bold">{{ $cId }}</td>
                                                <td class="px-3.5 py-2.5 font-semibold text-gray-900 dark:text-white">{{ $cNome }}</td>
                                                <td class="px-3.5 py-2.5 font-mono text-gray-600">{{ $cFone ?: '-' }}</td>
                                                <td class="px-3.5 py-2.5 text-right">
                                                    <button type="button"
                                                            wire:click="selecionarContatoSuri('{{ $cId }}', '{{ addslashes($cNome) }}', '{{ $cFone }}', '{{ $cCanal }}')"
                                                            class="px-2.5 py-1 bg-purpura-50 hover:bg-purpura-100 text-purpura-700 border border-purpura-200 rounded-md font-bold text-[11px] transition">
                                                        Usar Contato
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-8 text-center text-xs text-gray-400 border border-dashed border-gray-200 rounded-xl">
                                Clique em <b>"Buscar"</b> acima para listar os contatos da Suri.
                            </div>
                        @endif

                        <div class="flex justify-end pt-3 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--medium">Fechar</button>
                        </div>
                    @endif

                    @if(in_array($modalAtivo, ['contato', 'tpl_botoes', 'tpl_variaveis']))
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
                                <input type="text" wire:model="userId" placeholder="Ex: wp483709398162745:5511964616752" class="w-full rounded-lg border-gray-300 text-xs font-mono">
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
                                <input type="text" wire:model="userId" placeholder="Ex: wp483709398162745:5511964616752" class="w-full rounded-lg border-gray-300 text-xs font-mono">
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
                                <p class="mt-1">• Clique no cartão <b>"1. Listar Contatos"</b> (`GET /api/contacts`) e clique em <b>"Usar Contato"</b> na tabela, ou execute o cartão <b>"2. Importar Contato"</b> (`POST /api/contacts`).<br>• O `userId` na Suri tem o formato `channelId:5511999999999` (ex: `wp483709398162745:5511964616752`).</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">2. Onde pegar o <code class="font-mono">templateId</code> (e evitar o erro #132001)?</h4>
                                <p class="mt-1">• No Portal da Suri: vá em <b>Configurações (⚙️) → Templates</b>. Certifique-se de que o template esteja com status <b>Aprovado (Approved)</b> pela Meta e vinculado ao mesmo canal do contato.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-extrabold text-purpura-700 dark:text-purpura-400">3. Onde pegar o <code class="font-mono">flowId</code> (sendTo)?</h4>
                                <p class="mt-1">• No Portal da Suri: acesse o menu <b>Fluxos de Atendimento (Chatbot)</b>, clique no fluxo desejado e copie o <b>UUID no final da URL</b>.</p>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif

</div>