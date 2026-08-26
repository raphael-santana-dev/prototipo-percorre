<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Ciclos
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Construtor de Regras de Pontuação</h2>
            <p class="text-gray-500 text-sm">Definindo pontuação automatizada para o ciclo: <span class="font-bold text-purpura-600">{{ $ciclo->nome }}</span></p>
        </div>
        @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
            <div class="flex items-center gap-3">
                <button wire:click="salvar" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Todas as Regras
                </button>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="p-4 bg-yellow-50 border-b border-yellow-200 flex items-start gap-3">
            <i class="ph-fill ph-warning-circle text-yellow-600 text-2xl"></i>
            <div>
                <h3 class="font-bold text-yellow-900">Como funciona o cálculo?</h3>
                <p class="text-sm text-yellow-800 mt-1">O sistema primeiro soma todas as regras do tipo <b>Padrão (Soma)</b>. Depois, aplica as <b>Regras Especiais</b> (como Bônus Multiplicador ou Porcentagem) sobre o valor base. Para condições `entre` e `dentre opções`, separe os valores por vírgula (Ex: `18,25` ou `pcd,baixa renda`).</p>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                @foreach($regras as $index => $regra)
                    @php
                        $tipo = $regra['tipo_regra'] ?? 'padrao';
                        $isEspecial = $tipo !== 'padrao';
                        $escopo = $regra['escopo'] ?? 'especifico';
                        $isGlobal = $isEspecial && $escopo === 'todos';
                    @endphp

                    <div class="flex flex-col lg:flex-row items-end gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg group transition-colors hover:border-purpura-300 relative {{ $isEspecial ? 'border-indigo-200 bg-indigo-50/30' : '' }}" wire:key="regra-{{ $index }}">
                        
                        <div class="absolute -left-2 -top-2 w-6 h-6 bg-gray-900 text-white font-bold text-xs flex items-center justify-center rounded-full shadow-sm z-10">
                            {{ $index + 1 }}
                        </div>

                        <!-- 1. TIPO DE CÁLCULO -->
                        <div class="w-full {{ $isEspecial ? 'lg:w-[15%]' : 'lg:w-[20%]' }}">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tipo de Cálculo <span class="text-red-500">*</span></label>
                            <select wire:model.live="regras.{{ $index }}.tipo_regra" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple {{ $isEspecial ? 'bg-indigo-50 border-indigo-300 text-indigo-900 font-semibold' : '' }}">
                                <option value="padrao">Padrão (Soma)</option>
                                <option value="bonus_por_acerto">Bônus por Acerto</option>
                                <option value="multiplicador_percentual">Multiplicador (%)</option>
                            </select>
                            @error("regras.$index.tipo_regra") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- 1.5 ESCOPO (Aparece Apenas para Regras Especiais) -->
                        @if($isEspecial)
                        <div class="w-full lg:w-[15%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Aplicação <span class="text-red-500">*</span></label>
                            <select wire:model.live="regras.{{ $index }}.escopo" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple bg-indigo-50 border-indigo-300 text-indigo-900 font-semibold">
                                <option value="especifico">Campos Específicos</option>
                                <option value="todos">Todos os Campos</option>
                            </select>
                        </div>
                        @endif

                        <!-- CONDICIONAIS (Somem se a regra for Global) -->
                        @if(!$isGlobal)
                            <!-- 2. QUAL PERGUNTA SERÁ AVALIADA -->
                            <div class="w-full {{ $isEspecial ? 'lg:w-[20%]' : 'lg:w-[30%]' }}">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Qual campo será avaliado? <span class="text-red-500">*</span></label>
                                <select wire:model="regras.{{ $index }}.campo" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                                    <option value="">Selecione um campo...</option>
                                    @foreach($camposDisponiveis as $campo)
                                        <option value="{{ $campo['name'] }}">{{ Str::limit($campo['label'], 35) }} ({{ $campo['name'] }})</option>
                                    @endforeach
                                </select>
                                @error("regras.$index.campo") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- 3. CONDIÇÃO -->
                            <div class="w-full {{ $isEspecial ? 'lg:w-[12%]' : 'lg:w-[15%]' }}">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Condição <span class="text-red-500">*</span></label>
                                <select wire:model="regras.{{ $index }}.operador" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                                    <option value="=">Igual a</option>
                                    <option value="!=">Diferente de</option>
                                    <option value=">=">Maior ou igual a</option>
                                    <option value="<=">Menor ou igual a</option>
                                    <option value="between">Entre valores</option>
                                    <option value="in">Dentre opções</option>
                                </select>
                            </div>

                            <!-- 4. VALOR ESPERADO -->
                            <div class="w-full {{ $isEspecial ? 'lg:w-[18%]' : 'lg:w-[20%]' }}">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Valor Esperado <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="regras.{{ $index }}.valor" placeholder="Ex: sim / 18,25" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                                @error("regras.$index.valor") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <!-- PLACEHOLDER GLOBAL -->
                            <div class="w-full lg:w-[50%] flex items-center justify-center p-[10px] mb-[2px] bg-indigo-100/50 border border-dashed border-indigo-300 rounded-md text-indigo-700 text-sm font-medium">
                                <i class="ph-bold ph-globe-hemisphere-east mr-2 text-lg"></i> Regra incondicional. Aplica-se a todos do formulário.
                            </div>
                        @endif

                        <!-- 5. PONTOS / AÇÕES -->
                        <div class="w-full flex-1 flex gap-2">
                            <div class="flex-1 relative">
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    @if($tipo === 'multiplicador_percentual')
                                        Porcentagem (%) <span class="text-red-500">*</span>
                                    @else
                                        Pontos (+ Pts) <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                
                                <input type="number" step="0.1" wire:model="regras.{{ $index }}.pontos" placeholder="0" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple text-center font-bold {{ $isEspecial ? 'text-indigo-700 bg-indigo-100 border-indigo-300' : 'text-green-600 bg-green-50' }}">
                                
                                @if($tipo === 'multiplicador_percentual')
                                    <span class="absolute right-3 top-[28px] text-indigo-500 font-extrabold">%</span>
                                @endif
                                
                                @error("regras.$index.pontos") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="flex-none pb-[1px]">
                                @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                                    <button type="button" wire:click="removeRegra({{ $index }})" class="h-[38px] px-3 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 rounded-md transition" title="Remover Regra">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- DICAS DE CONTEXTO -->
                        @if($isEspecial)
                            @if($tipo === 'bonus_por_acerto')
                                <div class="absolute -bottom-8 left-4 right-4 p-1.5 px-3 bg-indigo-100 text-indigo-800 rounded-b-md text-xs font-medium border border-t-0 border-indigo-200 shadow-sm flex items-center gap-1.5 z-0">
                                    <i class="ph-fill ph-info"></i> O candidato ganhará <b>+{{ $regra['pontos'] ?: 'X' }} pts</b> extras multiplicados pelo total de regras PADRÃO que ele atender neste formulário.
                                </div>
                            @elseif($tipo === 'multiplicador_percentual')
                                <div class="absolute -bottom-8 left-4 right-4 p-1.5 px-3 bg-indigo-100 text-indigo-800 rounded-b-md text-xs font-medium border border-t-0 border-indigo-200 shadow-sm flex items-center gap-1.5 z-0">
                                    <i class="ph-fill ph-info"></i> O candidato receberá um acréscimo de <b>+{{ $regra['pontos'] ?: 'X' }}%</b> sobre o Score Base Total dele.
                                </div>
                            @endif
                        @endif

                    </div>
                @endforeach
            </div>

            <div class="mt-12 border-t border-dashed border-gray-300 pt-6 text-center">
                @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                    <button type="button" wire:click="addRegra" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded-full shadow-sm transition inline-flex items-center gap-2 text-sm">
                        <i class="ph-bold ph-plus-circle text-lg"></i> Adicionar Nova Regra
                    </button>
                @endif
            </div>

        </div>
    </div>
</div>