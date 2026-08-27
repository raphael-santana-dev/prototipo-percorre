<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Formulários" 
        icon="ph ph-list-dashes"
        badge="Formulários Gerais"
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            @if(feature('formulario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.criar')))
                <a href="{{ route('formularios.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold">
                    <i class="ph ph-plus text-lg"></i> Novo Formulário
                </a>
            @endif
        </x-slot>
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $form)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $form->id }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $form->titulo }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($form->descricao, 50) }}</div>
                </td>
                
                {{-- Coluna Nova: Regras de Acesso --}}
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                        @if($form->acesso_livre)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase w-max"><i class="ph-bold ph-globe"></i> Público</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase w-max"><i class="ph-bold ph-lock-key"></i> Restrito</span>
                        @endif
                        
                        @if($form->data_inicio || $form->data_fim)
                            <span class="text-[10px] text-gray-500 font-bold flex items-center gap-1">
                                <i class="ph-bold ph-calendar"></i>
                                {{ $form->data_inicio ? $form->data_inicio->format('d/m/y') : 'Sempre' }} até {{ $form->data_fim ? $form->data_fim->format('d/m/y') : 'Sempre' }}
                            </span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                        @else
                            <span class="w-2 h-2 rounded-full {{ $form->status ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        @endif
                        <span class="text-[10px] font-bold {{ $form->status ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('formularios.respostas.show', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Ver Respostas Coletadas">
                            <i class="text-lg ph ph-database"></i>
                        </a>
                        <a href="{{ route('formularios.publico', ['slug' => $form->slug]) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Acessar Formulário (Link)">
                            <i class="text-lg ph ph-arrow-square-in"></i>
                        </a>
                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Blocos">
                                <i class="text-lg ph ph-list-dashes"></i>
                            </a>
                            <button wire:click="abrirModal({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Configurações do Form">
                                <i class="text-lg ph ph-gear"></i>
                            </button>
                        @endif

                        <a href="{{ route('formularios.edit', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Configurações do Form">
                            <i class="text-lg ph ph-gear"></i>
                        </a>
                        
                        @if(feature('formulario.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.excluir')))
                            <button wire:click="excluir({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Atenção: Ao excluir o formulário, todas as respostas vinculadas a ele também serão deletadas. Deseja continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhum formulário geral encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            {{-- Grid oculto por brevidade (Acompanha os mesmos botões da table) --}}
        </x-slot>
    </x-table>

    <!-- Modal Enriquecido -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-gear text-purpura-600"></i> {{ $formId ? 'Configurar Formulário' : 'Novo Formulário' }}
                        </h3>
                        <button wire:click="$set('modalAberto', false)" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xl"></i></button>
                    </div>
                    
                    <form wire:submit.prevent="salvar" class="space-y-6">
                        
                        {{-- 1. Dados Básicos --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Título Interno do Formulário <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="titulo" placeholder="Ex: Pesquisa de Clima Organizacional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:text-white">
                                @error('titulo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição Opcional</label>
                                <textarea wire:model="descricao" rows="2" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>

                        {{-- 2. Restrições de Prazo (Trava de Tempo) --}}
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-2"><i class="ph-bold ph-calendar"></i> Período de Disponibilidade</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Abre em (Opcional)</label>
                                    <input type="datetime-local" wire:model="data_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-purpura-500 focus:ring-purpura-500">
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Encerra em (Opcional)</label>
                                    <input type="datetime-local" wire:model="data_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-purpura-500 focus:ring-purpura-500">
                                </div>
                            </div>
                        </div>

                        {{-- 3. Travas de Acesso (Privacidade) --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-5 rounded-xl border border-indigo-100 dark:border-indigo-800">
                            <h4 class="text-sm font-extrabold uppercase tracking-wider text-indigo-800 dark:text-indigo-400 mb-4 flex items-center gap-2">
                                <i class="ph-bold ph-shield-check text-xl"></i> Níveis de Acesso
                            </h4>
                            
                            <!-- Toggle Principal: Livre vs Restrito -->
                            <div class="flex gap-6 mb-6 pb-4 border-b border-indigo-200/60">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model.live="acesso_livre" value="1" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="font-bold text-gray-800 dark:text-white">Acesso Livre (Público)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model.live="acesso_livre" value="0" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="font-bold text-gray-800 dark:text-white">Acesso Restrito (Requer Login)</span>
                                </label>
                            </div>

                            <!-- OPÇÕES PARA ACESSO LIVRE -->
                            @if($acesso_livre)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" wire:model="exigir_email" class="w-5 h-5 text-purpura-600 mt-0.5 rounded border-gray-300 focus:ring-purpura-500">
                                        <div>
                                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Necessidade de incluir e-mail</span>
                                            <span class="block text-xs text-gray-500">Adiciona um campo de E-mail obrigatório no início do formulário para saber quem respondeu.</span>
                                        </div>
                                    </label>
                                </div>
                            @endif

                            <!-- OPÇÕES PARA ACESSO RESTRITO -->
                            @if(!$acesso_livre)
                                <div class="space-y-6">
                                    
                                    <!-- Bloco 1: Permissões de Estudantes -->
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200">
                                        <label class="flex items-center gap-2 cursor-pointer mb-3">
                                            <input type="checkbox" wire:model.live="apenas_estudantes" class="w-5 h-5 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">Permitir Estudantes</span>
                                        </label>
                                        
                                        @if($apenas_estudantes)
                                            <div class="pl-7 space-y-3 mt-2 border-l-2 border-purpura-200">
                                                <p class="text-xs text-gray-500 mb-2">Se nenhum filtro for selecionado abaixo, todos os alunos terão acesso.</p>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Restringir Unidades</label>
                                                        <select multiple wire:model="unidades_permitidas" class="w-full text-xs rounded border-gray-300 h-24 custom-scrollbar">
                                                            @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Restringir Cursos</label>
                                                        <select multiple wire:model="cursos_permitidos" class="w-full text-xs rounded border-gray-300 h-24 custom-scrollbar">
                                                            @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Restringir Turnos</label>
                                                        <select multiple wire:model="turnos_permitidas" class="w-full text-xs rounded border-gray-300 h-24 custom-scrollbar">
                                                            @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Bloco 2: Permissões da Equipe Interna -->
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-3">Permitir Colaboradores Administrativos / Web</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Níveis de Acesso (Roles)</label>
                                                <select multiple wire:model="roles_permitidas" class="w-full text-xs rounded border-gray-300 h-32 custom-scrollbar">
                                                    @foreach($rolesDb as $role) <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option> @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Usuário(s) Específico(s)</label>
                                                <select multiple wire:model="users_permitidos" class="w-full text-xs rounded border-gray-300 h-32 custom-scrollbar">
                                                    @foreach($usersDb as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">
                                Ativar link do formulário (Status Geral)
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('modalAberto', false)" class="px-5 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 flex items-center gap-2 transition">
                                <i class="ph-bold ph-floppy-disk"></i> Salvar Formulário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>