<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('automacoes.index') }}" class="text-purpura-600 hover:text-purpura-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Regras
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">
                {{ $automacaoId ? 'Editar Gatilho de Automação' : 'Configurar Gatilho de Automação' }}
            </h2>
        </div>
    </div>

    <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-gray-200">
        <form wire:submit.prevent="salvar" class="space-y-6">
            
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">Apelido da Regra <span class="text-red-500">*</span></label>
                <input wire:model="nome" type="text" placeholder="Ex: Enviar boas-vindas para aluno aprovado" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm">
                @error('nome') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="p-5 bg-blue-50 border border-blue-200 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div>
                    <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                        <i class="ph-fill ph-lightning"></i> Quando acontecer...
                    </label>
                    <select wire:model="evento_gatilho" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-white">
                        <option value="">Selecione o Gatilho...</option>
                        @foreach($eventosDisponiveis as $chave => $label)
                            <option value="{{ $chave }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('evento_gatilho') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                        <i class="ph-fill ph-paper-plane-tilt"></i> Então envie o e-mail...
                    </label>
                    <select wire:model="template_id" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-white">
                        <option value="">Selecione o Template...</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors {{ $status ? 'bg-green-500' : 'bg-gray-300' }}">
                        <input type="checkbox" wire:model="status" class="sr-only">
                        <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform {{ $status ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Regra Ativada (Disparar automaticamente)</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('automacoes.index') }}" class="px-5 py-2.5 text-sm font-bold border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancelar</a>
                <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Automação
                </button>
            </div>
        </form>
    </div>
</div>