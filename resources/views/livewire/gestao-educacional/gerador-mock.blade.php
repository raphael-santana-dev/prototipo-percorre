<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Simulador de Integração (Mock)" 
        icon="ph ph-magic-wand"
        badge="Ambiente de Testes">
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-center">
        
        <div class="inline-block p-4 bg-purpura-50 dark:bg-purpura-900/30 rounded-full mb-4">
            <i class="text-4xl text-purpura-600 dark:text-purpura-400 ph ph-database"></i>
        </div>
        
        <h3 class="text-xl font-black text-gray-800 dark:text-white mb-2">Simular Importação do Protheus</h3>
        <p class="text-gray-500 text-sm mb-8 max-w-2xl mx-auto">
            Este botão simula a carga de dados que futuramente será feita de forma automática. Ele criará: <br>
            <b>1 Curso, 1 Turma, 1 Professor, 1 Aluno, 1 Período (com 3 fases) e 3 Critérios de Avaliação</b>.
        </p>

        @if(!$ambienteGerado)
            <div class="flex justify-center">
                <button wire:click="gerarAmbienteCompleto" wire:loading.attr="disabled" class="px-8 py-3 bg-ponkan-500 hover:bg-ponkan-600 text-white font-black rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                    <span wire:loading.remove><i class="ph-bold ph-rocket-launch"></i> Injetar Dados Fictícios</span>
                    <span wire:loading><i class="ph ph-spinner animate-spin"></i> Processando...</span>
                </button>
            </div>
        @else
            <div class="bg-green-50 border border-green-200 p-6 rounded-xl text-left max-w-2xl mx-auto">
                <h4 class="text-green-800 font-bold text-lg mb-4 flex items-center gap-2">
                    <i class="ph-fill ph-check-circle text-2xl"></i> Dados Injetados com Sucesso!
                </h4>
                
                <p class="text-sm text-green-700 mb-4">As matrizes em branco já estão no banco de dados. Utilize as credenciais abaixo para testar as telas de resposta seguindo as permissões:</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded-lg border border-green-100 shadow-sm">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Acesso do Estudante</span>
                        <p class="text-sm text-gray-800"><b>E-mail:</b> {{ $credenciais['estudante']['login'] }}</p>
                        <p class="text-sm text-gray-800 mt-1"><b>Senha:</b> {{ $credenciais['estudante']['senha'] }}</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg border border-green-100 shadow-sm">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Acesso do Professor</span>
                        <p class="text-sm text-gray-800"><b>E-mail:</b> {{ $credenciais['professor']['login'] }}</p>
                        <p class="text-sm text-gray-800 mt-1"><b>Senha:</b> {{ $credenciais['professor']['senha'] }}</p>
                    </div>
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