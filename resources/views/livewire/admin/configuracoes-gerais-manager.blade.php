<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaLateral: $wire.entangle('abaLateral') }">
    
    <x-page-header title="Configurações Gerais" icon="ph ph-gear" badge="Sistema">
        <x-slot name="actions">
            <button wire:click="salvar" class="px-5 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Configurações
            </button>
        </x-slot>
    </x-page-header>

    <div class="flex flex-col md:flex-row gap-6 mt-6">
        <!-- Lado Esquerdo: Menu de Temas -->
        <div class="w-full md:w-1/4 shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-2 flex flex-col gap-1">
                <button @click="abaLateral = 'gestao_educacional'" :class="abaLateral === 'gestao_educacional' ? 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400' : 'border-transparent text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold rounded-lg border transition text-left">
                    <i class="ph-fill ph-graduation-cap text-lg"></i> Gestão Educacional
                </button>
                <!-- Mais temas podem ser adicionados aqui futuramente -->
            </div>
        </div>

        <!-- Lado Direito: Opções do Tema -->
        <div class="w-full md:w-3/4">
            <div x-show="abaLateral === 'gestao_educacional'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-bold ph-exam text-purpura-600"></i> Matrizes de Avaliação
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Controle de visualização e permissões de preenchimento das fases socioemocionais.</p>
                </div>

                <div class="space-y-4">
                    <!-- Config 1: Visibilidade -->
                    <label class="flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition {{ $ocultar_fases_restritas ? 'border-purpura-400 bg-purpura-50/30' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }}">
                        <div class="pt-0.5">
                            <input type="checkbox" wire:model="ocultar_fases_restritas" class="w-5 h-5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 shadow-sm">
                        </div>
                        <div class="flex-1">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Ocultar fases de outros responsáveis</span>
                            <span class="block text-xs text-gray-500 mt-1 leading-relaxed">Se ativado, alunos não verão as colunas exclusivas dos professores, e os professores não verão as colunas exclusivas dos alunos. Fases "Ambos" permanecem visíveis para os dois.</span>
                        </div>
                    </label>

                    <!-- Config 2: Edição da fase Ambos -->
                    <label class="flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition {{ $permitir_aluno_responder_ambos ? 'border-purpura-400 bg-purpura-50/30' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }}">
                        <div class="pt-0.5">
                            <input type="checkbox" wire:model="permitir_aluno_responder_ambos" class="w-5 h-5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 shadow-sm">
                        </div>
                        <div class="flex-1">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Permitir que aluno responda à fase "Ambos"</span>
                            <span class="block text-xs text-gray-500 mt-1 leading-relaxed">Se desativado, o aluno apenas <b>visualizará</b> as respostas dessa fase, garantindo que a responsabilidade de preenchimento de notas e metas recaia <b>exclusivamente sobre o professor</b>.</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>