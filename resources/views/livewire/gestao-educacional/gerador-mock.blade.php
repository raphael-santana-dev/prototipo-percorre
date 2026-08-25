<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Simulador de Integração em Lote" 
        icon="ph ph-magic-wand"
        badge="Ambiente de Testes">
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-center">
        
        <div class="inline-block p-4 bg-purpura-50 dark:bg-purpura-900/30 rounded-full mb-4">
            <i class="text-4xl text-purpura-600 dark:text-purpura-400 ph ph-database"></i>
        </div>
        
        <h3 class="text-xl font-black text-gray-800 dark:text-white mb-2">Simular Importação Aleatória</h3>
        <p class="text-gray-500 text-sm mb-6 max-w-2xl mx-auto">
            Defina quantas avaliações (Estudantes + Matrículas) deseja gerar. O sistema irá embaralhar os cursos e turmas já existentes ou criar novos se necessário para preencher as matrizes.
        </p>

        @if(!$ambienteGerado)
            <div class="flex flex-col items-center justify-center gap-4 max-w-sm mx-auto">
                <div class="w-full text-left">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Quantidade de Injeções</label>
                    <input type="number" wire:model="quantidadeInjecao" min="1" max="100" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 focus:border-purpura-500 shadow-sm text-center font-black text-lg py-3">
                    @error('quantidadeInjecao') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                @if(feature('ferramenta.mock') && (auth()->user()->hasRole('dev') || auth()->user()->can('ferramenta.mock')))
                    <button wire:click="gerarAmbienteCompleto" wire:loading.attr="disabled" class="w-full py-3.5 bg-ponkan-500 hover:bg-ponkan-600 text-white font-black rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                        <span wire:loading.remove><i class="ph-bold ph-rocket-launch text-lg"></i> Injetar Dados Fictícios</span>
                        <span wire:loading><i class="ph ph-spinner animate-spin text-lg"></i> Processando BD...</span>
                    </button>
                @else
                    <div class="p-4 bg-red-50 text-red-600 rounded-lg">Acesso negado para gerar dados.</div>
                @endif
            </div>
        @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 p-6 rounded-xl text-left max-w-3xl mx-auto">
                <div class="flex justify-between items-center mb-4 border-b border-green-200 dark:border-green-800/50 pb-4">
                    <h4 class="text-green-800 dark:text-green-400 font-bold text-lg flex items-center gap-2">
                        <i class="ph-fill ph-check-circle text-2xl"></i> {{ $quantidadeInjecao }} Registros Injetados!
                    </h4>
                    <button wire:click="$set('ambienteGerado', false)" class="text-sm font-bold text-green-700 dark:text-green-500 hover:underline">Gerar Mais</button>
                </div>
                
                <p class="text-sm text-green-700 dark:text-green-500 mb-4">Abaixo estão as credenciais dos estudantes gerados. A senha padrão para todos é <b>senha123</b>.</p>

                <div class="max-h-64 overflow-y-auto custom-scrollbar pr-2 space-y-2">
                    @foreach($alunosGerados as $aluno)
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-100 dark:border-green-800/30 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-2">
                            <div>
                                <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ $aluno['nome'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $aluno['login'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] font-bold px-2 py-1 rounded">
                                    {{ $aluno['turma'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- TOAST SYSTEM GLOBAL --}}
    <div x-data="{ show: false, msg: '', type: 'sucesso' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; type = 'sucesso'; setTimeout(() => show = false, 3500);"
        @erro.window="show = true; msg = $event.detail.msg; type = 'erro'; setTimeout(() => show = false, 4500);"
        x-show="show" x-transition
        :class="type === 'sucesso' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed bottom-8 right-8 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph" :class="type === 'sucesso' ? 'ph-check-circle' : 'ph-warning-circle'"></i>
        <span x-text="msg"></span>
    </div>
</div>