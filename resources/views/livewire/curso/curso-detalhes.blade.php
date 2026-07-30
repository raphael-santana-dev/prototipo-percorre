<div class="max-w-7xl px-4 py-8 mx-auto font-sans relative">
    
    <!-- Navegação Topo -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('cursos.index') }}" class="p-2 text-gray-500 transition-colors bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                <i class="text-xl ph ph-arrow-left"></i>
            </a>
            <nav class="hidden sm:flex text-sm text-gray-500 font-medium">
                <a href="{{ route('dashboard') }}" class="hover:text-purpura-600 transition-colors">Início</a>
                <span class="mx-2 text-gray-300">/</span>
                <a href="{{ route('cursos.index') }}" class="hover:text-purpura-600 transition-colors">Cursos</a>
                <span class="mx-2 text-gray-300">/</span>
                <span class="text-gray-900 dark:text-gray-200">{{ $this->curso->nome }}</span>
            </nav>
        </div>
        
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="px-4 py-2 text-sm font-bold text-pistache-700 bg-pistache-100 border border-pistache-200 rounded-lg shadow-sm animate-fade-in-down">
                <i class="ph ph-check-circle"></i> {{ session('success') }}
            </div>
        @endif
    </div>

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

                <!-- MODO LEITURA (Sempre Visível) -->
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

                    @can('curso.editar')
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button wire:click="openModal" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-purpura-600 bg-purpura-50 rounded-lg hover:bg-purpura-100 transition-colors border border-purpura-100 dark:bg-gray-700 dark:text-purpura-400 dark:border-gray-600 dark:hover:bg-gray-600">
                                <i class="text-lg ph ph-pencil-simple"></i> Editar & Vincular
                            </button>
                        </div>
                    @endcan
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

    <!-- ========================================== -->
    <!-- MODAL DE EDIÇÃO E VINCULAÇÃO RÁPIDA        -->
    <!-- ========================================== -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800 animate-fade-in-down">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        Configurações do Curso
                    </h3>
                    
                    <form wire:submit="salvarAlteracoes" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome do Curso <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('nome') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Idade Mínima</label>
                                <input type="number" wire:model="min_idade" placeholder="Opcional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('min_idade') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Idade Máxima</label>
                                <input type="number" wire:model="max_idade" placeholder="Opcional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('max_idade') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model="status" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="Ativo">Ativo</option>
                                    <option value="Inativo">Inativo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Vinculação Rápida (Multi-tenancy) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 mt-2 border-t border-gray-100 dark:border-gray-700">
                            <!-- Unidades -->
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-buildings text-purpura-500"></i> Unidades que ofertam
                                </label>
                                <div class="flex flex-col gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-40 overflow-y-auto custom-scrollbar">
                                    @forelse($this->todasUnidades as $unidade)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="unidadesSelecionadas" value="{{ $unidade->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $unidade->nome }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-gray-500">Nenhuma unidade ativa.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Turnos -->
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <i class="ph ph-clock text-ponkan-500"></i> Turnos de aula
                                </label>
                                <div class="flex flex-col gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 max-h-40 overflow-y-auto custom-scrollbar">
                                    @forelse($this->todosTurnos as $turno)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="turnosSelecionados" value="{{ $turno->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-500">
                                            <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $turno->nome }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-gray-500">Nenhum turno cadastrado.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" wire:model="permite_estado_diferente" id="estadoDif" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="estadoDif" class="font-bold text-gray-900 dark:text-white">Permitir alunos de outro Estado</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Marca se o curso aceita matrículas de residentes fora da UF da unidade.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-600 border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>