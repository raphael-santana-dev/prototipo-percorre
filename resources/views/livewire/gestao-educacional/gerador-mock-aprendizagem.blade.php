<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Simulador: Workflow de Aprendizagem" 
        icon="ph ph-tree-structure"
        badge="Ambiente de Testes">
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-center">
        
        <div class="inline-block p-4 bg-orange-50 dark:bg-orange-900/30 rounded-full mb-4 border border-orange-100">
            <i class="text-4xl text-orange-600 dark:text-orange-400 ph-bold ph-graduation-cap"></i>
        </div>
        
        <h3 class="text-xl font-black text-gray-800 dark:text-white mb-2">Simular Workflow Completo</h3>
        <p class="text-gray-500 text-sm mb-6 max-w-2xl mx-auto">
            Esta ferramenta cria Empresas fictícias, cadastra um Gestor, vincula um Aprendiz elegível e gera automaticamente a avaliação deles distribuindo-as aleatoriamente entre a Fase 1 (Aprendiz) e a Fase 2 (Empresa).
        </p>

        @if(!$ambienteGerado)
            <div class="flex flex-col items-center justify-center gap-4 max-w-sm mx-auto">
                <div class="w-full text-left">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Quantidade de Injeções</label>
                    <input type="number" wire:model="quantidadeInjecao" min="1" max="50" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-orange-500 focus:border-orange-500 shadow-sm text-center font-black text-lg py-3">
                    @error('quantidadeInjecao') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <button wire:click="gerarAmbienteCompleto" wire:loading.attr="disabled" class="w-full py-3.5 bg-orange-600 hover:bg-orange-700 text-white font-black rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                    <span wire:loading.remove><i class="ph-bold ph-lightning text-lg"></i> Injetar Dados Fictícios</span>
                    <span wire:loading><i class="ph ph-spinner animate-spin text-lg"></i> Processando BD...</span>
                </button>
            </div>
        @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 p-6 rounded-xl text-left max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-4 border-b border-green-200 dark:border-green-800/50 pb-4">
                    <h4 class="text-green-800 dark:text-green-400 font-bold text-lg flex items-center gap-2">
                        <i class="ph-fill ph-check-circle text-2xl"></i> {{ $quantidadeInjecao }} Fluxos Injetados!
                    </h4>
                    <button wire:click="$set('ambienteGerado', false)" class="text-sm font-bold text-green-700 dark:text-green-500 hover:underline">Gerar Mais</button>
                </div>
                
                <p class="text-sm text-green-700 dark:text-green-500 mb-4">
                    As matrículas e vínculos já estão prontos. A senha padrão do Gestor é <b>senha123</b>.<br>
                    Você pode usar o login do gestor abaixo no Portal da Empresa para ver a listagem e responder às avaliações!
                </p>

                <div class="max-h-80 overflow-y-auto custom-scrollbar pr-2 space-y-2">
                    @foreach($dadosGerados as $dado)
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-100 dark:border-green-800/30 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <span class="block text-sm font-bold text-gray-900 dark:text-white"><i class="ph-fill ph-student text-orange-500"></i> {{ $dado['aluno_nome'] }}</span>
                                <span class="block text-[11px] text-gray-500 mt-0.5"><i class="ph-fill ph-buildings"></i> {{ $dado['empresa_nome'] }}</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-xs font-mono text-gray-600 dark:text-gray-300 font-bold bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded mb-1 border border-gray-200">
                                    {{ $dado['gestor_email'] }}
                                </span>
                                <span class="inline-block bg-orange-100 text-orange-800 text-[9px] font-black uppercase px-2 py-0.5 rounded-full border border-orange-200">
                                    {{ $dado['fase'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>