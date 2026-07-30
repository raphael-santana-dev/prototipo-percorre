<div class="max-w-7xl px-4 py-8 mx-auto font-sans">
    
    <!-- Breadcrumbs & Navegação Topo -->
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
                <span class="text-gray-900 dark:text-gray-200">{{ $curso->nome }}</span>
            </nav>
        </div>
        
        <!-- Notificação de Sucesso -->
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="px-4 py-2 text-sm font-bold text-pistache-700 bg-pistache-100 border border-pistache-200 rounded-lg shadow-sm animate-fade-in-down">
                <i class="ph ph-check-circle"></i> {{ session('success') }}
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
        
        <!-- ========================================== -->
        <!-- COLUNA ESQUERDA: STICKY CARD (Sidebar)     -->
        <!-- ========================================== -->
        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-6">
            
            <!-- Card Principal de Metadados -->
            <div class="bg-white border border-gray-100 shadow-xl shadow-gray-200/40 rounded-2xl p-6 dark:bg-gray-800 dark:border-gray-700 dark:shadow-none transition-all relative overflow-hidden">
                
                <!-- Detalhe visual de topo -->
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-purpura-500 to-ponkan-500"></div>

                <!-- Título e Status -->
                <div class="flex items-start justify-between mt-2 mb-6">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $curso->nome }}</h1>
                        <p class="text-xs text-gray-400 font-mono mt-1">ID: #{{ str_pad($curso->id, 4, '0', STR_PAD_LEFT) }} • {{ $curso->slug }}</p>
                    </div>
                </div>

                <!-- Formulário de Edição Inline vs Modo Leitura -->
                @if($isEditMode)
                    <!-- MODO EDIÇÃO -->
                    <form wire:submit="salvarAlteracoes" class="space-y-4 animate-fade-in">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase dark:text-gray-300">Nome do Curso</label>
                            <input type="text" wire:model="nome" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 text-sm">
                            @error('nome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase dark:text-gray-300">Idade Min.</label>
                                <input type="number" wire:model="min_idade" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase dark:text-gray-300">Idade Max.</label>
                                <input type="number" wire:model="max_idade" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 text-sm">
                            </div>
                        </div>
                        @error('max_idade') <span class="block text-xs text-red-500">{{ $message }}</span> @enderror

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase dark:text-gray-300">Status</label>
                            <select wire:model="status" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 text-sm">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 mt-2">
                            <input type="checkbox" wire:model="permite_estado_diferente" id="permite_estado" class="w-4 h-4 text-purpura-600 rounded border-gray-300">
                            <label for="permite_estado" class="text-sm text-gray-700 dark:text-gray-300">Aceita alunos fora do Estado</label>
                        </div>

                        <div class="flex gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="toggleEditMode" class="flex-1 px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                            <button type="submit" class="flex-1 px-3 py-2 text-sm font-bold text-white bg-purpura-600 rounded-lg hover:bg-purpura-700 transition-colors shadow-sm">Salvar</button>
                        </div>
                    </form>
                @else
                    <!-- MODO LEITURA -->
                    <div class="space-y-5 animate-fade-in">
                        
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl dark:bg-gray-700/30">
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300"><i class="ph ph-activity text-purpura-500"></i> Status</span>
                            @if($curso->status === 'Ativo')
                                <span class="px-2 py-1 text-xs font-bold text-pistache-700 bg-pistache-100 rounded uppercase">Ativo</span>
                            @else
                                <span class="px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded uppercase">Inativo</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase">Idade Mínima</span>
                                <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $curso->min_idade ?? '--' }} <span class="text-sm font-normal text-gray-500">anos</span></span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase">Idade Máxima</span>
                                <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $curso->max_idade ?? '--' }} <span class="text-sm font-normal text-gray-500">anos</span></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                            <span>Aceita fora do Estado?</span>
                            @if($curso->permite_estado_diferente)
                                <i class="text-xl ph-fill ph-check-circle text-pistache-500"></i>
                            @else
                                <i class="text-xl ph-fill ph-x-circle text-red-500"></i>
                            @endif
                        </div>

                        <!-- Botão de Ação Rápida -->
                        @can('curso.editar')
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button wire:click="toggleEditMode" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-purpura-600 bg-purpura-50 rounded-lg hover:bg-purpura-100 transition-colors border border-purpura-100 dark:bg-gray-700 dark:text-purpura-400 dark:border-gray-600 dark:hover:bg-gray-600">
                                    <i class="text-lg ph ph-pencil-simple"></i> Editar Configurações
                                </button>
                            </div>
                        @endcan
                    </div>
                @endif
            </div>

            <!-- Card de Professores Vinculados -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4">
                    <i class="ph ph-chalkboard-teacher text-lg text-ponkan-500"></i> Corpo Docente
                </h3>
                <div class="space-y-3">
                    @forelse($professoresVinculados as $professor)
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($professor->name) }}&background=F3E8FF&color=9B26B6&bold=true" class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-200 leading-none">{{ $professor->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $professor->unidades->first()->nome ?? 'Acesso Global' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Nenhum professor vinculado ainda.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- COLUNA DIREITA: CONTEÚDO PRINCIPAL (Listas)-->
        <!-- ========================================== -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Linha de Tags (Unidades e Turnos) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Unidades -->
                <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3">
                        <i class="ph ph-buildings text-lg text-blue-500"></i> Unidades Presentes
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse($curso->unidades as $unidade)
                            <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-100 rounded-md border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                {{ $unidade->nome }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">Nenhuma unidade.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Turnos -->
                <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3">
                        <i class="ph ph-clock text-lg text-amber-500"></i> Turnos Habilitados
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse($curso->turnosVinculados as $turno)
                            <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-100 rounded-md border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                {{ $turno->nome }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">Nenhum turno.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Bloco de Inscrições Recentes -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-users-three text-xl text-purpura-500"></i> Últimas Inscrições
                    </h3>
                    <a href="{{ route('inscricoes.index', ['filtroCurso' => $curso->id]) }}" class="text-xs font-bold text-purpura-600 hover:text-purpura-700 hover:underline">
                        Ver todas &rarr;
                    </a>
                </div>
                
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($curso->inscricoes as $inscricao)
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
                                <!-- Você pode usar o Quick View Global aqui futuramente -->
                                <button class="ml-2 p-1.5 text-gray-400 hover:text-purpura-500 rounded bg-white border border-gray-200 shadow-sm opacity-0 group-hover:opacity-100 transition-all dark:bg-gray-700 dark:border-gray-600">
                                    <i class="ph ph-eye"></i>
                                </button>
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