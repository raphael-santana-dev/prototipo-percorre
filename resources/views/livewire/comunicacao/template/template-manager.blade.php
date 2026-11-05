<div class="p-6 max-w-7xl mx-auto font-sans relative space-y-5">
    
    <x-page-header 
        title="Templates de Comunicação (E-mail & WhatsApp Suri)" 
        icon="ph ph-envelope-simple"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <a href="{{ route('suri.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-colors rounded-lg shadow-sm font-bold text-sm">
                <i class="ph-fill ph-whatsapp-logo text-lg"></i> Laboratório Suri
            </a>

            <a href="{{ route('templates.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                <i class="ph ph-plus text-lg"></i> Novo Template
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <!-- SELETOR DE ABAS: TEMPLATES LOCAIS VS TEMPLATES RETORNADOS DA SURI -->
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
                    <button type="button"
                            wire:click="alternarAba('locais')"
                            class="px-4 py-1.5 rounded-lg text-xs font-extrabold transition flex items-center gap-1.5 {{ $abaAtiva === 'locais' ? 'bg-white dark:bg-gray-900 text-purpura-600 shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                        <i class="ph-bold ph-database"></i> Templates do Sistema
                    </button>
                    <button type="button"
                            wire:click="alternarAba('suri_remotos')"
                            class="px-4 py-1.5 rounded-lg text-xs font-extrabold transition flex items-center gap-1.5 {{ $abaAtiva === 'suri_remotos' ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                        <i class="ph-fill ph-whatsapp-logo"></i> Templates Retornados da Suri (API)
                    </button>
                </div>

                @if($abaAtiva === 'locais')
                    <div class="flex gap-2">
                        <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar nome, assunto ou ID Suri..." class="rounded-lg border-gray-300 text-xs shadow-sm focus:ring-purpura-500 w-64">
                        @if($filtro_busca !== '')
                            <button wire:click="limparFiltros" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-1">
                                <i class="ph-bold ph-x"></i> Limpar
                            </button>
                        @endif
                    </div>
                @else
                    <div class="flex gap-2">
                        <input wire:model.live.debounce.250ms="buscaSuriRemoto" type="text" placeholder="Filtrar templates da Suri..." class="rounded-lg border-gray-300 text-xs shadow-sm focus:ring-emerald-500 w-64">
                        <button type="button" wire:click="carregarTemplatesDaSuri" class="px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg flex items-center gap-1.5 shadow-xs">
                            <i class="ph-bold ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="carregarTemplatesDaSuri"></i>
                            Atualizar da Suri (GET /api/templates)
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    @if($abaAtiva === 'locais')
        {{-- ABA 1: TEMPLATES LOCAIS DO SISTEMA --}}
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $template)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $template->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $template->nome }}</div>
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                        @php $canalTpl = $template->canal ?? 'email'; @endphp
                        @if($canalTpl === 'whatsapp')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="ph-fill ph-whatsapp-logo"></i> WhatsApp
                            </span>
                        @elseif($canalTpl === 'ambos')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purpura-50 text-purpura-700 border border-purpura-200">
                                <i class="ph-fill ph-intersect"></i> Ambos
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                <i class="ph-fill ph-envelope-simple"></i> E-mail
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600 truncate max-w-sm">{{ $template->assunto }}</div>
                        @if($template->suri_template_id)
                            <span class="inline-block text-[10px] font-mono text-emerald-600 font-bold mt-0.5">
                                Suri ID: {{ $template->suri_template_id }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if(!$template->suri_template_id && in_array($template->canal ?? 'email', ['whatsapp', 'ambos']))
                                <button wire:click="sincronizarSuri({{ $template->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-green-600 hover:bg-green-50" title="Sincronizar com Suri">
                                    <i class="text-lg ph-bold ph-arrows-clockwise"></i>
                                </button>
                            @elseif($template->suri_template_id)
                                <button type="button" wire:click="inspecionarTemplateSuriEspecifico('{{ $template->suri_template_id }}')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Inspecionar na Suri (GET /api/templates/:id)">
                                    <i class="text-lg ph-fill ph-whatsapp-logo"></i>
                                </button>
                            @endif

                            <button wire:click="preVisualizar({{ $template->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50" title="Pré-visualizar">
                                <i class="text-lg ph ph-eye"></i>
                            </button>

                            <a href="{{ route('templates.edit', $template->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50" title="Editar">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </a>

                            <button wire:click="excluir({{ $template->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50" title="Excluir" onclick="confirm('Excluir este template permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 text-sm">
                        Nenhum template local encontrado.
                    </td>
                </tr>
            @endforelse
        </x-table>

    @else
        {{-- ABA 2: LISTAGEM ESPECÍFICA DOS TEMPLATES RETORNADOS DA SURI (GET {{baseUrl}}/api/templates) --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-emerald-50/40 dark:bg-gray-900/40">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-whatsapp-logo text-emerald-600 text-base"></i>
                        Templates Retornados da Suri (<code class="font-mono text-[11px] text-emerald-700">GET {{ rtrim(env('SURI_API_URL', ''), '/') }}/api/templates</code>)
                    </h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Visualize todos os templates diretamente da Suri, consulte um template específico (<code class="font-mono">/api/templates/:template_id</code>) ou importe para o sistema.
                    </p>
                </div>
                <span class="text-xs font-extrabold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full">
                    {{ count($templatesSuriFiltrados) }} template(s)
                </span>
            </div>

            @if($erroApiSuri)
                <div class="p-4 m-4 rounded-xl bg-red-50 border border-red-200 text-xs font-bold text-red-700">
                    <i class="ph-fill ph-warning-circle mr-1"></i> {{ $erroApiSuri }}
                </div>
            @endif

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-[10px] font-bold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Template ID na Suri</th>
                            <th class="px-4 py-3">Título / Nome</th>
                            <th class="px-4 py-3">Categoria & Idioma</th>
                            <th class="px-4 py-3">Prévia do Corpo (Body)</th>
                            <th class="px-4 py-3 text-center">Status Meta/Suri</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($templatesSuriFiltrados as $tplSuri)
                            @php
                                $suriId    = (string) ($tplSuri['id'] ?? $tplSuri['templateId'] ?? '');
                                $suriTitle = (string) ($tplSuri['title'] ?? $tplSuri['name'] ?? 'Sem título');
                                $suriBody  = (string) ($tplSuri['body'] ?? $tplSuri['text'] ?? '');
                                $suriCat   = (string) ($tplSuri['category'] ?? 'MARKETING');
                                $suriLang  = (string) ($tplSuri['language'] ?? 'pt_BR');
                                $suriSt    = (string) ($tplSuri['status'] ?? $tplSuri['approvalStatus'] ?? 'N/A');
                            @endphp
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-mono font-bold text-purpura-700 dark:text-purpura-300">
                                    {{ $suriId }}
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    {{ $suriTitle }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-bold text-[10px]">{{ $suriCat }}</span>
                                    <span class="text-[10px] font-mono text-gray-400 ml-1">{{ $suriLang }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $suriBody }}">
                                    {{ \Illuminate\Support\Str::limit($suriBody, 65) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $suriSt }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                wire:click="inspecionarTemplateSuriEspecifico('{{ $suriId }}')"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg font-bold text-[11px] transition flex items-center gap-1"
                                                title="GET /api/templates/{{ $suriId }}">
                                            <i class="ph-bold ph-magnifying-glass"></i> Detalhes API
                                        </button>

                                        <button type="button"
                                                wire:click="importarTemplateDaSuriParaLocal('{{ $suriId }}', '{{ addslashes($suriTitle) }}', '{{ addslashes($suriBody) }}', '{{ $suriCat }}')"
                                                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[11px] transition flex items-center gap-1">
                                            <i class="ph-bold ph-download-simple"></i> Importar p/ Sistema
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-xs">
                                    Nenhum template retornado no momento. Clique em <b>"Atualizar da Suri"</b> acima para consultar <code class="font-mono">GET /api/templates</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL DETALHE DE TEMPLATE ESPECÍFICO DA SURI (GET /api/templates/:template_id) --}}
    @if($modalDetalheSuriAberto && $templateSuriDetalhe)
        <div class="fixed inset-0 z-[220] flex items-center justify-center bg-gray-900/65 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ph-fill ph-whatsapp-logo text-emerald-400 text-xl"></i>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider">
                            Retorno de Template Específico (GET /api/templates/:template_id)
                        </h3>
                    </div>
                    <button type="button" wire:click="$set('modalDetalheSuriAberto', false)" class="text-gray-400 hover:text-white">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>
                <div class="p-6 bg-gray-950 text-emerald-300 font-mono text-xs max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <pre class="leading-relaxed">{{ json_encode($templateSuriDetalhe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="button" wire:click="$set('modalDetalheSuriAberto', false)" class="btn btn--secondary btn--medium">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>