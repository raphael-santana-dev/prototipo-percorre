<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-4 gap-4">
        <div>
            <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-xs mb-1 inline-flex items-center gap-1 font-bold uppercase tracking-wider">
                <i class="ph ph-arrow-left"></i> Voltar para Ciclos
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Regras de Pontuação</h2>
            <p class="text-gray-500 text-sm">Configurando pontuação automatizada para o ciclo <span class="font-bold text-purpura-600">{{ $ciclo->nome }}</span>.</p>
        </div>
        @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
            <div class="flex items-center gap-3">
                <button wire:click="salvar" class="flex items-center gap-2 px-5 py-2.5 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Regras
                </button>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Cabeçalho das Colunas (Apenas Desktop) -->
        <div class="hidden md:flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
            <div class="w-6 text-center">#</div>
            <div class="w-40">Tipo de Regra</div>
            <div class="flex-1">Campo / Aplicação</div>
            <div class="w-32">Condição</div>
            <div class="w-48">Valor Esperado</div>
            <div class="w-24 text-center">Pontos / %</div>
            <div class="w-10"></div>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($regras as $index => $regra)
                @php
                    $tipo = $regra['tipo_regra'] ?? 'padrao';
                    $isEspecial = $tipo !== 'padrao';
                    $escopo = $regra['escopo'] ?? 'especifico';
                    $isGlobal = $isEspecial && $escopo === 'todos';
                @endphp

                <div class="flex flex-col md:flex-row items-start md:items-center gap-3 py-3 px-4 hover:bg-gray-50/50 transition-colors {{ $isEspecial ? 'bg-indigo-50/20' : '' }}" wire:key="regra-{{ $index }}">
                    
                    <div class="hidden md:block w-6 text-xs font-black text-gray-300 text-center">
                        {{ $index + 1 }}
                    </div>

                    <!-- 1. TIPO DE CÁLCULO -->
                    <div class="w-full md:w-40 shrink-0">
                        <select wire:model.live="regras.{{ $index }}.tipo_regra" class="w-full border-gray-200 rounded text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm {{ $isEspecial ? 'bg-indigo-50 text-indigo-700 font-bold' : 'bg-gray-50' }}">
                            <option value="padrao">Padrão (+ Pts)</option>
                            <option value="bonus_por_acerto">Bônus Acerto</option>
                            <option value="multiplicador_percentual">Multiplicador (%)</option>
                        </select>
                    </div>

                    <!-- 1.5 ESCOPO E CAMPOS -->
                    @if($isGlobal)
                        <div class="w-full md:flex-1 text-xs font-bold text-indigo-500 bg-indigo-50 px-3 py-1.5 rounded flex items-center gap-2">
                            <i class="ph-fill ph-globe-hemisphere-east text-base"></i> Aplica-se incondicionalmente a todos os campos.
                        </div>
                    @else
                        @if($isEspecial)
                            <div class="w-full md:w-32 shrink-0">
                                <select wire:model.live="regras.{{ $index }}.escopo" class="w-full border-gray-200 rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                                    <option value="especifico">Específico</option>
                                    <option value="todos">Global</option>
                                </select>
                            </div>
                        @endif
                        
                        <div class="w-full md:flex-1">
                            <select wire:model="regras.{{ $index }}.campo" class="w-full border-gray-200 rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                                <option value="">Qual campo será avaliado?</option>
                                @foreach($camposDisponiveis as $campo)
                                    <option value="{{ $campo['name'] }}">{{ Str::limit($campo['label'], 40) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. CONDIÇÃO -->
                        <div class="w-full md:w-32 shrink-0">
                            <select wire:model="regras.{{ $index }}.operador" class="w-full border-gray-200 rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                                <option value="=">Igual a</option>
                                <option value="!=">Diferente de</option>
                                <option value=">=">Maior ou igual a</option>
                                <option value="<=">Menor ou igual a</option>
                                <option value="between">Entre valores</option>
                                <option value="in">Dentre opções</option>
                            </select>
                        </div>

                        <!-- 4. VALOR ESPERADO -->
                        <div class="w-full md:w-48 shrink-0">
                            <input type="text" wire:model="regras.{{ $index }}.valor" placeholder="Ex: sim, não" class="w-full border-gray-200 rounded text-xs bg-white focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm">
                        </div>
                    @endif

                    <!-- 5. PONTOS -->
                    <div class="w-full md:w-24 shrink-0 relative flex items-center gap-2 md:block">
                        <span class="md:hidden text-[10px] font-bold text-gray-500 uppercase">Valor:</span>
                        <input type="number" step="0.1" wire:model="regras.{{ $index }}.pontos" placeholder="0" class="w-full border-gray-200 rounded text-xs font-bold text-center focus:ring-purpura-500 py-1.5 shadow-sm {{ $isEspecial ? 'bg-indigo-100 text-indigo-700' : 'bg-green-50 text-green-700' }}">
                        @if($tipo === 'multiplicador_percentual')
                            <span class="absolute right-3 top-[7px] text-[10px] font-black text-indigo-400">%</span>
                        @endif
                    </div>

                    <!-- 6. AÇÃO -->
                    @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                        <div class="w-full md:w-10 shrink-0 text-right md:text-center mt-2 md:mt-0">
                            <button type="button" wire:click="removeRegra({{ $index }})" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded transition" title="Excluir">
                                <i class="ph-bold ph-trash text-base"></i>
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Rodapé Minimalista -->
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-center">
            @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                <button type="button" wire:click="addRegra" class="text-sm font-bold text-purpura-600 hover:text-purpura-800 transition flex items-center gap-1">
                    <i class="ph-bold ph-plus"></i> Adicionar Nova Regra
                </button>
            @endif
        </div>
    </div>
</div>