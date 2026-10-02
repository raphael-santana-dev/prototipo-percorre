<div class="p-6 max-w-3xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $automacaoId ? 'Editar Regra de Automação' : 'Nova Regra de Automação' }}" 
        icon="ph ph-lightning"
        badge="">
        <x-slot name="actions">
            <a href="{{ route('automacoes.index') }}" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 transition dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 mt-4">
        <form wire:submit.prevent="salvar" class="space-y-8">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Apelido da Regra <span class="text-red-500">*</span></label>
                    <input wire:model="nome" type="text" placeholder="Ex: Enviar boas-vindas para aluno aprovado" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm">
                    @error('nome') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Ação Executada <span class="text-red-500">*</span></label>
                    <select wire:model="tipo_acao" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                        <option value="enviar_email">Apenas disparar E-mail (Template)</option>
                        <option value="criar_aluno_enviar_email">Criar Acesso (Student) + Disparar E-mail</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-2">Dica: Se criar o acesso, pode usar as variáveis <code class="text-purpura-600 bg-purpura-50 px-1 rounded">@{{senha_provisoria}}</code> e <code class="text-purpura-600 bg-purpura-50 px-1 rounded">@{{link_login}}</code> no template.</p>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="ph-fill ph-lightning text-purpura-500"></i> Evento Gatilho <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="evento_gatilho" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                        <option value="">Selecione quando ocorrerá...</option>
                        @foreach($eventosDisponiveis as $chave => $label)
                            <option value="{{ $chave }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('evento_gatilho') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="ph-fill ph-paper-plane-tilt text-purpura-500"></i> Template Alvo <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="template_id" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                        <option value="">Selecione o layout...</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group w-max">
                    <div class="relative inline-flex items-center h-5 rounded-full w-9 transition-colors {{ $status ? 'bg-green-500' : 'bg-gray-300' }}">
                        <input type="checkbox" wire:model.live="status" class="sr-only">
                        <span class="inline-block w-3.5 h-3.5 transform bg-white rounded-full transition-transform" style="{{ $status ? 'transform: translateX(18px);' : 'transform: translateX(3px);' }}"></span>
                    </div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">Regra Ativa</span>
                </label>
            </div>

            <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
                <a href="{{ route('automacoes.index') }}" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800 transition">Cancelar</a>
                @if(feature('automacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('automacao.editar')))
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-gray-900 hover:bg-black transition flex items-center gap-2">
                        Salvar Automação
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>