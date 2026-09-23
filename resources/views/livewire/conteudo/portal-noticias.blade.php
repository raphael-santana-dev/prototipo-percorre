<div class="bg-white dark:bg-gray-900 min-h-screen font-sans pb-20">
    
    {{-- BARRA SUPERIOR E PESQUISA --}}
    <div class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 pt-8 pb-6">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purpura-600 rounded-full flex items-center justify-center text-white">
                        <i class="ph-bold ph-compass text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Portal Editorial</h1>
                </div>
                
                {{-- Navegação de Categorias (Estilo Navbar da Imagem) --}}
                <div class="hidden lg:flex items-center gap-6 text-sm font-bold text-gray-600 dark:text-gray-300">
                    <button wire:click="setCategoria('')" class="{{ $categoriaFiltro === '' ? 'text-purpura-600 border-b-2 border-purpura-600 pb-1' : 'hover:text-purpura-600 transition' }}">Todas</button>
                    @foreach($categoriasDb as $cat)
                        <button wire:click="setCategoria('{{ $cat->id }}')" class="{{ $categoriaFiltro === (string)$cat->id ? 'text-purpura-600 border-b-2 border-purpura-600 pb-1' : 'hover:text-purpura-600 transition' }}">{{ $cat->nome }}</button>
                    @endforeach
                </div>

                <div class="relative w-full md:w-72">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input wire:model.live.debounce.500ms="termoBusca" type="text" placeholder="Pesquisar..." class="w-full pl-10 pr-4 py-2 rounded-full border-gray-200 bg-gray-50 focus:bg-white focus:ring-purpura-500 focus:border-purpura-500 text-sm shadow-sm transition dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </div>
            </div>
        </div>
    </div>

    {{-- APENAS MOSTRA DESTAQUES E STORIES SE NÃO HOUVER FILTROS ATIVOS --}}
    @if(empty($categoriaFiltro) && empty($termoBusca))
        
        {{-- ================= HERO SECTION (DESTAQUES) ================= --}}
        @if($destaques->count() > 0)
            <div x-data="{ activeSlide: 0, slides: {{ $destaques->count() }}, timer: null }"
                 x-init="timer = setInterval(() => { activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1 }, 7000)"
                 class="relative w-full h-[75vh] min-h-[500px] bg-gray-900 overflow-hidden">
                
                @foreach($destaques as $index => $destaque)
                    <div x-show="activeSlide === {{ $index }}"
                         x-transition:enter="transition-opacity ease-linear duration-500"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition-opacity ease-linear duration-500"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute inset-0 w-full h-full">

                        <picture>
                            <source media="(min-width: 768px)" srcset="{{ Storage::url($destaque->banner_destaque ?? $destaque->banner_desktop ?? $destaque->banner_interno) }}">
                            <img src="{{ Storage::url($destaque->banner_destaque ?? $destaque->banner_mobile ?? $destaque->banner_interno) }}" class="w-full h-full object-cover" alt="Hero">
                        </picture>

                        <div class="absolute inset-0 bg-gradient-to-r from-gray-900/90 via-gray-900/50 to-transparent flex flex-col justify-center p-8 md:p-20">
                            <div class="max-w-3xl">
                                @if($destaque->categoria)
                                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 backdrop-blur text-white text-[10px] font-bold rounded-full mb-6 uppercase tracking-widest border border-white/30">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div> {{ $destaque->categoria->nome }}
                                    </span>
                                @endif
                                
                                <h2 class="text-4xl md:text-6xl font-black text-white leading-[1.1] mb-6 drop-shadow-lg">
                                    {{ $destaque->titulo }}
                                </h2>
                                
                                <a href="{{ route('conteudo.show', $destaque->slug) }}" class="inline-flex items-center gap-2 text-white bg-transparent border border-white hover:bg-white hover:text-gray-900 px-6 py-3 rounded-full text-sm font-bold transition-all">
                                    Ler História <i class="ph-bold ph-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Navegação Inferior do Hero (Estilo Referência) --}}
                @if($destaques->count() > 1)
                    <div class="absolute bottom-0 left-0 right-0 bg-black/40 backdrop-blur-md border-t border-white/20 hidden md:flex h-20">
                        @foreach($destaques as $index => $destaque)
                            <button @click="activeSlide = {{ $index }}; clearInterval(timer); timer = setInterval(() => { activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1 }, 7000)" 
                                    class="flex-1 flex items-center gap-3 px-6 border-r border-white/10 hover:bg-white/10 transition text-left group"
                                    :class="activeSlide === {{ $index }} ? 'bg-white/10 relative' : ''">
                                
                                <div x-show="activeSlide === {{ $index }}" class="absolute top-0 left-0 right-0 h-1 bg-purpura-500"></div>
                                
                                <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full border border-white/30 text-white text-xs font-bold group-hover:border-white transition">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-white text-xs font-medium line-clamp-2 opacity-80 group-hover:opacity-100 transition">
                                    {{ $destaque->titulo }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            {{-- ================= FEATURED STORIES ================= --}}
            @if($storiesRow->count() > 0)
                <div class="mb-16">
                    <div class="flex justify-between items-end mb-6">
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white border-l-4 border-purpura-600 pl-4 leading-none">
                            Últimos Stories
                        </h3>
                        <button wire:click="$set('filtro_tipo', 'story')" class="text-sm font-bold text-gray-500 hover:text-purpura-600 flex items-center gap-1 transition">
                            Ver todos <i class="ph-bold ph-arrow-right"></i>
                        </button>
                    </div>

                    <div class="flex gap-4 overflow-x-auto pb-6 snap-x custom-scrollbar">
                        @foreach($storiesRow as $story)
                            <a href="{{ route('conteudo.show', $story->slug) }}" class="min-w-[160px] sm:min-w-[200px] h-64 sm:h-72 rounded-2xl relative overflow-hidden snap-start group shadow-sm">
                                <img src="{{ Storage::url($story->banner_mobile ?? $story->banner_interno) }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent flex flex-col justify-end p-4">
                                    @if($story->categoria)
                                        <span class="inline-block px-2 py-0.5 bg-white/20 backdrop-blur text-white text-[9px] font-bold rounded mb-2 uppercase w-max border border-white/20">
                                            {{ $story->categoria->nome }}
                                        </span>
                                    @endif
                                    <h4 class="text-white font-bold text-sm leading-snug line-clamp-3">{{ $story->titulo }}</h4>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 {{ (empty($categoriaFiltro) && empty($termoBusca)) ? 'mt-8' : 'mt-12' }}">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            {{-- ================= COLUNA ESQUERDA: THE LATEST ================= --}}
            <div class="lg:col-span-8">
                <h3 class="text-2xl font-black text-gray-900 dark:text-white border-l-4 border-purpura-600 pl-4 leading-none mb-8">
                    @if(!empty($termoBusca)) Resultados da Pesquisa @elseif(!empty($categoriaFiltro)) Publicações da Categoria @else As Últimas @endif
                </h3>

                @if($noticias->count() > 0)
                    <div class="space-y-8">
                        @foreach($noticias as $noticia)
                            <a href="{{ route('conteudo.show', $noticia->slug) }}" class="flex flex-col sm:flex-row gap-6 group items-start border-b border-gray-100 dark:border-gray-800 pb-8 last:border-0">
                                
                                {{-- Imagem Lado Esquerdo --}}
                                <div class="relative w-full sm:w-64 h-48 sm:h-40 shrink-0 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 shadow-sm">
                                    <picture>
                                        <source media="(min-width: 768px)" srcset="{{ Storage::url($noticia->banner_desktop ?? $noticia->banner_interno) }}">
                                        <img src="{{ Storage::url($noticia->banner_mobile ?? $noticia->banner_interno) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    </picture>
                                    
                                    {{-- BADGES DE FORMATO SOBRE A IMAGEM --}}
                                    @if($noticia->tipo === 'carrossel')
                                        <div class="absolute top-2 right-2 bg-black/70 backdrop-blur text-white px-2 py-1 rounded shadow text-[10px] font-bold flex items-center gap-1 uppercase tracking-wider">
                                            <i class="ph-fill ph-images text-sm"></i> Galeria
                                        </div>
                                    @elseif($noticia->tipo === 'story')
                                        <div class="absolute top-2 right-2 bg-pink-600/90 backdrop-blur text-white px-2 py-1 rounded shadow text-[10px] font-bold flex items-center gap-1 uppercase tracking-wider">
                                            <i class="ph-fill ph-instagram-logo text-sm"></i> Story
                                        </div>
                                    @endif
                                </div>

                                {{-- Textos Lado Direito --}}
                                <div class="flex flex-col flex-1 py-1">
                                    @if($noticia->categoria)
                                        <span class="text-[10px] font-black text-purpura-600 dark:text-purpura-400 uppercase tracking-widest mb-2 block">
                                            {{ $noticia->categoria->nome }}
                                        </span>
                                    @endif
                                    
                                    <h4 class="text-xl font-black text-gray-900 dark:text-white leading-snug group-hover:text-purpura-600 dark:group-hover:text-purpura-400 transition-colors mb-3">
                                        {{ $noticia->titulo }}
                                    </h4>
                                    
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                        {{ $noticia->texto_overlay ?? strip_tags($noticia->corpo) }}
                                    </p>

                                    <div class="mt-auto flex items-center gap-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <i class="ph-bold ph-clock"></i>
                                            {{ $noticia->data_inicio ? $noticia->data_inicio->diffForHumans() : $noticia->created_at->diffForHumans() }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="ph-fill ph-eye"></i> {{ number_format($noticia->visualizacoes, 0, ',', '.') }}
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
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                        <i class="ph-fill ph-newspaper text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sem resultados</h3>
                        <p class="text-sm text-gray-500 mt-1">Nenhuma publicação encontrada para o critério de pesquisa.</p>
                    </div>
                @endif
            </div>

            {{-- ================= COLUNA DIREITA: TRENDING SIDEBAR ================= --}}
            @if(empty($categoriaFiltro) && empty($termoBusca) && $trending->count() > 0)
                <div class="lg:col-span-4">
                    <div class="sticky top-24">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 mb-8">
                            Em Alta <i class="ph-fill ph-fire text-green-500"></i>
                        </h3>

                        <div class="space-y-6">
                            @foreach($trending as $index => $trend)
                                <a href="{{ route('conteudo.show', $trend->slug) }}" class="flex gap-4 group items-start">
                                    <div class="text-4xl font-black text-gray-200 dark:text-gray-700 group-hover:text-green-500 transition-colors pt-1">
                                        #{{ $index + 1 }}
                                    </div>
                                    <div class="flex-1 pt-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-snug line-clamp-3 group-hover:text-purpura-600 dark:group-hover:text-purpura-400 transition-colors mb-2">
                                            {{ $trend->titulo }}
                                        </h4>
                                        <div class="text-[10px] text-gray-400 font-medium flex items-center gap-2">
                                            <span>{{ $trend->data_inicio ? $trend->data_inicio->diffForHumans() : $trend->created_at->diffForHumans() }}</span>
                                            <span>•</span>
                                            <span>{{ number_format($trend->visualizacoes, 0, ',', '.') }} leituras</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        
                        <div class="mt-8 bg-purpura-50 dark:bg-purpura-900/20 rounded-2xl p-6 text-center border border-purpura-100 dark:border-purpura-900/50">
                            <h4 class="text-sm font-bold text-purpura-900 dark:text-purpura-300 mb-2">Fique por dentro!</h4>
                            <p class="text-xs text-purpura-700 dark:text-purpura-400 mb-4">Aceda diariamente para ver os novos conteúdos, dicas e comunicados da instituição.</p>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>