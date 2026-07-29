<div class="p-6 mx-auto font-sans max-w-7xl space-y-6">
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('ciclos.index') }}" class="p-2 text-gray-500 transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
            <i class="text-xl ph ph-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            Detalhes do Processo Seletivo
        </h2>
    </div>

    <!-- 1. CABEÇALHO (Master Card) -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 relative">
        <div class="absolute top-0 w-full h-32 bg-gradient-to-r from-emerald-600 to-teal-600"></div>
        
        <div class="relative px-6 pt-24 pb-6 sm:px-8">
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-6">
                <!-- Ícone -->
                <div class="flex items-center justify-center w-24 h-24 bg-white border-4 border-white rounded-2xl shadow-lg dark:bg-gray-900 dark:border-gray-800 shrink-0">
                    <i class="text-4xl ph ph-calendar-check text-emerald-500"></i>
                </div>
                
                <div class="flex-1 w-full">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $ciclo->nome }}</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1 font-bold">
                                {{ $ciclo->ano }} / {{ $ciclo->semestre }}º Semestre
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Período de Inscrição</p>
                                <p class="text-sm text-gray-900 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($ciclo->data_inicio)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($ciclo->data_fim)->format('d/m/Y') }}
                                </p>
                            </div>
                            @if($ciclo->status)
                                <span class="px-4 py-2 text-sm font-bold text-pistache-700 bg-pistache-100 rounded-full uppercase border border-pistache-200">Ativo</span>
                            @else
                                <span class="px-4 py-2 text-sm font-bold text-red-700 bg-red-100 rounded-full uppercase border border-red-200">Encerrado</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- 2. CURSOS VINCULADOS -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center justify-between">
                    <span class="flex items-center gap-2"><i class="ph ph-graduation-cap text-purpura-500"></i> Cursos Ofertados</span>
                    <span class="px-2 py-1 text-xs bg-gray-100 rounded text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $ciclo->cursos->count() }}</span>
                </h3>
                
                <div class="flex flex-col gap-2 max-h-96 overflow-y-auto pr-2">
                    @forelse($ciclo->cursos as $curso)
                        <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg dark:bg-gray-900/50 dark:border-gray-700">
                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $curso->nome }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4 border border-dashed border-gray-200 rounded-lg dark:border-gray-700">Nenhum curso vinculado a este ciclo.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 3. LISTAGEM DE INSCRIÇÕES -->
        <div class="md:col-span-2">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700 h-full">
                
                <!-- Barra de Busca -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-users text-purpura-500"></i> Candidatos Inscritos
                        <span class="px-2 py-1 text-xs bg-purpura-100 rounded text-purpura-700 ml-2 dark:bg-purpura-900/30 dark:text-purpura-400">{{ $inscricoes->total() }}</span>
                    </h3>
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-gray-400"></i>
                        </div>
                        <input type="text" wire:model.live.debounce.500ms="search" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Buscar por nome, CPF...">
                    </div>
                </div>
                
                <!-- Tabela -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Candidato</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Data</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-wider text-center text-gray-500 uppercase dark:text-gray-400">Etapa</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($inscricoes as $inscricao)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $inscricao->nome }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $inscricao->cpf }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ $inscricao->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($inscricao->etapa_atual === 99)
                                            <span class="inline-flex px-2 py-1 text-[10px] font-bold text-green-700 bg-green-100 rounded uppercase">Concluída</span>
                                        @elseif($inscricao->etapa_atual === 100)
                                            <span class="inline-flex px-2 py-1 text-[10px] font-bold text-yellow-700 bg-yellow-100 rounded uppercase">Lista de Espera</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-[10px] font-bold text-orange-700 bg-orange-100 rounded uppercase">Passo {{ $inscricao->etapa_atual }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button class="p-2 text-gray-500 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-purpura-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Ver Inscrição Completa">
                                            <i class="text-lg ph ph-arrow-right"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 border border-dashed border-gray-200 rounded-lg dark:border-gray-700">
                                        {{ $search ? 'Nenhum candidato encontrado na busca.' : 'Nenhuma inscrição registrada neste ciclo ainda.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $inscricoes->links() }}
                </div>
            </div>
        </div>

    </div>
</div>