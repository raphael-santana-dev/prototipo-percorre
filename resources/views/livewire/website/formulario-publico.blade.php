@php
    $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
    
    $bgSize = $formSettings['bg_size'] ?? 'cover';
    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    $cardClass = $isTranslucent ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-2xl border border-gray-100 dark:border-gray-800' : 'bg-white dark:bg-gray-900 shadow-xl border border-gray-100 dark:border-gray-800';
    $textoForm = $isTranslucent ? 'text-gray-900 dark:text-white drop-shadow-sm' : 'text-gray-900 dark:text-white';
@endphp

<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent"
     x-data="{
        storageKey: 'percorre_form_publico_{{ $formulario->id }}{{ $isTeste ? '_TESTE' : '' }}',
        readyToSave: false,
        init() {
            if (@js((bool) request()->query('preview')) || @js($bloqueado)) return;

            const raw = localStorage.getItem(this.storageKey);
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        $wire.restaurarRascunhoLocal(parsed).then(() => {
                            this.readyToSave = true;
                        });
                        return;
                    }
                } catch (e) {
                    localStorage.removeItem(this.storageKey);
                }
            }
            this.readyToSave = true;
        },
        salvarNoLocalStorage() {
            if (!this.readyToSave || @js($isPreview) || @js($isTeste) || @js($bloqueado)) return;
            if ($wire.finalizado) {
                localStorage.removeItem(this.storageKey);
                return;
            }

            const payload = {
                respostaId: $wire.respostaId,
                etapaAtual: $wire.etapaAtual,
                respostas: $wire.respostas
            };

            try {
                localStorage.setItem(this.storageKey, JSON.stringify(payload));
            } catch (e) {}
        }
     }"
     @input.debounce.400ms="salvarNoLocalStorage()"
     @change="salvarNoLocalStorage()"
     @mouseup.debounce.300ms="salvarNoLocalStorage()"
     @touchend.debounce.300ms="salvarNoLocalStorage()"
     @limpar-storage-formulario.window="localStorage.removeItem(storageKey)">
    
    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    <div class="relative z-10 w-full {{ $formWidth }} mx-auto {{ request()->query('embed') ? 'py-4' : 'py-12' }} px-4 sm:px-6 flex-1 flex flex-col justify-center">
        @if($isTeste)
            <div class="mb-6 p-4 bg-amber-400 text-amber-900 rounded-xl text-xs font-bold flex items-center justify-between shadow-md ring-2 ring-amber-500 z-50">
                <span class="flex items-center gap-2">
                    <i class="ph-fill ph-flask text-lg text-amber-800"></i> 
                    MODO DE TESTE (SANDBOX): Você está testando este formulário. Nenhuma resposta será gravada no banco de dados.
                </span>
                <button onclick="window.close()" class="px-3 py-1 bg-amber-900 text-amber-100 hover:bg-black hover:text-white transition-colors rounded text-[10px] font-extrabold uppercase tracking-wider">
                    Encerrar Teste
                </button>
            </div>
        @endif
        
        @if($bloqueado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center transition-all duration-300 relative overflow-hidden">
                <div class="absolute inset-0 bg-yellow-50/30 dark:bg-yellow-900/10 pointer-events-none"></div>
                
                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-yellow-50 text-yellow-600 mb-6 shadow-sm border border-yellow-100 dark:bg-yellow-900/30 dark:border-yellow-800">
                        <i class="ph-fill {{ $iconeBloqueio }} text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Acesso Indisponível</h2>
                    <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed max-w-md mx-auto">{{ $mensagemBloqueio }}</p>
                    
                    @if($exibirBotaoLogin)
                        <a href="{{ route('login') }}" class="mt-8 inline-flex items-center gap-2 bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                            Fazer Login no Portal <i class="ph-bold ph-sign-in"></i>
                        </a>
                    @else
                        <button type="button" onclick="window.history.back()" class="mt-8 inline-block bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                            Voltar à página anterior
                        </button>
                    @endif
                </div>
            </div>

        @elseif($finalizado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-purpura-500 transition-all duration-300">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-purpura-100 text-purpura-600 mb-6 shadow-sm border border-purpura-200 dark:bg-purpura-900/30 dark:border-purpura-800">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Formulário Concluído!</h2>
                <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed max-w-lg mx-auto">
                    Muito obrigado! As suas respostas foram registadas com sucesso no nosso sistema. Agradecemos a sua participação.
                </p>
                
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" wire:click="baixarFichaPdf" wire:loading.attr="disabled" class="inline-flex items-center gap-2 bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition duration-200">
                        <i class="ph-bold ph-file-pdf text-lg" wire:loading.remove wire:target="baixarFichaPdf"></i>
                        <i class="ph-bold ph-spinner animate-spin text-lg" wire:loading wire:target="baixarFichaPdf"></i>
                        <span>Baixar Ficha de Respostas (PDF)</span>
                    </button>

                    <button type="button" onclick="window.location.reload()" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-black dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                        <i class="ph-bold ph-arrow-counter-clockwise"></i> Preencher Novamente
                    </button>
                </div>
            </div>
        @else
            <div class="{{ $cardClass }} p-8 md:p-12 rounded-xl border-t-4 border-purpura-600 transition-all duration-300">
                
                <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-3xl font-extrabold mb-3 {{ $textoForm }}">{{ $formulario->titulo }}</h1>
                    @if($formulario->descricao)
                        <p class="text-gray-600 dark:text-gray-300 text-base leading-relaxed">{{ $formulario->descricao }}</p>
                    @endif
                </div>
                
                @if($totalEtapas > 1)
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Etapa {{ $etapaAtual }} de {{ $totalEtapas }}</span>
                            <span class="text-xs font-bold text-purpura-600">{{ round(($etapaAtual / $totalEtapas) * 100) }}% Concluído</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 shadow-inner">
                            <div class="bg-purpura-600 h-2 rounded-full transition-all duration-500" style="width: {{ ($etapaAtual / $totalEtapas) * 100 }}%"></div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8 {{ request()->query('preview') ? 'pointer-events-none opacity-80' : '' }}">
                    
                    @if($formulario->exigir_email && $etapaAtual === 1)
                        <div class="col-span-12 relative rounded-lg transition-all duration-300">
                            <div class="relative z-10">
                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 {{ isset($formSettings['bg_image']) ? 'text-white drop-shadow-md' : '' }}">
                                    Seu E-mail Institucional ou Pessoal <span class="text-red-500">*</span>
                                </label>
                                <input type="email" wire:model.live.debounce.500ms="respostas._email_coletado" class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 dark:text-white dark:bg-gray-800 @error('respostas._email_coletado') border-red-500 bg-red-50 @else border-gray-300 dark:border-gray-700 bg-white @enderror" placeholder="seu.email@exemplo.com">
                                @error('respostas._email_coletado') <span class="text-red-500 text-xs font-bold mt-1 block drop-shadow-md">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposDinamicos->where('etapa', $etapaAtual)])
                </div>

                <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    @if($etapaAtual > 1)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" wire:loading.attr="disabled" wire:target="avancarEtapa, uploads" class="text-gray-600 dark:text-gray-300 hover:text-purpura-600 dark:hover:text-purpura-400 font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center gap-2 disabled:opacity-50">
                            <i class="ph ph-arrow-left text-lg"></i> Voltar
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if(request()->query('preview') && $etapaAtual === $totalEtapas)
                        <div class="bg-blue-50 text-blue-700 px-6 py-3 rounded-lg font-bold flex items-center gap-2 border border-blue-200 shadow-sm">
                            <i class="ph-fill ph-eye"></i> Visualização Concluída
                        </div>
                    @else
                        <button type="button" wire:click="avancarEtapa" wire:loading.attr="disabled" wire:target="avancarEtapa, uploads" class="bg-purpura-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-purpura-700 hover:shadow-lg transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="avancarEtapa, uploads" class="inline-flex items-center gap-2">
                                {{ $etapaAtual === $totalEtapas ? 'Enviar Respostas' : 'Próxima Etapa' }}
                                @if($etapaAtual !== $totalEtapas) <i class="ph ph-arrow-right text-lg"></i> @else <i class="ph-bold ph-paper-plane-tilt text-lg"></i> @endif
                            </span>
                            <span wire:loading wire:target="uploads" class="inline-flex items-center gap-2">
                                <i class="ph-bold ph-spinner animate-spin"></i> A carregar anexo(s)...
                            </span>
                            <span wire:loading wire:target="avancarEtapa" class="inline-flex items-center gap-2">
                                <i class="ph-bold ph-spinner animate-spin"></i> A gravar respostas...
                            </span>
                        </button>
                    @endif
                </div>

            </div>
        @endif
    </div>
</div>