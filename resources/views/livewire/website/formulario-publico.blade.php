@php
    // Variáveis do Papel de Parede Global
    $formBgUrl = $formSettings['bg_image'] ?? null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; // fundo cinza claro padrão
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
@endphp

<div class="min-h-[90vh] flex flex-col relative w-full font-sans">
    
    {{-- BACKGROUND DE FUNDO --}}
    @if($formBgUrl)
        <div class="absolute inset-0 z-0 bg-cover bg-center bg-fixed" style="background-image: url('{{ $formBgUrl }}');"></div>
    @endif
    <div class="absolute inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    {{-- CONTAINER DO FORMULÁRIO --}}
    <div class="relative z-10 w-full max-w-4xl mx-auto py-12 px-4 sm:px-6 flex-1 flex flex-col justify-center">
        
        @if($finalizado)
            <div class="bg-white p-10 md:p-16 rounded-xl shadow-xl text-center border-t-4 border-green-500">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-600 mb-6">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Formulário Enviado!</h2>
                <p class="text-gray-600 text-lg leading-relaxed">Suas respostas foram registradas com sucesso. Agradecemos muito pelo seu tempo e pela sua participação.</p>
                
                <button type="button" onclick="window.location.reload()" class="mt-8 bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                    Preencher Novamente
                </button>
            </div>
        @else
            <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl border-t-4 border-brand-purple">
                
                <div class="mb-8 pb-6 border-b border-gray-100">
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-3">{{ $formulario->titulo }}</h1>
                    @if($formulario->descricao)
                        <p class="text-gray-600 text-base leading-relaxed">{{ $formulario->descricao }}</p>
                    @endif
                </div>
                
                {{-- BARRA DE PROGRESSO (Apenas se houver mais de 1 etapa) --}}
                @if($totalEtapas > 1)
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Etapa {{ $etapaAtual }} de {{ $totalEtapas }}</span>
                            <span class="text-xs font-bold text-brand-purple">{{ round(($etapaAtual / $totalEtapas) * 100) }}% Concluído</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-brand-purple h-2 rounded-full transition-all duration-500" style="width: {{ ($etapaAtual / $totalEtapas) * 100 }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- O "Miolo" do formulário usando a Grade de 12 Colunas --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
                    @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])
                </div>

                {{-- BOTÕES DE AÇÃO --}}
                <div class="mt-10 pt-6 border-t border-gray-100 flex justify-between items-center">
                    @if($etapaAtual > 1)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" class="text-gray-600 hover:text-brand-purple font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center gap-2">
                            <i class="ph ph-arrow-left"></i> Voltar
                        </button>
                    @else
                        <div></div> {{-- Spacer --}}
                    @endif

                    <button type="button" wire:click="avancarEtapa" class="bg-brand-purple text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-brand-purpleHover hover:shadow-lg transition duration-200 flex items-center gap-2">
                        {{ $etapaAtual === $totalEtapas ? 'Enviar Respostas' : 'Próxima Etapa' }}
                        @if($etapaAtual !== $totalEtapas) <i class="ph ph-arrow-right"></i> @else <i class="ph-bold ph-paper-plane-tilt"></i> @endif
                    </button>
                </div>

            </div>
        @endif
    </div>
</div>