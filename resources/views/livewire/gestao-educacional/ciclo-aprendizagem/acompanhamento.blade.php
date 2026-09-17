<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Acompanhamento: {{ $ciclo->nome }}" icon="ph ph-presentation-chart" badge="Aprendizagem">
        <x-slot name="filters">
            <div class="flex gap-3 w-full md:w-auto">
                <input type="text" wire:model.live.debounce.500ms="busca" placeholder="Buscar por aluno ou CPF..." class="w-64 border border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <select wire:model.live="filtroStatus" class="border border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="">Todos os Status</option>
                    <option value="1">Gerada</option>
                    <option value="2">Pendente / Em Andamento</option>
                    <option value="3">Concluída (Todas as Fases)</option>
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Aprendiz / Empresa</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Fase Atual / Parada</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Status Global</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-right">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($alunos as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $item->student->name ?? 'Aluno N/A' }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400"><i class="ph-fill ph-buildings"></i> {{ $item->student->empresa->nome_fantasia ?? 'Empresa N/A' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-bold rounded-md border border-indigo-200 dark:border-indigo-800 shadow-sm">
                                {{ $item->faseAtual->nome ?? 'Finalizado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item->status == '3')
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-check"></i> Concluído</span>
                            @elseif($item->status == '2')
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-spinner animate-spin"></i> Em Andamento</span>
                            @else
                                <span class="px-2.5 py-1 bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-clock"></i> {{ $item->status == '1' ? 'Gerada' : 'Atrasada' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="verRespostas({{ $item->student_id }})" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-bold rounded-lg transition shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                Ver Respostas
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-500">Nenhum aluno vinculado a este ciclo com o filtro selecionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $alunos->links() }}
        </div>
    </div>

    <!-- MODAL DE RESPOSTAS DO ALUNO -->
    @if($modalRespostasAberto && $alunoSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="fecharModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-files text-purpura-600"></i> Histórico de Avaliações
                        </h3>
                        <button wire:click="fecharModal" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xl"></i></button>
                    </div>

                    <div class="mb-5 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Aprendiz Analisado</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white">{{ $alunoSelecionado->name }}</p>
                        <p class="text-xs text-gray-500">{{ $alunoSelecionado->email }}</p>
                    </div>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar pr-2">
                        @forelse($respostasAluno as $resposta)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-purpura-300 transition-colors flex justify-between items-center">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purpura-600 bg-purpura-50 dark:bg-purpura-900/30 px-2 py-0.5 rounded">{{ $resposta->formulario->faseAprendizagem->nome ?? 'Fase Desconhecida' }}</span>
                                    <p class="font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $resposta->formulario->titulo }}</p>
                                    <p class="text-xs text-gray-500 mt-1"><i class="ph-fill ph-clock"></i> Respondido em {{ $resposta->updated_at->format('d/m/Y \à\s H:i') }}</p>
                                </div>
                                <a href="{{ route('formularios.respostas.show', $resposta->id) }}" target="_blank" class="px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-lg shadow-sm hover:bg-black transition shrink-0 flex items-center gap-2 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                    <i class="ph-bold ph-file-text"></i> Ler Resposta
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="ph-fill ph-empty text-3xl mb-2 text-gray-300"></i>
                                <p class="font-bold">Nenhum formulário respondido ainda.</p>
                                <p class="text-xs">As avaliações aparecerão aqui assim que o aluno, gestor ou equipe finalizarem os questionários desta trilha.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="flex justify-end pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="fecharModal" class="px-5 py-2 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Fechar Janela</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>