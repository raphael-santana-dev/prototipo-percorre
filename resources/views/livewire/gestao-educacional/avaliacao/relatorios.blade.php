<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Relatórios Estratégicos" 
        icon="ph ph-chart-polar"
        badge="Indicadores"
        :metricas="$metricas">

        @if(feature('relatorio.exportar') && (auth()->user()->hasRole('dev') || auth()->user()->can('relatorio.exportar')))
            <x-slot name="actions">
                <button wire:click="exportarCSV" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition flex items-center gap-2">
                    <i class="ph-bold ph-microsoft-excel-logo text-lg"></i>
                    Exportar CSV
                </button>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filtrar por Período</label>
                    <select wire:model.live="periodoFiltro" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todos os Períodos</option>
                        @foreach($periodosDisponiveis as $per)
                            <option value="{{ $per->id }}">Ano {{ $per->ano }} / Ciclo {{ $per->ciclo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filtrar por Turma</label>
                    <select wire:model.live="turmaFiltro" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Todas as Turmas</option>
                        @foreach($turmasDisponiveis as $tur)
                            <option value="{{ $tur->id }}">{{ $tur->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar Aluno</label>
                    <input wire:model.live.debounce.300ms="busca" type="text" placeholder="Nome ou CPF..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </div>
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
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $av->student->name ?? 'Removido' }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">{{ $av->student->cpf ?? '-' }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-700 dark:text-gray-300 text-sm">{{ $av->turma->nome ?? 'N/A' }}</div>
                    <div class="text-[11px] text-purpura-600 font-bold mt-0.5 uppercase tracking-wider">
                        Ano {{ $av->periodo->ano }} - C{{ $av->periodo->ciclo }}
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    <span class="inline-block px-3 py-1 bg-gray-100 border border-gray-200 text-gray-800 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 font-black text-sm rounded shadow-sm">
                        {{ $av->mediaParcial }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-center">
                    <span class="inline-block px-3 py-1 bg-purpura-50 border border-purpura-100 text-purpura-700 dark:bg-purpura-900/30 dark:border-purpura-800 dark:text-purpura-400 font-black text-sm rounded shadow-sm">
                        {{ $av->mediaFinal }}
                    </span>
                </td>
                
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if(feature('avaliacao.responder') && (auth()->user()->hasRole('dev') || auth()->user()->can('avaliacao.responder')))
                        <a href="{{ route('avaliacoes.responder', ['periodo' => $av->periodo_id, 'turma' => $av->turma_id, 'student' => $av->student_id]) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Matriz Completa">
                            <i class="text-xl ph ph-eye"></i>
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <i class="ph ph-chart-polar text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nenhum registro encontrado.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>