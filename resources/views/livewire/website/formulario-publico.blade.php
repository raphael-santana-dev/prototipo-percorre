@php
    $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
    
    // Novas variáveis resgatadas do Banco de Dados
    $bgSize = $formSettings['bg_size'] ?? 'cover';
    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    // Lógica dinâmica de classes
    $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
    $textoForm = $isTranslucent ? 'text-gray-900 drop-shadow-sm' : 'text-gray-900';
@endphp

<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent">
    
    {{-- APLICAÇÃO DO TIPO DE CROP (bgSize) --}}
    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    {{-- APLICAÇÃO DA LARGURA DINÂMICA (formWidth) --}}
    <div class="relative z-10 w-full {{ $formWidth }} mx-auto py-12 px-4 sm:px-6 flex-1 flex flex-col justify-center">
        
        @if($finalizado)
            {{-- APLICAÇÃO DO EFEITO TRANSLÚCIDO NO SUCESSO (cardClass) --}}
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-green-500 transition-all duration-300">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-600 mb-6 shadow-sm">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Formulário Enviado!</h2>
                <p class="text-gray-600 text-lg leading-relaxed">Suas respostas foram registradas com sucesso. Agradecemos muito pelo seu tempo e pela sua participação.</p>
                
                <button type="button" onclick="window.location.reload()" class="mt-8 bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                    Preencher Novamente
                </button>
            </div>
        @else
            {{-- APLICAÇÃO DO EFEITO TRANSLÚCIDO NO FORM (cardClass) --}}
            <div class="{{ $cardClass }} p-8 md:p-12 rounded-xl border-t-4 border-purpura-600 transition-all duration-300">
                
                <div class="mb-8 pb-6 border-b border-gray-200">
                    <h1 class="text-3xl font-extrabold mb-3 {{ $textoForm }}">{{ $formulario->titulo }}</h1>
                    @if($formulario->descricao)
                        <p class="text-gray-600 text-base leading-relaxed">{{ $formulario->descricao }}</p>
                    @endif
                </div>
                
                @if($totalEtapas > 1)
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Etapa {{ $etapaAtual }} de {{ $totalEtapas }}</span>
                            <span class="text-xs font-bold text-purpura-600">{{ round(($etapaAtual / $totalEtapas) * 100) }}% Concluído</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 shadow-inner">
                            <div class="bg-purpura-600 h-2 rounded-full transition-all duration-500" style="width: {{ ($etapaAtual / $totalEtapas) * 100 }}%"></div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
                    @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])
                </div>

                <div class="mt-10 pt-6 border-t border-gray-200 flex justify-between items-center">
                    @if($etapaAtual > 1)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" class="text-gray-600 hover:text-purpura-600 font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center gap-2">
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