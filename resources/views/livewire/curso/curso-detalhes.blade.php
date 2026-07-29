<div class="p-6 mx-auto font-sans max-w-7xl space-y-6">
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('cursos.index') }}" class="p-2 text-gray-500 transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
            <i class="text-xl ph ph-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Detalhes do Curso
        </h2>
    </div>

    <!-- Cabeçalho (Master) -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 relative">
        <div class="absolute top-0 w-full h-32 bg-gradient-to-r from-blue-600 to-indigo-600"></div>
        
        <div class="relative px-6 pt-24 pb-6 sm:px-8">
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-6">
                <!-- Ícone do Curso -->
                <div class="flex items-center justify-center w-24 h-24 bg-white border-4 border-white rounded-2xl shadow-lg dark:bg-gray-900 dark:border-gray-800 shrink-0">
                    <i class="text-4xl ph ph-graduation-cap text-indigo-500"></i>
                </div>
                
                <div class="flex-1 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $curso->nome }}</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1 font-mono text-sm">
                                Slug: {{ $curso->slug }}
                            </p>
                        </div>
                        <div>
                            @if($curso->status === 'Ativo')
                                <span class="px-4 py-2 text-sm font-bold text-pistache-700 bg-pistache-100 rounded-full uppercase border border-pistache-200">Ativo</span>
                            @else
                                <span class="px-4 py-2 text-sm font-bold text-red-700 bg-red-100 rounded-full uppercase border border-red-200">Inativo</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Informações -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Regras e Configurações -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">Regras de Matrícula</h3>
                
                <ul class="space-y-4">
                    <li class="flex flex-col gap-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-bold text-gray-900 dark:text-white"><i class="ph ph-user text-purpura-500 mr-1"></i> Idade Mínima:</span>
                        {{ $curso->min_idade ? $curso->min_idade . ' anos' : 'Não exigida' }}
                    </li>
                    <li class="flex flex-col gap-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-bold text-gray-900 dark:text-white"><i class="ph ph-user text-purpura-500 mr-1"></i> Idade Máxima:</span>
                        {{ $curso->max_idade ? $curso->max_idade . ' anos' : 'Sem limite' }}
                    </li>
                    <li class="flex items-center justify-between p-3 mt-4 text-sm font-medium border rounded-lg bg-gray-50 border-gray-100 dark:bg-gray-700/50 dark:border-gray-600">
                        <span class="text-gray-700 dark:text-gray-300">Aceita fora do Estado?</span>
                        @if($curso->permite_estado_diferente)
                            <i class="text-xl ph-fill ph-check-circle text-pistache-500" title="Sim"></i>
                        @else
                            <i class="text-xl ph-fill ph-x-circle text-red-500" title="Não"></i>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <!-- Relacionamentos (Unidades, Turnos, Ciclos) -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Unidades -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center justify-between">
                    <span class="flex items-center gap-2"><i class="ph ph-buildings text-indigo-500"></i> Unidades Vinculadas</span>
                    <span class="px-2 py-1 text-xs bg-gray-100 rounded text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $curso->unidades->count() }}</span>
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($curso->unidades as $unidade)
                        <span class="px-3 py-1 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-full dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800">
                            {{ $unidade->nome }}
                        </span>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 w-full text-center py-2">Nenhuma unidade vinculada.</p>
                    @endforelse
                </div>
            </div>

            <!-- Turnos -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center justify-between">
                    <span class="flex items-center gap-2"><i class="ph ph-clock text-ponkan-500"></i> Turnos Disponíveis</span>
                    <span class="px-2 py-1 text-xs bg-gray-100 rounded text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $curso->turnosVinculados->count() }}</span>
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($curso->turnosVinculados as $turno)
                        <span class="px-3 py-1 text-sm font-medium text-ponkan-700 bg-ponkan-50 border border-ponkan-100 rounded-full dark:bg-ponkan-900/30 dark:text-ponkan-400 dark:border-ponkan-800">
                            {{ $turno->nome }}
                        </span>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 w-full text-center py-2">Nenhum turno vinculado.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>