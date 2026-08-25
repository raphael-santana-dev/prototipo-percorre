<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Avaliações Socioemocionais" 
        icon="ph ph-exam"
        badge="Acompanhamento">
        
        <x-slot name="filters">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.500ms="busca" placeholder="Buscar por aluno, CPF ou turma..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm transition text-sm">
                <i class="ph ph-magnifying-glass text-gray-400 absolute left-3 top-2.5 text-lg"></i>
            </div>
        </x-slot>
    </x-page-header>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="false">
        
        @forelse($registros as $av)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purpura-100 text-purpura-600 flex items-center justify-center font-bold text-xs shrink-0">
                            {{ substr($av->student->name ?? 'A', 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $av->student->name ?? 'Aluno Removido' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $av->student->email ?? '-' }}</div>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-gray-700 dark:text-gray-300 text-sm">{{ $av->turma->nome ?? 'Turma N/A' }}</div>
                    <div class="text-[11px] text-purpura-600 font-bold mt-0.5 uppercase tracking-wider">
                        Ciclo {{ $av->periodo->ciclo ?? '-' }}
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-medium">
                    Ano Referência: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $av->periodo->ano ?? '-' }}</span>
                </td>
                
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if(feature('avaliacao.responder'))
                        <a href="{{ route('avaliacoes.responder', ['periodo' => $av->periodo_id, 'turma' => $av->turma_id, 'student' => $av->student_id]) }}" 
                        wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 text-xs font-bold rounded-lg transition shadow-sm border border-indigo-200 dark:border-indigo-800">
                            <i class="ph-bold ph-pencil-simple text-sm"></i>
                            Acessar Matriz
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <i class="ph ph-exam text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nenhuma avaliação encontrada.</p>
                    <p class="text-xs text-gray-500 mt-1">Você não possui matrizes de avaliação pendentes neste momento.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>