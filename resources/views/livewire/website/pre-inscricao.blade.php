<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent">
    @php
        $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
        $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
    @endphp

    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }};"></div>

    <div class="relative z-10 w-full max-w-4xl mx-auto py-12 px-4 sm:px-6 flex-1 flex flex-col justify-center">
        @if($finalizado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-green-500">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-600 mb-6">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Interesse Registrado!</h2>
                <p class="text-gray-600 text-lg">Agradecemos o seu interesse. Entraremos em contato assim que abrirmos novas turmas.</p>
            </div>
        @else
            <div class="{{ $cardClass }} p-8 md:p-12 rounded-xl border-t-4 border-emerald-600">
                <div class="mb-8 border-b border-gray-200 pb-6">
                    <h1 class="text-3xl font-extrabold mb-2 text-gray-900">{{ $formulario->titulo }}</h1>
                    <p class="text-gray-600 font-medium">Pré-Inscrição de Interesse</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
                    <!-- O Seu Renderizador Mágico do FormBuilder -->
                    @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])
                </div>

                <div class="mt-10 pt-6 border-t border-gray-200 flex justify-end">
                    <button type="button" wire:click="avancarEtapa" class="bg-emerald-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-emerald-700 transition">
                        {{ $etapaAtual === $totalEtapas ? 'Registrar Interesse' : 'Avançar' }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>