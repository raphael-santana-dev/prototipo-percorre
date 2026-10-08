@php
    if (!function_exists('formatWppText')) {
        function formatWppText($text) {
            // Remove injeções maliciosas de código
            $text = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
            // Formata negrito, itálico e riscado
            $text = preg_replace('/\*(.*?)\*/s', '<strong>$1</strong>', $text);
            $text = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $text);
            $text = preg_replace('/\~(.*?)\~/s', '<del>$1</del>', $text);
            // Preserva as quebras de linha
            return nl2br($text);
        }
    }
@endphp

<div class="hidden col-span-3 col-span-4 col-span-6 col-span-12 md:col-span-3 md:col-span-4 md:col-span-6 md:col-span-12"></div>

@foreach($camposVigentes as $campo)
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
                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 {{ isset($config['bg_image']) ? 'text-white drop-shadow-md' : '' }}">
                    {!! formatWppText($campo->label) !!} 
                    @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
                </label>
            @endif
            
            @if($campo->tipo === 'select')
                <select wire:model.live="respostas.{{ $campo->name }}" 
                        @if(isset($podeResponder) && !$podeResponder) disabled class="w-full rounded-md border px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed opacity-70 dark:bg-gray-800 dark:border-gray-700" @else class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 dark:text-white bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" @endif>
                    <option value="">Selecione...</option>
                    @foreach($listaOpcoes as $opcao)
                        <option value="{{ trim($opcao) }}">{{ trim($opcao) }}</option>
                    @endforeach
                </select>
                
            @elseif($campo->tipo === 'radio')
                <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-2' : 'flex-wrap gap-4' }} mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="radio" value="{{ trim($opcao) }}" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($campo->tipo === 'check')
                <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-2' : 'flex-wrap gap-4' }} mt-2">
                    @foreach($listaOpcoes as $opcao)
                        <label class="inline-flex items-center {{ isset($config['bg_image']) ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">
                            <input wire:model.live="respostas.{{ $campo->name }}" type="checkbox" value="{{ trim($opcao) }}" class="form-checkbox text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                            <span class="ml-2 text-sm">{{ trim($opcao) }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($campo->tipo === 'matriz')
                @php 
                    $linhas = $config['linhas'] ?? []; 
                    $colunas = $config['colunas'] ?? []; 
                @endphp
                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mt-2">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 text-gray-500 dark:text-gray-400 font-medium w-1/3"></th>
                                @foreach($colunas as $col)
                                    <th class="p-3 text-center text-gray-600 dark:text-gray-300 font-bold border-l border-gray-200 dark:border-gray-700">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($linhas as $indexLinha => $linha)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="p-3 font-medium text-gray-800 dark:text-gray-200">{{ $linha }}</td>
                                    @foreach($colunas as $col)
                                        <td class="p-3 text-center border-l border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                            <input wire:model.live="respostas.{{ $campo->name }}.{{ $indexLinha }}" type="radio" value="{{ $col }}" class="w-4 h-4 text-purpura-600 focus:ring-purpura-500 border-gray-300 dark:border-gray-600 cursor-pointer bg-white dark:bg-gray-700">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($campo->tipo === 'html')
                <div class="{{ isset($config['bg_image']) ? 'text-white' : 'text-gray-800 dark:text-gray-200' }}">
                    @if($campo->subtipo === 'h1') <h1 class="text-3xl font-extrabold">{!! formatWppText($campo->label) !!}</h1>
                    @elseif($campo->subtipo === 'h2') <h2 class="text-2xl font-bold">{!! formatWppText($campo->label) !!}</h2>
                    @elseif($campo->subtipo === 'h3') <h3 class="text-xl font-bold">{!! formatWppText($campo->label) !!}</h3>
                    @elseif($campo->subtipo === 'p') <p class="text-base leading-relaxed">{!! formatWppText($campo->label) !!}</p>
                    @elseif($campo->subtipo === 'link') <a href="{{ $config['url'] ?? '#' }}" target="_blank" class="text-purpura-600 dark:text-purpura-400 font-bold hover:underline">{!! formatWppText($campo->label) !!}</a>
                    @elseif($campo->subtipo === 'info_card') 
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 text-blue-800 dark:text-blue-200 rounded-r-md">
                            <p class="font-bold mb-1">{!! formatWppText($campo->label) !!}</p>
                            <p class="text-sm">{!! formatWppText($config['descricao'] ?? '') !!}</p>
                        </div>
                    @endif
                </div>

            @elseif($campo->tipo === 'media')
                <div class="w-full flex justify-center mt-2 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                    @if($campo->subtipo === 'image')
                        <img src="{{ $config['url'] ?? '' }}" alt="{!! formatWppText($campo->label) !!}" class="max-w-full h-auto bg-white dark:bg-gray-800">
                    @elseif($campo->subtipo === 'video')
                        <iframe class="w-full aspect-video bg-black" src="{{ $config['url'] ?? '' }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @endif
                </div>

            @elseif($campo->tipo === 'divider')
                <hr class="border-t-2 border-dashed border-gray-300 dark:border-gray-700 my-6">

            @elseif($campo->tipo === 'social')
                @php $redesPermitidas = $config['redes_permitidas'] ?? []; @endphp
                
                @if(count($redesPermitidas) > 0)
                    <div class="space-y-3 mt-1">
                        @foreach($redesPermitidas as $redeKey)
                            <div class="flex items-center bg-white dark:bg-gray-800 border @error('respostas.'.$campo->name.'.'.$redeKey) border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 dark:border-gray-700 @enderror rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-purpura-500/25 focus-within:border-purpura-500 transition-all">
                                
                                <div class="w-12 h-10 flex items-center justify-center bg-gray-50 dark:bg-gray-900/60 border-r border-gray-200 dark:border-gray-700 text-gray-600 shrink-0">
                                    @if($redeKey === 'instagram') <i class="ph-fill ph-instagram-logo text-2xl text-pink-500"></i>
                                    @elseif($redeKey === 'facebook') <i class="ph-fill ph-facebook-logo text-2xl text-blue-600"></i>
                                    @elseif($redeKey === 'youtube') <i class="ph-fill ph-youtube-logo text-2xl text-red-600"></i>
                                    @elseif($redeKey === 'tiktok') <i class="ph-fill ph-tiktok-logo text-2xl text-gray-900 dark:text-white"></i>
                                    @elseif($redeKey === 'vsco') <i class="ph-fill ph-aperture text-2xl text-gray-800 dark:text-gray-200"></i>
                                    @elseif($redeKey === 'linkedin') <i class="ph-fill ph-linkedin-logo text-2xl text-blue-700"></i>
                                    @endif
                                </div>
                                
                                <input type="text" 
                                    wire:model.live.debounce.500ms="respostas.{{ $campo->name }}.{{ $redeKey }}" 
                                    placeholder="Qual o seu @usuario ou link do perfil?" 
                                    class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0">
                            </div>
                            @error('respostas.'.$campo->name.'.'.$redeKey) 
                                <span class="text-red-500 text-xs font-bold block drop-shadow-md">{{ $message }}</span> 
                            @enderror
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">Nenhuma rede social configurada.</p>
                @endif

            @elseif($campo->tipo === 'rating')
                <div class="flex gap-2 text-3xl" x-data="{ temp: 0, rating: @entangle('respostas.'.$campo->name) }">
                    @for($i = 1; $i <= ($config['max_stars'] ?? 5); $i++)
                        <i class="cursor-pointer transition-colors" 
                           :class="(temp >= {{ $i }} || (!temp && rating >= {{ $i }})) ? 'ph-fill ph-star text-yellow-400 drop-shadow-sm' : 'ph ph-star text-gray-300 dark:text-gray-600 bg-white dark:bg-gray-800 rounded-full'"
                           @mouseover="temp = {{ $i }}" 
                           @mouseleave="temp = 0" 
                           @click="rating = {{ $i }}">
                        </i>
                    @endfor
                </div>

            @elseif($campo->tipo === 'scale')
                @php 
                    $maxScale = $config['escala_maxima'] ?? 10;
                @endphp
                <div class="mt-2 w-full overflow-x-auto">
                    <div class="flex w-full min-w-max border border-gray-300 dark:border-gray-700 rounded-md overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                        @for($i = 0; $i <= $maxScale; $i++)
                            <label class="flex-1 relative cursor-pointer border-r last:border-r-0 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <input type="radio" wire:model.live="respostas.{{ $campo->name }}" value="{{ $i }}" class="peer sr-only" @if(isset($podeResponder) && !$podeResponder) disabled @endif>
                                <div class="py-3 px-2 text-center text-gray-600 dark:text-gray-300 text-sm font-bold peer-checked:bg-purpura-600 peer-checked:text-white transition-colors {{ isset($podeResponder) && !$podeResponder ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    {{ $i }}
                                </div>
                            </label>
                        @endfor
                    </div>
                    <div class="flex justify-between mt-1.5 text-xs text-gray-500 font-bold px-1">
                        <span>{{ $config['label_min'] ?? '' }}</span>
                        <span>{{ $config['label_max'] ?? '' }}</span>
                    </div>
                </div>

            {{-- BLOCO DEDICADO DE UPLOAD DE ARQUIVOS (IMAGEM, VÍDEO, DOCUMENTO) --}}
            @elseif($campo->tipo === 'file')
                @php
                    $tipoArquivo = $config['tipo_arquivo'] ?? 'todos';
                    $aceitaMultiplos = !empty($config['aceita_multiplos']);
                    $extensoes = $config['extensoes_permitidas'] ?? '';
                    $maxMb = $config['max_size_mb'] ?? 10;

                    $accept = '*/*';
                    if ($tipoArquivo === 'imagem') {
                        $accept = 'image/*';
                    } elseif ($tipoArquivo === 'video') {
                        $accept = 'video/*';
                    } elseif ($tipoArquivo === 'documento') {
                        $accept = '.pdf,.doc,.docx,.xls,.xlsx,.txt';
                    } elseif (!empty($extensoes)) {
                        $extensoesArray = array_map(fn($e) => '.' . ltrim(trim($e), '.'), explode(',', $extensoes));
                        $accept = implode(',', $extensoesArray);
                    }

                    $arquivosSalvos = $respostas[$campo->name] ?? null;
                    $listaSalvos = array_filter((array) $arquivosSalvos);

                    $arquivosPendentes = $uploads[$campo->name] ?? null;
                    $listaPendentes = !empty($arquivosPendentes) ? (is_array($arquivosPendentes) ? $arquivosPendentes : [$arquivosPendentes]) : [];
                @endphp

                <div class="space-y-2 mt-1" x-data="{ isDropping: false }">
                    <div class="relative border-2 border-dashed rounded-lg p-4 transition-colors text-center"
                         :class="isDropping ? 'border-purpura-500 bg-purpura-50/40 dark:bg-purpura-900/20' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800'"
                         @dragover.prevent="isDropping = true"
                         @dragleave.prevent="isDropping = false"
                         @drop="isDropping = false">
                        
                        <input type="file"
                               wire:model="uploads.{{ $campo->name }}"
                               accept="{{ $accept }}"
                               @if($aceitaMultiplos) multiple @endif
                               id="file_{{ $campo->name }}"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                               @if(isset($podeResponder) && !$podeResponder) disabled @endif>

                        <div class="flex flex-col items-center justify-center pointer-events-none py-2">
                            @if($tipoArquivo === 'imagem')
                                <i class="ph ph-image text-3xl text-purpura-600 mb-1"></i>
                            @elseif($tipoArquivo === 'video')
                                <i class="ph ph-video-camera text-3xl text-purpura-600 mb-1"></i>
                            @elseif($tipoArquivo === 'documento')
                                <i class="ph ph-file-text text-3xl text-purpura-600 mb-1"></i>
                            @else
                                <i class="ph ph-cloud-arrow-up text-3xl text-purpura-600 mb-1"></i>
                            @endif

                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Clique para selecionar ou arraste o ficheiro
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                @if(!empty($extensoes))
                                    Formatos: {{ strtoupper($extensoes) }} &middot;
                                @endif
                                Máx: {{ $maxMb }}MB {{ $aceitaMultiplos ? '(múltiplos permitidos)' : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Indicador de Upload em Andamento --}}
                    <div wire:loading wire:target="uploads.{{ $campo->name }}" class="text-xs text-purpura-600 dark:text-purpura-400 font-semibold flex items-center gap-1.5 pt-1">
                        <i class="ph-bold ph-spinner animate-spin"></i> A carregar ficheiro(s), aguarde...
                    </div>

                    {{-- Lista de Ficheiros Recém-Selecionados (Staged/Pendente de envio) --}}
                    @if(!empty($listaPendentes))
                        <div class="space-y-1.5 pt-1">
                            <span class="text-[11px] font-bold text-purpura-600 dark:text-purpura-400 uppercase tracking-wider block">
                                Ficheiro(s) Selecionado(s) (Pronto para envio):
                            </span>
                            @foreach($listaPendentes as $pIdx => $pendente)
                                @if($pendente && method_exists($pendente, 'getClientOriginalName'))
                                    <div class="flex items-center justify-between p-2 rounded-md bg-purpura-50/60 dark:bg-purpura-900/30 border border-purpura-200 dark:border-purpura-800 text-xs">
                                        <div class="flex items-center gap-2 truncate">
                                            <i class="ph-bold ph-file-arrow-up text-base text-purpura-600 shrink-0"></i>
                                            <span class="truncate text-gray-800 dark:text-gray-200 font-medium">{{ $pendente->getClientOriginalName() }}</span>
                                            <span class="text-[10px] text-gray-400 shrink-0">({{ round($pendente->getSize() / 1024, 1) }} KB)</span>
                                        </div>
                                        <button type="button" 
                                                wire:click="removerArquivo('{{ $campo->name }}'{{ $aceitaMultiplos ? ', ' . $pIdx : '' }})" 
                                                class="text-red-500 hover:text-red-700 dark:text-red-400 p-1 transition-colors" 
                                                title="Remover anexo">
                                            <i class="ph-bold ph-x-circle text-base"></i>
                                        </button>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Lista de Ficheiros Já Salvos / Registados --}}
                    @if(!empty($listaSalvos))
                        <div class="space-y-1.5 pt-2">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Ficheiro(s) Anexado(s):</span>
                            @foreach($listaSalvos as $sIdx => $path)
                                @php
                                    $nomeOriginal = basename($path);
                                    $isImagem = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $path);
                                    $isVideo = preg_match('/\.(mp4|mov|webm|mkv|avi)$/i', $path);
                                    $isPdf = preg_match('/\.pdf$/i', $path);
                                @endphp
                                <div class="flex items-center justify-between p-2 rounded-md bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 text-xs">
                                    <div class="flex items-center gap-2 truncate">
                                        @if($isImagem)
                                            <img src="{{ asset('storage/' . $path) }}" class="w-7 h-7 rounded object-cover border border-gray-200 shrink-0">
                                        @elseif($isVideo)
                                            <i class="ph ph-video-camera text-base text-blue-500 shrink-0"></i>
                                        @elseif($isPdf)
                                            <i class="ph ph-file-pdf text-base text-red-500 shrink-0"></i>
                                        @else
                                            <i class="ph ph-paperclip text-base text-gray-500 shrink-0"></i>
                                        @endif
                                        <span class="truncate text-gray-700 dark:text-gray-200 font-medium">{{ $nomeOriginal }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 ml-2">
                                        <a href="{{ asset('storage/' . $path) }}" target="_blank" class="text-purpura-600 dark:text-purpura-400 hover:text-purpura-700 font-bold">
                                            Ver ↗
                                        </a>
                                        @if(!isset($podeResponder) || $podeResponder)
                                            <button type="button" 
                                                    wire:click="removerArquivo('{{ $campo->name }}'{{ $aceitaMultiplos ? ', ' . $sIdx : '' }})" 
                                                    class="text-red-500 hover:text-red-700 dark:text-red-400 p-1 transition-colors" 
                                                    title="Excluir ficheiro">
                                                <i class="ph ph-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Erros Específicos de Upload --}}
                    @error('uploads.'.$campo->name)
                        <span class="text-red-500 text-xs font-bold block drop-shadow-md">{{ $message }}</span>
                    @enderror
                    @error('uploads.'.$campo->name.'.*')
                        <span class="text-red-500 text-xs font-bold block drop-shadow-md">{{ $message }}</span>
                    @enderror
                </div>
                
            @elseif($campo->tipo === 'system')
                @php
                    $opcoesSistema = [];
                    if ($campo->subtipo === 'unidade' && isset($unidadesDisponiveis)) $opcoesSistema = $unidadesDisponiveis;
                    elseif ($campo->subtipo === 'curso' && isset($cursosDisponiveis)) $opcoesSistema = $cursosDisponiveis;
                    elseif ($campo->subtipo === 'turno' && isset($turnosDisponiveis)) $opcoesSistema = $turnosDisponiveis;
                @endphp
                
                <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 dark:text-white dark:bg-gray-800 @error('respostas.'.$campo->name) border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 dark:border-gray-700 bg-white @enderror">
                    <option value="">Selecione...</option>
                    @foreach($opcoesSistema as $id => $nome)
                        <option value="{{ $id }}">{{ $nome }}</option>
                    @endforeach
                </select>
                
            @else
                @if($campo->subtipo === 'money')
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-bold sm:text-sm">R$</span>
                        </div>
                        <input type="text" 
                            wire:model.live.debounce.500ms="respostas.{{ $campo->name }}" 
                            x-mask:dynamic="$money($input, ',', '.')" 
                            class="w-full rounded-md border pl-10 pr-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 dark:text-white dark:bg-gray-800 @error('respostas.'.$campo->name) border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 dark:border-gray-700 bg-white @enderror"
                            placeholder="0,00">
                    </div>
                @else
                    <input type="{{ in_array($campo->subtipo, ['date', 'datetime-local', 'time', 'text', 'email', 'number', 'password', 'tel']) ? $campo->subtipo : 'text' }}" 
                        wire:model.live.debounce.500ms="respostas.{{ $campo->name }}" 
                        
                        @if($campo->subtipo === 'tel')
                            x-mask:dynamic="$input.length > 14 ? '(99) 99999-9999' : '(99) 9999-9999'"
                            placeholder="(00) 00000-0000"
                        @elseif($campo->regex_mascara) 
                            x-mask="{{ $campo->regex_mascara }}" 
                        @endif

                        @if($campo->tamanho_min && $campo->subtipo == 'number') min="{{ $campo->tamanho_min }}" @endif
                        @if($campo->tamanho_max && $campo->subtipo == 'number') max="{{ $campo->tamanho_max }}" @endif

                        class="w-full rounded-md border px-3 py-2 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900 dark:text-white dark:bg-gray-800 @error('respostas.'.$campo->name) border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 dark:border-gray-700 bg-white @enderror">
                @endif
            @endif

            @error('respostas.'.$campo->name) <span class="text-red-500 text-xs font-bold mt-1 block drop-shadow-md">{{ $message }}</span> @enderror
        </div>
    </div>
@endforeach