<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $formId ? 'Editar Formulário Geral' : 'Novo Formulário Geral' }}" 
        icon="ph ph-list-dashes"
        badge="Configurações">
        
        <x-slot name="actions">
            <a href="{{ route('formularios.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="space-y-8">
        
        {{-- SEÇÃO 1: DADOS BÁSICOS E PRAZOS --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200">
                <i class="ph-fill ph-text-t text-purpura-500"></i> Informações Básicas
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Título do Formulário <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="titulo" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm font-bold">
                    @error('titulo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Descrição (Opcional)</label>
                    <textarea wire:model="descricao" rows="2" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Disponível A Partir De (Opcional)</label>
                    <input type="datetime-local" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Encerrar Automático Em (Opcional)</label>
                    <input type="datetime-local" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                </div>
            </div>
            
            <div class="flex items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300 cursor-pointer">
                    Habilitar formulário no sistema (Status Ativo)
                </label>
            </div>
        </div>

        {{-- SEÇÃO 2: REGRAS DE PRIVACIDADE --}}
        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-6 rounded-xl border border-indigo-100 dark:border-indigo-800">
            <h3 class="text-lg font-extrabold text-indigo-900 dark:text-indigo-400 mb-4 flex items-center gap-2">
                <i class="ph-fill ph-shield-check text-xl"></i> Regras e Níveis de Acesso
            </h3>
            
            <div class="flex gap-6 mb-6 pb-4 border-b border-indigo-200/60 dark:border-indigo-800/50">
                <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border border-indigo-100 dark:border-indigo-700 shadow-sm w-full md:w-auto transition hover:ring-2 hover:ring-indigo-500">
                    <input type="radio" wire:model.live="acesso_livre" value="1" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                    <span class="font-bold text-gray-800 dark:text-white">Acesso Livre (Público)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border border-indigo-100 dark:border-indigo-700 shadow-sm w-full md:w-auto transition hover:ring-2 hover:ring-indigo-500">
                    <input type="radio" wire:model.live="acesso_livre" value="0" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                    <span class="font-bold text-gray-800 dark:text-white">Acesso Restrito (Requer Login)</span>
                </label>
            </div>

            <!-- ACESSO LIVRE (PÚBLICO) -->
            @if($acesso_livre)
                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="exigir_email" class="w-5 h-5 text-purpura-600 mt-0.5 rounded border-gray-300 focus:ring-purpura-500">
                        <div>
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Necessidade de incluir e-mail</span>
                            <span class="block text-xs text-gray-500">Injeta uma pergunta obrigatória de e-mail no início do formulário para identificar o participante.</span>
                        </div>
                    </label>
                </div>
            @endif

            <!-- ACESSO RESTRITO -->
            @if(!$acesso_livre)
                <div class="space-y-6">
                    
                    {{-- BLOCO DE COLABORADORES (ROLES E USERS) --}}
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-3">Permitir Equipe Administrativa (Backoffice)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">Por Perfil de Acesso (Roles)</label>
                                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto custom-scrollbar p-2 bg-gray-50 dark:bg-gray-900/50 rounded border border-gray-100 dark:border-gray-700">
                                    @foreach($rolesDb as $role)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="roles_permitidas" value="{{ $role->name }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ ucfirst($role->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">Por Usuários Específicos</label>
                                <div class="flex flex-col gap-2 max-h-40 overflow-y-auto custom-scrollbar p-2 bg-gray-50 dark:bg-gray-900/50 rounded border border-gray-100 dark:border-gray-700">
                                    @foreach($usersDb as $user)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="users_permitidos" value="{{ $user->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $user->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BLOCO DE ESTUDANTES E VÍNCULOS (MAC OS EXPLORER) --}}
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                            <input type="checkbox" wire:model.live="apenas_estudantes" id="apenas_estudantes" class="w-5 h-5 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                            <label for="apenas_estudantes" class="font-bold text-gray-900 dark:text-white cursor-pointer text-lg">Liberar para Estudantes Cadastrados</label>
                        </div>
                        
                        @if($apenas_estudantes)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Você pode direcionar o formulário para toda a base ou <b>restringir para alunos matriculados em combinações específicas</b> marcando as opções abaixo. O aluno só terá acesso se a matrícula dele coincidir com as marcações de Unidade + Curso + Turno. Se deixar tudo vazio, todos acessam.
                            </p>
                            
                            <div class="flex flex-col md:flex-row h-[350px] border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-inner">
                                
                                {{-- COLUNA 1: UNIDADES --}}
                                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                    <div class="p-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[10px] font-bold uppercase text-gray-500 tracking-wider">
                                        1. Unidades
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-1">
                                        @foreach($unidadesDb as $u)
                                            <div wire:click="setActiveUnidade({{ $u->id }})" 
                                                 class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/30 ring-1 ring-purpura-200 dark:ring-purpura-800' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                    <input type="checkbox" wire:model.live="unidades_permitidas" value="{{ $u->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                                    <span class="text-xs font-bold {{ $activeUnidadeId == $u->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $u->nome }}</span>
                                                </label>
                                                <i class="ph ph-caret-right {{ $activeUnidadeId == $u->id ? 'text-purpura-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- COLUNA 2: CURSOS --}}
                                <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/80">
                                    <div class="p-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[10px] font-bold uppercase text-gray-500 tracking-wider">
                                        2. Cursos
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-1">
                                        @if($activeUnidadeId)
                                            @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                                <div wire:click="setActiveCurso({{ $c->id }})" 
                                                     class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/30 ring-1 ring-purpura-200 dark:ring-purpura-800' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                        <input type="checkbox" wire:model.live="cursos_permitidos" value="{{ $c->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                                        <span class="text-xs font-bold {{ $activeCursoId == $c->id ? 'text-purpura-700 dark:text-purpura-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $c->nome }}</span>
                                                    </label>
                                                    <i class="ph ph-caret-right {{ $activeCursoId == $c->id ? 'text-purpura-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 opacity-60">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-center px-4">Selecione uma Unidade ao lado</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- COLUNA 3: TURNOS --}}
                                <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900/30">
                                    <div class="p-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[10px] font-bold uppercase text-gray-500 tracking-wider">
                                        3. Turnos
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-1">
                                        @if($activeCursoId)
                                            @foreach($cursosDb->firstWhere('id', $activeCursoId)->turnosVinculados as $t)
                                                <div class="flex items-center p-2 rounded-lg transition hover:bg-white dark:hover:bg-gray-700">
                                                    <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                        <input type="checkbox" wire:model.live="turnos_permitidas" value="{{ $t->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t->nome }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 opacity-60">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-center px-4">Selecione um Curso ao lado</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="flex justify-end pt-4 pb-10">
            <button type="submit" class="px-8 py-3.5 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2">
                <i class="ph-bold ph-floppy-disk text-xl"></i> Salvar e Prosseguir
            </button>
        </div>
    </form>
</div>