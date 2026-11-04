<div class="p-6 max-w-[1400px] mx-auto font-sans">

    <x-breadcrumb :items="[
        ['label' => 'Formulários', 'url' => route('formularios.index')],
        ['label' => $formulario->titulo, 'url' => route('formularios.show', $formulario->id)],
        ['label' => 'Ficha de Resposta #' . str_pad($resposta->id, 5, '0', STR_PAD_LEFT), 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha de Resposta" 
        subtitle="Submetida em {{ $resposta->created_at->format('d/m/Y \à\s H:i') }}"
        backUrl="{{ route('formularios.show', $formulario->id) }}"
        backLabel="Voltar às Respostas"
        avatarInitials="{{ strtoupper(substr($nomeParticipante, 0, 2)) }}"
        itemName="{{ $nomeParticipante }}"
        itemDescription="{{ $cpfParticipante ? 'CPF: ' . $cpfParticipante : '' }}{{ ($cpfParticipante && $emailParticipante) ? ' • ' : '' }}{{ $emailParticipante ?: 'Formulário: ' . $formulario->titulo }}">
        
        <x-slot name="badge">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="tag tag--small tag--filled tag--pistache border-0 uppercase">
                    Protocolo #{{ str_pad($resposta->id, 6, '0', STR_PAD_LEFT) }}
                </span>

                <button type="button"
                        wire:click="baixarFichaRespostaPdf({{ $resposta->id }})"
                        wire:loading.attr="disabled"
                        class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none shadow-xs flex items-center gap-1.5">
                    <i class="ph-bold ph-file-pdf text-sm"></i>
                    <span>Ficha de Respostas (PDF)</span>
                </button>
            </div>
        </x-slot>

        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Protocolo Oficial</span>
            <span class="block text-sm font-mono font-bold text-gray-900 mt-1">#{{ str_pad($resposta->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Data e Hora de Envio</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $resposta->created_at->format('d/m/Y \à\s H:i') }}</span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Formulário Vinculado</span>
            <span class="block text-sm font-bold text-gray-900 mt-1 truncate" title="{{ $formulario->titulo }}">{{ $formulario->titulo }}</span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Perguntas Estruturadas</span>
            <span class="block text-sm font-bold text-purpura-600 mt-1">{{ $totalPerguntas }} campo(s)</span>
        </div>
    </x-details-card>

    {{-- CONTEÚDO EM GRID NO MESMO PADRÃO DA FICHA DE INSCRIÇÃO --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-2">
            <div class="card p-6 md:p-8 space-y-10">
                @forelse($camposPorEtapa as $etapaNum => $campos)
                    <section>
                        <div class="flex justify-between items-center mb-5 border-b border-gray-100 dark:border-gray-700 pb-2">
                            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                                <i class="ph-fill ph-list-dashes text-lg text-purpura-500"></i> Etapa {{ $etapaNum }} — Respostas Registradas
                            </h3>
                            <span class="tag tag--small tag--outline tag--neutral">{{ count($campos) }} item(ns)</span>
                        </div>

                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-6">
                            @foreach($campos as $campo)
                                @php 
                                    $valor = $respostasSalvas[$campo->name] ?? null; 
                                    $temResposta = !empty($valor) || $valor === '0' || $valor === 0;
                                    $cfg = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                                    $isFullWidth = in_array($campo->tipo, ['textarea', 'matriz', 'signature', 'file', 'social']) || ($campo->largura ?? 12) == 12;
                                @endphp

                                <div class="{{ $isFullWidth ? 'md:col-span-2' : '' }}">
                                    <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                        @if($campo->tipo === 'signature')
                                            {{ ($cfg['tipo_assinante'] ?? $campo->subtipo) === 'responsavel' ? 'Assinatura do Responsável' : ($campo->label ?: 'Assinatura do Participante') }}
                                        @else
                                            {{ strip_tags($campo->label) }}
                                        @endif
                                    </dt>

                                    @if(!$temResposta)
                                        <dd class="text-xs text-gray-400 italic">Não preenchido ou oculto por regra condicional</dd>

                                    @elseif($campo->tipo === 'signature' || (is_string($valor) && str_starts_with($valor, 'data:image/')))
                                        <dd class="inline-flex flex-col items-center p-3 bg-white border border-gray-200 rounded-xl shadow-xs mt-1">
                                            <img src="{{ $valor }}" alt="Assinatura" class="max-h-28 w-auto object-contain">
                                            <div class="w-full border-t border-gray-100 mt-2 pt-1.5 flex items-center justify-between gap-4 text-[10px] text-gray-500">
                                                <span class="font-bold uppercase">Assinatura Digital</span>
                                                <a href="{{ $valor }}" download="assinatura-{{ $resposta->id }}.png" class="text-purpura-600 hover:underline font-bold flex items-center gap-1">
                                                    <i class="ph-bold ph-download-simple"></i> Baixar PNG
                                                </a>
                                            </div>
                                        </dd>

                                    @elseif($campo->tipo === 'file')
                                        @php $arquivos = is_array($valor) ? $valor : [$valor]; @endphp
                                        <dd class="flex flex-wrap gap-2 mt-1">
                                            @foreach(array_filter($arquivos) as $arqPath)
                                                <a href="{{ asset('storage/' . $arqPath) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs font-bold text-purpura-600 hover:border-purpura-400 shadow-2xs transition">
                                                    <i class="ph-bold ph-paperclip text-sm"></i>
                                                    <span class="truncate max-w-[220px]">{{ basename($arqPath) }}</span>
                                                    <i class="ph-bold ph-arrow-square-out"></i>
                                                </a>
                                            @endforeach
                                        </dd>

                                    @elseif($campo->tipo === 'matriz' && is_array($valor))
                                        @php $linhas = $cfg['linhas'] ?? []; @endphp
                                        <dd class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                                            <table class="min-w-full text-xs">
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    @foreach($linhas as $idx => $linhaTexto)
                                                        <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-900/50' : 'bg-white dark:bg-gray-800' }}">
                                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400 font-medium w-1/2 border-r border-gray-100 dark:border-gray-700">{{ $linhaTexto }}</td>
                                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-200 font-bold w-1/2">{{ $valor[$idx] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </dd>

                                    @elseif($campo->tipo === 'social' && is_array($valor))
                                        <dd class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-1">
                                            @foreach($valor as $rede => $userRede)
                                                @continue(empty($userRede))
                                                <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                    <i class="ph-fill ph-{{ strtolower($rede) }}-logo text-lg text-purpura-600"></i>
                                                    <div class="min-w-0">
                                                        <span class="block text-[10px] font-bold uppercase text-gray-400">{{ $rede }}</span>
                                                        <span class="block text-xs font-bold text-gray-900 dark:text-white truncate">{{ $userRede }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </dd>

                                    @else
                                        <dd class="text-sm font-bold text-gray-900 dark:text-white break-words">
                                            {{ is_array($valor) ? implode(', ', array_filter($valor)) : $valor }}
                                        </dd>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @empty
                    <p class="text-sm text-gray-500">Nenhuma pergunta configurada para este formulário.</p>
                @endforelse
            </div>
        </div>

        {{-- COLUNA LATERAL DIREITA: COMPROVANTE / FICHA PDF --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="card p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="w-10 h-10 rounded-xl bg-purpura-50 text-purpura-600 flex items-center justify-center text-xl border border-purpura-100">
                        <i class="ph-fill ph-file-pdf"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Comprovante Oficial</h3>
                        <p class="text-xs text-gray-500">Mesmo documento gerado ao final do preenchimento.</p>
                    </div>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    Exporte a ficha completa desta resposta em formato PDF (padrão A4 com protocolo, respostas e termo de ciência/assinatura).
                </p>

                <button type="button"
                        wire:click="baixarFichaRespostaPdf({{ $resposta->id }})"
                        wire:loading.attr="disabled"
                        class="btn btn--primary btn--medium w-full bg-purpura-600 hover:bg-purpura-700 border-none justify-center">
                    <i class="ph-bold ph-download-simple text-base"></i> Baixar Ficha de Respostas (PDF)
                </button>
            </div>
        </div>
    </div>
</div>