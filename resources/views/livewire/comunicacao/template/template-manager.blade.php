<div class="p-6 max-w-7xl mx-auto font-sans space-y-5">
    
    <x-page-header 
        title="Templates de Comunicação" 
        subtitle="Modelos de mensagens para E-mail e WhatsApp (Suri)"
        icon="ph ph-envelope-simple"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <a href="{{ route('suri.dashboard') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                Laboratório Suri
            </a>

            <a href="{{ route('templates.create') }}" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                <i class="ph ph-plus"></i> Novo Template
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button"
                            wire:click="alternarAba('locais')"
                            class="px-3.5 py-1.5 rounded-md text-xs font-medium transition {{ $abaAtiva === 'locais' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                        Templates do Sistema
                    </button>
                    <button type="button"
                            wire:click="alternarAba('suri_remotos')"
                            class="px-3.5 py-1.5 rounded-md text-xs font-medium transition {{ $abaAtiva === 'suri_remotos' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                        Templates na Suri (API)
                    </button>
                </div>

                @if($abaAtiva === 'locais')
                    <div class="flex gap-2">
                        <div class="relative w-64">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar nome, assunto ou ID..." class="w-full pl-8 pr-3 h-9 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-xs focus:ring-1 focus:ring-purpura-500">
                        </div>
                        @if($filtro_busca !== '')
                            <button wire:click="limparFiltros" class="h-9 px-3 text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-lg hover:text-gray-900">
                                Limpar
                            </button>
                        @endif
                    </div>
                @else
                    <div class="flex gap-2">
                        <input wire:model.live.debounce.250ms="buscaSuriRemoto" type="text" placeholder="Filtrar templates da Suri..." class="h-9 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-xs w-60">
                        <button type="button" wire:click="carregarTemplatesDaSuri" wire:loading.attr="disabled" class="btn btn--secondary btn--small bg-white dark:bg-gray-800">
                            <i class="ph ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="carregarTemplatesDaSuri"></i>
                            Atualizar da Suri
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
                @php
                    $qtdAutomacoes = $template->automacoes_count ?? $template->automacoes()->count();
                    $qtdEsteiras   = $template->esteira_etapas_count ?? $template->esteiraEtapas()->count();
                    $totalUsos     = $qtdAutomacoes + $qtdEsteiras;
                    $isAtivo       = (bool) ($template->status ?? true);
                @endphp
                <tr wire:key="tpl-row-{{ $template->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors {{ !$isAtivo ? 'opacity-60 bg-gray-50/40 dark:bg-gray-900/20' : '' }}">
                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-400">
                        #{{ $template->id }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $template->nome }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @php $canalTpl = $template->canal ?? 'email'; @endphp
                        <span class="px-2 py-0.5 rounded text-[11px] font-medium uppercase border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            {{ $canalTpl === 'whatsapp' ? 'WhatsApp' : ($canalTpl === 'ambos' ? 'Ambos' : 'E-mail') }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs text-gray-600 dark:text-gray-300 truncate max-w-sm">{{ $template->assunto }}</div>
                        @if($template->suri_template_id)
                            <span class="inline-block text-[11px] font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                                Suri ID: {{ $template->suri_template_id }}
                            </span>
                        @endif
                    </td>

                    {{-- NOVA COLUNA: EM QUAIS AUTOMAÇÕES / ESTEIRAS ESTÁ SENDO USADO --}}
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @if($totalUsos > 0)
                            <button type="button"
                                    wire:click="verUsoAutomacoes({{ $template->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purpura-50 text-purpura-700 border border-purpura-200 hover:bg-purpura-100 dark:bg-purpura-900/30 dark:text-purpura-300 dark:border-purpura-800 transition cursor-pointer"
                                    title="Clique para visualizar em quais automações e esteiras este template está vinculado">
                                <i class="ph ph-git-branch"></i>
                                <span>{{ $totalUsos }} {{ $totalUsos === 1 ? 'vínculo' : 'vínculos' }}</span>
                            </button>
                        @else
                            <span class="text-xs text-gray-400">Não utilizado</span>
                        @endif
                    </td>

                    {{-- NOVA COLUNA: STATUS INTERNO (ATIVO / INATIVO) --}}
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="inline-flex items-center gap-2">
                            <x-toggle :status="$isAtivo" action="toggleStatus({{ $template->id }})" />
                            <span class="text-[11px] font-medium {{ $isAtivo ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                {{ $isAtivo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if(!$template->suri_template_id && in_array($template->canal ?? 'email', ['whatsapp', 'ambos']))
                                <button type="button"
                                        wire:click="sincronizarSuri({{ $template->id }})"
                                        wire:loading.attr="disabled"
                                        class="p-1.5 text-gray-400 rounded hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-gray-800 transition"
                                        title="Sincronizar com Suri">
                                    <i class="text-base ph ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="sincronizarSuri({{ $template->id }})"></i>
                                </button>
                            @elseif($template->suri_template_id)
                                <button type="button" wire:click="inspecionarTemplateSuriEspecifico('{{ $template->suri_template_id }}')" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Inspecionar JSON na Suri">
                                    <i class="text-base ph ph-code"></i>
                                </button>
                            @endif

                            <button type="button" wire:click="preVisualizar({{ $template->id }})" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Pré-visualizar">
                                <i class="text-base ph ph-eye"></i>
                            </button>

                            <a href="{{ route('templates.edit', $template->id) }}" class="p-1.5 text-gray-400 rounded hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-800 transition" title="Editar">
                                <i class="text-base ph ph-pencil-simple"></i>
                            </a>

                            <button type="button" wire:click="excluir({{ $template->id }})" class="p-1.5 text-gray-400 rounded hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-800 transition" title="Excluir" onclick="confirm('Excluir este template permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-base ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm">
                        Nenhum template local encontrado.
                    </td>
                </tr>
            @endforelse
        </x-table>

    @else
        {{-- ABA 2: TEMPLATES RETORNADOS DA SURI --}}
        @php
            $mapaLocaisPorSuriId = \App\Modules\Comunicacao\Domain\Models\EmailTemplate::whereNotNull('suri_template_id')
                ->get()
                ->keyBy('suri_template_id');
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                        Templates Retornados da Suri (<code class="font-mono text-[11px]">GET /api/templates</code>)
                    </h3>
                </div>
                <span class="text-xs text-gray-500">
                    {{ count($templatesSuriFiltrados) }} template(s)
                </span>
            </div>

            @if($erroApiSuri)
                <div class="p-3.5 m-4 rounded-lg bg-red-50 border border-red-200 text-xs font-medium text-red-700">
                    {{ $erroApiSuri }}
                </div>
            @endif

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-gray-900/40 text-[11px] font-medium uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Template ID</th>
                            <th class="px-4 py-3">Título</th>
                            <th class="px-4 py-3">Categoria</th>
                            <th class="px-4 py-3">Prévia do Corpo</th>
                            <th class="px-4 py-3 text-center">Status Meta</th>
                            <th class="px-4 py-3 text-center">Uso no Sistema</th>
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
                                $tplLocalVinculado = $mapaLocaisPorSuriId->get($suriId);
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                    {{ $suriId }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $suriTitle }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-medium">{{ $suriCat }}</span>
                                    <span class="text-[10px] font-mono text-gray-400 ml-1">{{ $suriLang }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate" title="{{ $suriBody }}">
                                    {{ \Illuminate\Support\Str::limit($suriBody, 55) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded border text-[10px] font-medium uppercase bg-emerald-50 text-emerald-700 border-emerald-200">
                                        {{ $suriSt }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($tplLocalVinculado)
                                        <div class="inline-flex items-center gap-2">
                                            <x-toggle :status="(bool) ($tplLocalVinculado->status ?? true)" action="toggleStatus({{ $tplLocalVinculado->id }})" />
                                            <span class="text-[11px] font-medium {{ ($tplLocalVinculado->status ?? true) ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400' }}">
                                                {{ ($tplLocalVinculado->status ?? true) ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-gray-400">Não importado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                wire:click="inspecionarTemplateSuriEspecifico('{{ $suriId }}')"
                                                class="px-2.5 py-1 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded text-xs font-medium transition">
                                            JSON API
                                        </button>

                                        @if($tplLocalVinculado)
                                            <a href="{{ route('templates.edit', $tplLocalVinculado->id) }}" class="px-2.5 py-1 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded text-xs font-medium hover:bg-purpura-100 transition">
                                                Editar Local
                                            </a>
                                        @else
                                            <button type="button"
                                                    wire:click="importarTemplateDaSuriParaLocal('{{ $suriId }}', '{{ addslashes($suriTitle) }}', '{{ addslashes($suriBody) }}', '{{ $suriCat }}')"
                                                    class="px-2.5 py-1 bg-gray-900 hover:bg-black text-white rounded text-xs font-medium transition">
                                                Importar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-xs">
                                    Nenhum template retornado. Clique em "Atualizar da Suri" acima.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL DETALHE DE TEMPLATE DA SURI --}}
    @if($modalDetalheSuriAberto && $templateSuriDetalhe)
        <x-modal
            title="Retorno da API (GET /api/templates/:template_id)"
            max-width="3xl"
            close-method="$set('modalDetalheSuriAberto', false)">
            <div class="p-4 bg-gray-950 text-gray-200 rounded-lg font-mono text-xs max-h-[65vh] overflow-y-auto custom-scrollbar">
                <pre class="leading-relaxed">{{ json_encode($templateSuriDetalhe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </x-modal>
    @endif

    {{-- INDICADOR VISUAL DE INTEGRAÇÃO SURI NO CANTO INFERIOR DIREITO --}}
    <template x-teleport="body">
        <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none font-sans">
            <div wire:loading.flex
                 wire:target="sincronizarSuri, carregarTemplatesDaSuri, importarTemplateDaSuriParaLocal, inspecionarTemplateSuriEspecifico"
                 class="pointer-events-auto bg-gray-900 text-white p-4 rounded-lg shadow-2xl border border-gray-700 items-center gap-3.5">
                <div class="w-9 h-9 rounded-lg bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                    <i class="ph ph-spinner animate-spin text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white">Comunicando com a API da Suri...</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Sincronizando dados do template no WhatsApp.</p>
                </div>
            </div>

            @if(!empty($feedbackSuri))
                <div class="pointer-events-auto bg-white dark:bg-gray-800 p-4 rounded-lg shadow-2xl border-l-4 {{ $feedbackSuri['sucesso'] ? 'border-l-emerald-500 border border-gray-200 dark:border-gray-700' : 'border-l-red-600 border border-red-200 dark:border-red-800' }} flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $feedbackSuri['sucesso'] ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                            <i class="ph-fill {{ $feedbackSuri['sucesso'] ? 'ph-check-circle' : 'ph-warning-circle' }} text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $feedbackSuri['titulo'] }}</h4>
                                <span class="px-1.5 py-0.5 text-[10px] font-mono rounded {{ $feedbackSuri['sucesso'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                    HTTP {{ $feedbackSuri['http'] }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 break-words leading-relaxed">
                                {{ $feedbackSuri['detalhe'] }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="fecharFeedbackSuri" class="text-gray-400 hover:text-gray-700 p-1">
                        <i class="ph ph-x text-sm"></i>
                    </button>
                </div>
            @endif
        </div>
    </template>
</div>