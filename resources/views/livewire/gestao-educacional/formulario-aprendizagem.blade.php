<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        
        <div class="mb-8 border-b border-gray-100 dark:border-gray-700 pb-4">
            <span class="px-2.5 py-1 bg-purpura-50 text-purpura-700 text-xs font-bold rounded-md uppercase tracking-wider">
                Aprendiz: {{ $aluno->name }}
            </span>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mt-2">{{ $formulario->titulo }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $formulario->descricao ?: 'Preencha os campos abaixo conforme as diretrizes da fase atual.' }}</p>
        </div>

        @if(!empty($mensagemBloqueio))
            <div class="mb-6 p-4 rounded-lg border {{ $podeResponder ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800' }} flex items-center gap-3 font-bold text-sm">
                <i class="ph-fill {{ $podeResponder ? 'ph-info' : 'ph-lock-key' }} text-xl"></i>
                {{ $mensagemBloqueio }}
            </div>
        @endif

        <!-- Renderizador Dinâmico dos Campos com bloqueio se não puder responder -->
        <div class="space-y-6 {{ !$podeResponder ? 'pointer-events-none opacity-80' : '' }}">
            @foreach($camposDinamicos->where('etapa', $etapaAtual) as $campo)
                <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-2">
                        {{ $campo->label }} @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
                    </label>

                    @if($campo->tipo === 'text' || $campo->tipo === 'email' || $campo->tipo === 'number')
                        <input type="{{ $campo->tipo }}" wire:model="respostas.{{ $campo->name }}" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm shadow-sm" {{ !$podeResponder ? 'disabled' : '' }}>
                    
                    @elseif($campo->tipo === 'textarea')
                        <textarea wire:model="respostas.{{ $campo->name }}" rows="3" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm shadow-sm" {{ !$podeResponder ? 'disabled' : '' }}></textarea>
                    
                    @elseif($campo->tipo === 'select')
                        <select wire:model="respostas.{{ $campo->name }}" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm shadow-sm" {{ !$podeResponder ? 'disabled' : '' }}>
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