<div class="max-w-7xl px-4 py-8 mx-auto font-sans relative">

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
        
        <!-- ========================================== -->
        <!-- COLUNA ESQUERDA: SIDEBAR DE METADADOS      -->
        <!-- ========================================== -->
        <div class="lg:col-span-4 space-y-5 lg:sticky lg:top-6">
            
            <!-- 1. Card Principal de Metadados -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-6 dark:bg-gray-800 dark:border-gray-700 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-purpura-500 to-ponkan-500"></div>

                <div class="mt-2 mb-6">
                    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $this->curso->nome }}</h1>
                    <p class="text-xs text-gray-400 font-mono mt-1">ID: #{{ str_pad($this->curso->id, 4, '0', STR_PAD_LEFT) }} • {{ $this->curso->slug }}</p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl dark:bg-gray-700/30">
                        <span class="text-sm font-bold text-gray-600 dark:text-gray-300"><i class="ph ph-activity text-purpura-500"></i> Status</span>
                        @if($this->curso->status === 'Ativo')
                            <span class="px-2 py-1 text-xs font-bold text-pistache-700 bg-pistache-100 rounded uppercase">Ativo</span>
                        @else
                            <span class="px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded uppercase">Inativo</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Idade Mínima</span>
                            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $this->curso->min_idade ?? '--' }} <span class="text-sm font-normal text-gray-500">anos</span></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Idade Máxima</span>
                            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $this->curso->max_idade ?? '--' }} <span class="text-sm font-normal text-gray-500">anos</span></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Aceita fora do Estado?</span>
                        @if($this->curso->permite_estado_diferente)
                            <i class="text-xl ph-fill ph-check-circle text-pistache-500"></i>
                        @else
                            <i class="text-xl ph-fill ph-x-circle text-red-500"></i>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Unidades Presentes -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3">
                    <i class="ph ph-buildings text-lg text-blue-500"></i> Unidades Presentes
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($this->curso->unidades as $unidade)
                        <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-100 rounded-md border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            {{ $unidade->nome }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-400">Nenhuma unidade vinculada.</span>
                    @endforelse
                </div>
            </div>

            <!-- 3. Turnos Habilitados -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3">
                    <i class="ph ph-clock text-lg text-amber-500"></i> Turnos Habilitados
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($this->curso->turnosVinculados as $turno)
                        <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-100 rounded-md border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            {{ $turno->nome }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-400">Nenhum turno vinculado.</span>
                    @endforelse
                </div>
            </div>

            <!-- 4. Corpo Docente -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4">
                    <i class="ph ph-chalkboard-teacher text-lg text-ponkan-500"></i> Corpo Docente
                </h3>
                <div class="space-y-3">
                    @forelse($this->professoresVinculados as $professor)
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($professor->name) }}&background=F3E8FF&color=9B26B6&bold=true" class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-200 leading-none">{{ $professor->name }}</p>
                                <p class="text-[10px] text-gray-500 mt-1 uppercase">{{ $professor->unidades->first()->nome ?? 'Global' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Nenhum professor vinculado.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- COLUNA DIREITA: INSCRIÇÕES (Listagem)      -->
        <!-- ========================================== -->
        <div class="lg:col-span-8">
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-users-three text-xl text-purpura-500"></i> Últimas Inscrições
                    </h3>
                    <a href="{{ route('inscricoes.index', ['filtroCurso' => $this->curso->id]) }}" class="text-xs font-bold text-purpura-600 hover:text-purpura-700 hover:underline">
                        Ver todas &rarr;
                    </a>
                </div>
                
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->inscricoesRecentes as $inscricao)
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold dark:bg-gray-700">
                                    {{ substr($inscricao->nome, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-200">{{ $inscricao->nome }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $inscricao->email }} • {{ $inscricao->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Etapa {{ $inscricao->etapa_atual }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 mb-3 dark:bg-gray-700">
                                <i class="ph ph-empty text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-200">Nenhum aluno matriculado.</p>
                            <p class="text-xs text-gray-500 mt-1">As inscrições para este curso aparecerão aqui.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>