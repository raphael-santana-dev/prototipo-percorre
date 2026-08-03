<div class="p-6 mx-auto font-sans max-w-7xl">
    
    <x-breadcrumb :items="$breadcrumbs" />

    <!-- CABEÇALHO PADRONIZADO REUTILIZÁVEL -->
    <x-details-header 
        title="{{ $ciclo->nome }}" 
        subtitle="{{ $ciclo->ano }} / {{ $ciclo->semestre }}º Semestre" 
        icon="ph-calendar-check" 
        bannerColor="bg-emerald-600 dark:bg-emerald-700" 
        iconColor="text-emerald-500">
        
        <!-- Slot da direita (O que vai ao lado do título) -->
        <div class="text-right mr-4 hidden sm:block">
            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Período de Inscrição</p>
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                {{ \Carbon\Carbon::parse($ciclo->data_inicio)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($ciclo->data_fim)->format('d/m/Y') }}
            </p>
        </div>
        
        @if($ciclo->status)
            <span class="px-4 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full uppercase dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">Ativo</span>
        @else
            <span class="px-4 py-1.5 text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-full uppercase dark:bg-red-900/30 dark:text-red-400 dark:border-red-800">Encerrado</span>
        @endif
    </x-details-header>

    <!-- CORPO EM GRID (Layout Minimalista) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Coluna 1: Cursos Ofertados -->
        <div class="md:col-span-1">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-5 dark:bg-gray-800 dark:border-gray-700 h-full">
                
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="ph ph-graduation-cap text-purpura-500 text-lg"></i> Cursos Ofertados
                    </span>
                    <span class="px-2 py-0.5 text-xs font-bold bg-gray-100 rounded text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $ciclo->cursos->count() }}</span>
                </h3>
                
                <div class="flex flex-col gap-2 max-h-96 overflow-y-auto pr-1 custom-scrollbar">
                    @forelse($ciclo->cursos as $curso)
                        <div class="p-3 bg-gray-50 border border-gray-100/50 rounded-lg dark:bg-gray-900/50 dark:border-gray-700/50 hover:bg-gray-100 transition-colors cursor-default">
                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ $curso->nome }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4 border border-dashed border-gray-200 rounded-lg dark:border-gray-700">Nenhum curso vinculado a este ciclo.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Coluna 2: Lista de Candidatos (Tabela simplificada) -->
        <div class="md:col-span-2">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-5 dark:bg-gray-800 dark:border-gray-700 h-full">
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-users text-purpura-500 text-lg"></i> Candidatos Inscritos
                        <span class="px-2 py-0.5 text-xs font-bold bg-purpura-50 rounded text-purpura-700 dark:bg-purpura-900/30 dark:text-purpura-400">{{ $inscricoes->total() }}</span>
                    </h3>
                    
                    <!-- Busca Minimalista -->
                    <div class="relative w-full sm:w-64">
                        <i class="absolute left-3 top-1/2 transform -translate-y-1/2 ph ph-magnifying-glass text-gray-400"></i>
                        <input type="text" wire:model.live.debounce.500ms="search" class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Buscar por nome, CPF...">
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-2 py-3 font-medium">Candidato</th>
                                <th class="px-2 py-3 font-medium">Data</th>
                                <th class="px-2 py-3 font-medium text-center">Etapa</th>
                                <th class="px-2 py-3 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @forelse($inscricoes as $inscricao)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-2 py-3">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $inscricao->nome }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $inscricao->cpf }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-xs">{{ $inscricao->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-2 py-3 text-center">
                                        <span class="px-2 py-1 text-[10px] font-bold text-gray-600 bg-gray-100 rounded uppercase dark:bg-gray-700 dark:text-gray-300">
                                            Passo {{ $inscricao->etapa_atual }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-right">
                                        <a href="{{ route('inscricoes.show', $inscricao->id) }}" class="p-1.5 inline-block text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 rounded transition-colors dark:hover:bg-gray-700">
                                            <i class="text-lg ph ph-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-2 py-8 text-center text-gray-400 text-sm">
                                        Nenhuma inscrição registrada neste ciclo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-center">
                    {{ $inscricoes->links('components.paginacao-customizada') }}
                </div>
            </div>
        </div>

    </div>
</div>