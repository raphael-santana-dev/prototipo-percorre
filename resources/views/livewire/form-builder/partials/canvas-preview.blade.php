<div class="flex-1 flex flex-col h-full overflow-hidden relative z-10">
    @php
        $previewBgUrl = null;
        if ($bg_image_upload) {
            try { $previewBgUrl = $bg_image_upload->temporaryUrl(); } catch (\Exception $e) {}
        } elseif (!empty($formSettings['bg_image'])) {
            $previewBgUrl = asset($formSettings['bg_image']);
        }
        
        $formBgColor   = $formSettings['bg_color'] ?? '#f3f4f6';
        $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
        $bgSize        = $formSettings['bg_size'] ?? 'cover';
        $formWidth     = $formSettings['form_width'] ?? 'max-w-4xl';
        $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Exatamente o efeito de transparência original que funcionava perfeitamente
        $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
        $textoForm = $isTranslucent ? 'text-gray-900 drop-shadow-sm' : 'text-gray-900';

        if (!function_exists('formatRichFormText')) {
            function formatRichFormText($text) {
                if (empty($text)) return '';
                if ($text !== strip_tags($text)) {
                    return strip_tags($text, '<strong><b><em><i><u><s><del><span><br><p><ul><ol><li><a>');
                }
                $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                $text = preg_replace('/\*(.*?)\*/s', '<strong>$1</strong>', $text);
                $text = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $text);
                $text = preg_replace('/\~(.*?)\~/s', '<del>$1</del>', $text);
                return nl2br($text);
            }
        }
    @endphp

    @if($previewBgUrl)
        <div class="absolute inset-0 z-0 bg-center bg-no-repeat pointer-events-none" style="background-image: url('{{ $previewBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="absolute inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    {{-- BARRA DE PÁGINAS / ETAPAS --}}
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-2.5 flex items-center gap-2 overflow-x-auto custom-scrollbar shrink-0 relative z-20">
        <button type="button" wire:click="adicionarEtapa" class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 bg-purpura-50 border border-purpura-200 rounded-lg text-xs font-bold text-purpura-700 hover:bg-purpura-100 transition">
            <i class="ph-bold ph-plus"></i> Nova Página
        </button>
        <div class="w-px h-5 bg-gray-200 mx-1 shrink-0"></div>
        
        @foreach($etapasDisponiveis as $et)
            <div class="shrink-0 flex items-center bg-white border {{ $etapa == $et->numero ? 'border-purpura-500 ring-1 ring-purpura-500' : 'border-gray-200' }} rounded-lg text-xs shadow-sm overflow-hidden group transition-all">
                <button type="button" wire:click="$set('etapa', {{ $et->numero }})" class="px-2.5 py-1.5 border-r border-gray-100 font-bold {{ $etapa == $et->numero ? 'bg-purpura-600 text-white' : 'bg-gray-50 text-gray-500' }}">
                    #{{ $et->numero }}
                </button>
                <input type="text" 
                       wire:change="atualizarNomeEtapa({{ $et->id }}, $event.target.value)" 
                       value="{{ $et->nome }}" 
                       class="w-28 !border-none !shadow-none focus:!ring-0 text-xs font-bold text-gray-700 px-2.5 py-1.5 bg-transparent">
                @if($etapasDisponiveis->count() > 1)
                    <button type="button" wire:click="excluirEtapa({{ $et->id }})" wire:confirm="Excluir esta página? As páginas seguintes serão renumeradas automaticamente." class="px-2 py-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 transition border-l border-gray-100">
                        <i class="ph-bold ph-trash"></i>
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    {{-- BARRA DE MOVIMENTAÇÃO EM LOTE (MULTI-SELECT) --}}
    @if(count($camposSelecionados) > 0)
        <div class="bg-gray-900 text-white px-5 py-2.5 border-b border-gray-800 flex flex-wrap items-center justify-between gap-3 shrink-0 relative z-30 shadow-md">
            <div class="flex items-center gap-2.5">
                <span class="px-2 py-0.5 bg-purpura-600 rounded text-xs font-extrabold">{{ count($camposSelecionados) }}</span>
                <span class="text-xs font-bold">input(s) selecionado(s)</span>
                <button type="button" wire:click="selecionarTodosDaEtapa({{ $etapa }})" class="text-[11px] font-semibold text-purpura-300 hover:underline ml-2">
                    Selecionar/Desmarcar Página #{{ $etapa }}
                </button>
                <button type="button" wire:click="limparSelecaoCampos" class="text-[11px] text-gray-400 hover:text-white ml-2">
                    Limpar seleção
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="moverSelecionadosNaMesmaEtapa('topo')" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-[11px] font-bold flex items-center gap-1 border border-gray-700 transition">
                    <i class="ph-bold ph-arrow-line-up text-purpura-400"></i> Início da Pág.
                </button>
                <button type="button" wire:click="moverSelecionadosNaMesmaEtapa('fim')" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-[11px] font-bold flex items-center gap-1 border border-gray-700 transition">
                    <i class="ph-bold ph-arrow-line-down text-purpura-400"></i> Fim da Pág.
                </button>

                @if($etapasDisponiveis->count() > 1)
                    <div class="flex items-center gap-1.5 pl-2 border-l border-gray-700">
                        <select wire:model="etapaDestinoLote" class="!bg-gray-800 !border-gray-700 !text-white text-xs rounded-lg py-1 pl-2.5 pr-7 focus:ring-purpura-500">
                            <option value="">Mover para página...</option>
                            @foreach($etapasDisponiveis as $etDestino)
                                <option value="{{ $etDestino->numero }}">Página #{{ $etDestino->numero }} - {{ $etDestino->nome }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="moverCamposSelecionadosParaEtapa" class="px-3 py-1 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                            Mover Selecionados
                        </button>
                    </div>
                @endif

                <div class="pl-2 border-l border-gray-700">
                    <button type="button" wire:click="excluirCamposSelecionados" wire:confirm="Tem certeza de que deseja excluir permanentemente todos os inputs selecionados?" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1">
                        <i class="ph-bold ph-trash"></i> Excluir Selecionados
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- CANVAS DE PREVIEW (SCROLLÁVEL COM O EFEITO TRANSLÚCIDO ORIGINAL) --}}
    <div class="relative flex-1 w-full overflow-y-auto custom-scrollbar p-4 md:p-8 flex flex-col items-center z-10"
         @click="if ($event.target === $el && ($wire.campoId || $wire.painelCampoAtivo)) { $wire.cancelarEdicao(); }">
        <div class="relative z-10 w-full {{ $formWidth }} {{ $cardClass }} p-6 md:p-10 rounded-xl border-t-4 border-purpura-600 transition-all duration-300"
             style="{{ !empty($formSettings['form_bg_color']) && !$isTranslucent ? 'background-color: ' . $formSettings['form_bg_color'] . ';' : '' }}{{ !empty($formSettings['form_text_color']) ? 'color: ' . $formSettings['form_text_color'] . ';' : '' }}"
             @click="if (!$event.target.closest('[data-campo-builder-item]') && !$event.target.closest('button, a, input, select, textarea, label') && ($wire.campoId || $wire.painelCampoAtivo)) { $wire.cancelarEdicao(); }">
            
            <div class="mb-8 border-b border-gray-200 pb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold {{ $textoForm }}">{{ $contextoNome }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Clique num bloco abaixo para editar ou marque as caixas para mover múltiplos inputs.</p>
                </div>
            </div>

            @foreach($etapasDisponiveis as $etapaObj)
                @php
                    $numEtapa = (int) $etapaObj->numero;
                    $camposDaEtapa = $camposPorEtapa->get($numEtapa, collect());
                    $nomeEtapaPreview = $etapaObj->nome ?: "Etapa {$numEtapa}";

                    $gruposRender = [];
                    if ($numEtapa === 1 && $contextoTipo === 'ciclo') {
                        $camposTopo = $camposDaEtapa->filter(function($c) {
                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                            return !empty($cfg['exibir_no_topo']);
                        });
                        $camposRodape = $camposDaEtapa->filter(function($c) {
                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                            return empty($cfg['exibir_no_topo']);
                        });
                        if ($camposTopo->count() > 0) $gruposRender[] = ['titulo' => 'Informações Preliminares', 'campos' => $camposTopo];
                        $gruposRender[] = ['titulo' => 'placeholder_dados_pessoais', 'campos' => collect()];
                        if ($camposRodape->count() > 0) $gruposRender[] = ['titulo' => 'Informações Adicionais', 'campos' => $camposRodape];
                    } else {
                        $gruposRender[] = ['titulo' => null, 'campos' => $camposDaEtapa];
                    }
                @endphp

                <div class="mb-10" wire:key="grupo-etapa-{{ $numEtapa }}">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-purpura-600 rounded-full shadow-sm">{{ $numEtapa }}</span>
                        <h3 class="text-lg font-bold {{ $textoForm }}">{{ $nomeEtapaPreview }}</h3>
                        @if($camposDaEtapa->count() > 0)
                            <button type="button" wire:click="selecionarTodosDaEtapa({{ $numEtapa }})" class="text-[10px] font-bold text-purpura-600 hover:underline px-2 py-0.5 rounded bg-purpura-50 border border-purpura-200">
                                Selecionar página
                            </button>
                        @endif
                        <div class="flex-1 h-px ml-2 bg-gray-200"></div>
                    </div>

                    @foreach($gruposRender as $grupo)
                        @if($grupo['titulo'] === 'placeholder_dados_pessoais')
                            @php
                                $showPersonal = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_personal'] ?? true, FILTER_VALIDATE_BOOLEAN);
                                $showAddress  = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_address'] ?? true, FILTER_VALIDATE_BOOLEAN);
                                $showCourse   = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_course'] ?? true, FILTER_VALIDATE_BOOLEAN);
                            @endphp
                            @if($showPersonal || $showAddress || $showCourse)
                                <div class="w-full my-5">
                                    <div class="rounded-xl border border-indigo-200/80 bg-white/90 p-5 shadow-2xs">
                                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3.5 mb-4 border-b border-gray-100">
                                            <div>
                                                <h4 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider">
                                                    Campos Padrões e Fixos da 1ª Página
                                                </h4>
                                                <p class="text-[11px] text-gray-500">
                                                    Estes blocos nativos são carregados automaticamente para o candidato nesta etapa inicial.
                                                </p>
                                            </div>

                                            @if($isCicloCustomizado)
                                                <button type="button"
                                                        @click="$wire.set('drawerFormSettingsAberto', true)"
                                                        class="px-2.5 py-1 text-[10px] font-bold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 rounded-lg transition">
                                                    Personalizar Fixos
                                                </button>
                                            @else
                                                <span class="px-2.5 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md uppercase tracking-wider">
                                                    Bloco Nativo Obrigatório
                                                </span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pointer-events-none">
                                            @if($showPersonal)
                                                <div class="p-3.5 rounded-lg bg-gray-50/80 border border-gray-200/90 flex flex-col justify-between">
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <span class="text-xs font-bold text-gray-800">1. Dados Pessoais</span>
                                                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">Ativo</span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-500 leading-relaxed">Nome Completo, Nome Social, CPF, Data de Nascimento, E-mail, Celular e PcD.</p>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($showAddress)
                                                <div class="p-3.5 rounded-lg bg-gray-50/80 border border-gray-200/90 flex flex-col justify-between">
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <span class="text-xs font-bold text-gray-800">2. Endereço Residencial</span>
                                                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">ViaCEP</span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-500 leading-relaxed">CEP, Logradouro, Número, Complemento, Bairro, Cidade, UF e Região.</p>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($showCourse)
                                                <div class="p-3.5 rounded-lg bg-gray-50/80 border border-gray-200/90 flex flex-col justify-between">
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <span class="text-xs font-bold text-gray-800">3. Unidade / Curso / Turno</span>
                                                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">Ativo</span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-500 leading-relaxed">Seleção em cascata sincronizada com a oferta de vagas e faixa etária.</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            @if($grupo['titulo'])
                                <div class="mb-3 mt-2 border-b border-gray-200 pb-1.5 w-full">
                                    <h4 class="text-sm font-bold {{ $textoForm }}">{{ $grupo['titulo'] }}</h4>
                                </div>
                            @endif

                            <div class="grid grid-cols-12 gap-x-5 gap-y-4 w-full">
                                @php $colunasUsadasLinha = 0; @endphp
                                @foreach($grupo['campos'] as $c)
                                    @php 
                                        $isActive = $campoId == $c->id; 
                                        $isSelecionadoLote = in_array((string) $c->id, $camposSelecionados, true);
                                        $larguraSolicitada = (int) $c->largura;

                                        if ($larguraSolicitada === 0) {
                                            $larguraEfetiva = ($colunasUsadasLinha === 0) ? 12 : (12 - $colunasUsadasLinha);
                                            $colunasUsadasLinha = 0;
                                        } else {
                                            if ($colunasUsadasLinha + $larguraSolicitada > 12) $colunasUsadasLinha = 0;
                                            $larguraEfetiva = $larguraSolicitada;
                                            $colunasUsadasLinha = ($colunasUsadasLinha + $larguraSolicitada) % 12;
                                        }

                                        $colSpan = "col-span-12 md:col-span-{$larguraEfetiva}";
                                        $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                        $layoutOpcoes = ($isActive && isset($configuracoes['layout_opcoes'])) ? $configuracoes['layout_opcoes'] : ($cfg['layout_opcoes'] ?? 'horizontal');

                                        $dicaModoPv = $cfg['dica_modo'] ?? 'nenhum';
                                        $tooltipTextoPv = null;
                                        $tooltipTriggerPv = 'hover';
                                        $legendaTextoPv = null;

                                        if ($dicaModoPv === 'legenda') {
                                            $legendaTextoPv = $cfg['dica_1_texto'] ?? null;
                                        } elseif ($dicaModoPv === 'tooltip') {
                                            $tooltipTextoPv = $cfg['dica_1_texto'] ?? null;
                                            $tooltipTriggerPv = $cfg['dica_1_trigger'] ?? 'hover';
                                        } elseif ($dicaModoPv === 'ambos') {
                                            if (($cfg['dica_1_tipo'] ?? 'legenda') === 'tooltip') {
                                                $tooltipTextoPv = $cfg['dica_1_texto'] ?? null;
                                                $tooltipTriggerPv = $cfg['dica_1_trigger'] ?? 'hover';
                                                $legendaTextoPv = $cfg['dica_2_texto'] ?? null;
                                            } else {
                                                $legendaTextoPv = $cfg['dica_1_texto'] ?? null;
                                                $tooltipTextoPv = $cfg['dica_2_texto'] ?? null;
                                                $tooltipTriggerPv = $cfg['dica_2_trigger'] ?? 'hover';
                                            }
                                        }
                                    @endphp

                                    <div wire:key="campo-{{ $c->id }}"
                                         data-campo-builder-item="true"
                                         class="{{ $colSpan }} relative group rounded-lg transition-all duration-200 {{ $isActive ? 'ring-2 ring-purpura-500 shadow-md p-3.5 bg-purpura-50/20' : ($isSelecionadoLote ? 'ring-2 ring-indigo-500 bg-indigo-50/30 p-3' : 'border border-transparent hover:border-gray-200 p-2 -mx-2 cursor-pointer hover:bg-gray-50/60') }}"
                                         wire:click.stop="editar({{ $c->id }})">
                                        
                                        <div class="absolute right-2 -top-3 {{ $isActive ? 'flex' : 'hidden group-hover:flex' }} gap-1 bg-white border border-gray-200 shadow-md rounded-md overflow-hidden z-20 text-gray-600">
                                            <button type="button" wire:click.stop="editar({{ $c->id }})" class="p-1.5 hover:bg-purpura-50 hover:text-purpura-600 transition" title="Editar Campo"><i class="ph ph-pencil-simple text-sm"></i></button>
                                            <button type="button" wire:click.stop="excluir({{ $c->id }})" wire:confirm="Tem certeza de que deseja excluir este campo?" class="p-1.5 hover:bg-red-50 hover:text-red-600 transition border-l border-gray-100" title="Excluir Campo"><i class="ph ph-trash text-sm"></i></button>
                                        </div>

                                        <div class="relative z-10">
                                            <div class="flex justify-between items-start mb-1.5 gap-2">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <input type="checkbox"
                                                           wire:model.live="camposSelecionados"
                                                           value="{{ (string) $c->id }}"
                                                           @click.stop
                                                           class="w-4 h-4 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500 cursor-pointer shrink-0"
                                                           title="Selecionar para mover em lote">

                                                    @if(!in_array($c->tipo, ['html', 'divider', 'media', 'spacer', 'link']))
                                                        <label class="block text-xs font-bold text-gray-800 cursor-pointer">
                                                            @if($c->tipo === 'signature')
                                                                {!! formatRichFormText($c->label ?: (($cfg['tipo_assinante'] ?? $c->subtipo) === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura')) !!}
                                                            @else
                                                                {!! formatRichFormText($c->label) !!}
                                                            @endif
                                                            @if($c->obrigatorio) <span class="text-red-500">*</span> @endif
                                                        </label>

                                                        @if(!empty($tooltipTextoPv))
                                                            <div x-data="{ showTip: false }" class="relative inline-flex items-center" @click.stop>
                                                                <button type="button"
                                                                        @if($tooltipTriggerPv === 'hover')
                                                                            @mouseenter="showTip = true" @mouseleave="showTip = false"
                                                                        @else
                                                                            @click="showTip = !showTip" @click.away="showTip = false"
                                                                        @endif
                                                                        class="text-purpura-600 hover:text-purpura-800 focus:outline-none inline-flex items-center justify-center w-4 h-4 rounded-full bg-purpura-50 border border-purpura-200"
                                                                        title="Dica">
                                                                    <i class="ph-bold ph-question text-[10px]"></i>
                                                                </button>
                                                                <div x-show="showTip" x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl z-50 leading-relaxed">
                                                                    {!! formatRichFormText($tooltipTextoPv) !!}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                                <span class="text-[10px] font-mono font-bold bg-white text-gray-500 px-1.5 py-0.5 rounded border border-gray-200 shrink-0">#{{ $c->ordem }}</span>
                                            </div>

                                            {{-- PREVIEW FIEL DE TODOS OS TIPOS E SUBTIPOS --}}
                                            @if($c->tipo === 'text')
                                                <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between gap-2 shadow-sm pointer-events-none">
                                                    <div class="flex items-center gap-2 truncate">
                                                        @if($c->subtipo == 'email') <i class="ph ph-envelope-simple text-base"></i> <span>exemplo@email.com</span>
                                                        @elseif($c->subtipo == 'date') <i class="ph ph-calendar-blank text-base"></i> <span>dd/mm/aaaa</span>
                                                        @elseif($c->subtipo == 'time') <i class="ph ph-clock text-base"></i> <span>00:00</span>
                                                        @elseif($c->subtipo == 'datetime-local') <i class="ph ph-calendar-check text-base"></i> <span>dd/mm/aaaa 00:00</span>
                                                        @elseif($c->subtipo == 'number') <i class="ph ph-hash text-base"></i> <span>Número...</span>
                                                        @elseif($c->subtipo == 'money') <i class="ph ph-currency-circle-dollar text-base text-emerald-600"></i> <span>R$ 0,00</span>
                                                        @elseif($c->subtipo == 'tel') <i class="ph ph-device-mobile text-base text-blue-500"></i> <span>(00) 00000-0000</span>
                                                        @elseif($c->subtipo == 'cpf') <i class="ph ph-identification-card text-base text-indigo-500"></i> <span>000.000.000-00</span>
                                                        @elseif($c->subtipo == 'cnpj') <i class="ph ph-buildings text-base text-amber-600"></i> <span>00.000.000/0000-00</span>
                                                        @elseif($c->subtipo == 'cep') <i class="ph ph-map-pin-line text-base text-rose-500"></i> <span>00000-000</span>
                                                        @else <i class="ph ph-text-t text-base"></i> <span>Texto curto...</span> @endif
                                                    </div>
                                                    @if($c->subtipo === 'cep' && !empty($cfg['viacep']))
                                                        <span class="px-1.5 py-0.5 bg-rose-50 text-rose-600 border border-rose-200 rounded text-[9px] font-bold shrink-0">ViaCEP Ativo</span>
                                                    @elseif($c->subtipo === 'date' && !empty($cfg['calcular_idade']))
                                                        <span class="px-1.5 py-0.5 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded text-[9px] font-bold shrink-0"><i class="ph-fill ph-cake"></i> Calcula Idade</span>
                                                    @elseif(!empty($cfg['viacep_parent']))
                                                        <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold shrink-0">Auto (ViaCEP)</span>
                                                    @endif
                                                </div>

                                            @elseif($c->tipo === 'signature')
                                                <div class="w-full border-2 border-dashed border-indigo-200 rounded-lg p-4 bg-white text-center pointer-events-none">
                                                    <i class="ph-duotone ph-signature text-3xl text-indigo-500 mb-1"></i>
                                                    <div class="w-48 max-w-full mx-auto border-b border-gray-400 my-2"></div>
                                                    <p class="text-[11px] font-bold text-gray-600">
                                                        {{ ($cfg['tipo_assinante'] ?? $c->subtipo) === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura' }}
                                                    </p>
                                                </div>

                                            @elseif($c->tipo === 'link')
                                                <div class="w-full p-3.5 bg-blue-50/60 border border-blue-200 rounded-lg flex items-center justify-between gap-3 pointer-events-none">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <div class="w-9 h-9 rounded-lg bg-white border border-blue-200 flex items-center justify-center shrink-0 text-blue-600">
                                                            <i class="ph-bold ph-link-simple text-xl"></i>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-bold text-gray-900 truncate">{!! formatRichFormText($c->label ?: ($cfg['arquivo_nome'] ?? 'Link')) !!}</p>
                                                            <p class="text-[10px] text-gray-500 truncate">{{ $cfg['url'] ?? 'URL não definida' }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                            @elseif($c->tipo === 'file')
                                                <div class="w-full border-2 border-dashed border-gray-300 rounded-lg p-3 bg-gray-50/60 text-center pointer-events-none">
                                                    <i class="ph ph-cloud-arrow-up text-xl text-purpura-500"></i>
                                                    <p class="text-xs font-bold text-gray-700">Carregar Anexo</p>
                                                </div>

                                            @elseif($c->tipo === 'select')
                                                <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between shadow-sm pointer-events-none">
                                                    <span>Selecione...</span><i class="ph ph-caret-down text-gray-500"></i>
                                                </div>

                                            @elseif($c->tipo === 'scale')
                                                @php $maxScale = $cfg['escala_maxima'] ?? 10; @endphp
                                                <div class="mt-1 pointer-events-none">
                                                    <div class="flex w-full border border-gray-300 rounded-md overflow-hidden bg-white">
                                                        @for($i = 0; $i <= $maxScale; $i++)
                                                            <div class="flex-1 py-1.5 text-center border-r last:border-r-0 border-gray-200 text-gray-600 text-[11px] font-bold">{{ $i }}</div>
                                                        @endfor
                                                    </div>
                                                </div>

                                            @elseif($c->tipo === 'radio' || $c->tipo === 'check')
                                                <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-1.5' : 'flex-wrap gap-3' }} mt-1 pointer-events-none">
                                                    <div class="flex items-center gap-1.5 text-gray-600 text-xs">
                                                        <div class="w-3.5 h-3.5 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 1
                                                    </div>
                                                    <div class="flex items-center gap-1.5 text-gray-600 text-xs">
                                                        <div class="w-3.5 h-3.5 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 2
                                                    </div>
                                                </div>

                                            @elseif($c->tipo === 'system')
                                                <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between shadow-sm pointer-events-none">
                                                    <span class="flex items-center gap-1.5"><i class="ph ph-database text-purpura-400"></i> Selecione {{ ucfirst($c->subtipo) }}...</span><i class="ph ph-caret-down text-gray-500"></i>
                                                </div>

                                            @elseif($c->tipo === 'matriz')
                                                <div class="w-full p-2 bg-white border border-gray-200 rounded text-xs text-gray-500 font-bold">Matriz de Avaliação</div>

                                            @elseif($c->tipo === 'html')
                                                <div class="text-gray-800">
                                                    @if($c->subtipo === 'h1') <h1 class="text-2xl font-extrabold">{!! formatRichFormText($c->label) !!}</h1>
                                                    @elseif($c->subtipo === 'h2') <h2 class="text-xl font-bold">{!! formatRichFormText($c->label) !!}</h2>
                                                    @elseif($c->subtipo === 'h3') <h3 class="text-lg font-bold">{!! formatRichFormText($c->label) !!}</h3>
                                                    @elseif($c->subtipo === 'p') <div class="text-xs leading-relaxed">{!! formatRichFormText($c->label) !!}</div>
                                                    @elseif($c->subtipo === 'link') <span class="text-purpura-600 font-bold underline text-xs">{!! formatRichFormText($c->label) !!}</span>
                                                    @elseif($c->subtipo === 'info_card') 
                                                        <div class="p-2.5 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                                                            <p class="font-bold text-xs">{!! formatRichFormText($c->label) !!}</p>
                                                            @if(!empty($cfg['descricao']))
                                                                <p class="text-[11px] mt-1">{!! formatRichFormText($cfg['descricao']) !!}</p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                            @elseif($c->tipo === 'divider')
                                                <hr class="border-t-2 border-dashed border-gray-300 my-2">

                                            @elseif($c->tipo === 'spacer')
                                                <div class="w-full h-10 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50 text-gray-400 text-[11px] font-semibold select-none">
                                                    Espaço ({{ $c->largura == 0 ? "Restante" : "{$c->largura}/12" }})
                                                </div>

                                            @elseif($c->tipo === 'media')
                                                <div class="w-full bg-gray-50 rounded-md p-3 text-center border border-gray-200 pointer-events-none text-gray-500">
                                                    <i class="ph ph-{{ $c->subtipo == 'video' ? 'video-camera' : 'image' }} text-xl"></i>
                                                    <p class="text-[11px] font-bold">Mídia ({{ ucfirst($c->subtipo) }})</p>
                                                </div>

                                            @elseif($c->tipo === 'social')
                                                @php $redesPreview = $cfg['redes_permitidas'] ?? ['instagram']; @endphp
                                                <div class="flex flex-col gap-1.5 mt-1 pointer-events-none">
                                                    @foreach($redesPreview as $rede)
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-7 h-7 rounded bg-gray-100 flex items-center justify-center text-gray-600 border border-gray-200"><i class="text-sm ph-fill ph-{{ strtolower($rede) }}-logo"></i></div>
                                                            <div class="flex-1 h-7 bg-white border border-gray-200 rounded"></div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($c->tipo === 'rating')
                                                <div class="flex gap-1 text-xl text-yellow-400 pointer-events-none">
                                                    <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-gray-300"></i><i class="ph ph-star text-gray-300"></i>
                                                </div>
                                            @endif

                                            @if(!empty($legendaTextoPv))
                                                <div class="mt-1.5 text-[11px] text-gray-500 leading-snug">
                                                    {!! formatRichFormText($legendaTextoPv) !!}
                                                </div>
                                            @endif

                                            <div class="mt-2 flex flex-wrap gap-1.5 items-center">
                                                @if(!in_array($c->tipo, ['html', 'divider', 'social', 'media', 'spacer', 'link']))
                                                    <span class="text-[9px] bg-gray-100 border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-mono font-bold"><i class="ph ph-database"></i> {{ $c->name }}</span>
                                                @endif
                                                @if($c->depende_de || !empty($cfg['condicoes_visibilidade']['grupos']))
                                                    <span class="inline-flex items-center gap-1 bg-yellow-100 border border-yellow-200 text-yellow-800 text-[9px] px-1.5 py-0.5 rounded font-bold">
                                                        <i class="ph-fill ph-warning-circle"></i> Condicional
                                                    </span>
                                                @endif
                                                @if($dicaModoPv !== 'nenhum')
                                                    <span class="inline-flex items-center gap-1 bg-purpura-50 border border-purpura-200 text-purpura-700 text-[9px] px-1.5 py-0.5 rounded font-bold">
                                                        <i class="ph-fill ph-info"></i> Dica: {{ ucfirst($dicaModoPv) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @if($grupo['campos']->isEmpty() && !($numEtapa === 1 && $contextoTipo === 'ciclo'))
                        <div class="py-8 px-4 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50 text-gray-400 text-xs">
                            Nenhum campo personalizado adicionado nesta página. Clique num tipo de input à esquerda para inserir.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>