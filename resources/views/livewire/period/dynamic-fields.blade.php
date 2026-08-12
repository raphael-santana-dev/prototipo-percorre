<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ $contextoTipo === 'ciclo' ? route('ciclos.index') : route('formularios.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para {{ $contextoTipo === 'ciclo' ? 'Ciclos' : 'Formulários' }}
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Construtor do Formulário</h2>
            <p class="text-gray-500 text-sm">Gerenciando blocos para: <span class="font-bold text-purpura-600">{{ $contextoNome }}</span></p>
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
            
            <div class="hidden col-span-3 col-span-4 col-span-6 col-span-12 md:col-span-3 md:col-span-4 md:col-span-6 md:col-span-12"></div>

            @php
                $previewBgUrl = null;
                if ($bg_image_upload) {
                    try { 
                        $previewBgUrl = $bg_image_upload->temporaryUrl(); 
                    } catch (\Exception $e) {
                        // Ignora falhas de preview temporário
                    }
                } elseif (!empty($formSettings['bg_image'])) {
                    $previewBgUrl = asset($formSettings['bg_image']);
                }
                
                $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6';
                $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
            @endphp

            {{-- CAIXA DA PREVIEW --}}
            <div class="relative w-full min-h-[600px] rounded-xl overflow-hidden bg-transparent shadow-inner">
                
                {{-- FUNDO FIXO --}}
                @if($previewBgUrl)
                    <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $previewBgUrl }}');"></div>
                @endif
                
                {{-- Camada de Cor / Opacidade --}}
                <div class="absolute inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

                {{-- CONTEÚDO --}}
                <div class="relative z-10 w-full p-8 md:p-12 h-full overflow-y-auto">
                    
                    <div class="mb-10 border-b {{ $previewBgUrl ? 'border-white/20' : 'border-gray-100' }} pb-6">
                        <h1 class="text-3xl font-extrabold mb-2 {{ $previewBgUrl ? 'text-white drop-shadow-md' : 'text-gray-900' }}">Simulador da Inscrição</h1>
                        <p class="{{ $previewBgUrl ? 'text-white/80' : 'text-gray-500' }}">Abaixo está a estrutura de como o aluno verá este formulário.</p>
                    </div>

                    @forelse($camposPorEtapa as $numEtapa => $camposDaEtapa)
                        <div class="mb-12" wire:key="grupo-etapa-{{ $numEtapa }}">
                            
                            <div class="flex items-center gap-3 mb-6">
                                <span class="flex items-center justify-center w-7 h-7 text-sm font-bold text-white bg-indigo-600 rounded-full shadow-sm">{{ $numEtapa }}</span>
                                <h3 class="text-xl font-bold {{ $previewBgUrl ? 'text-white drop-shadow-md' : 'text-gray-800' }}">Etapa {{ $numEtapa }}</h3>
                                <div class="flex-1 h-px ml-4 {{ $previewBgUrl ? 'bg-white/20' : 'bg-gray-200' }}"></div>
                            </div>
                            
                            <div class="grid grid-cols-12 gap-x-6 gap-y-4">
                                @foreach($camposDaEtapa as $c)
                                    @php 
                                        $isActive = $campoId == $c->id; 
                                        $colSpan = "col-span-12 md:col-span-{$c->largura}";
                                        $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                    @endphp

                                    <div wire:key="campo-{{ $c->id }}" class="{{ $colSpan }} relative group rounded-lg transition-all duration-200 {{ $isActive ? 'ring-2 ring-indigo-500 shadow-md p-4 bg-white/95' : 'border border-transparent hover:border-gray-200 p-4 cursor-pointer hover:bg-white/50' }}" wire:click="editar({{ $c->id }})">
                                        
                                        <div class="absolute right-2 -top-4 {{ $isActive ? 'flex' : 'hidden group-hover:flex' }} gap-1 bg-white border border-gray-200 shadow-md rounded-md overflow-hidden z-20 text-gray-600">
                                            <button wire:click.stop="editar({{ $c->id }})" class="p-2 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Editar Campo"><i class="ph ph-pencil-simple text-base"></i></button>
                                            <button wire:click.stop="excluir({{ $c->id }})" wire:confirm="Tem certeza que deseja excluir este campo?" class="p-2 hover:bg-red-50 hover:text-red-600 transition border-l border-gray-100" title="Excluir Campo"><i class="ph ph-trash text-base"></i></button>
                                        </div>

                                        <div class="relative z-10">
                                            <div class="flex justify-between items-start mb-2">
                                                @if(!in_array($c->tipo, ['html', 'divider', 'media', 'social']))
                                                    <label class="block text-sm font-bold {{ $previewBgUrl ? 'text-white drop-shadow-md' : 'text-gray-800' }}">
                                                        {{ $c->label }} @if($c->obrigatorio) <span class="text-red-500">*</span> @endif
                                                    </label>
                                                @else
                                                    <span></span>
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
                                                    <div class="flex items-center gap-2 {{ $previewBgUrl ? 'text-white drop-shadow-sm' : 'text-gray-500' }} text-sm">
                                                        <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 1
                                                    </div>
                                                    <div class="flex items-center gap-2 {{ $previewBgUrl ? 'text-white drop-shadow-sm' : 'text-gray-500' }} text-sm">
                                                        <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 2
                                                    </div>
                                                </div>
                                            @elseif($c->tipo === 'system')
                                                <div class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-md text-gray-400 text-sm flex items-center justify-between shadow-sm pointer-events-none">
                                                    <span class="flex items-center gap-2"><i class="ph ph-database text-indigo-400"></i> Selecione {{ ucfirst($c->subtipo) }}...</span><i class="ph ph-caret-down text-gray-500"></i>
                                                </div>
                                                
                                            @elseif($c->tipo === 'matriz')
                                                @php
                                                    $linhasPv = [];
                                                    $colunasPv = [];
                                                    if ($isActive) {
                                                        $linhasPv = array_filter(array_map('trim', explode("\n", $matriz_linhas ?? '')));
                                                        $colunasPv = array_filter(array_map('trim', explode(',', $matriz_colunas ?? '')));
                                                    }
                                                    if (empty($linhasPv)) $linhasPv = !empty($cfg['linhas']) ? $cfg['linhas'] : ['Item 1', 'Item 2'];
                                                    if (empty($colunasPv)) $colunasPv = !empty($cfg['colunas']) ? $cfg['colunas'] : ['Opção A', 'Opção B'];
                                                @endphp
                                                <div class="overflow-x-auto w-full border border-gray-200 rounded-lg pointer-events-none bg-white mt-2 shadow-sm">
                                                    <table class="min-w-full text-xs text-left">
                                                        <thead class="bg-gray-50 border-b border-gray-200">
                                                            <tr>
                                                                <th class="p-2 w-1/3"></th>
                                                                @foreach($colunasPv as $col)
                                                                    <th class="p-2 text-center text-gray-600 font-bold border-l border-gray-100">{{ $col }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            @foreach($linhasPv as $linha)
                                                                <tr>
                                                                    <td class="p-2 font-medium text-gray-800">{{ $linha }}</td>
                                                                    @foreach($colunasPv as $col)
                                                                        <td class="p-2 text-center border-l border-gray-100">
                                                                            <div class="w-3 h-3 border border-gray-300 rounded-full inline-block"></div>
                                                                        </td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                            @elseif($c->tipo === 'html')
                                                <div class="{{ $previewBgUrl ? 'text-white drop-shadow-md' : 'text-gray-800' }}">
                                                    @if($c->subtipo === 'h1') <h1 class="text-3xl font-extrabold">{{ $c->label }}</h1>
                                                    @elseif($c->subtipo === 'h2') <h2 class="text-2xl font-bold">{{ $c->label }}</h2>
                                                    @elseif($c->subtipo === 'h3') <h3 class="text-xl font-bold">{{ $c->label }}</h3>
                                                    @elseif($c->subtipo === 'p') <p class="text-sm leading-relaxed">{{ $c->label }}</p>
                                                    @elseif($c->subtipo === 'link') <span class="text-indigo-400 font-bold underline">{{ $c->label }}</span>
                                                    @elseif($c->subtipo === 'info_card') 
                                                        <div class="p-3 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                                                            <p class="font-bold text-sm">{{ $c->label }}</p>
                                                        </div>
                                                    @endif
                                                </div>

                                            @elseif($c->tipo === 'divider')
                                                <hr class="border-t-2 border-dashed {{ $previewBgUrl ? 'border-white/30' : 'border-gray-300' }} my-2">

                                            @elseif($c->tipo === 'media')
                                                <div class="w-full bg-white/90 rounded-md p-4 text-center border border-gray-200 pointer-events-none text-gray-500 shadow-sm">
                                                    <i class="ph ph-{{ $c->subtipo == 'video' ? 'video-camera' : 'image' }} text-2xl mb-1"></i>
                                                    <p class="text-xs font-bold">Mídia Visual ({{ ucfirst($c->subtipo) }})</p>
                                                </div>

                                            @elseif($c->tipo === 'social')
                                                <div class="flex gap-2 justify-center py-2 pointer-events-none">
                                                    <div class="w-8 h-8 rounded-full bg-white/90 shadow-sm flex items-center justify-center text-gray-600"><i class="ph-fill ph-instagram-logo"></i></div>
                                                    <div class="w-8 h-8 rounded-full bg-white/90 shadow-sm flex items-center justify-center text-gray-600"><i class="ph-fill ph-facebook-logo"></i></div>
                                                </div>
                                                
                                            @elseif($c->tipo === 'rating')
                                                <div class="flex gap-1 text-2xl text-yellow-400 pointer-events-none drop-shadow-sm">
                                                    <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-white"></i><i class="ph ph-star text-white"></i>
                                                </div>
                                            @endif
                                            
                                            <!-- Badges de Log -->
                                            <div class="mt-2.5 flex flex-wrap gap-2 items-center">
                                                @if(!in_array($c->tipo, ['html', 'divider', 'social', 'media']))
                                                    <span class="text-[10px] bg-white/80 text-gray-600 px-1.5 py-0.5 rounded font-mono font-bold shadow-sm"><i class="ph ph-database"></i> {{ $c->name }}</span>
                                                @endif
                                                @if($c->depende_de)
                                                    <span class="inline-flex items-center gap-1 bg-yellow-400 text-yellow-900 text-[10px] px-1.5 py-0.5 rounded font-bold shadow-sm">
                                                        <i class="ph-fill ph-warning-circle"></i> Condicional
                                                    </span>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-20 text-center relative z-10">
                            <div class="w-20 h-20 bg-white/80 rounded-full flex items-center justify-center mb-4 shadow-sm">
                                <i class="ph ph-list-plus text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-bold {{ $previewBgUrl ? 'text-white' : 'text-gray-900' }} mb-1">Formulário Vazio</h3>
                            <p class="{{ $previewBgUrl ? 'text-white/80' : 'text-gray-500' }} text-sm max-w-sm">Use a aba de configurações ao lado para construir seu layout.</p>
                        </div>
                    @endforelse

                    <div class="relative z-10">
                        <button type="button" wire:click="cancelarEdicao" class="w-full mt-4 py-4 border-2 border-dashed {{ $previewBgUrl ? 'border-white/40 text-white hover:bg-white/10 hover:border-white' : 'border-indigo-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-400' }} rounded-xl font-bold transition flex justify-center items-center gap-2">
                            <i class="ph ph-plus-circle text-xl"></i> Adicionar Novo Bloco
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- COLUNA DIREITA: CONFIGURAÇÕES (4/12)       --}}
        {{-- ========================================== --}}
        <div x-data="{ activeTab: 'field' }" class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden flex flex-col max-h-[85vh]">
            
            <div class="flex border-b border-gray-200 shrink-0 bg-gray-50">
                <button type="button" @click="activeTab = 'field'" :class="activeTab === 'field' ? 'border-indigo-600 text-indigo-700 bg-white shadow-[0_2px_0_0_#4f46e5]' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-3.5 text-xs font-bold border-b-2 text-center transition tracking-wide uppercase flex flex-col items-center gap-1">
                    <i class="ph-fill ph-textbox text-lg"></i> Bloco
                </button>
                <button type="button" @click="activeTab = 'form'" :class="activeTab === 'form' ? 'border-indigo-600 text-indigo-700 bg-white shadow-[0_2px_0_0_#4f46e5]' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-3.5 text-xs font-bold border-b-2 text-center transition tracking-wide uppercase flex flex-col items-center gap-1">
                    <i class="ph-fill ph-browser text-lg"></i> Formulário
                </button>
            </div>

            <!-- ==================== ABA 1: CONFIG DO BLOCO ==================== -->
            <div x-show="activeTab === 'field'" class="flex-1 flex flex-col overflow-hidden">
                
                <form wire:submit.prevent="salvar" class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-5 overflow-y-auto flex-1 custom-scrollbar space-y-5">
                        
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-800">{{ $campoId ? 'Editar Propriedades' : 'Inserir Novo Elemento' }}</h3>
                            @if($campoId) <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Editando</span> @endif
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Etapa e Posição</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <select wire:model.live="etapa" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50 font-semibold text-gray-700">
                                        @foreach($etapasDisponiveis as $et)
                                            <option value="{{ $et->numero }}">Etapa {{ $et->numero }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <input type="number" wire:model="ordem" min="1" max="{{ $this->limiteOrdem() }}" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50 font-semibold text-gray-700 text-center">
                                </div>
                            </div>
                            @error('ordem') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <hr class="border-gray-100">

                        <div>
                            <label class="block text-xs font-bold text-gray-800 mb-3">Escolha o Tipo de Bloco <span class="text-red-500">*</span></label>
                            
                            <div class="space-y-4 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Entrada de Dados</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" wire:click="setTipo('text', 'text')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'text' && in_array($subtipo, ['text', 'email', 'number', 'password']) ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-text-t text-xl {{ $tipo == 'text' && in_array($subtipo, ['text', 'email', 'number', 'password']) ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Texto Curto</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('text', 'date')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'text' && in_array($subtipo, ['date', 'datetime-local', 'time', 'date_range']) ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-calendar-blank text-xl {{ $tipo == 'text' && in_array($subtipo, ['date', 'datetime-local', 'time', 'date_range']) ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Datas e Horas</span>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Seleção e Escolhas</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" wire:click="setTipo('select')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'select' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-caret-circle-down text-xl {{ $tipo == 'select' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Dropdown</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('radio')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'radio' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-radio-button text-xl {{ $tipo == 'radio' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Múltipla Escolha</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('check')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'check' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-check-square-offset text-xl {{ $tipo == 'check' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Checkboxes</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('matriz')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'matriz' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-table text-xl {{ $tipo == 'matriz' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Matriz de Rádios</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('rating')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'rating' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-star text-xl {{ $tipo == 'rating' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Avaliação (Estrelas)</span>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Layout e Visual</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" wire:click="setTipo('html', 'p')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'html' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-article text-xl {{ $tipo == 'html' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Texto Descritivo</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('media', 'image')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'media' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-image text-xl {{ $tipo == 'media' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Mídia (Img/Vid)</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('social')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'social' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-share-network text-xl {{ $tipo == 'social' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Redes Sociais</span>
                                        </button>
                                        <button type="button" wire:click="setTipo('divider')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'divider' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                            <i class="ph ph-minus text-xl {{ $tipo == 'divider' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                            <span class="text-xs font-bold">Linha Divisória</span>
                                        </button>
                                    </div>
                                </div>

                                @if($contextoTipo === 'formulario')
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Integração do Sistema</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" wire:click="setTipo('system', 'unidade')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'system' && $subtipo == 'unidade' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                                <i class="ph ph-buildings text-xl {{ $tipo == 'system' && $subtipo == 'unidade' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                                <span class="text-xs font-bold">Unidades Ativas</span>
                                            </button>
                                            <button type="button" wire:click="setTipo('system', 'curso')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'system' && $subtipo == 'curso' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                                <i class="ph ph-graduation-cap text-xl {{ $tipo == 'system' && $subtipo == 'curso' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                                <span class="text-xs font-bold">Cursos Ativos</span>
                                            </button>
                                            <button type="button" wire:click="setTipo('system', 'turno')" class="flex flex-col items-start gap-1 p-3 border rounded-lg text-left transition {{ $tipo == 'system' && $subtipo == 'turno' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-indigo-300 text-gray-700' }}">
                                                <i class="ph ph-clock text-xl {{ $tipo == 'system' && $subtipo == 'turno' ? 'text-indigo-500' : 'text-gray-400' }}"></i>
                                                <span class="text-xs font-bold">Turnos Ativos</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr class="border-gray-100">

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
                                    <span>ID no Banco <span class="text-red-500">*</span></span>
                                </label>
                                <input type="text" wire:model="name" class="w-full text-[11px] rounded-lg border-gray-300 shadow-sm bg-gray-100 text-gray-600 font-mono cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Largura na Tela <span class="text-red-500">*</span></label>
                                <select wire:model="largura" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="12">100% (Linha Inteira)</option>
                                    <option value="6">50% (Metade)</option>
                                    <option value="4">33% (Um Terço)</option>
                                    <option value="3">25% (Um Quarto)</option>
                                </select>
                            </div>
                        </div>

                        @if($tipo === 'text')
                            <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-lg space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900 mb-1.5">Formato Exato</label>
                                    <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="text">Texto Simples</option>
                                        <option value="email">E-mail</option>
                                        <option value="number">Números</option>
                                        <option value="date">Data (Calendário)</option>
                                        <option value="time">Hora (Relógio)</option>
                                        <option value="datetime-local">Data e Hora</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-indigo-800 mb-1">Mínimo</label>
                                        <input type="number" wire:model="tamanho_min" class="w-full text-sm rounded-lg border-indigo-200 shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-indigo-800 mb-1">Máximo</label>
                                        <input type="number" wire:model="tamanho_max" class="w-full text-sm rounded-lg border-indigo-200 shadow-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-indigo-800 mb-1">Máscara (x-mask)</label>
                                    <input type="text" wire:model="regex_mascara" class="w-full text-sm font-mono rounded-lg border-indigo-200 shadow-sm">
                                </div>
                            </div>
                        @elseif(in_array($tipo, ['select', 'radio', 'check']))
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-700">Opções de Resposta</label>
                                <p class="text-[10px] text-gray-500 mb-1.5">Separe as opções por vírgula.</p>
                                <textarea wire:model="opcoes" rows="3" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        
                        {{-- MATRIZ COM WIRE:MODEL.LIVE PARA PREVIEW INSTANTÂNEO --}}
                        @elseif($tipo === 'matriz')
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Itens (Linhas)</label>
                                    <p class="text-[10px] text-gray-500 mb-1.5">Um item por linha (Enter)</p>
                                    <textarea wire:model.live="matriz_linhas" rows="3" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex:&#10;Camiseta&#10;Horários"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Opções (Colunas)</label>
                                    <p class="text-[10px] text-gray-500 mb-1.5">Separadas por vírgula</p>
                                    <input type="text" wire:model.live="matriz_colunas" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex: Concordo, Não Concordo">
                                </div>
                            </div>
                        @elseif($tipo === 'system')
                            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model="configuracoes.aplicar_regras" class="h-4 w-4 text-indigo-600 rounded border-indigo-300 focus:ring-indigo-500">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-indigo-900 font-bold">Aplicar Regras de Cascata (Dependência)</span>
                                        <span class="text-[10px] text-indigo-700 leading-tight mt-1">O Curso listará apenas opções da Unidade selecionada, e o Turno do Curso. Se desmarcado, listará todos os ativos do banco de dados.</span>
                                    </div>
                                </label>
                            </div>
                        @elseif($tipo === 'html')
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Estilo do Texto</label>
                                <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                                    <option value="h1">Título Gigante (H1)</option>
                                    <option value="h2">Título Médio (H2)</option>
                                    <option value="h3">Título Pequeno (H3)</option>
                                    <option value="p">Parágrafo Normal</option>
                                    <option value="info_card">Caixa de Aviso (Azul)</option>
                                    <option value="link">Hyperlink Clicável</option>
                                </select>
                                @if($subtipo === 'info_card')
                                    <textarea wire:model="configuracoes.descricao" rows="2" placeholder="Texto adicional de aviso" class="w-full text-sm rounded-lg border-gray-300"></textarea>
                                @elseif($subtipo === 'link')
                                    <input type="url" wire:model="configuracoes.url" placeholder="URL Destino (https://...)" class="w-full text-sm rounded-lg border-gray-300">
                                @endif
                            </div>
                        @elseif($tipo === 'media')
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Tipo de Mídia</label>
                                <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                                    <option value="image">Imagem Estática</option>
                                    <option value="video">Vídeo Externo</option>
                                </select>
                                <input type="url" wire:model="configuracoes.url" placeholder="URL da Imagem ou YouTube" class="w-full text-sm rounded-lg border-gray-300">
                            </div>
                        @elseif($tipo === 'social')
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Listagem Social</label>
                                <p class="text-[10px] text-gray-500 mb-1.5">Formato: <b>icone|link</b> (Um por linha)</p>
                                <textarea wire:model="configuracoes.social_redes" rows="3" class="w-full text-xs font-mono rounded-lg border-gray-300" placeholder="instagram-logo|https://..."></textarea>
                            </div>
                        @elseif($tipo === 'rating')
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Total de Estrelas</label>
                                <input type="number" wire:model="configuracoes.max_stars" min="3" max="10" placeholder="5" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                            </div>
                        @endif

                        @if(!in_array($tipo, ['html', 'divider', 'media', 'social']))
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition mt-4">
                                <input type="checkbox" wire:model="obrigatorio" class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-900 font-bold">Campo Obrigatório</span>
                                    <span class="text-[10px] text-gray-500">O aluno não avança sem preencher.</span>
                                </div>
                            </label>
                        @endif

                        <div x-data="{ openCond: {{ !empty($depende_de) ? 'true' : 'false' }} }" class="mt-4 border border-yellow-200 rounded-lg overflow-hidden">
                            <button type="button" @click="openCond = !openCond" class="w-full flex items-center justify-between p-3 bg-yellow-50 hover:bg-yellow-100 transition text-sm font-bold text-yellow-800">
                                <span class="flex items-center gap-2"><i class="ph-fill ph-git-branch text-yellow-600 text-lg"></i> Regras de Exibição</span>
                                <i class="ph ph-caret-down transition-transform" :class="openCond ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="openCond" x-collapse x-cloak class="p-4 bg-white border-t border-yellow-200 space-y-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-yellow-700 mb-1">Mostrar este bloco SOMENTE SE:</label>
                                    <select wire:model.live="depende_de" class="w-full text-sm rounded-lg border-yellow-300 shadow-sm focus:border-yellow-500">
                                        <option value="">-- Sempre Visível --</option>
                                        @foreach($camposCadastrados as $c)
                                            @if($c->id !== $campoId && !in_array($c->tipo, ['html', 'divider', 'media', 'social']))
                                                <option value="{{ $c->name }}">{{ Str::limit($c->label, 30) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                @if(!empty($depende_de))
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="col-span-1">
                                            <select wire:model="depende_operador" class="w-full text-xs rounded-lg border-gray-300 shadow-sm">
                                                <option value="=">Igual a</option>
                                                <option value="!=">Dif.</option>
                                                <option value=">">Maior</option>
                                                <option value="<">Menor</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <input type="text" wire:model="depende_valor" placeholder="Valor esperado" class="w-full text-sm rounded-lg border-gray-300 shadow-sm">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0 flex gap-3">
                        @if($campoId)
                            <button type="button" wire:click="cancelarEdicao" class="flex-1 bg-white border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 hover:text-gray-900 transition">Cancelar</button>
                        @endif
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-700 transition flex justify-center items-center gap-2">
                            <i class="ph-bold ph-floppy-disk text-lg"></i> {{ $campoId ? 'Atualizar Bloco' : 'Inserir Bloco' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ==================== ABA 2: CONFIG DO FORMULÁRIO (GERAL) ==================== -->
            <div x-show="activeTab === 'form'" x-cloak class="flex-1 flex flex-col overflow-hidden bg-white">
                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                    
                    <div class="text-center mb-8">
                        <i class="ph-fill ph-magic-wand text-4xl text-indigo-500 mb-2"></i>
                        <h3 class="text-lg font-bold text-gray-900">Aparência Global</h3>
                        <p class="text-xs text-gray-500 mt-1">Defina o papel de parede e o estilo da página do formulário inteiro.</p>
                    </div>

                    <form wire:submit.prevent="salvarFormSettings" class="space-y-6">
                        
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <label class="block text-sm font-bold text-gray-800 mb-2">Upload de Imagem de Fundo</label>
                            
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="ph ph-upload-simple text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-xs text-gray-500"><span class="font-bold">Clique para enviar</span> ou arraste (PNG/JPG)</p>
                                    </div>
                                    <input type="file" wire:model="bg_image_upload" class="hidden" accept="image/*">
                                </label>
                            </div>
                            
                            <div wire:loading wire:target="bg_image_upload" class="mt-2 text-xs font-bold text-indigo-600 flex items-center gap-2">
                                <i class="ph ph-spinner animate-spin text-lg"></i> Fazendo upload seguro...
                            </div>
                            @error('bg_image_upload') <span class="text-xs text-red-500 mt-2 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Cor da Sobreposição</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="formSettings.bg_color" class="w-12 h-10 rounded-md border-gray-300 cursor-pointer p-0.5">
                                    <span class="text-xs font-mono text-gray-600" x-text="$wire.formSettings.bg_color"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Opacidade (0.0 - 1.0)</label>
                                <input type="number" step="0.1" min="0" max="1" wire:model.live="formSettings.bg_opacity" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50">
                            </div>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                            <p class="text-xs text-blue-800 leading-relaxed font-medium">A sobreposição serve para escurecer ou aplicar um filtro de cor na sua imagem, garantindo que os blocos fiquem legíveis para os alunos.</p>
                        </div>
                        
                    </form>
                </div>
                
                <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0">
                    <button type="button" wire:click="salvarFormSettings" class="w-full bg-gray-900 text-white py-3 rounded-lg text-sm font-bold shadow-sm hover:bg-black transition flex justify-center items-center gap-2">
                        <i class="ph-bold ph-check"></i> Aplicar Tema ao Formulário
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>