<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    
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
                <i class="ph ph-squares-four"></i> {{ $camposCadastrados->count() }} Campos
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
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Formulário de Inscrição</h1>
                    <p class="text-gray-500">Preencha os dados abaixo para concluir sua inscrição no ciclo <b>{{ $ciclo->nome }}</b>.</p>
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
                                @endphp

                                <div wire:key="campo-{{ $c->id }}" class="{{ $colSpan }} relative group rounded-lg transition-all duration-200 {{ $isActive ? 'ring-2 ring-indigo-500 bg-indigo-50/20 p-4 -m-4' : 'border border-transparent hover:border-gray-200 hover:bg-gray-50 p-4 -m-4 cursor-pointer' }}" wire:click="editar({{ $c->id }})">
                                    
                                    <!-- Toolbar Flutuante de Ações -->
                                    <div class="absolute right-2 -top-4 {{ $isActive ? 'flex' : 'hidden group-hover:flex' }} gap-1 bg-white border border-gray-200 shadow-md rounded-md overflow-hidden z-10 text-gray-600">
                                        <button wire:click.stop="editar({{ $c->id }})" class="p-2 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Editar Campo">
                                            <i class="ph ph-pencil-simple text-base"></i>
                                        </button>
                                        <button wire:click.stop="excluir({{ $c->id }})" wire:confirm="Tem certeza que deseja excluir este campo?" class="p-2 hover:bg-red-50 hover:text-red-600 transition border-l border-gray-100" title="Excluir Campo">
                                            <i class="ph ph-trash text-base"></i>
                                        </button>
                                    </div>

                                    <!-- Renderização Visual do Campo -->
                                    <div class="flex justify-between items-start mb-1.5">
                                        <label class="block text-sm font-bold text-gray-800">
                                            {{ $c->label }} @if($c->obrigatorio) <span class="text-red-500">*</span> @endif
                                        </label>
                                        <span class="text-[10px] font-mono text-gray-400 font-medium">#{{ $c->ordem }}</span>
                                    </div>
                                    
                                    <!-- Input Fake -->
                                    @if($c->tipo === 'text')
                                        <div class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-md text-gray-400 text-sm flex items-center gap-2 shadow-sm pointer-events-none">
                                            @if($c->subtipo == 'email') <i class="ph ph-envelope-simple text-gray-400 text-lg"></i>
                                            @elseif($c->subtipo == 'date') <i class="ph ph-calendar-blank text-gray-400 text-lg"></i>
                                            @elseif($c->subtipo == 'number') <i class="ph ph-hash text-gray-400 text-lg"></i>
                                            @else <i class="ph ph-text-t text-gray-400 text-lg"></i> @endif
                                            <span class="truncate">Digite sua resposta...</span>
                                        </div>
                                    @elseif($c->tipo === 'select')
                                        <div class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-md text-gray-400 text-sm flex items-center justify-between shadow-sm pointer-events-none">
                                            <span>Selecione uma opção...</span>
                                            <i class="ph ph-caret-down text-gray-500"></i>
                                        </div>
                                    @elseif($c->tipo === 'radio' || $c->tipo === 'check')
                                        <div class="flex flex-wrap gap-4 mt-2 pointer-events-none">
                                            <div class="flex items-center gap-2 text-gray-500 text-sm">
                                                <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 1
                                            </div>
                                            <div class="flex items-center gap-2 text-gray-500 text-sm">
                                                <div class="w-4 h-4 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 2
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Badges informativos (DB Name e Condicional) -->
                                    <div class="mt-2.5 flex flex-wrap gap-2 items-center">
                                        <span class="text-[10px] text-gray-400 font-mono"><i class="ph ph-database"></i> {{ $c->name }}</span>
                                        @if($c->depende_de)
                                            <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-[10px] px-2 py-0.5 rounded border border-yellow-200 font-bold">
                                                <i class="ph-fill ph-warning-circle"></i> Condicional
                                            </span>
                                        @endif
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
                        <p class="text-gray-500 text-sm max-w-sm">Comece a adicionar campos utilizando o painel de configurações ao lado direito.</p>
                    </div>
                @endforelse

                <!-- Botão de Adicionar Novo Campo ao Final -->
                <button type="button" wire:click="cancelarEdicao" class="w-full mt-8 py-4 border-2 border-dashed border-indigo-200 rounded-xl text-indigo-600 font-bold hover:bg-indigo-50 hover:border-indigo-400 transition flex justify-center items-center gap-2">
                    <i class="ph ph-plus-circle text-xl"></i> Adicionar Novo Campo
                </button>

            </div>
        </div>

        {{-- ========================================== --}}
        {{-- COLUNA DIREITA: CONFIGURAÇÕES (4/12)       --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden flex flex-col max-h-[85vh]">
            
            <!-- Header do Sidebar -->
            <div class="bg-gray-50 border-b border-gray-200 p-4 flex items-center justify-between shrink-0">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="ph-fill ph-sliders-horizontal text-indigo-600"></i> 
                    {{ $campoId ? 'Configurações do Campo' : 'Novo Campo' }}
                </h3>
                @if($campoId)
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Editando</span>
                @endif
            </div>

            <!-- Corpo do Formulário -->
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

                    <hr class="border-gray-100">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Título da Pergunta (Label) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="label" placeholder="Ex: Qual sua escolaridade?" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                            <span>Nome no Banco (Name) <span class="text-red-500">*</span></span>
                            <i class="ph ph-info text-gray-400 cursor-help" title="Gerado automaticamente. Usado como chave no banco de dados."></i>
                        </label>
                        <input type="text" wire:model="name" class="w-full text-sm rounded-lg border-gray-300 shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Tipo de Campo <span class="text-red-500">*</span></label>
                            <select wire:model.live="tipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="text">Texto (Input)</option>
                                <option value="select">Lista (Select)</option>
                                <option value="radio">Múltipla Escolha</option>
                                <option value="check">Caixas de Seleção</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Largura na Tela <span class="text-red-500">*</span></label>
                            <select wire:model="largura" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="12">100% (Linha Inteira)</option>
                                <option value="6">50% (Metade)</option>
                                <option value="4">33% (Um Terço)</option>
                                <option value="3">25% (Um Quarto)</option>
                            </select>
                        </div>
                    </div>

                    {{-- CAMPOS ESPECÍFICOS PARA TIPO TEXT --}}
                    @if($tipo === 'text')
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Subtipo / Formato</label>
                                <select wire:model.live="subtipo" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="text">Texto Simples</option>
                                    <option value="email">E-mail</option>
                                    <option value="number">Apenas Números</option>
                                    <option value="date">Data (Calendário)</option>
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
                    @endif

                    {{-- CAMPOS ESPECÍFICOS PARA MULTIPLA ESCOLHA --}}
                    @if(in_array($tipo, ['select', 'radio', 'check']))
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700">Opções de Resposta</label>
                            <p class="text-[10px] text-gray-500 mb-1.5">Separe as opções por vírgula. Ex: Sim, Não, Talvez</p>
                            <textarea wire:model="opcoes" rows="3" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    @endif

                    <hr class="border-gray-100">

                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" wire:model="obrigatorio" class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-900 font-bold">Campo Obrigatório</span>
                            <span class="text-[10px] text-gray-500">O usuário não poderá avançar sem preencher.</span>
                        </div>
                    </label>

                    {{-- CONDICIONAIS AVANÇADAS --}}
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 class="text-xs font-bold text-yellow-800 mb-3 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-fill ph-git-branch text-yellow-600"></i> Lógica Condicional
                        </h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-yellow-700 mb-1">Mostrar este campo SOMENTE SE:</label>
                                <select wire:model.live="depende_de" class="w-full text-sm rounded border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 bg-white">
                                    <option value="">-- Sempre Visível --</option>
                                    @foreach($camposCadastrados as $c)
                                        @if($c->id !== $campoId)
                                            <option value="{{ $c->name }}">{{ $c->label }} (Etapa {{ $c->etapa }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            
                            @if(!empty($depende_de))
                                <div class="grid grid-cols-3 gap-2 p-3 bg-white rounded border border-yellow-200">
                                    <div class="col-span-1">
                                        <select wire:model="depende_operador" class="w-full text-xs rounded border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                            <option value="=">For igual a</option>
                                            <option value="!=">For diferente de</option>
                                            <option value=">">Maior que</option>
                                            <option value="<">Menor que</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" wire:model="depende_valor" placeholder="Valor esperado" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
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
                    <i class="ph-bold ph-floppy-disk"></i> {{ $campoId ? 'Salvar Edição' : 'Adicionar Campo' }}
                </button>
            </div>

        </div>
    </div>
</div>

