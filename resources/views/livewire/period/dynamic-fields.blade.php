<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #9ca3af; }
    </style>
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Ciclos
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Construtor do Formulário</h2>
            <p class="text-gray-500 text-sm">Gerenciando os campos de inscrição para: <span class="font-bold text-purpura-600">{{ $ciclo->nome }}</span></p>
        </div>
        
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-md font-medium">
                <i class="ph ph-squares-four"></i> {{ $camposCadastrados->count() }} Blocos
            </span>
        </div>
    </div>

    @if (session()->has('sucesso'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg shadow-sm flex items-center gap-2 font-medium">
            <i class="ph-fill ph-check-circle text-xl text-green-500"></i> {{ session('sucesso') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- ========================================== --}}
        {{-- COLUNA ESQUERDA: CANVAS / PREVIEW (8/12)   --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- SAFELIST DO TAILWIND: Não apague esta div --}}
            <div class="hidden col-span-3 col-span-4 col-span-6 col-span-12 md:col-span-3 md:col-span-4 md:col-span-6 md:col-span-12"></div>

            <!-- Formulário Visual (Folha de Papel) -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8 md:p-12 min-h-[600px] relative">
                
                <!-- Cabeçalho Falso do Formulário -->
                <div class="mb-10 border-b border-gray-100 pb-6">
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Simulador da Inscrição</h1>
                    <p class="text-gray-500">Abaixo está a estrutura de como o aluno verá este formulário.</p>
                </div>

                @forelse($camposPorEtapa as $numEtapa => $camposDaEtapa)
                    <div class="mb-12" wire:key="grupo-etapa-{{ $numEtapa }}">
                        
                        <div class="flex items-center gap-3 mb-6">
                            <span class="flex items-center justify-center w-7 h-7 text-sm font-bold text-white bg-indigo-600 rounded-full shadow-sm">{{ $numEtapa }}</span>
                            <h3 class="text-xl font-bold text-gray-800">Etapa {{ $numEtapa }}</h3>
                            <div class="flex-1 h-px bg-gray-200 ml-4"></div>
                        </div>
                        
                        <div class="grid grid-cols-12 gap-x-6 gap-y-4">
                            @foreach($camposDaEtapa as $c)
                                @php 
                                    $isActive = $campoId == $c->id; 
                                    $colSpan = "col-span-12 md:col-span-{$c->largura}";
                                    
                                    // Pega Configurações Visuais para o Preview
                                    $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                    $bgStyle = isset($cfg['bg_image']) && !empty($cfg['bg_image']) ? "background-image: url('{$cfg['bg_image']}'); background-size: cover; background-position: center;" : "";
                                @endphp

                                <div wire:key="campo-{{ $c->id }}" class="{{ $colSpan }} relative group rounded-lg transition-all duration-200 {{ $isActive ? 'ring-2 ring-indigo-500 shadow-md p-4' : 'border border-transparent hover:border-gray-200 hover:bg-gray-50 p-4 cursor-pointer' }} {{ $bgStyle ? 'overflow-hidden' : '' }}" style="{{ $isActive && !$bgStyle ? 'background-color: #f8fafc;' : '' }} {{ $bgStyle }}" wire:click="editar({{ $c->id }})">
                                    
                                    @if($bgStyle)
                                        <div class="absolute inset-0 z-0 pointer-events-none" style="background-color: {{ $cfg['bg_color'] ?? '#000' }}; opacity: {{ $cfg['bg_opacity'] ?? '0.5' }};"></div>
                                    @endif

                                    <!-- Toolbar Flutuante de Ações -->
                                    <div class="absolute right-2 -top-4 {{ $isActive ? 'flex' : 'hidden group-hover:flex' }} gap-1 bg-white border border-gray-200 shadow-md rounded-md overflow-hidden z-20 text-gray-600">
                                        <button wire:click.stop="editar({{ $c->id }})" class="p-2 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Editar Campo">
                                            <i class="ph ph-pencil-simple text-base"></i>
                                        </button>
                                        <button wire:click.stop="excluir({{ $c->id }})" wire:confirm="Tem certeza que deseja excluir este campo?" class="p-2 hover:bg-red-50 hover:text-red-600 transition border-l border-gray-100" title="Excluir Campo">
                                            <i class="ph ph-trash text-base"></i>
                                        </button>
                                    </div>

                                    <div class="relative z-10">
                                        
                                        <!-- Header Interno -->
                                        <div class="flex justify-between items-start mb-2">
                                            @if(!in_array($c->tipo, ['html', 'divider', 'media', 'social']))
                                                <label class="block text-sm font-bold {{ $bgStyle ? 'text-white' : 'text-gray-800' }}">
                                                    {{ $c->label }} @if($c->obrigatorio) <span class="text-red-500">*</span> @endif
                                                </label>
                                            @else
                                                <span></span> {{-- Placeholder flex --}}
                                            @endif
                                            <span class="text-[10px] font-mono font-bold bg-white/80 text-gray-500 px-1.5 py-0.5 rounded">#{{ $c->ordem }}</span>
                                        </div>
                                        
                                        <!-- PREVIEWS VISUAIS -->
                                        @if($c->tipo === 'text')
                                            <div class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-md text-gray-400 text-sm flex items-center gap-2 shadow-sm pointer-events-none">
                                                @if($c->subtipo == 'email') <i class="ph ph-envelope-simple text-lg"></i>
                                                @elseif(in_array($c->subtipo, ['date', 'datetime-local', 'date_range'])) <i class="ph ph-calendar-blank text-lg"></i>
                                                @elseif($c->subtipo == 'time') <i class="ph ph-clock text-lg"></i>
                                                @elseif($c->subtipo == 'number') <i class="ph ph-hash text-lg"></i>
                                                @else <i class="ph ph-text-t text-lg"></i> @endif
                                                <span class="truncate">Preenchimento ({{ $c->subtipo }})...</span>
                                            </div>
                                        
                                        @elseif($c->tipo === 'select')
                                            <div class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-md text-gray-400 text-sm flex items-center justify-between shadow-sm pointer-events-none">
                                                <span>Lista Suspensa...</span><i class="ph ph-caret-down text-gray-500"></i>
                                            </div>
                                        
                                        @elseif($c->tipo === 'radio' || $c->tipo === 'check')
                                            <div class="flex flex-wrap gap-4 mt-1 pointer-events-none">
                                                <div class="flex items-center gap-2 {{ $bgStyle ? 'text-gray-200' : 'text-gray-500' }} text-sm">
                                                    <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 1
                                                </div>
                                                <div class="flex items-center gap-2 {{ $bgStyle ? 'text-gray-200' : 'text-gray-500' }} text-sm">
                                                    <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 2
                                                </div>
                                            </div>
                                            
                                        @elseif($c->tipo === 'matriz')
                                            <div class="w-full bg-gray-100 rounded-md p-3 text-center border border-gray-200 pointer-events-none">
                                                <i class="ph ph-table text-gray-400 text-2xl mb-1"></i>
                                                <p class="text-xs font-bold text-gray-500">Matriz de Escolhas Múltiplas</p>
                                            </div>

                                        @elseif($c->tipo === 'html')
                                            <div class="{{ $bgStyle ? 'text-white' : 'text-gray-800' }}">
                                                @if($c->subtipo === 'h1') <h1 class="text-3xl font-extrabold">{{ $c->label }}</h1>
                                                @elseif($c->subtipo === 'h2') <h2 class="text-2xl font-bold">{{ $c->label }}</h2>
                                                @elseif($c->subtipo === 'p') <p class="text-sm leading-relaxed">{{ $c->label }}</p>
                                                @elseif($c->subtipo === 'link') <span class="text-indigo-500 font-bold underline">{{ $c->label }}</span>
                                                @elseif($c->subtipo === 'info_card') 
                                                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                                                        <p class="font-bold text-sm">{{ $c->label }}</p>
                                                    </div>
                                                @endif
                                            </div>

                                        @elseif($c->tipo === 'divider')
                                            <hr class="border-t-2 border-dashed border-gray-200 my-2">

                                        @elseif($c->tipo === 'media')
                                            <div class="w-full bg-gray-100 rounded-md p-4 text-center border border-gray-200 pointer-events-none">
                                                <i class="ph ph-{{ $c->subtipo == 'video' ? 'video-camera' : 'image' }} text-gray-400 text-2xl mb-1"></i>
                                                <p class="text-xs font-bold text-gray-500">Mídia Visual ({{ ucfirst($c->subtipo) }})</p>
                                            </div>

                                        @elseif($c->tipo === 'social')
                                            <div class="flex gap-2 justify-center py-2 pointer-events-none">
                                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="ph-fill ph-instagram-logo"></i></div>
                                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="ph-fill ph-facebook-logo"></i></div>
                                            </div>
                                            
                                        @elseif($c->tipo === 'rating')
                                            <div class="flex gap-1 text-2xl text-yellow-400 pointer-events-none">
                                                <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-gray-300"></i><i class="ph ph-star text-gray-300"></i>
                                            </div>
                                        @endif
                                        
                                        <!-- Badges de Log -->
                                        <div class="mt-2.5 flex flex-wrap gap-2 items-center">
                                            @if($c->tipo != 'divider' && $c->tipo != 'html')
                                                <span class="text-[10px] bg-gray-200/50 text-gray-600 px-1.5 py-0.5 rounded font-mono font-bold"><i class="ph ph-database"></i> {{ $c->name }}</span>
                                            @endif
                                            @if($c->depende_de)
                                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-[10px] px-1.5 py-0.5 rounded border border-yellow-200 font-bold">
                                                    <i class="ph-fill ph-warning-circle"></i> Condição
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="ph ph-list-plus text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Seu formulário está vazio</h3>
                        <p class="text-gray-500 text-sm max-w-sm">Comece a adicionar blocos utilizando o painel de configurações ao lado direito.</p>
                    </div>
                @endforelse

                <button type="button" wire:click="cancelarEdicao" class="w-full mt-8 py-4 border-2 border-dashed border-indigo-200 rounded-xl text-indigo-600 font-bold hover:bg-indigo-50 hover:border-indigo-400 transition flex justify-center items-center gap-2">
                    <i class="ph ph-plus-circle text-xl"></i> Adicionar Novo Bloco
                </button>

            </div>
        </div>

        {{-- ========================================== --}}
        {{-- COLUNA DIREITA: CONFIGURAÇÕES (4/12)       --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden flex flex-col max-h-[85vh]">
            
            <div class="bg-gray-50 border-b border-gray-200 p-4 flex items-center justify-between shrink-0">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="ph-fill ph-sliders-horizontal text-indigo-600"></i> 
                    {{ $campoId ? 'Configurações do Bloco' : 'Novo Bloco' }}
                </h3>
                @if($campoId)
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Editando</span>
                @endif
            </div>

            <div class="p-5 overflow-y-auto flex-1 custom-scrollbar">
                <form wire:submit.prevent="salvar" class="space-y-5">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Etapa e Posição</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <select wire:model="etapa" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50">
                                    @foreach($etapasDisponiveis as $et)
                                        <option value="{{ $et->numero }}">Etapa {{ $et->numero }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="number" wire:model="ordem" min="1" placeholder="Ordem" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Módulo Principal <span class="text-red-500">*</span></label>
                            <select wire:model.live="tipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-bold bg-indigo-50 text-indigo-800">
                                <option value="text">Texto e Pickers (Input, Data)</option>
                                <option value="select">Lista (Select)</option>
                                <option value="radio">Múltipla Escolha</option>
                                <option value="check">Caixas de Seleção</option>
                                <option value="matriz">Matriz de Escolha</option>
                                <option value="html">Textos e Informações</option>
                                <option value="media">Mídia (Imagem/Vídeo)</option>
                                <option value="divider">Linha Divisória</option>
                                <option value="social">Redes Sociais</option>
                                <option value="rating">Avaliação (Estrelas)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- TEXTO PRINCIPAL (Label) - Escondido para Divisores --}}
                    @if($tipo !== 'divider')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                                <span>{{ in_array($tipo, ['html', 'media']) ? 'Conteúdo Principal (Texto/Título)' : 'Título da Pergunta' }} <span class="text-red-500">*</span></span>
                            </label>
                            <textarea wire:model.live="label" rows="2" placeholder="Digite aqui..." class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                                <span>Nome DB (Key) <span class="text-red-500">*</span></span>
                            </label>
                            <input type="text" wire:model="name" class="w-full text-xs rounded-lg border-gray-300 shadow-sm bg-gray-100 text-gray-600 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Largura do Bloco <span class="text-red-500">*</span></label>
                            <select wire:model="largura" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="12">100% (Linha Inteira)</option>
                                <option value="6">50% (Metade)</option>
                                <option value="4">33% (Um Terço)</option>
                                <option value="3">25% (Um Quarto)</option>
                            </select>
                        </div>
                    </div>

                    {{-- BLOCO DINÂMICO DE ACORDO COM O TIPO --}}
                    
                    {{-- 1. TIPO: TEXT & PICKERS --}}
                    @if($tipo === 'text')
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Formato do Preenchimento</label>
                                <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="text">Texto Simples</option>
                                    <option value="email">E-mail</option>
                                    <option value="number">Apenas Números</option>
                                    <option value="date">Data (Calendário)</option>
                                    <option value="time">Hora (Relógio)</option>
                                    <option value="datetime-local">Data e Hora</option>
                                    <option value="date_range">Período (Range)</option>
                                    <option value="password">Senha</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] uppercase tracking-wider font-bold text-gray-500 mb-1">Mínimo</label>
                                    <input type="number" wire:model="tamanho_min" placeholder="Ex: 0" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase tracking-wider font-bold text-gray-500 mb-1">Máximo</label>
                                    <input type="number" wire:model="tamanho_max" placeholder="Ex: 100" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-wider font-bold text-gray-500 mb-1">Máscara (Alpine x-mask)</label>
                                <input type="text" wire:model="regex_mascara" placeholder="Ex: 999.999.999-99" class="w-full text-sm font-mono rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                    {{-- 2. TIPO: MULTIPLA ESCOLHA --}}
                    @elseif(in_array($tipo, ['select', 'radio', 'check']))
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700">Opções de Resposta</label>
                            <p class="text-[10px] text-gray-500 mb-1.5">Separe as opções por vírgula. Ex: Sim, Não, Talvez</p>
                            <textarea wire:model="opcoes" rows="3" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                    {{-- 3. TIPO: MATRIZ DE RADIO --}}
                    @elseif($tipo === 'matriz')
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Itens (Linhas)</label>
                                <p class="text-[10px] text-gray-500 mb-1.5">Um item por linha (Enter)</p>
                                <textarea wire:model="configuracoes.matriz_linhas" rows="3" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex:&#10;Camiseta&#10;Horários"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Opções (Colunas)</label>
                                <p class="text-[10px] text-gray-500 mb-1.5">Separe as colunas por vírgula</p>
                                <input type="text" wire:model="configuracoes.matriz_colunas" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex: Concordo, Não Concordo">
                            </div>
                        </div>

                    {{-- 4. TIPO: HTML/TEXTO --}}
                    @elseif($tipo === 'html')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Estilo do Texto</label>
                            <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="h1">Título Grande (H1)</option>
                                <option value="h2">Título Médio (H2)</option>
                                <option value="h3">Título Pequeno (H3)</option>
                                <option value="p">Parágrafo Padrão (P)</option>
                                <option value="info_card">Card de Informação (Azul)</option>
                                <option value="link">Hyperlink Externo</option>
                            </select>
                        </div>
                        @if($subtipo === 'info_card')
                            <div class="mt-3">
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Descrição Adicional</label>
                                <textarea wire:model="configuracoes.descricao" rows="2" class="w-full text-sm rounded-lg border-gray-300"></textarea>
                            </div>
                        @elseif($subtipo === 'link')
                            <div class="mt-3">
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">URL de Destino</label>
                                <input type="url" wire:model="configuracoes.url" placeholder="https://..." class="w-full text-sm rounded-lg border-gray-300">
                            </div>
                        @endif

                    {{-- 5. TIPO: MIDIA --}}
                    @elseif($tipo === 'media')
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Tipo de Mídia</label>
                                <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="image">Imagem Fixa</option>
                                    <option value="video">Vídeo (YouTube Embed)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">URL da Mídia</label>
                                <input type="url" wire:model="configuracoes.url" placeholder="https://..." class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                            </div>
                        </div>

                    {{-- 6. TIPO: SOCIAL --}}
                    @elseif($tipo === 'social')
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Lista de Redes Sociais</label>
                            <p class="text-[10px] text-gray-500 mb-1.5">Formato obrigatório: <b>rede|url</b> (Uma por linha)<br>Ex: instagram|https://inst... <br>Ex: linkedin-logo|https://link...</p>
                            <textarea wire:model="configuracoes.social_redes" rows="4" class="w-full text-xs font-mono rounded-lg border-gray-300 shadow-sm" placeholder="instagram-logo|https://...&#10;facebook-logo|https://..."></textarea>
                        </div>
                    
                    {{-- 7. TIPO: RATING --}}
                    @elseif($tipo === 'rating')
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 mb-1">Quantidade de Estrelas</label>
                            <input type="number" wire:model="configuracoes.max_stars" min="3" max="10" placeholder="Padrão: 5" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                        </div>
                    @endif

                    {{-- CAMPOS OBRIGATÓRIOS (Exceto Visuais) --}}
                    @if(!in_array($tipo, ['html', 'divider', 'media', 'social']))
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition mt-4">
                            <input type="checkbox" wire:model="obrigatorio" class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-900 font-bold">Campo Obrigatório</span>
                                <span class="text-[10px] text-gray-500">O usuário não poderá avançar sem preencher.</span>
                            </div>
                        </label>
                    @endif

                    {{-- ESTILIZAÇÃO E BACKGROUND (ACORDEÃO ALPINE) --}}
                    <div x-data="{ openConfig: {{ isset($configuracoes['bg_image']) ? 'true' : 'false' }} }" class="mt-4 border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" @click="openConfig = !openConfig" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 transition text-sm font-bold text-gray-700">
                            <span class="flex items-center gap-2"><i class="ph ph-image text-indigo-500 text-lg"></i> Fundo e Estilo Customizado</span>
                            <i class="ph ph-caret-down transition-transform" :class="openConfig ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="openConfig" x-collapse x-cloak class="p-4 bg-white border-t border-gray-200 space-y-3">
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">URL da Imagem de Fundo (Capa)</label>
                                <input type="url" wire:model.live.debounce.1000ms="configuracoes.bg_image" placeholder="https://..." class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Cor da Sobreposição</label>
                                    <input type="color" wire:model.live="configuracoes.bg_color" class="w-full h-9 rounded-lg border-gray-300 shadow-sm cursor-pointer p-1">
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Opacidade (0.0 a 1.0)</label>
                                    <input type="number" step="0.1" min="0" max="1" wire:model.live="configuracoes.bg_opacity" placeholder="0.5" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LÓGICA CONDICIONAL (ACORDEÃO ALPINE) --}}
                    <div x-data="{ openCond: {{ !empty($depende_de) ? 'true' : 'false' }} }" class="mt-4 border border-yellow-200 rounded-lg overflow-hidden">
                        <button type="button" @click="openCond = !openCond" class="w-full flex items-center justify-between p-3 bg-yellow-50 hover:bg-yellow-100 transition text-sm font-bold text-yellow-800">
                            <span class="flex items-center gap-2"><i class="ph-fill ph-git-branch text-yellow-600 text-lg"></i> Regras de Exibição (Condicional)</span>
                            <i class="ph ph-caret-down transition-transform" :class="openCond ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="openCond" x-collapse x-cloak class="p-4 bg-white border-t border-yellow-200 space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-yellow-700 mb-1">Mostrar este campo SOMENTE SE:</label>
                                <select wire:model.live="depende_de" class="w-full text-sm rounded-lg border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                    <option value="">-- Sempre Visível --</option>
                                    @foreach($camposCadastrados as $c)
                                        @if($c->id !== $campoId && !in_array($c->tipo, ['html', 'divider', 'media', 'social']))
                                            <option value="{{ $c->name }}">{{ Str::limit($c->label, 30) }} (Etapa {{ $c->etapa }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            
                            @if(!empty($depende_de))
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-1">
                                        <select wire:model="depende_operador" class="w-full text-xs rounded-lg border-gray-300 shadow-sm">
                                            <option value="=">Igual a</option>
                                            <option value="!=">Dif. de</option>
                                            <option value=">">Maior que</option>
                                            <option value="<">Menor que</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" wire:model="depende_valor" placeholder="Valor esperado..." class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </form>
            </div>

            <!-- Rodapé Fixo do Sidebar (Botões) -->
            <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0 flex gap-3">
                @if($campoId)
                    <button type="button" wire:click="cancelarEdicao" class="flex-1 bg-white border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="salvar" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> {{ $campoId ? 'Salvar Edição' : 'Adicionar Bloco' }}
                </button>
            </div>

        </div>
    </div>
</div>

