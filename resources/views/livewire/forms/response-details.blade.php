<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Protocolo #{{ str_pad($resposta->id, 5, '0', STR_PAD_LEFT) }}" 
        icon="ph ph-file-text"
        badge="Resposta">
        
        <x-slot name="actions">
            <a href="{{ route('formularios.show', $formulario->id) }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition rounded-lg font-bold text-sm">
                <i class="ph-bold ph-printer text-lg"></i> Imprimir Resposta
            </button>
        </x-slot>
    </x-page-header>

    <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        <i class="ph-fill ph-clock"></i> Formulário submetido em {{ $resposta->created_at->format('d \d\e F \d\e Y \à\s H:i') }}
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden print:shadow-none print:border-none">
        
        <!-- Cabeçalho do Documento -->
        <div class="bg-indigo-50 border-b border-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800 p-6 flex items-start gap-4 print:bg-white print:border-b-2 print:border-gray-800">
            <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-sm print:text-black print:bg-gray-100 shrink-0">
                <i class="ph-fill ph-file-text"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-indigo-900 dark:text-indigo-300 print:text-black">{{ $formulario->titulo }}</h3>
                <p class="text-indigo-700 dark:text-indigo-400 text-sm mt-1 print:text-gray-600">Visualização de documento de resposta do usuário.</p>
            </div>
        </div>

        <!-- Corpo das Respostas -->
        <div class="p-6 md:p-8 space-y-8">
            
            @php 
                // Garante que a resposta é um array iterável
                $respostasSalvas = is_string($resposta->respostas) ? json_decode($resposta->respostas, true) : $resposta->respostas;
            @endphp

            @forelse($camposPorEtapa as $etapaNum => $campos)
                <div class="mb-8 print:mb-6">
                    <h4 class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
                        <span class="w-5 h-5 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300">{{ $etapaNum }}</span> 
                        Respostas da Etapa {{ $etapaNum }}
                    </h4>
                    
                    <div class="space-y-6">
                        @foreach($campos as $campo)
                            @php 
                                $valor = $respostasSalvas[$campo->name] ?? null; 
                                $temResposta = !empty($valor) || $valor === '0' || $valor === 0;
                            @endphp

                            <div class="bg-gray-50/50 dark:bg-gray-900/30 p-4 rounded-lg border border-gray-100 dark:border-gray-700 print:p-0 print:border-none print:bg-white print:mb-4">
                                <label class="block text-sm font-bold text-gray-800 dark:text-gray-300 mb-2 print:text-base print:mb-1">
                                    {{ $campo->label }}
                                </label>
                                
                                @if(!$temResposta)
                                    <p class="text-gray-400 dark:text-gray-500 text-sm italic border-l-2 border-gray-300 dark:border-gray-600 pl-3">Nenhuma resposta fornecida ou campo ocultado por regra condicional.</p>
                                @else
                                    
                                    {{-- Renderização Específica para Checkboxes (Array de Strings) --}}
                                    @if($campo->tipo === 'check' && is_array($valor))
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach($valor as $v)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 text-sm font-medium border border-indigo-100 dark:border-indigo-800 print:border-none print:p-0 print:bg-white print:text-black print:before:content-['✓']">
                                                    <i class="ph-bold ph-check text-indigo-500 dark:text-indigo-400 print:hidden"></i> {{ $v }}
                                                </span>
                                            @endforeach
                                        </div>

                                    {{-- Renderização Específica para Matriz --}}
                                    @elseif($campo->tipo === 'matriz' && is_array($valor))
                                        @php
                                            $cfg = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                                            $linhas = $cfg['linhas'] ?? [];
                                        @endphp
                                        <div class="mt-3 border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden bg-white dark:bg-gray-800">
                                            <table class="min-w-full text-sm">
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    @foreach($linhas as $idx => $linhaTexto)
                                                        <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-900/50' : 'bg-white dark:bg-gray-800' }}">
                                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400 font-medium w-1/2 border-r border-gray-100 dark:border-gray-700">{{ $linhaTexto }}</td>
                                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-200 font-bold w-1/2">
                                                                {{ $valor[$idx] ?? '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    {{-- Renderização Específica para Avaliação em Estrelas --}}
                                    @elseif($campo->tipo === 'rating')
                                        @php $maxStars = $cfg['max_stars'] ?? 5; @endphp
                                        <div class="flex gap-1 text-2xl text-yellow-400 mt-1">
                                            @for($i = 1; $i <= $maxStars; $i++)
                                                <i class="{{ $i <= $valor ? 'ph-fill' : 'ph' }} ph-star {{ $i > $valor ? 'text-gray-300 dark:text-gray-600' : '' }}"></i>
                                            @endfor
                                            <span class="ml-2 text-sm text-gray-500 font-bold self-center">({{ $valor }}/{{ $maxStars }})</span>
                                        </div>

                                    {{-- Renderização Padrão (Texto, Select, Radio) --}}
                                    @else
                                        <p class="text-gray-900 dark:text-white text-base border-l-2 border-indigo-400 dark:border-indigo-600 pl-3 bg-white dark:bg-transparent py-1 print:border-none print:pl-0 print:font-bold">
                                            {{ $valor }}
                                        </p>
                                    @endif

                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <p class="text-gray-500 dark:text-gray-400">Este formulário não possuía perguntas ativas no momento desta resposta.</p>
                </div>
            @endforelse

        </div>
    </div>
</div>