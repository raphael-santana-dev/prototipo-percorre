@php
    $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
    $bgSize = $formSettings['bg_size'] ?? 'cover';
    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    $cardClass = $isTranslucent ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-2xl border border-gray-100 dark:border-gray-800' : 'bg-white dark:bg-gray-900 shadow-xl border border-gray-100 dark:border-gray-800';
    $textoForm = $isTranslucent ? 'text-gray-900 dark:text-white drop-shadow-sm' : 'text-gray-900 dark:text-white';
    
    $inputClassBase = "w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/25 focus:border-emerald-500 transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-700";
@endphp

<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent">
    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    <div class="relative z-10 w-full {{ $formWidth }} mx-auto py-12 px-4 sm:px-6 flex-1 flex flex-col justify-center">
        @if($finalizado)
            <div class="{{ $cardClass }} p-10 md:p-16 rounded-xl text-center border-t-4 border-emerald-500 transition-all duration-300">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 mb-6 shadow-sm dark:bg-emerald-900/30 dark:border-emerald-800">
                    <i class="ph-fill ph-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Interesse Registrado!</h2>
                <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed max-w-lg mx-auto">Agradecemos o seu interesse. Entraremos em contato assim que abrirmos novas turmas.</p>
                
                <button type="button" onclick="window.location.reload()" class="mt-8 inline-flex items-center gap-2 bg-gray-900 hover:bg-black dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition duration-200">
                    <i class="ph-bold ph-arrow-counter-clockwise"></i> Preencher Novamente
                </button>
            </div>
        @else
            <div class="{{ $cardClass }} p-8 md:p-12 rounded-xl border-t-4 border-emerald-600 transition-all duration-300">
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h1 class="text-3xl font-extrabold mb-2 {{ $textoForm }}">{{ $formulario->titulo }}</h1>
                    <p class="text-gray-600 dark:text-gray-300 font-medium">Pré-Inscrição de Interesse</p>
                </div>

                @if($totalEtapas > 1)
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Etapa {{ $etapaAtual }} de {{ $totalEtapas }}</span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ round(($etapaAtual / $totalEtapas) * 100) }}% Concluído</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 shadow-inner">
                            <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" style="width: {{ ($etapaAtual / $totalEtapas) * 100 }}%"></div>
                        </div>
                    </div>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8">
                    @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposDinamicos->where('etapa', $etapaAtual)])

                    @if($etapaAtual == 1)
                        <div class="col-span-12 mt-4 p-5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-md">
                            <div class="flex justify-center">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" wire:model.live="deseja_informar" class="w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 bg-white dark:bg-gray-800 dark:border-gray-600 transition-colors">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Desejo informar o local e curso que tenho interesse</span>
                                </label>
                            </div>

                            @if($deseja_informar)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 animate-fade-in-down">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Unidade de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="unidade_interesse" class="{{ $inputClassBase }} @error('unidade_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                            <option value="">Selecione a Unidade...</option>
                                            @foreach($unidadesInteresseDb ?? [] as $u)
                                                <option value="{{ $u->id }}">{{ $u->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('unidade_interesse') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Curso de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="curso_interesse" class="{{ $inputClassBase }} @error('curso_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                            <option value="">Selecione o Curso...</option>
                                            @foreach($cursosInteresseDb ?? [] as $c)
                                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('curso_interesse') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Turno de Interesse <span class="text-red-500">*</span></label>
                                        <select wire:model="turno_interesse" class="{{ $inputClassBase }} @error('turno_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                            <option value="">Selecione o Turno...</option>
                                            @foreach($turnosInteresseDb ?? [] as $t)
                                                <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                            @endforeach
                                        </select>
                                        @error('turno_interesse') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    @if($etapaAtual > 1)
                        <button type="button" wire:click="$set('etapaAtual', {{ $etapaAtual - 1 }})" wire:loading.attr="disabled" wire:target="avancarEtapa, uploads" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center gap-2 disabled:opacity-50">
                            <i class="ph ph-arrow-left text-lg"></i> Voltar
                        </button>
                    @else
                        <div></div>
                    @endif

                    <button type="button" wire:click="avancarEtapa" wire:loading.attr="disabled" wire:target="avancarEtapa, uploads" class="bg-emerald-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-emerald-700 hover:shadow-lg transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="avancarEtapa, uploads" class="inline-flex items-center gap-2">
                            {{ $etapaAtual === $totalEtapas ? 'Registrar Interesse' : 'Avançar' }}
                            @if($etapaAtual !== $totalEtapas) <i class="ph ph-arrow-right text-lg"></i> @else <i class="ph-bold ph-paper-plane-tilt text-lg"></i> @endif
                        </span>
                        <span wire:loading wire:target="uploads" class="inline-flex items-center gap-2">
                            <i class="ph-bold ph-spinner animate-spin"></i> A carregar anexo(s)...
                        </span>
                        <span wire:loading wire:target="avancarEtapa" class="inline-flex items-center gap-2">
                            <i class="ph-bold ph-spinner animate-spin"></i> A processar...
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>