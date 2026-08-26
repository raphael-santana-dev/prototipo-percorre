@php
    $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
    
    $bgSize = $formSettings['bg_size'] ?? 'cover';
    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
    $textoForm = $isTranslucent ? 'text-gray-900 drop-shadow-sm' : 'text-gray-900';
@endphp

<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent">
    
    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    <div class="relative z-10 w-full {{ $formWidth }} mx-auto py-12 px-4 sm:px-6 flex-1 flex flex-col justify-center">
        
        {{-- TELA DE BLOQUEIO (GATEKEEPER) --}}
        @if($bloqueado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-yellow-500 transition-all duration-300 relative overflow-hidden">
                <div class="absolute inset-0 bg-yellow-50/30 dark:bg-yellow-900/10 pointer-events-none"></div>
                
                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-yellow-50 text-yellow-600 mb-6 shadow-sm border border-yellow-100 dark:bg-yellow-900/30 dark:border-yellow-800">
                        <i class="ph-fill {{ $iconeBloqueio }} text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Acesso Indisponível</h2>
                    <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed max-w-md mx-auto">{{ $mensagemBloqueio }}</p>
                    
                    @if($exibirBotaoLogin)
                        <a href="{{ route('portal.login') }}" class="mt-8 inline-flex items-center gap-2 bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                            Fazer Login no Portal <i class="ph-bold ph-sign-in"></i>
                        </a>
                    @else
                        <button type="button" onclick="window.history.back()" class="mt-8 inline-block bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                            Voltar à página anterior
                        </button>
                    @endif
                </div>
            </div>

        {{-- TELA DE SUCESSO --}}
        @elseif($finalizado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-green-500 transition-all duration-300">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-600 mb-6 shadow-sm border border-green-200 dark:bg-green-900/30 dark:border-green-800">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Formulário Enviado!</h2>
                <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed max-w-lg mx-auto">Suas respostas foram registradas com sucesso no sistema. Agradecemos pela sua participação.</p>
                
                <button type="button" onclick="window.location.reload()" class="mt-8 inline-flex items-center gap-2 bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                    <i class="ph-bold ph-arrow-counter-clockwise"></i> Preencher Novamente
                </button>
            </div>

        {{-- TELA DO FORMULÁRIO --}}
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

                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
                    @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])
                </div>

                <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    @if($etapaAtual > 1)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" class="text-gray-600 dark:text-gray-300 hover:text-purpura-600 dark:hover:text-purpura-400 font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center gap-2">
                            <i class="ph ph-arrow-left text-lg"></i> Voltar
                        </button>
                    @else
                        <div></div> {{-- Spacer --}}
                    @endif

                    <button type="button" wire:click="avancarEtapa" class="bg-purpura-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-purpura-700 hover:shadow-lg transition duration-200 flex items-center gap-2">
                        {{ $etapaAtual === $totalEtapas ? 'Enviar Respostas' : 'Próxima Etapa' }}
                        @if($etapaAtual !== $totalEtapas) <i class="ph ph-arrow-right text-lg"></i> @else <i class="ph-bold ph-paper-plane-tilt text-lg"></i> @endif
                    </button>
                </div>

            </div>
        @endif
    </div>
</div>