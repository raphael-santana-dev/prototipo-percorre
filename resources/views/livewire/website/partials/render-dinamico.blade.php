{{-- SAFELIST: Força o Tailwind a compilar essas classes de largura (Não apague esta div) --}}
<div class="hidden col-span-3 col-span-4 col-span-6 col-span-12 md:col-span-3 md:col-span-4 md:col-span-6 md:col-span-12"></div>

@foreach($camposDinamicos->where('etapa', $etapaAtual) as $campo)
    @php
        $isCondicional = !empty($campo->depende_de) && !empty($campo->depende_valor);
        
        $listaOpcoes = [];
        if (!empty($campo->opcoes)) {
            $decodificado = is_string($campo->opcoes) ? json_decode($campo->opcoes, true) : $campo->opcoes;
            $listaOpcoes = is_array($decodificado) ? $decodificado : explode(',', $campo->opcoes);
        }

        $config = [];
        if (!empty($campo->configuracoes)) {
            $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : $campo->configuracoes;
        }

        $colSpan = "col-span-12 md:col-span-{$campo->largura}";
        
        // Estilos de Background customizados
        $bgStyle = "";
        $overlayStyle = "";
        if(isset($config['bg_image']) && !empty($config['bg_image'])) {
            $bgStyle = "background-image: url('{$config['bg_image']}'); background-size: cover; background-position: center;";
            $opacity = $config['bg_opacity'] ?? '0.5';
            $color = $config['bg_color'] ?? '#000000';
            $overlayStyle = "background-color: {$color}; opacity: {$opacity};";
        }
    @endphp

    <div class="{{ $colSpan }} relative rounded-lg overflow-hidden transition-all duration-300 {{ isset($config['bg_image']) ? 'p-6 shadow-sm' : '' }}"
        @if($isCondicional)
            data-target="{{ $campo->depende_valor }}"
            x-data="{
                get isVisivel() {
                    let respostas = $wire.respostas;
                    let atual = null;
                    
                    if (respostas && respostas['{{ $campo->depende_de }}'] !== undefined && respostas['{{ $campo->depende_de }}'] !== '') {
                        atual = respostas['{{ $campo->depende_de }}'];
                    } else {
                        atual = $wire.{{ $campo->depende_de }};
                    }
                    
                    if (atual === null || atual === undefined) {
                        atual = '';
                    }
                    
                    // O pulo do gato: pega o valor de forma segura via dataset do HTML
                    let target = String($el.dataset.target).toLowerCase().trim();
                    let op = '{{ $campo->depende_operador }}';
                    
                    if (Array.isArray(atual)) {
                        let atualArray = atual.map(s => String(s).toLowerCase().trim());
                        let targetArray = target.split(',').map(s => s.trim());
                        
                        if (op === '=') return atualArray.includes(target);
                        if (op === '!=') return !atualArray.includes(target);
                        if (op === 'in') return atualArray.some(r => targetArray.includes(r));
                        return false;
                    }

                    let val = String(atual).toLowerCase().trim();
                    let numVal = Number(val);
                    let numTarget = Number(target);

                    if (op === '=') return val === target;
                    if (op === '!=') return val !== target;
                    if (op === '>') return !isNaN(numVal) && !isNaN(numTarget) && numVal > numTarget;
                    if (op === '<') return !isNaN(numVal) && !isNaN(numTarget) && numVal < numTarget;
                    if (op === '>=') return !isNaN(numVal) && !isNaN(numTarget) && numVal >= numTarget;
                    if (op === '<=') return !isNaN(numVal) && !isNaN(numTarget) && numVal <= numTarget;
                    if (op === 'in') return target.split(',').map(s => s.trim()).includes(val);
                    
                    return false;
                }
            }"
            x-show="isVisivel"
            x-cloak
        @endif
        style="{{ $bgStyle }}"
    >
        {{-- Overlay de opacidade/brilho para imagem de fundo --}}
        @if(isset($config['bg_image']))
            <div class="absolute inset-0 z-0 pointer-events-none" style="{{ $overlayStyle }}"></div>
        @endif

        {{-- Container Z-Index para ficar acima do background --}}
        <div class="relative z-10">
            
            {{-- LABEL PADRÃO (Oculto para divisores e componentes HTML puros) --}}
            @if(!in_array($campo->tipo, ['html', 'divider', 'social', 'media']))
                <label class="block text-sm font-semibold text-brand-textLabel mb-2 {{ isset($config['bg_image']) ? 'text-white drop-shadow-md' : '' }}">
                    {{ $campo->label }} 
                    @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
                </label>
            @endif
            
            {{-- 1. SELECT --}}
            @if($campo->tipo === 'select')
                <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border border-brand-border px-3 py-2 focus:ring-brand-purple focus:border-brand-purple @error('respostas.'.$campo->name) border-red-500 @enderror">
                    <option value="">Selecione...</option>
                    @foreach($listaOpcoes as $opcao)
                        <option value="{{ trim($opcao) }}">{{ trim($opcao) }}</option>
                    @endforeach
                </select>
                
            {{-- 2. RADIO --}}
            @elseif($campo->tipo === 'radio')
                <div class="flex flex-wrap gap-4 mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="radio" value="{{ trim($opcao) }}" class="form-radio text-brand-purple focus:ring-brand-purple">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

            {{-- 3. CHECKBOX --}}
            @elseif($campo->tipo === 'check')
                <div class="flex flex-wrap gap-4 mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="checkbox" value="{{ trim($opcao) }}" class="form-checkbox text-brand-purple focus:ring-brand-purple">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

            {{-- 4. MATRIZ (RADIO BUTTONS EM TABELA) --}}
            @elseif($campo->tipo === 'matriz')
                @php 
                    $linhas = $config['linhas'] ?? []; 
                    $colunas = $config['colunas'] ?? []; 
                @endphp
                <div class="overflow-x-auto bg-white rounded-lg border border-gray-200 shadow-sm mt-2">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="p-3 text-gray-500 font-medium w-1/3"></th>
                                @foreach($colunas as $col)
                                    <th class="p-3 text-center text-gray-600 font-bold border-l border-gray-200">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($linhas as $indexLinha => $linha)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 font-medium text-gray-800">{{ $linha }}</td>
                                    @foreach($colunas as $col)
                                        <td class="p-3 text-center border-l border-gray-100 bg-white">
                                            <input wire:model.live="respostas.{{ $campo->name }}.{{ $indexLinha }}" type="radio" value="{{ $col }}" class="w-4 h-4 text-brand-purple focus:ring-brand-purple border-gray-300 cursor-pointer">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            {{-- 5. TEXTOS HTML FORMATADOS (h1-h6, p, info, link) --}}
            @elseif($campo->tipo === 'html')
                <div class="{{ isset($config['bg_image']) ? 'text-white' : 'text-gray-800' }}">
                    @if($campo->subtipo === 'h1') <h1 class="text-3xl font-extrabold">{{ $campo->label }}</h1>
                    @elseif($campo->subtipo === 'h2') <h2 class="text-2xl font-bold">{{ $campo->label }}</h2>
                    @elseif($campo->subtipo === 'h3') <h3 class="text-xl font-bold">{{ $campo->label }}</h3>
                    @elseif($campo->subtipo === 'p') <p class="text-base leading-relaxed">{{ $campo->label }}</p>
                    @elseif($campo->subtipo === 'link') <a href="{{ $config['url'] ?? '#' }}" target="_blank" class="text-brand-purple font-bold hover:underline">{{ $campo->label }}</a>
                    @elseif($campo->subtipo === 'info_card') 
                        <div class="p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                            <p class="font-bold mb-1">{{ $campo->label }}</p>
                            <p class="text-sm">{{ $config['descricao'] ?? '' }}</p>
                        </div>
                    @endif
                </div>

            {{-- 6. MEDIA (Imagem e Vídeo) --}}
            @elseif($campo->tipo === 'media')
                <div class="w-full flex justify-center mt-2 rounded-lg overflow-hidden border border-gray-100">
                    @if($campo->subtipo === 'image')
                        <img src="{{ $config['url'] ?? '' }}" alt="{{ $campo->label }}" class="max-w-full h-auto">
                    @elseif($campo->subtipo === 'video')
                        <iframe class="w-full aspect-video" src="{{ $config['url'] ?? '' }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @endif
                </div>

            {{-- 7. DIVIDER --}}
            @elseif($campo->tipo === 'divider')
                <hr class="border-t-2 border-dashed border-gray-200 my-6">

            {{-- 8. REDES SOCIAIS --}}
            @elseif($campo->tipo === 'social')
                @php $redes = $config['redes'] ?? []; @endphp
                <div class="flex flex-wrap gap-4 items-center justify-center py-4">
                    @foreach($redes as $rede)
                        <a href="{{ $rede['url'] }}" target="_blank" class="p-3 bg-gray-100 rounded-full hover:bg-gray-200 transition text-gray-700 hover:text-brand-purple">
                            <i class="text-2xl ph-fill ph-{{ strtolower($rede['nome']) }}"></i>
                        </a>
                    @endforeach
                </div>

            {{-- 9. RATING (Estrelas) --}}
            @elseif($campo->tipo === 'rating')
                <div class="flex gap-2 text-3xl" x-data="{ temp: 0, rating: @entangle('respostas.'.$campo->name) }">
                    @for($i = 1; $i <= ($config['max_stars'] ?? 5); $i++)
                        <i class="cursor-pointer transition-colors" 
                           :class="(temp >= {{ $i }} || (!temp && rating >= {{ $i }})) ? 'ph-fill ph-star text-yellow-400' : 'ph ph-star text-gray-300'"
                           @mouseover="temp = {{ $i }}" 
                           @mouseleave="temp = 0" 
                           @click="rating = {{ $i }}">
                        </i>
                    @endfor
                </div>
            {{-- INTEGRAÇÃO SISTEMA (Unidade, Curso, Turno) --}}
            @elseif($campo->tipo === 'system')
                @php
                    $opcoesSistema = [];
                    if ($campo->subtipo === 'unidade' && isset($unidadesDisponiveis)) $opcoesSistema = $unidadesDisponiveis;
                    elseif ($campo->subtipo === 'curso' && isset($cursosDisponiveis)) $opcoesSistema = $cursosDisponiveis;
                    elseif ($campo->subtipo === 'turno' && isset($turnosDisponiveis)) $opcoesSistema = $turnosDisponiveis;
                @endphp
                
                <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border border-brand-border px-3 py-2 focus:ring-brand-purple focus:border-brand-purple bg-white text-gray-900 @error('respostas.'.$campo->name) border-red-500 @enderror">
                    <option value="">Selecione...</option>
                    @foreach($opcoesSistema as $id => $nome)
                        <option value="{{ $id }}">{{ $nome }}</option>
                    @endforeach
                </select>

            {{-- 10. TEXT & PICKERS (Input padrão estendido) --}}
            @else
                <input type="{{ in_array($campo->subtipo, ['date', 'datetime-local', 'time', 'text', 'email', 'number', 'password']) ? $campo->subtipo : 'text' }}" 
                    wire:model.live.debounce.500ms="respostas.{{ $campo->name }}" 
                    
                    @if($campo->regex_mascara)
                            x-mask="{{ $campo->regex_mascara }}"
                    @endif
                    
                    @if($campo->tamanho_min && $campo->subtipo == 'number') min="{{ $campo->tamanho_min }}" @endif
                    @if($campo->tamanho_max && $campo->subtipo == 'number') max="{{ $campo->tamanho_max }}" @endif

                    class="w-full rounded-md border border-brand-border px-3 py-2 focus:ring-brand-purple focus:border-brand-purple bg-white text-gray-900 @error('respostas.'.$campo->name) border-red-500 @enderror">
            @endif

            @error('respostas.'.$campo->name) <span class="text-red-500 text-xs font-bold mt-1 block drop-shadow-md">{{ $message }}</span> @enderror
        </div>
    </div>
@endforeach