<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent">
    @php
        $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
        $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
        
        $inputClassBase = "w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purpura-500/25 focus:border-purpura-500 transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-700";
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
                    @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])

                    @if($etapaAtual == 1)
                        <div class="col-span-12 mt-4 p-5 bg-gray-50 border border-gray-200 rounded-md">
                            <div class="flex justify-center">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" wire:model.live="deseja_informar" class="w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 bg-white transition-colors">
                                    <span class="text-sm font-bold text-gray-800 group-hover:text-emerald-600 transition-colors">Desejo informar o local e curso que tenho interesse</span>
                                </label>
                            </div>

                            @if($deseja_informar)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-1">Unidade de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="unidade_interesse" class="{{ $inputClassBase }} @error('unidade_interesse') !border-red-500 !bg-red-50 @enderror">
                                            <option value="">Selecione a Unidade...</option>
                                            @foreach($unidadesInteresseDb ?? [] as $u)
                                                <option value="{{ $u->id }}">{{ $u->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('unidade_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-1">Curso de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="curso_interesse" class="{{ $inputClassBase }} @error('curso_interesse') !border-red-500 !bg-red-50 @enderror">
                                            <option value="">Selecione o Curso...</option>
                                            @foreach($cursosInteresseDb ?? [] as $c)
                                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('curso_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-1">Turno de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="turno_interesse" class="{{ $inputClassBase }} @error('turno_interesse') !border-red-500 !bg-red-50 @enderror">
                                            <option value="">Selecione o Turno...</option>
                                            @foreach($turnosInteresseDb ?? [] as $t)
                                                <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('turno_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
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