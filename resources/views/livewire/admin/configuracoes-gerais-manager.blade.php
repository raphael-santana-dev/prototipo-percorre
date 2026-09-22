<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaLateral: $wire.entangle('abaLateral') }">
    
    <x-page-header title="Configurações Gerais" icon="ph ph-gear" badge="Sistema">
        <x-slot name="actions">
            <button wire:click="salvar" class="px-5 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Configurações
            </button>
        </x-slot>
    </x-page-header>

    <div class="flex flex-col md:flex-row gap-6 mt-6">
        <div class="w-full md:w-1/4 shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-2 flex flex-col gap-1">
                <button @click="abaLateral = 'gestao_educacional'" :class="abaLateral === 'gestao_educacional' ? 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400' : 'border-transparent text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold rounded-lg border transition text-left">
                    <i class="ph-fill ph-graduation-cap text-lg"></i> Gestão Educacional
                </button>
                <button @click="abaLateral = 'processos_seletivos'" :class="abaLateral === 'processos_seletivos' ? 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400' : 'border-transparent text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold rounded-lg border transition text-left">
                    <i class="ph-fill ph-users text-lg"></i> Processos Seletivos
                </button>
            </div>
        </div>

        <div class="w-full md:w-3/4">
            <div x-show="abaLateral === 'gestao_educacional'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-bold ph-exam text-purpura-600"></i> Matrizes de Avaliação
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Controle de visualização e permissões de preenchimento das fases socioemocionais.</p>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition {{ $ocultar_fases_restritas ? 'border-purpura-400 bg-purpura-50/30' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }}">
                        <div class="pt-0.5">
                            <input type="checkbox" wire:model="ocultar_fases_restritas" class="w-5 h-5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 shadow-sm">
                        </div>
                        <div class="flex-1">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Ocultar fases de outros responsáveis</span>
                            <span class="block text-xs text-gray-500 mt-1 leading-relaxed">Se ativado, alunos não verão as colunas exclusivas dos professores, e os professores não verão as colunas exclusivas dos alunos. Fases "Ambos" permanecem visíveis para os dois.</span>
                        </div>
                    </label>

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

            <!-- NOVA ABA PROCESSOS SELETIVOS -->
            <div x-show="abaLateral === 'processos_seletivos'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-bold ph-users text-purpura-600"></i> Preenchimento de Vagas
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Defina quando uma inscrição passa a contar como vaga ocupada na turma e bloqueia a superlotação.</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">Critério de Ocupação da Vaga</label>
                        <div class="flex flex-col gap-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" wire:model.live="regra_ocupacao_vaga" value="por_status" class="w-5 h-5 text-purpura-600 focus:ring-purpura-500 border-gray-300">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Baseado no Status da Inscrição (Recomendado)</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" wire:model.live="regra_ocupacao_vaga" value="por_matricula" class="w-5 h-5 text-purpura-600 focus:ring-purpura-500 border-gray-300">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Baseado na Matrícula Ativa</span>
                                    <span class="text-xs text-gray-500">A vaga só é preenchida se o aluno possuir usuário (Student) gerado e o campo "Matriculado" estiver como Sim na tabela de estudantes.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">Quais Status acionam e bloqueiam a Vaga?</label>
                        <p class="text-xs text-gray-500 mb-3">Selecione os status que o sistema deve considerar para barrar superlotação no Kanban e Listagem (se a regra for por matrícula, ele usará isso para saber quando barrar a ação do usuário de enviar pra lá).</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($statusDb as $status)
                                <label class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                                    <input type="checkbox" wire:model="status_ocupacao_vaga" value="{{ $status->id }}" class="w-4 h-4 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $status->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>