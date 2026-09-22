<div class="bg-gray-50 dark:bg-gray-900 min-h-screen font-sans pb-20">
    
    {{-- BARRA SUPERIOR E PESQUISA --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 pt-8 pb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Portal Editorial</h1>
                    <p class="text-sm text-gray-500 mt-1">Acompanhe as últimas atualizações, avisos e conteúdos exclusivos.</p>
                </div>
                <div class="relative w-full md:w-96">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input wire:model.live.debounce.500ms="termoBusca" type="text" placeholder="Procurar publicações..." class="w-full pl-10 pr-4 py-3 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-purpura-500 focus:border-purpura-500 shadow-sm transition dark:bg-gray-900 dark:border-gray-700 dark:text-white dark:focus:bg-gray-800">
                </div>
            </div>
            
            {{-- FILTROS DE CATEGORIA EM PILLS --}}
            <div class="flex flex-wrap items-center gap-2 mt-6">
                <button wire:click="setCategoria('')" class="px-4 py-1.5 rounded-full text-xs font-bold transition-colors {{ $categoriaFiltro === '' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                    Todas
                </button>
                @foreach($categoriasDb as $cat)
                    <button wire:click="setCategoria('{{ $cat->id }}')" class="px-4 py-1.5 rounded-full text-xs font-bold transition-colors {{ $categoriaFiltro === (string)$cat->id ? 'bg-purpura-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                        {{ $cat->nome }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-4">
        
        {{-- CARROSSEL DE DESTAQUES (Visível apenas na vista inicial sem filtros) --}}
        @if($destaques->count() > 0)
            <div x-data="{ activeSlide: 0, slides: {{ $destaques->count() }}, timer: null }"
                 x-init="timer = setInterval(() => { activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1 }, 6000)"
                 class="relative w-full h-[60vh] min-h-[450px] max-h-[600px] rounded-2xl overflow-hidden mb-12 shadow-xl bg-gray-900">
                
                @foreach($destaques as $index => $destaque)
                    <div x-show="activeSlide === {{ $index }}"
                         x-transition:enter="transition-opacity ease-linear duration-500"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition-opacity ease-linear duration-500"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute inset-0 w-full h-full">

                        {{-- MAGIA DA RESPONSIVIDADE NATIVA: Desktop(16:9) vs Mobile(4:5) --}}
                        <picture>
                            <source media="(min-width: 768px)" srcset="{{ Storage::url($destaque->banner_destaque ?? $destaque->banner_desktop ?? $destaque->banner_interno) }}">
                            <img src="{{ Storage::url($destaque->banner_destaque ?? $destaque->banner_mobile ?? $destaque->banner_interno) }}" class="w-full h-full object-cover transform scale-105 hover:scale-100 transition-transform duration-[10000ms] ease-out" alt="Capa Destaque">
                        </picture>

                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent flex flex-col justify-end p-8 md:p-14">
                            <div class="max-w-4xl">
                                @if($destaque->categoria)
                                    <span class="inline-block px-3 py-1 bg-purpura-600 text-white text-[10px] font-bold rounded mb-4 uppercase tracking-widest shadow">
                                        {{ $destaque->categoria->nome }}
                                    </span>
                                @endif
                                
                                <h2 class="text-3xl md:text-5xl font-black text-white leading-tight mb-4 drop-shadow-md">
                                    {{ $destaque->titulo }}
                                </h2>
                                
                                @php
                                    $opcoesVisuais = is_string($destaque->opcoes_visuais) ? json_decode($destaque->opcoes_visuais, true) : ($destaque->opcoes_visuais ?? []);
                                    $txtOverlay = $opcoesVisuais['texto_destaque_overlay'] ?? $destaque->texto_overlay;
                                @endphp
                                
                                @if($txtOverlay)
                                    <p class="text-gray-200 text-base md:text-lg mb-6 line-clamp-2 drop-shadow">
                                        {{ $txtOverlay }}
                                    </p>
                                @endif
                                
                                <a href="{{ route('conteudo.show', $destaque->slug) }}" class="inline-flex items-center gap-2 text-white bg-white/20 hover:bg-white/30 backdrop-blur-md px-6 py-3 rounded-lg font-bold transition-all mt-2">
                                    Aceder ao Conteúdo <i class="ph-bold ph-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- NAVEGAÇÃO DO CARROSSEL (Bolinhas) --}}
                @if($destaques->count() > 1)
                    <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-3 z-20">
                        @foreach($destaques as $index => $destaque)
                            <button @click="activeSlide = {{ $index }}; clearInterval(timer); timer = setInterval(() => { activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1 }, 6000)" 
                                    class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                                    :class="activeSlide === {{ $index }} ? 'bg-white scale-125 w-6' : 'bg-white/50 hover:bg-white/80'"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- GRELHA DE NOTÍCIAS --}}
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                <i class="ph-fill ph-squares-four text-purpura-500"></i> Últimas Publicações
            </h3>
        </div>

        @if($noticias->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($noticias as $noticia)
                    <a href="{{ route('conteudo.show', $noticia->slug) }}" class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1">
                        
                        {{-- CAPA DO CARD --}}
                        <div class="relative h-48 w-full overflow-hidden bg-gray-100 dark:bg-gray-900">
                            <picture>
                                <source media="(min-width: 768px)" srcset="{{ Storage::url($noticia->banner_desktop ?? $noticia->banner_interno) }}">
                                <img src="{{ Storage::url($noticia->banner_mobile ?? $noticia->banner_interno) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Capa">
                            </picture>
                            
                            @if($noticia->tipo === 'carrossel')
                                <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white p-1.5 rounded-lg shadow-sm">
                                    <i class="ph-fill ph-images text-lg"></i>
                                </div>
                            @elseif($noticia->tipo === 'story')
                                <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white p-1.5 rounded-lg shadow-sm">
                                    <i class="ph-fill ph-instagram-logo text-lg"></i>
                                </div>
                            @endif

                            @if($noticia->categoria)
                                <div class="absolute bottom-3 left-3">
                                    <span class="px-2 py-1 bg-white/90 dark:bg-gray-900/90 backdrop-blur text-purpura-700 dark:text-purpura-400 text-[9px] font-black uppercase tracking-wider rounded shadow-sm">
                                        {{ $noticia->categoria->nome }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- CONTEÚDO DO CARD --}}
                        <div class="p-5 flex flex-col flex-1">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white leading-snug group-hover:text-purpura-600 dark:group-hover:text-purpura-400 transition-colors line-clamp-3 mb-3">
                                {{ $noticia->titulo }}
                            </h4>
                            
                            <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 font-medium">
                                <span class="flex items-center gap-1.5">
                                    <i class="ph-fill ph-calendar-blank"></i>
                                    {{ $noticia->data_inicio ? $noticia->data_inicio->format('d M, Y') : $noticia->created_at->format('d M, Y') }}
                                </span>
                                <span class="flex items-center gap-1 text-purpura-600 dark:text-purpura-400 group-hover:translate-x-1 transition-transform">
                                    Ler mais <i class="ph-bold ph-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $noticias->links() }}
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center shadow-sm">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ph-fill ph-newspaper text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Nenhum conteúdo encontrado</h3>
                <p class="text-sm text-gray-500 mt-1">Ainda não existem publicações ativas para o filtro seleccionado.</p>
            </div>
        @endif

    </div>
</div>