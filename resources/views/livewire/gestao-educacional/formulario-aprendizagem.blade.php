<div class="p-6 max-w-4xl mx-auto font-sans relative">
    @php
        $formSettings = [];
        foreach($camposDinamicos as $c) {
            if($c->tipo === 'config' && !empty($c->configuracoes)) {
                $formSettings = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : $c->configuracoes;
                break;
            }
        }
        $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
        $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
        $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
        $bgSize = $formSettings['bg_size'] ?? 'cover';
        $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
        $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $cardClass = $isTranslucent ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-2xl border border-gray-100 dark:border-gray-800' : 'bg-white dark:bg-gray-900 shadow-xl border border-gray-100 dark:border-gray-800';
        $textoForm = $isTranslucent ? 'text-gray-900 dark:text-white drop-shadow-sm' : 'text-gray-900 dark:text-white';
        $inputClassBase = "w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purpura-500/25 focus:border-purpura-500 transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-700";
    @endphp

    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat pointer-events-none" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    <div class="relative z-10 w-full {{ $formWidth }} mx-auto py-12 px-4 sm:px-6">
        <div class="{{ $cardClass }} p-8 md:p-12 rounded-xl border-t-4 border-purpura-600 transition-all duration-300">
            
            <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                <span class="inline-block px-2.5 py-1 bg-purpura-50 text-purpura-700 dark:bg-purpura-900/30 dark:text-purpura-300 text-xs font-bold rounded-md uppercase tracking-wider mb-2">
                    Aprendiz: {{ $aluno->name }}
                </span>
                <h1 class="text-3xl font-extrabold mb-3 {{ $textoForm }}">{{ $formulario->titulo }}</h1>
                <p class="text-gray-600 dark:text-gray-300 text-base leading-relaxed">{{ $formulario->descricao ?: 'Preencha os campos abaixo conforme as diretrizes da fase atual.' }}</p>
            </div>

            @if(!empty($mensagemBloqueio))
                <div class="mb-6 p-4 rounded-lg border {{ $podeResponder ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800' }} flex items-center gap-3 font-bold text-sm">
                    <i class="ph-fill {{ $podeResponder ? 'ph-info' : 'ph-lock-key' }} text-xl"></i>
                    {{ $mensagemBloqueio }}
                </div>
            @endif

            <div class="space-y-6 {{ !$podeResponder ? 'pointer-events-none opacity-80' : '' }}">
                @foreach($camposDinamicos->where('etapa', $etapaAtual)->where('tipo', '!=', 'config') as $campo)
                    <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-2">
                            {{ $campo->label }} @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
                        </label>

                        @if($campo->tipo === 'text' || $campo->tipo === 'email' || $campo->tipo === 'number')
                            <input type="{{ $campo->tipo }}" wire:model="respostas.{{ $campo->name }}" class="{{ $inputClassBase }}" {{ !$podeResponder ? 'disabled' : '' }}>
                        @elseif($campo->tipo === 'textarea')
                            <textarea wire:model="respostas.{{ $campo->name }}" rows="3" class="{{ $inputClassBase }}" {{ !$podeResponder ? 'disabled' : '' }}></textarea>
                        @elseif($campo->tipo === 'select')
                            <select wire:model="respostas.{{ $campo->name }}" class="{{ $inputClassBase }}" {{ !$podeResponder ? 'disabled' : '' }}>
                                <option value="">Selecione...</option>
                                @foreach($campo->opcoes ?? [] as $opcao)
                                    <option value="{{ $opcao }}">{{ $opcao }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                @if($etapaAtual > 1)
                    <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Voltar Etapa
                    </button>
                @else
                    <div></div>
                @endif

                @if($podeResponder)
                    @if($etapaAtual < $totalEtapas)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual + 1 }})" class="px-6 py-2.5 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow transition">
                            Avançar Etapa
                        </button>
                    @else
                        <button type="button" wire:click="salvarEAvancar" class="px-8 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-lg transition flex items-center gap-2">
                            <i class="ph-bold ph-paper-plane-tilt"></i> Finalizar e Enviar Respostas
                        </button>
                    @endif
                @else
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Modo de Apenas Leitura</span>
                @endif
            </div>

        </div>
    </div>
</div>