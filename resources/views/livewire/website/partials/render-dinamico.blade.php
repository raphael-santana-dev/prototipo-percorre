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
        
        $bgStyle = "";
        $overlayStyle = "";
        if(isset($config['bg_image']) && !empty($config['bg_image'])) {
            $bgStyle = "background-image: url('{$config['bg_image']}'); background-size: cover; background-position: center;";
            $opacity = $config['bg_opacity'] ?? '0.5';
            $color = $config['bg_color'] ?? '#000000';
            $overlayStyle = "background-color: {$color}; opacity: {$opacity};";
        }

        $layoutOpcoes = $config['layout_opcoes'] ?? 'horizontal';
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
                    if (atual === null || atual === undefined) atual = '';
                    
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
        @if(isset($config['bg_image']))
            <div class="absolute inset-0 z-0 pointer-events-none" style="{{ $overlayStyle }}"></div>
        @endif

        <div class="relative z-10">
            @if(!in_array($campo->tipo, ['html', 'divider', 'social', 'media']))
                <label class="block text-sm font-semibold text-gray-800 mb-2 {{ isset($config['bg_image']) ? 'text-white drop-shadow-md' : '' }}">
                    {{ $campo->label }} 
                    @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
                </label>
            @endif
            
            @if($campo->tipo === 'select')
                <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 @error('respostas.'.$campo->name) border-red-500 bg-red-50 @else border-gray-300 bg-white @enderror">
                    <option value="">Selecione...</option>
                    @foreach($listaOpcoes as $opcao)
                        <option value="{{ trim($opcao) }}">{{ trim($opcao) }}</option>
                    @endforeach
                </select>
                
            @elseif($campo->tipo === 'radio')
                <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-2' : 'flex-wrap gap-4' }} mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="radio" value="{{ trim($opcao) }}" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($campo->tipo === 'check')
                <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-2' : 'flex-wrap gap-4' }} mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="checkbox" value="{{ trim($opcao) }}" class="form-checkbox text-purpura-600 focus:ring-purpura-500 bg-white">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

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
                                            <input wire:model.live="respostas.{{ $campo->name }}.{{ $indexLinha }}" type="radio" value="{{ $col }}" class="w-4 h-4 text-purpura-600 focus:ring-purpura-500 border-gray-300 cursor-pointer bg-white">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($campo->tipo === 'html')
                <div class="{{ isset($config['bg_image']) ? 'text-white' : 'text-gray-800' }}">
                    @if($campo->subtipo === 'h1') <h1 class="text-3xl font-extrabold">{{ $campo->label }}</h1>
                    @elseif($campo->subtipo === 'h2') <h2 class="text-2xl font-bold">{{ $campo->label }}</h2>
                    @elseif($campo->subtipo === 'h3') <h3 class="text-xl font-bold">{{ $campo->label }}</h3>
                    @elseif($campo->subtipo === 'p') <p class="text-base leading-relaxed">{{ $campo->label }}</p>
                    @elseif($campo->subtipo === 'link') <a href="{{ $config['url'] ?? '#' }}" target="_blank" class="text-purpura-600 font-bold hover:underline">{{ $campo->label }}</a>
                    @elseif($campo->subtipo === 'info_card') 
                        <div class="p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                            <p class="font-bold mb-1">{{ $campo->label }}</p>
                            <p class="text-sm">{{ $config['descricao'] ?? '' }}</p>
                        </div>
                    @endif
                </div>

            @elseif($campo->tipo === 'media')
                <div class="w-full flex justify-center mt-2 rounded-lg overflow-hidden border border-gray-100">
                    @if($campo->subtipo === 'image')
                        <img src="{{ $config['url'] ?? '' }}" alt="{{ $campo->label }}" class="max-w-full h-auto bg-white">
                    @elseif($campo->subtipo === 'video')
                        <iframe class="w-full aspect-video bg-black" src="{{ $config['url'] ?? '' }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @endif
                </div>

            @elseif($campo->tipo === 'divider')
                <hr class="border-t-2 border-dashed border-gray-300 my-6">

            @elseif($campo->tipo === 'social')
                @php $redes = $config['redes'] ?? []; @endphp
                <div class="flex flex-wrap gap-4 items-center justify-center py-4">
                    @foreach($redes as $rede)
                        <a href="{{ $rede['url'] }}" target="_blank" class="p-3 bg-white rounded-full shadow-sm hover:bg-gray-100 transition text-gray-700 hover:text-purpura-600">
                            <i class="text-2xl ph-fill ph-{{ strtolower($rede['nome']) }}"></i>
                        </a>
                    @endforeach
                </div>

            @elseif($campo->tipo === 'rating')
                <div class="flex gap-2 text-3xl" x-data="{ temp: 0, rating: @entangle('respostas.'.$campo->name) }">
                    @for($i = 1; $i <= ($config['max_stars'] ?? 5); $i++)
                        <i class="cursor-pointer transition-colors" 
                           :class="(temp >= {{ $i }} || (!temp && rating >= {{ $i }})) ? 'ph-fill ph-star text-yellow-400 drop-shadow-sm' : 'ph ph-star text-gray-300 bg-white rounded-full'"
                           @mouseover="temp = {{ $i }}" 
                           @mouseleave="temp = 0" 
                           @click="rating = {{ $i }}">
                        </i>
                    @endfor
                </div>
                
            @elseif($campo->tipo === 'system')
                @php
                    $opcoesSistema = [];
                    if ($campo->subtipo === 'unidade' && isset($unidadesDisponiveis)) $opcoesSistema = $unidadesDisponiveis;
                    elseif ($campo->subtipo === 'curso' && isset($cursosDisponiveis)) $opcoesSistema = $cursosDisponiveis;
                    elseif ($campo->subtipo === 'turno' && isset($turnosDisponiveis)) $opcoesSistema = $turnosDisponiveis;
                @endphp
                
                <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 @error('respostas.'.$campo->name) border-red-500 bg-red-50 @else border-gray-300 bg-white @enderror">
                    <option value="">Selecione...</option>
                    @foreach($opcoesSistema as $id => $nome)
                        <option value="{{ $id }}">{{ $nome }}</option>
                    @endforeach
                </select>
                
            @else
                <input type="{{ in_array($campo->subtipo, ['date', 'datetime-local', 'time', 'text', 'email', 'number', 'password']) ? $campo->subtipo : 'text' }}" 
                    wire:model.live.debounce.500ms="respostas.{{ $campo->name }}" 
                    
                    @if($campo->regex_mascara) x-mask="{{ $campo->regex_mascara }}" @endif
                    @if($campo->tamanho_min && $campo->subtipo == 'number') min="{{ $campo->tamanho_min }}" @endif
                    @if($campo->tamanho_max && $campo->subtipo == 'number') max="{{ $campo->tamanho_max }}" @endif

                    class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 @error('respostas.'.$campo->name) border-red-500 bg-red-50 @else border-gray-300 bg-white @enderror">
            @endif

            @error('respostas.'.$campo->name) <span class="text-red-500 text-xs font-bold mt-1 block drop-shadow-md">{{ $message }}</span> @enderror
        </div>
    </div>
@endforeach