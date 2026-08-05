<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans">
    
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Ciclos
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Construtor de Regras de Pontuação</h2>
            <p class="text-gray-500 text-sm">Definindo pontuação automatizada para o ciclo: <span class="font-bold text-purpura-600">{{ $ciclo->nome }}</span></p>
        </div>
        
        <div class="flex items-center gap-3">
            <button wire:click="salvar" class="bg-brand-purple hover:bg-brand-purpleHover text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Todas as Regras
            </button>
        </div>
    </div>

    @if (session()->has('sucesso'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg shadow-sm flex items-center gap-2 font-bold">
            <i class="ph-fill ph-check-circle text-xl text-green-500"></i> {{ session('sucesso') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="p-4 bg-yellow-50 border-b border-yellow-200 flex items-start gap-3">
            <i class="ph-fill ph-warning-circle text-yellow-600 text-2xl"></i>
            <div>
                <h3 class="font-bold text-yellow-900">Como funciona o cálculo?</h3>
                <p class="text-sm text-yellow-800 mt-1">Ao enviar a inscrição, o sistema avaliará cada resposta. Se o candidato atender à condição definida abaixo, ele ganhará os pontos informados. Para condições `entre` e `dentre opções`, separe os valores por vírgula (Ex: `18,25` ou `aprovado,selecionado`).</p>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                @foreach($regras as $index => $regra)
                    <div class="flex flex-col lg:flex-row items-end gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg group transition-colors hover:border-purpura-300 relative" wire:key="regra-{{ $index }}">
                        
                        <div class="absolute -left-2 -top-2 w-6 h-6 bg-gray-900 text-white font-bold text-xs flex items-center justify-center rounded-full shadow-sm">
                            {{ $index + 1 }}
                        </div>

                        <div class="w-full lg:w-1/3">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Qual pergunta será avaliada? <span class="text-red-500">*</span></label>
                            <select wire:model="regras.{{ $index }}.campo" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                                <option value="">Selecione um campo...</option>
                                @foreach($camposDisponiveis as $campo)
                                    <option value="{{ $campo['name'] }}">{{ $campo['label'] }} ({{ $campo['name'] }})</option>
                                @endforeach
                            </select>
                            @error("regras.$index.campo") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="w-full lg:w-1/6">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Condição <span class="text-red-500">*</span></label>
                            <select wire:model="regras.{{ $index }}.operador" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                                <option value="=">Igual a</option>
                                <option value="!=">Diferente de</option>
                                <option value=">=">Maior ou igual a</option>
                                <option value="<=">Menor ou igual a</option>
                                <option value="between">Entre os valores</option>
                                <option value="in">Dentre as opções</option>
                            </select>
                        </div>

                        <div class="w-full lg:w-1/3">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Valor Esperado <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="regras.{{ $index }}.valor" placeholder="Ex: sim / 18,25 / facebook" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple">
                            @error("regras.$index.valor") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="w-full lg:w-1/6 flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Pontos <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="regras.{{ $index }}.pontos" placeholder="0" class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-brand-purple focus:border-brand-purple text-center font-bold text-green-600 bg-green-50">
                                @error("regras.$index.pontos") <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="flex-none pb-[1px]">
                                <button type="button" wire:click="removeRegra({{ $index }})" class="h-[38px] px-3 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 rounded-md transition" title="Remover Regra">
                                    <i class="ph-bold ph-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-dashed border-gray-300 pt-4 text-center">
                <button type="button" wire:click="addRegra" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded-full shadow-sm transition inline-flex items-center gap-2 text-sm">
                    <i class="ph-bold ph-plus-circle text-lg"></i> Adicionar Nova Regra
                </button>
            </div>

        </div>
    </div>
</div>