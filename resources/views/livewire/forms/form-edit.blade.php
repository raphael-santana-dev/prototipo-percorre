<div class="w-full max-w-5xl mx-auto relative font-sans">

    @if($encerrado)
        <div class="mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-xs text-amber-900 dark:text-amber-200">
                <p class="font-semibold">Formulário encerrado (somente visualização)</p>
                <p class="text-amber-800 dark:text-amber-300 mt-0.5">
                    A edição das configurações e dos campos está bloqueada. As respostas permanecem disponíveis para consulta e exportação.
                </p>
            </div>
            @if($formId && feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                <button type="button" wire:click="toggleEncerrado" class="px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 text-xs font-semibold rounded-md shrink-0 transition">
                    Reabrir edição
                </button>
            @endif
        </div>
    @endif
    
    <x-page-header 
        title="{{ $formId ? 'Editar Formulário Geral' : 'Novo Formulário Geral' }}" 
        icon="ph ph-list-dashes"
        badge="{{ $encerrado ? 'Encerrado' : 'Configurações' }}">
        
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                @if($formId && feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                    <button type="button" wire:click="toggleEncerrado" class="px-3 py-2 text-xs font-medium border rounded-md transition bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-700 hover:bg-gray-50">
                        {{ $encerrado ? 'Reabrir formulário' : 'Encerrar edição' }}
                    </button>
                @endif

                <a href="{{ route('formularios.index') }}" wire:navigate class="px-3.5 py-2 text-xs font-medium border border-gray-300 dark:border-gray-700 rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 transition inline-flex items-center gap-1.5">
                    <i class="ph ph-arrow-left"></i> Voltar
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="mt-4 {{ $encerrado ? 'opacity-80' : '' }}">
        <fieldset @if($encerrado) disabled @endif class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
            
            {{-- SEÇÃO 1: INFORMAÇÕES BÁSICAS --}}
            <div class="p-5 sm:p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Informações Básicas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Identificação, período de disponibilidade e status do formulário.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Título do formulário <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="titulo" placeholder="Ex: Pesquisa de Satisfação 2026" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-md focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 text-sm">
                        @error('titulo') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Descrição (opcional)
                        </label>
                        <textarea wire:model="descricao" rows="2" placeholder="Breve orientação exibida no topo do formulário..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-md focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Disponível a partir de (opcional)
                        </label>
                        <input type="datetime-local" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-md focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Encerrar automaticamente em (opcional)
                        </label>
                        <input type="datetime-local" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-md focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500 text-sm">
                    </div>
                </div>
                
                <div class="pt-2">
                    <label for="status" class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" wire:model="status" id="status" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-600">
                        <span class="text-xs font-medium text-gray-800 dark:text-gray-200">
                            Formulário ativo (disponível para receber respostas)
                        </span>
                    </label>
                </div>
            </div>

            {{-- SEÇÃO 2: REGRAS DE ACESSO --}}
            <div class="p-5 sm:p-6 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Regras de Acesso</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Defina se qualquer pessoa com o link pode responder ou se é necessário login.</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-2.5 cursor-pointer p-3.5 rounded-md border transition {{ $acesso_livre ? 'border-gray-900 dark:border-gray-300 bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900' }}">
                        <input type="radio" wire:model.live="acesso_livre" value="1" class="mt-0.5 text-purpura-600 focus:ring-purpura-500 border-gray-300">
                        <div class="text-xs">
                            <span class="font-semibold text-gray-900 dark:text-white block">Acesso Livre (Público)</span>
                            <span class="text-gray-500 dark:text-gray-400">Qualquer pessoa com o link pode preencher.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer p-3.5 rounded-md border transition {{ !$acesso_livre ? 'border-gray-900 dark:border-gray-300 bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900' }}">
                        <input type="radio" wire:model.live="acesso_livre" value="0" class="mt-0.5 text-purpura-600 focus:ring-purpura-500 border-gray-300">
                        <div class="text-xs">
                            <span class="font-semibold text-gray-900 dark:text-white block">Acesso Restrito (Requer Login)</span>
                            <span class="text-gray-500 dark:text-gray-400">Limitado a perfis, usuários ou estudantes específicos.</span>
                        </div>
                    </label>
                </div>

                @if($acesso_livre)
                    <div class="p-3.5 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="exigir_email" class="w-4 h-4 mt-0.5 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                            <div class="text-xs">
                                <span class="block font-medium text-gray-900 dark:text-white">Exigir e-mail do participante</span>
                                <span class="block text-gray-500 dark:text-gray-400 mt-0.5">Inclui um campo obrigatório de e-mail no início do formulário.</span>
                            </div>
                        </label>
                    </div>
                @endif

                @if(!$acesso_livre)
                    <div class="space-y-5 pt-2">
                        
                        {{-- EQUIPE ADMINISTRATIVA --}}
                        <div class="space-y-3">
                            <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200">Equipe Administrativa</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Perfis de acesso (Roles)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto custom-scrollbar p-3 bg-gray-50 dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700">
                                        @foreach($rolesDb as $role)
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" wire:model="roles_permitidas" value="{{ $role->name }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                                <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ ucfirst($role->name) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Usuários específicos</label>
                                    <div class="flex flex-col gap-2 max-h-40 overflow-y-auto custom-scrollbar p-3 bg-gray-50 dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700">
                                        @foreach($usersDb as $user)
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" wire:model="users_permitidos" value="{{ $user->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                                <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $user->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ESTUDANTES --}}
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                            <label for="apenas_estudantes" class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" wire:model.live="apenas_estudantes" id="apenas_estudantes" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white">Liberar para estudantes cadastrados</span>
                            </label>
                            
                            @if($apenas_estudantes)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Opcionalmente restrinja por Unidade, Curso e Turno. Deixando tudo desmarcado, todos os estudantes têm acesso.
                                </p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 md:h-[300px] border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                                    
                                    {{-- 1. UNIDADES --}}
                                    <div class="flex flex-col bg-white dark:bg-gray-800 max-h-52 md:max-h-none overflow-hidden">
                                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-600 dark:text-gray-400 shrink-0">
                                            1. Unidades
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-0.5">
                                            @foreach($unidadesDb as $u)
                                                <div wire:click="setActiveUnidade({{ $u->id }})" 
                                                     class="flex items-center justify-between px-2.5 py-2 rounded cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                    <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                                        <input type="checkbox" wire:model.live="unidades_permitidas" value="{{ $u->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 shrink-0">
                                                        <span class="text-xs text-gray-800 dark:text-gray-200 truncate {{ $activeUnidadeId == $u->id ? 'font-semibold' : '' }}">{{ $u->nome }}</span>
                                                    </label>
                                                    <i class="ph ph-caret-right text-xs text-gray-400 shrink-0"></i>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- 2. CURSOS --}}
                                    <div class="flex flex-col bg-white dark:bg-gray-800 max-h-52 md:max-h-none overflow-hidden">
                                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-600 dark:text-gray-400 shrink-0">
                                            2. Cursos
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-0.5">
                                            @if($activeUnidadeId)
                                                @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                                    <div wire:click="setActiveCurso({{ $c->id }})" 
                                                         class="flex items-center justify-between px-2.5 py-2 rounded cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                                            <input type="checkbox" wire:model.live="cursos_permitidos" value="{{ $c->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 shrink-0">
                                                            <span class="text-xs text-gray-800 dark:text-gray-200 truncate {{ $activeCursoId == $c->id ? 'font-semibold' : '' }}">{{ $c->nome }}</span>
                                                        </label>
                                                        <i class="ph ph-caret-right text-xs text-gray-400 shrink-0"></i>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="h-full flex items-center justify-center p-4 text-xs text-gray-400 text-center">
                                                    Selecione uma unidade
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- 3. TURNOS --}}
                                    <div class="flex flex-col bg-white dark:bg-gray-800 max-h-52 md:max-h-none overflow-hidden">
                                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-600 dark:text-gray-400 shrink-0">
                                            3. Turnos
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-1.5 custom-scrollbar space-y-0.5">
                                            @if($activeCursoId)
                                                @foreach($cursosDb->firstWhere('id', $activeCursoId)?->turnosVinculados ?? [] as $t)
                                                    <label class="flex items-center gap-2 px-2.5 py-2 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <input type="checkbox" wire:model.live="turnos_permitidas" value="{{ $t->id }}" class="w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 shrink-0">
                                                        <span class="text-xs text-gray-800 dark:text-gray-200">{{ $t->nome }}</span>
                                                    </label>
                                                @endforeach
                                            @else
                                                <div class="h-full flex items-center justify-center p-4 text-xs text-gray-400 text-center">
                                                    Selecione um curso
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

            {{-- RODAPÉ DE AÇÕES --}}
            @if(!$encerrado)
                <div class="px-5 py-4 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 rounded-b-lg">
                    <a href="{{ route('formularios.index') }}" wire:navigate class="px-4 py-2 text-center text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-5 py-2 bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold rounded-md transition">
                        Salvar e prosseguir
                    </button>
                </div>
            @endif
        </fieldset>
    </form>
</div>