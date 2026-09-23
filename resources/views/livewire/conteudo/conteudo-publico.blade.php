<div class="bg-gray-50 dark:bg-gray-900 min-h-screen font-sans pb-20">
    
    {{-- CSS DO QUILL PARA RENDERIZAÇÃO PERFEITA NO FRONTEND --}}
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Ajustes para a renderização pública do Quill não parecer um editor */
        .ql-container.ql-snow { border: none !important; font-family: inherit; font-size: 1rem; }
        .ql-editor { padding: 0 !important; overflow-y: visible; }
        .ql-editor h1, .ql-editor h2, .ql-editor h3 { color: #111827; margin-bottom: 1rem; font-weight: 900; }
        .dark .ql-editor h1, .dark .ql-editor h2, .dark .ql-editor h3 { color: #f9fafb; }
        .ql-editor p { color: #374151; line-height: 1.8; margin-bottom: 1.25rem; }
        .dark .ql-editor p { color: #d1d5db; }
        .ql-editor img { border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin: 2rem auto; }
        /* Oculta scrollbars nos Stories */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    {{-- BARRA DE NAVEGAÇÃO E VOLTAR --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('portal.index') }}" class="flex items-center gap-2 text-sm font-bold text-gray-600 hover:text-purpura-600 dark:text-gray-300 dark:hover:text-purpura-400 transition-colors">
                <i class="ph-bold ph-arrow-left text-lg"></i> Voltar ao Portal
            </a>
            @if($conteudo->categoria)
                <span class="px-3 py-1 bg-purpura-50 dark:bg-purpura-900/30 text-purpura-700 dark:text-purpura-400 text-[10px] font-black uppercase tracking-wider rounded-full">
                    {{ $conteudo->categoria->nome }}
                </span>
            @endif
        </div>
    </div>

    {{-- =============== 1. FORMATO PADRÃO (NOTÍCIA) =============== --}}
    @if($conteudo->tipo === 'padrao')
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 sm:mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- COLUNA ESQUERDA: ARTIGO PRINCIPAL --}}
            <article class="lg:col-span-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                {{-- Banner Principal --}}
                <div class="relative w-full h-64 sm:h-96 bg-gray-100 dark:bg-gray-900 overflow-hidden">
                    <picture>
                        <source media="(min-width: 768px)" srcset="{{ Storage::url($conteudo->banner_desktop ?? $conteudo->banner_interno) }}">
                        <img src="{{ Storage::url($conteudo->banner_mobile ?? $conteudo->banner_interno) }}" class="w-full h-full object-cover" alt="Banner do Artigo">
                    </picture>
                    
                    @if($conteudo->texto_overlay)
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/30 to-transparent flex items-end p-6 sm:p-10">
                            <h2 class="text-2xl sm:text-4xl font-black text-white drop-shadow-md max-w-3xl">
                                {{ $conteudo->texto_overlay }}
                            </h2>
                        </div>
                    @endif
                </div>

                {{-- Cabeçalho do Artigo --}}
                <div class="px-6 sm:px-10 pt-10 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white leading-tight mb-6">
                        {{ $conteudo->titulo }}
                    </h1>
                    
                    <div class="flex flex-wrap items-center justify-between gap-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1.5"><i class="ph-fill ph-calendar-blank text-purpura-500 text-base"></i> Publicado em {{ $conteudo->data_inicio ? $conteudo->data_inicio->format('d/m/Y \à\s H:i') : $conteudo->created_at->format('d/m/Y \à\s H:i') }}</span>
                            @if($conteudo->autor)
                                <span class="flex items-center gap-1.5"><i class="ph-fill ph-user-circle text-purpura-500 text-base"></i> Por {{ $conteudo->autor->name }}</span>
                            @endif
                        </div>
                        <span class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                            <i class="ph-fill ph-eye text-base"></i> {{ number_format($conteudo->visualizacoes, 0, ',', '.') }} visualizações
                        </span>
                    </div>
                </div>

                {{-- Corpo do Artigo (Renderização Nativa do Quill) --}}
                <div class="px-6 sm:px-10 py-10">
                    <div class="ql-snow">
                        <div class="ql-editor">
                            {!! $conteudo->corpo !!}
                        </div>
                    </div>
                </div>
            </article>

            {{-- COLUNA DIREITA: TOP 5 MAIS LIDAS --}}
            <aside class="lg:col-span-4 sticky top-24">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2 mb-6 border-b border-gray-100 dark:border-gray-700 pb-3">
                        <i class="ph-fill ph-trend-up text-purpura-500"></i> Em Alta (Mais Lidas)
                    </h3>

                    @if(count($topLidas) > 0)
                        <div class="space-y-6">
                            @foreach($topLidas as $index => $top)
                                <a href="{{ route('conteudo.show', $top->slug) }}" class="flex gap-4 group">
                                    <div class="text-3xl font-black text-gray-200 dark:text-gray-700 group-hover:text-purpura-500 transition-colors pt-1">
                                        #{{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        @if($top->categoria)
                                            <span class="text-[9px] font-bold text-purpura-600 dark:text-purpura-400 uppercase tracking-widest block mb-1">
                                                {{ $top->categoria->nome }}
                                            </span>
                                        @endif
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-snug line-clamp-3 group-hover:text-purpura-600 dark:group-hover:text-purpura-400 transition-colors">
                                            {{ $top->titulo }}
                                        </h4>
                                        <div class="mt-2 text-[10px] text-gray-400 font-medium flex items-center gap-2">
                                            <span>{{ $top->data_inicio ? $top->data_inicio->diffForHumans() : $top->created_at->diffForHumans() }}</span>
                                            <span>•</span>
                                            <span>{{ number_format($top->visualizacoes, 0, ',', '.') }} leituras</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-500 italic">Nenhuma outra publicação disponível no momento.</p>
                    @endif
                </div>
            </aside>
        </div>
    @endif

    {{-- =============== 2. FORMATO CARROSSEL =============== --}}
    @if($conteudo->tipo === 'carrossel')
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
            <div class="mb-8 text-center max-w-3xl mx-auto">
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white mb-4">{{ $conteudo->titulo }}</h1>
                <div class="ql-snow"><div class="ql-editor !text-center">{!! $conteudo->corpo !!}</div></div>
            </div>

            <div x-data="{ active: 0, total: {{ count($slides) }} }" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                
                <div class="relative h-[50vh] sm:h-[70vh] bg-black">
                    @foreach($slides as $index => $slide)
                        <div x-show="active === {{ $index }}" 
                             x-transition.opacity.duration.500ms
                             class="absolute inset-0 flex items-center justify-center">
                             
                            <img src="{{ Storage::url($slide['imagem_path']) }}" class="w-full h-full object-contain sm:object-cover">
                            
                            @if(!empty($slide['texto']))
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-8 md:p-12 text-center">
                                    <p class="text-white text-lg sm:text-2xl font-bold drop-shadow-lg max-w-3xl mx-auto">
                                        {{ $slide['texto'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Setas Navegação --}}
                    <button @click="active = active === 0 ? total - 1 : active - 1" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/30 backdrop-blur-md rounded-full text-white flex items-center justify-center transition">
                        <i class="ph-bold ph-caret-left text-xl"></i>
                    </button>
                    <button @click="active = active === total - 1 ? 0 : active + 1" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/30 backdrop-blur-md rounded-full text-white flex items-center justify-center transition">
                        <i class="ph-bold ph-caret-right text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- =============== 3. FORMATO STORY =============== --}}
    @if($conteudo->tipo === 'story')
        
        {{-- O FUNDO BORRADO DA LISTAGEM (EFEITO VISUAL) --}}
        <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none opacity-40 dark:opacity-20 flex flex-col pt-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-8 filter blur-lg transform scale-110">
                @foreach($noticiasFundo as $fundo)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl h-64 shadow-lg border border-gray-100 opacity-80 overflow-hidden">
                        <div class="h-32 bg-gray-200 dark:bg-gray-700">
                            @if($fundo->banner_desktop || $fundo->banner_interno)
                                <img src="{{ Storage::url($fundo->banner_desktop ?? $fundo->banner_interno) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="p-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="fixed inset-0 z-[100] bg-black/70 backdrop-blur-xl flex justify-center items-center">
            
            {{-- Fechar Story --}}
            <a href="{{ route('portal.index') }}" class="absolute top-6 right-6 z-50 w-10 h-10 bg-white/10 hover:bg-white/30 backdrop-blur-md rounded-full text-white flex items-center justify-center transition">
                <i class="ph-bold ph-x text-xl"></i>
            </a>

            <div x-data="{ 
                active: 0, 
                total: {{ count($slides) }},
                progress: 0,
                autoplayTimer: null,
                paused: false,
                startAutoplay() {
                    clearInterval(this.autoplayTimer);
                    this.progress = 0;
                    this.autoplayTimer = setInterval(() => {
                        if(!this.paused) {
                            this.progress += 2; // 2% a cada 100ms = 5 segundos por slide
                            if(this.progress >= 100) {
                                this.next();
                            }
                        }
                    }, 100);
                },
                next() {
                    if(this.active < this.total - 1) {
                        this.active++;
                        this.startAutoplay();
                    } else {
                        // Se terminar o último Story, volta ao portal
                        window.location.href = '{{ route('portal.index') }}'; 
                    }
                },
                prev() {
                    if(this.active > 0) {
                        this.active--;
                        this.startAutoplay();
                    }
                }
             }" 
             x-init="startAutoplay()"
             class="relative w-full max-w-md h-full sm:h-[90vh] sm:rounded-2xl overflow-hidden bg-black shadow-2xl">
            
            {{-- Barras de Progresso Animadas no Topo --}}
            <div class="absolute top-4 left-4 right-4 z-40 flex gap-1.5">
                @foreach($slides as $index => $slide)
                    <div class="h-1 flex-1 bg-white/30 rounded-full overflow-hidden">
                        <div class="h-full bg-white" 
                             :style="active > {{ $index }} ? 'width: 100%' : (active === {{ $index }} ? 'width: ' + progress + '%' : 'width: 0%')"></div>
                    </div>
                @endforeach
            </div>

            {{-- Título Curto (Overlay) --}}
            <div class="absolute top-8 left-4 right-16 z-40">
                <h1 class="text-white text-sm font-bold drop-shadow-md truncate">{{ $conteudo->titulo }}</h1>
            </div>

            {{-- Slides do Story --}}
            @foreach($slides as $index => $slide)
                @php
                    $posClass = match($slide['posicao_texto'] ?? 'bottom') {
                        'top' => 'top-16',
                        'center' => 'top-1/2 -translate-y-1/2',
                        default => 'bottom-0',
                    };
                    $bgGradient = match($slide['posicao_texto'] ?? 'bottom') {
                        'top' => 'bg-gradient-to-b from-black/80 to-transparent pt-12 pb-10',
                        'center' => 'bg-black/60 backdrop-blur-sm py-4 rounded-xl mx-4',
                        default => 'bg-gradient-to-t from-black/90 via-black/50 to-transparent pt-20 pb-8',
                    };
                @endphp

                <div x-show="active === {{ $index }}" 
                     x-transition.opacity.duration.300ms
                     class="absolute inset-0 w-full h-full">
                     
                    <img src="{{ Storage::url($slide['imagem_path']) }}" class="w-full h-full object-cover">
                    
                    @if(!empty($slide['texto']))
                        <div class="absolute inset-x-0 {{ $posClass }} {{ $bgGradient }} px-6 flex flex-col justify-end pointer-events-none">
                            <div class="text-white text-base font-medium text-center drop-shadow-lg leading-tight ql-editor !p-0">
                                {!! $slide['texto'] !!}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Zonas de Interação (Toque e Clique) --}}
            {{-- Segurar o dedo (touchstart/mousedown) pausa o Story. Largar (touchend/mouseup) retoma. --}}
            <div class="absolute inset-y-0 left-0 w-1/3 z-30 cursor-pointer" 
                 @click="prev()"
                 @touchstart="paused = true" @touchend="paused = false"
                 @mousedown="paused = true" @mouseup="paused = false"></div>
                 
            <div class="absolute inset-y-0 right-0 w-2/3 z-30 cursor-pointer" 
                 @click="next()"
                 @touchstart="paused = true" @touchend="paused = false"
                 @mousedown="paused = true" @mouseup="paused = false"></div>
        </div>
        </div>
    @endif

</div>