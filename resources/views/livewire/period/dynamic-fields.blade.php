<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col">
    
    <div class="mb-6 flex justify-between items-center border-b pb-4">
        <div>
            <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:underline text-sm mb-1 inline-block">&larr; Voltar para Ciclos</a>
            <h2 class="text-2xl font-bold text-gray-800">Construtor do Formulário</h2>
            <p class="text-gray-500 text-sm">Gerenciando perguntas de: <span class="font-bold text-brand-purple">{{ $ciclo->nome }}</span></p>
        </div>
    </div>

    @if (session()->has('sucesso'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ session('sucesso') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- COLUNA ESQUERDA: FORMULÁRIO (Tamanho: 4/12) --}}
        <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6 max-h-[85vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                {{ $campoId ? '✏️ Editando Campo' : '✨ Adicionar Campo' }}
            </h3>

            <form wire:submit.prevent="salvar" class="space-y-4">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Etapa de Exibição <span class="text-red-500">*</span></label>
                        <select wire:model="etapa" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($etapasDisponiveis as $et)
                                <option value="{{ $et->numero }}">Etapa {{ $et->numero }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pergunta (Label) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="label" placeholder="Ex: Qual sua escolaridade?" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Name (DB) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Ordem (Posição) <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="ordem" min="1" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tipo de Campo <span class="text-red-500">*</span></label>
                        <select wire:model.live="tipo" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="text">Texto (Input)</option>
                            <option value="select">Select (Lista)</option>
                            <option value="radio">Múltipla Escolha</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Largura na Tela <span class="text-red-500">*</span></label>
                        <select wire:model="largura" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="12">100% (Linha Inteira)</option>
                            <option value="6">50% (Metade)</option>
                            <option value="4">33% (Um Terço)</option>
                            <option value="3">25% (Um Quarto)</option>
                        </select>
                    </div>
                </div>

                @if($tipo === 'text')
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded">
                        <label class="block text-xs font-bold text-blue-900 mb-1">Subtipo / Formato</label>
                        <select wire:model.live="subtipo" class="w-full text-sm rounded-md border border-blue-300 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-3">
                            <option value="text">Texto Simples</option>
                            <option value="email">E-mail</option>
                            <option value="number">Apenas Números</option>
                            <option value="date">Data (Calendário)</option>
                            <option value="password">Senha</option>
                        </select>
                        
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div>
                                <label class="block text-[10px] font-bold text-blue-800 mb-1">Min. Caracteres/Valor</label>
                                <input type="number" wire:model="tamanho_min" class="w-full text-xs rounded-md border border-blue-300 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-blue-800 mb-1">Max. Caracteres/Valor</label>
                                <input type="number" wire:model="tamanho_max" class="w-full text-xs rounded-md border border-blue-300 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-blue-800 mb-1">Máscara Alpine (x-mask)</label>
                            <input type="text" wire:model="regex_mascara" placeholder="Ex: 999.999.999-99" class="w-full text-xs rounded-md border border-blue-300 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                @endif

                @if(in_array($tipo, ['select', 'radio']))
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Opções (Separadas por vírgula)</label>
                        <textarea wire:model="opcoes" rows="2" class="w-full text-sm rounded-md border border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                @endif

                <div class="flex items-center mt-2 mb-4">
                    <input type="checkbox" wire:model="obrigatorio" id="obrig" class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <label for="obrig" class="ml-2 block text-sm text-gray-900 font-bold">Tornar Obrigatório?</label>
                </div>

                {{-- CONDICIONAIS AVANÇADAS --}}
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <h4 class="text-xs font-bold text-yellow-800 mb-3">Lógica Condicional (Aparecer se...)</h4>
                    
                    <div class="space-y-3">
                        <select wire:model.live="depende_de" class="w-full text-sm rounded border border-yellow-300 p-2 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                            <option value="">Sempre Visível</option>
                            @foreach($camposCadastrados as $c)
                                @if($c->id !== $campoId)
                                    <option value="{{ $c->name }}">{{ $c->label }}</option>
                                @endif
                            @endforeach
                        </select>
                        
                        @if(!empty($depende_de))
                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-1">
                                    <select wire:model="depende_operador" class="w-full text-xs rounded border border-yellow-300 p-2 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 font-mono">
                                        <option value="=">Igual (=)</option>
                                        <option value="!=">Dif (!=)</option>
                                        <option value=">">Maior (>)</option>
                                        <option value="<">Menor (<)</option>
                                        <option value=">=">Maior I (>=)</option>
                                        <option value="<=">Menor I (<=)</option>
                                        <option value="in">Contém (in)</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="text" wire:model="depende_valor" placeholder="Valor esperado" class="w-full text-sm rounded border border-yellow-300 p-2 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    @if($campoId)
                        <button type="button" wire:click="cancelarEdicao" class="flex-1 bg-white border border-gray-300 py-2 rounded text-sm font-bold shadow-sm hover:bg-gray-50 transition">Cancelar</button>
                    @endif
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded text-sm font-bold shadow-sm hover:bg-indigo-700 transition">
                        {{ $campoId ? 'Salvar Edição' : 'Inserir Campo' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- COLUNA DIREITA: VISUALIZAÇÃO DOS CARDS (Tamanho: 8/12) --}}
        <div class="lg:col-span-8 space-y-8">
            
            {{-- SAFELIST DO TAILWIND PARA O ADMIN: Não apague esta div --}}
            <div class="hidden col-span-3 col-span-4 col-span-6 col-span-12"></div>

            @forelse($camposPorEtapa as $numEtapa => $camposDaEtapa)
                <div class="p-5 bg-gray-50 rounded-xl border border-gray-200" wire:key="grupo-etapa-{{ $numEtapa }}">
                    <h3 class="flex items-center gap-2 mb-4 text-lg font-bold text-gray-800">
                        <span class="flex items-center justify-center w-6 h-6 text-xs text-white bg-indigo-600 rounded-full">{{ $numEtapa }}</span>
                        Campos da Etapa {{ $numEtapa }}
                    </h3>
                    
                    {{-- Grade que simula a visualização real do formulário (12 colunas) --}}
                    <div class="grid grid-cols-12 gap-3">
                        @foreach($camposDaEtapa as $c)
                            <div wire:key="campo-{{ $c->id }}" class="col-span-{{ $c->largura }} relative group bg-white border {{ $campoId == $c->id ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-200' }} rounded-lg p-3 shadow-sm hover:border-indigo-300 transition">                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-bold text-gray-400">#{{ $c->ordem }}</span>
                                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                                        <button wire:click="editar({{ $c->id }})" class="text-indigo-600 hover:text-indigo-800"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                        <button wire:click="excluir({{ $c->id }})" wire:confirm="Excluir este campo?" class="text-red-500 hover:text-red-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </div>
                                </div>

                                <p class="text-sm font-bold text-gray-800 truncate">{{ $c->label }} @if($c->obrigatorio) <span class="text-red-500">*</span> @endif</p>
                                
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <span class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded font-mono">{{ $c->name }}</span>
                                    <span class="bg-indigo-50 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded uppercase">{{ $c->tipo == 'text' ? $c->subtipo : $c->tipo }}</span>
                                    @if($c->largura == 12) <span class="bg-blue-50 text-blue-700 text-[10px] px-1.5 py-0.5 rounded">100% (Toda Linha)</span> @endif
                                </div>

                                @if($c->depende_de)
                                    <div class="mt-2 bg-yellow-50 text-yellow-800 text-[10px] px-2 py-1 rounded border border-yellow-100 font-bold truncate">
                                        Se: {{ $c->depende_de }} {{ $c->depende_operador }} {{ $c->depende_valor }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center text-gray-500">
                    Nenhum campo cadastrado ainda. Use o formulário ao lado para iniciar.
                </div>
            @endforelse
        </div>
    </div>
</div>