<div class="p-6 max-w-7xl mx-auto font-sans space-y-6">

    <!-- CABEÇALHO E STATUS DO .ENV -->
    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Laboratório de Testes Suri (WhatsApp API)</h1>
                @if($configSuri['tem_token'] && $configSuri['base_url'] !== 'Não configurada')
                    <span class="px-2 py-0.5 text-[11px] font-medium rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Credenciais .ENV ativas
                    </span>
                @else
                    <span class="px-2 py-0.5 text-[11px] font-medium rounded bg-red-50 text-red-700 border border-red-200">
                        Configuração .ENV pendente
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Teste listagem e importação de contatos, disparos de templates e mensagens livres com inspeção de payload.
            </p>
            <div class="flex flex-wrap items-center gap-4 mt-2.5 text-xs font-mono text-gray-600 dark:text-gray-300">
                <span><strong class="font-sans text-gray-500">URL:</strong> {{ $configSuri['base_url'] }}</span>
                <span><strong class="font-sans text-gray-500">Channel ID:</strong> {{ $configSuri['channel_id'] }}</span>
                @if($userId)
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded">
                        userId ativo: {{ $userId }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" wire:click="abrirModal('guia_suri')" class="btn btn--secondary btn--medium bg-white dark:bg-gray-900">
                <i class="ph ph-question"></i> Onde buscar os IDs?
            </button>
            <a href="{{ route('templates.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-900">
                <i class="ph ph-arrow-left"></i> Templates
            </a>
        </div>
    </div>

    <!-- ATALHOS DOS 6 ENDPOINTS DE TESTE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        
        <!-- 1. LISTAR CONTATOS -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#1</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">GET /contacts</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Listar Contatos</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Consulta contatos paginados para capturar o <code class="font-mono">userId</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('listar_contatos')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Testar GET
            </button>
        </div>

        <!-- 2. IMPORTAR CONTATO -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#2</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">POST /contacts</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Importar Contato</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Registra nome, telefone (com DDI 55) e canal na Suri.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('contato')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Testar Importação
            </button>
        </div>

        <!-- 3. TEMPLATE SIMPLES -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#3</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">POST /messages/send</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Template Simples</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Dispara template pelo <code class="font-mono">userId</code> e <code class="font-mono">templateId</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_simples')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Disparar Simples
            </button>
        </div>

        <!-- 4. TEMPLATE COM BOTÕES -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#4</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">POST /messages/send</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Template c/ Botões</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Envia objeto <code class="font-mono">user</code> + <code class="font-mono">templateId</code> interativo.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_botoes')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Disparar c/ Botões
            </button>
        </div>

        <!-- 5. TEMPLATE COM VARIÁVEIS -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#5</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">POST /messages/send</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Template c/ Variáveis</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Envia objeto <code class="font-mono">user</code> + array <code class="font-mono">bodyParameters</code>.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('tpl_variaveis')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Disparar c/ Variáveis
            </button>
        </div>

        <!-- 6. MENSAGEM LIVRE -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400">#6</span>
                    <code class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-700">POST /messages/send</code>
                </div>
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Mensagem Livre</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    Envia texto simples para janelas abertas de 24h.
                </p>
            </div>
            <button type="button" wire:click="abrirModal('msg_simples')" class="w-full py-1.5 px-3 bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition">
                Enviar Texto Livre
            </button>
        </div>
    </div>

    <!-- PAINEL DO MEIO: TEMPLATES LOCAIS + CONSOLE HTTP -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LISTA DE TEMPLATES WHATSAPP -->
        <div class="lg:col-span-5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                    Templates WhatsApp no Sistema
                </h3>
                <span class="text-xs text-gray-400">{{ count($templatesLocais) }}</span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[420px] overflow-y-auto custom-scrollbar">
                @forelse($templatesLocais as $tpl)
                    <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ $tpl->nome }}</p>
                            @if($tpl->suri_template_id)
                                <p class="text-[11px] font-mono text-gray-500 mt-0.5 truncate">
                                    {{ $tpl->suri_template_id }}
                                </p>
                            @else
                                <p class="text-[11px] text-amber-600 mt-0.5">Não sincronizado</p>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if($tpl->suri_template_id)
                                <button type="button"
                                        wire:click="$set('templateId', '{{ $tpl->suri_template_id }}')"
                                        class="px-2.5 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-200 transition">
                                    Usar ID
                                </button>
                            @else
                                <button type="button"
                                        wire:click="sincronizarTemplateLocal({{ $tpl->id }})"
                                        class="px-2.5 py-1 text-xs font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 transition">
                                    Sincronizar
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

        <!-- CONSOLE HTTP -->
        <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between bg-gray-900 text-white">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold">
                        Console HTTP
                        @if(!empty($ultimoResultado['id']))
                            <span class="text-gray-400 font-mono ml-1">#{{ $ultimoResultado['id'] }}</span>
                        @endif
                    </span>
                </div>
                @if($ultimoResultado)
                    <span class="px-2 py-0.5 text-[11px] font-mono rounded {{ $ultimoResultado['sucesso'] ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300' }}">
                        HTTP {{ $ultimoResultado['http_status'] }} • {{ $ultimoResultado['horario'] }}
                    </span>
                @endif
            </div>

            @if($ultimoResultado)
                <div class="p-4 space-y-4 bg-gray-950 text-gray-100 font-mono text-xs max-h-[440px] overflow-y-auto custom-scrollbar">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-400">
                        <span>Ação: <strong class="text-gray-200">{{ $ultimoResultado['acao'] }}</strong></span>
                        @if(!empty($ultimoResultado['user_nome']))
                            <span>Por: <strong class="text-gray-200">{{ $ultimoResultado['user_nome'] }}</strong></span>
                        @endif
                    </div>

                    <code class="px-3 py-1.5 bg-gray-900 border border-gray-800 rounded block text-gray-200 break-all">
                        {{ $ultimoResultado['metodo'] ?? 'POST' }} {{ $ultimoResultado['url'] }}
                    </code>

                    @php
                        $erroMsgSuri = (string) data_get($ultimoResultado, 'resposta.error', '');
                    @endphp
                    @if(str_contains($erroMsgSuri, '132001'))
                        <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-200 font-sans text-xs space-y-1">
                            <p class="font-semibold text-amber-300">Diagnóstico (#132001 - Template name does not exist in the translation):</p>
                            <p>1. Verifique no Portal da Suri se o template já foi aprovado pela Meta.<br>2. Confirme se o template pertence ao canal <code class="font-mono">{{ $configSuri['channel_id'] }}</code>.</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <span class="text-[11px] text-gray-400 block mb-1">
                                {{ ($ultimoResultado['metodo'] ?? 'POST') === 'GET' ? 'Query Params:' : 'Body Enviado (JSON):' }}
                            </span>
                            <pre class="p-3 bg-gray-900 border border-gray-800 rounded-lg overflow-x-auto text-[11px] text-gray-200">{{ json_encode($ultimoResultado['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-400 block mb-1">Resposta da Suri:</span>
                            <pre class="p-3 bg-gray-900 border border-gray-800 rounded-lg overflow-x-auto text-[11px] {{ $ultimoResultado['sucesso'] ? 'text-emerald-300' : 'text-red-300' }}">{{ json_encode($ultimoResultado['resposta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-10 text-center text-gray-500 bg-gray-950 font-mono text-xs">
                    Execute um dos endpoints acima para inspecionar o payload e a resposta HTTP.
                </div>
            @endif
        </div>
    </div>

    <!-- TABELA DE LOGS DO LABORATÓRIO SURI -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                    Histórico de Requisições Suri ({{ $totalLogsSalvos }})
                </h3>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-52">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" wire:model.live.debounce.300ms="filtroBuscaLog" placeholder="Buscar no log..." class="w-full pl-8 pr-3 h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>

                <select wire:model.live="filtroAcaoLog" class="h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-0 px-2.5">
                    <option value="">Todas as ações</option>
                    @foreach($acoesDistintas as $acaoItem)
                        <option value="{{ $acaoItem }}">{{ $acaoItem }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroStatusLog" class="h-8 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-0 px-2.5">
                    <option value="">Todos os status</option>
                    <option value="1">Sucesso (2xx)</option>
                    <option value="0">Erro</option>
                </select>

                @if($filtroBuscaLog !== '' || $filtroAcaoLog !== '' || $filtroStatusLog !== '')
                    <button type="button" wire:click="limparFiltrosLog" class="h-8 px-2.5 text-xs font-medium text-gray-500 hover:text-gray-900 bg-gray-100 rounded-lg transition">
                        Limpar
                    </button>
                @endif

                @if($totalLogsSalvos > 0)
                    <button type="button"
                            wire:click="limparTodosOsLogs"
                            wire:confirm="Tem certeza de que deseja apagar todo o histórico de logs de testes da Suri?"
                            class="h-8 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50 border border-gray-200 rounded-lg transition">
                        Limpar Histórico
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-[11px] font-medium uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2.5">ID</th>
                        <th class="px-4 py-2.5">Data / Hora</th>
                        <th class="px-4 py-2.5">Ação</th>
                        <th class="px-4 py-2.5">Endpoint</th>
                        <th class="px-4 py-2.5 text-center">HTTP</th>
                        <th class="px-4 py-2.5">Origem</th>
                        <th class="px-4 py-2.5 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($logsHistorico as $logItem)
                        <tr wire:key="suri-log-{{ $logItem->id }}"
                            class="transition-colors {{ $logSelecionadoId === $logItem->id ? 'bg-gray-100/80 dark:bg-gray-800' : 'hover:bg-gray-50/70 dark:hover:bg-gray-800/40' }}">
                            <td class="px-4 py-2.5 font-mono text-gray-400">
                                #{{ $logItem->id }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-gray-600 dark:text-gray-300">
                                {{ $logItem->created_at ? $logItem->created_at->format('d/m/Y H:i:s') : '-' }}
                            </td>
                            <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white">
                                {{ $logItem->acao }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-[11px] text-gray-600 dark:text-gray-300 max-w-md truncate" title="{{ $logItem->url }}">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 mr-1">
                                    {{ $logItem->metodo }}
                                </span>
                                {{ $logItem->url }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded border text-[11px] font-mono font-medium {{ $logItem->sucesso ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                    {{ $logItem->http_status }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                                {{ $logItem->user_nome ?: 'Sistema' }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            wire:click="replayLog({{ $logItem->id }})"
                                            wire:confirm="Deseja reenviar (Replay) exatamente esta mesma requisição ({{ $logItem->metodo }} {{ $logItem->acao }}) para a Suri?"
                                            class="px-2 py-1 rounded text-xs font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 border border-gray-200 dark:border-gray-600 transition">
                                        Replay
                                    </button>

                                    <button type="button"
                                            wire:click="carregarLogNoConsole({{ $logItem->id }})"
                                            class="px-2 py-1 rounded text-xs font-medium transition {{ $logSelecionadoId === $logItem->id ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 border border-gray-200 dark:border-gray-600' }}">
                                        {{ $logSelecionadoId === $logItem->id ? 'Ativo' : 'Inspecionar' }}
                                    </button>

                                    <button type="button"
                                            wire:click="excluirLog({{ $logItem->id }})"
                                            class="p-1 text-gray-400 hover:text-red-600 rounded transition"
                                            title="Excluir log">
                                        <i class="ph ph-trash text-sm"></i>
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
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                {{ $logsHistorico->links() }}
            </div>
        @endif
    </div>

    <!-- MODAIS DE TESTE E GUIA DA SURI -->
    @if($modalAtivo)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full border border-gray-200 dark:border-gray-700 overflow-hidden my-8" @click.away="fecharModal">
                
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if($modalAtivo === 'listar_contatos') 1. Listar Contatos da Suri (GET /api/contacts)
                        @elseif($modalAtivo === 'contato') 2. Importar Contato para a Suri
                        @elseif($modalAtivo === 'tpl_simples') 3. Enviar Mensagem com Template Simples
                        @elseif($modalAtivo === 'tpl_botoes') 4. Enviar Mensagem com Template com Botões
                        @elseif($modalAtivo === 'tpl_variaveis') 5. Enviar Mensagem com Template com Variáveis
                        @elseif($modalAtivo === 'msg_simples') 6. Enviar Mensagem Simples (Sem Template)
                        @else Guia: Onde encontrar cada ID no Portal da Suri
                        @endif
                    </h3>
                    <button type="button" wire:click="fecharModal" class="text-gray-400 hover:text-gray-700 p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[78vh] overflow-y-auto custom-scrollbar">

                    {{-- MODAL 0: LISTAR CONTATOS --}}
                    @if($modalAtivo === 'listar_contatos')
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="sm:col-span-6">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">continuationToken (Opcional)</label>
                                <input type="text" wire:model="continuationToken" placeholder="Token de paginação..." class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">limit *</label>
                                <input type="text" wire:model="limit" placeholder="20" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-3">
                                <button type="button" wire:click="dispararListarContatos(false)" class="w-full py-2 px-3 bg-gray-900 hover:bg-black text-white text-xs font-medium rounded-lg transition">
                                    Buscar
                                </button>
                            </div>
                        </div>

                        @if($proximoContinuationToken)
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-700">
                                <span class="truncate font-mono text-[11px]"><b>Próximo token:</b> {{ $proximoContinuationToken }}</span>
                                <button type="button" wire:click="dispararListarContatos(true)" class="shrink-0 ml-2 px-3 py-1 bg-gray-900 text-white font-medium rounded text-xs">
                                    Próxima Página →
                                </button>
                            </div>
                        @endif

                        @if(!empty($contatosListadosSuri))
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 dark:bg-gray-900 text-[11px] font-medium uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-3.5 py-2">userId</th>
                                            <th class="px-3.5 py-2">Nome</th>
                                            <th class="px-3.5 py-2">Telefone</th>
                                            <th class="px-3.5 py-2 text-right">Ação</th>
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
                                                <td class="px-3.5 py-2 font-mono text-[11px] text-gray-700 dark:text-gray-300">{{ $cId }}</td>
                                                <td class="px-3.5 py-2 font-medium text-gray-900 dark:text-white">{{ $cNome }}</td>
                                                <td class="px-3.5 py-2 font-mono text-gray-500">{{ $cFone ?: '-' }}</td>
                                                <td class="px-3.5 py-2 text-right">
                                                    <button type="button"
                                                            wire:click="selecionarContatoSuri('{{ $cId }}', '{{ addslashes($cNome) }}', '{{ $cFone }}', '{{ $cCanal }}')"
                                                            class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded font-medium text-xs transition">
                                                        Usar Contato
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-8 text-center text-xs text-gray-400 border border-dashed border-gray-200 rounded-lg">
                                Clique em "Buscar" acima para listar os contatos da Suri.
                            </div>
                        @endif

                        <div class="flex justify-end pt-3 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                        </div>
                    @endif

                    @if(in_array($modalAtivo, ['contato', 'tpl_botoes', 'tpl_variaveis']))
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Preencher com dados de um Candidato (Opcional)</label>
                            <select wire:model.live="inscricaoTesteId" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="">Digitar manualmente abaixo...</option>
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
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nome do Contato (name) *</label>
                                <input type="text" wire:model="name" placeholder="Nome completo" class="w-full rounded-lg border-gray-300 text-xs">
                                @error('name') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Telefone c/ DDI e DDD (phone) *</label>
                                <input type="text" wire:model="phone" placeholder="5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('phone') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Channel ID (channelId) *</label>
                                <input type="text" wire:model="channelId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Gender</label>
                                    <select wire:model="gender" class="w-full rounded-lg border-gray-300 text-xs">
                                        <option value="0">0 (Não inf.)</option>
                                        <option value="1">1 (Masculino)</option>
                                        <option value="2">2 (Feminino)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Channel Type</label>
                                    <input type="number" wire:model="channelType" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                            <button type="button" wire:click="dispararImportarContato" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Executar POST /api/contacts
                            </button>
                        </div>

                    {{-- MODAL 2: TEMPLATE SIMPLES --}}
                    @elseif($modalAtivo === 'tpl_simples')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">ID do Contato na Suri (userId) *</label>
                                <input type="text" wire:model="userId" placeholder="wp483709398162745:5511964616752" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('userId') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">ID do Template na Suri (templateId) *</label>
                                <input type="text" wire:model="templateId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                                @error('templateId') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Ou escolha um Template Sincronizado do Sistema</label>
                                <select wire:model.live="templateId" class="w-full rounded-lg border-gray-300 text-xs">
                                    <option value="">Selecionar template sincronizado...</option>
                                    @foreach($templatesLocais->whereNotNull('suri_template_id') as $t)
                                        <option value="{{ $t->suri_template_id }}">{{ $t->nome }} ({{ $t->suri_template_id }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Flow ID Destino (responseAction.sendTo)</label>
                                <input type="text" wire:model="flowId" placeholder="UUID do Fluxo (Opcional)" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700">
                                    <input type="checkbox" wire:model="ignorePortfolio" class="rounded text-purpura-600">
                                    <span>ignorePortfolio (Ignorar Carteira)</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                            <button type="button" wire:click="dispararTemplateSimples" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Disparar Template Simples
                            </button>
                        </div>

                    {{-- MODAL 3: TEMPLATE COM BOTÕES --}}
                    @elseif($modalAtivo === 'tpl_botoes')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nome (user.name) *</label>
                                <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Telefone c/ DDI (user.phone) *</label>
                                <input type="text" wire:model="phone" placeholder="5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Channel ID (user.channelId) *</label>
                                <input type="text" wire:model="channelId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Default Department ID (Opcional)</label>
                                <input type="text" wire:model="defaultDepartmentId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Template ID (message.templateId) *</label>
                                <input type="text" wire:model="templateId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                            <button type="button" wire:click="dispararTemplateComBotoes" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Disparar Template com Botões
                            </button>
                        </div>

                    {{-- MODAL 4: TEMPLATE COM VARIÁVEIS --}}
                    @elseif($modalAtivo === 'tpl_variaveis')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nome (user.name) *</label>
                                <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Telefone c/ DDI (user.phone) *</label>
                                <input type="text" wire:model="phone" placeholder="5511999999999" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Channel ID *</label>
                                <input type="text" wire:model="channelId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Template ID (message.templateId) *</label>
                                <input type="text" wire:model="templateId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>

                            <div class="sm:col-span-2 space-y-2 pt-2 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-medium text-gray-800">Parâmetros da Mensagem (bodyParameters)</label>
                                    <button type="button" wire:click="adicionarParametro" class="text-xs font-medium text-purpura-600 hover:underline">+ Adicionar Variável</button>
                                </div>
                                @foreach($bodyParameters as $idx => $param)
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono text-gray-600">@{{ $idx + 1 }}</span>
                                        <input type="text" wire:model="bodyParameters.{{ $idx }}" placeholder="Valor da variável {{ $idx + 1 }}" class="flex-1 rounded-lg border-gray-300 text-xs">
                                        <button type="button" wire:click="removerParametro({{ $idx }})" class="text-gray-400 hover:text-red-600 p-1">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Flow ID (responseAction.sendTo)</label>
                                <input type="text" wire:model="flowId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Default Department ID (Opcional)</label>
                                <input type="text" wire:model="defaultDepartmentId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                            <button type="button" wire:click="dispararTemplateComVariaveis" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Disparar Template com Variáveis
                            </button>
                        </div>

                    {{-- MODAL 5: MENSAGEM SIMPLES --}}
                    @elseif($modalAtivo === 'msg_simples')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">ID do Contato na Suri (userId) *</label>
                                <input type="text" wire:model="userId" placeholder="wp483709398162745:5511964616752" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Conteúdo da Mensagem (message.text) *</label>
                                <textarea wire:model="textoMensagem" rows="3" placeholder="Digite o texto..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Context Message ID (context.msgId - Opcional)</label>
                                <input type="text" wire:model="contextMsgId" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="fecharModal" class="btn btn--secondary btn--small">Fechar</button>
                            <button type="button" wire:click="dispararMensagemSimples" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Enviar Mensagem Simples
                            </button>
                        </div>

                    {{-- MODAL GUIA --}}
                    @elseif($modalAtivo === 'guia_suri')
                        <div class="space-y-3 text-xs text-gray-700 dark:text-gray-200 leading-relaxed">
                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-white">1. Onde pegar o <code class="font-mono">userId</code> (ID do Contato)?</h4>
                                <p class="mt-1">• Execute o cartão <b>"#1 Listar Contatos"</b> (<code class="font-mono">GET /api/contacts</code>) e clique em <b>"Usar Contato"</b>, ou execute <b>"#2 Importar Contato"</b>.<br>• Formato padrão na Suri: <code class="font-mono">channelId:5511999999999</code>.</p>
                            </div>

                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-white">2. Onde pegar o <code class="font-mono">templateId</code>?</h4>
                                <p class="mt-1">• No Portal da Suri: acesse <b>Configurações → Templates</b>. Certifique-se de que o template esteja aprovado pela Meta e vinculado ao mesmo canal do contato.</p>
                            </div>

                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-white">3. Onde pegar o <code class="font-mono">flowId</code> (sendTo)?</h4>
                                <p class="mt-1">• No Portal da Suri: acesse <b>Fluxos de Atendimento</b>, clique no fluxo desejado e copie o UUID no final da URL.</p>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif

</div>