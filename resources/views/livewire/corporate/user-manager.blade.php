<div class="p-6 max-w-7xl mx-auto font-sans relative">


    <x-page-header 
        title="Usuários" 
        icon="ph ph-users"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        @if(feature('usuario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Usuário
                </button>
            </x-slot>
        @endif

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        
        @forelse($registros as $user)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                </td>
                <td class="px-4 py-2.5">
                    <div class="flex flex-wrap gap-1 mb-1.5">
                        @foreach($user->roles as $role)
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                    <div class="text-xs font-semibold text-gray-500 flex flex-wrap gap-1">
                        @if($user->unidades->count() > 0)
                            @foreach($user->unidades as $unidadeVinculada)
                                <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px] dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                    <i class="ph ph-map-pin"></i> {{ $unidadeVinculada->nome }}
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-600 px-1.5 py-0.5 rounded text-[10px] font-bold dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400">
                                <i class="ph ph-globe"></i> Acesso Global / Não Restrito
                            </span>
                        @endif
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('usuario.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.visualizar')))
                            <button wire:click="showQuickDetails({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>
                            <a href="{{ route('users.show', $user->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                        @endif
                        @if(feature('usuario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.editar')))
                            <button wire:click="edit({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Usuário">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif
                        @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                            <button wire:click="delete({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Usuário" onclick="confirm('Excluir este usuário permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $user)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[180px]">{{ $user->name }}</div>
                            <div class="text-[10px] text-gray-500 truncate max-w-[180px]">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1 my-3">
                        @foreach($user->roles as $role)
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">{{ $role->name }}</span>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-[10px] font-bold {{ $user->unidades->count() > 0 ? 'text-gray-500' : 'text-blue-500' }}">
                            <i class="{{ $user->unidades->count() > 0 ? 'ph ph-map-pin' : 'ph ph-globe' }}"></i> {{ $user->unidades->count() > 0 ? $user->unidades->count().' UNIDADES' : 'GLOBAL' }}
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickDetails({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-info"></i></button>
                            <a href="{{ route('users.show', $user->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-eye"></i></a>
                            <button wire:click="edit({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-pencil-simple"></i></button>
                            @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                <button wire:click="delete({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir este usuário?') || event.stopImmediatePropagation()"><i class="text-lg ph ph-trash"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Multi-tenancy -->
    @if($showModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                
                <div class="relative z-10 w-full max-w-4xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl dark:bg-gray-800">
                    <h3 class="mb-6 text-xl font-extrabold text-gray-900 border-b border-gray-100 pb-4 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Usuário' : 'Novo Usuário' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-6">
                        
                        <!-- Dados Pessoais -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Nome Completo</label>
                                <input type="text" wire:model="name" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                @error('name') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">E-mail</label>
                                <input type="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                @error('email') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Senha {{ $isEditMode ? '(Opcional)' : '' }}</label>
                                <input type="password" wire:model="password" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                @error('password') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Grupo Principal (Role)</label>
                                <select wire:model.live="roleName" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                    <option value="">Selecione...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                    @endforeach
                                </select>
                                @error('roleName') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Vínculos Operacionais (Multi-Tenancy Grid) -->
                        <div class="pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="flex items-center gap-2 mb-2 text-sm font-bold tracking-wider text-gray-500 uppercase">
                                <i class="text-ponkan-500 ph ph-git-merge"></i> Escopo de Acesso (Vínculos)
                            </h4>
                            
                            @if(strtolower($roleName) === 'professor')
                                <p class="mb-4 text-xs font-bold text-amber-600"><i class="ph ph-warning-circle"></i> O grupo Professor exige que você selecione a Unidade para visualizar os Cursos disponíveis.</p>
                            @else
                                <p class="mb-4 text-xs text-gray-500">Selecione as áreas que este usuário poderá gerenciar. Deixe vazio para não restringir o escopo.</p>
                            @endif

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                
                                <!-- Unidades -->
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900/50">
                                    <label class="block mb-2 text-xs font-bold text-gray-800 uppercase border-b border-gray-200 pb-2">
                                        <i class="ph ph-buildings text-purpura-500"></i> Unidades
                                    </label>
                                    <div class="space-y-1.5 max-h-40 overflow-y-auto custom-scrollbar">
                                        @foreach($todasUnidades as $unidade)
                                            <label class="flex items-center gap-2 p-1.5 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $unidade->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300">
                                                <span class="text-xs font-medium text-gray-700">{{ $unidade->nome }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Cursos -->
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900/50">
                                    <label class="block mb-2 text-xs font-bold text-gray-800 uppercase border-b border-gray-200 pb-2">
                                        <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos
                                    </label>
                                    <div class="space-y-1.5 max-h-40 overflow-y-auto custom-scrollbar">
                                        @forelse($cursosFiltrados as $curso)
                                            <label class="flex items-center gap-2 p-1.5 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300">
                                                <span class="text-xs font-medium text-gray-700">{{ $curso->nome }}</span>
                                            </label>
                                        @empty
                                            <p class="text-[10px] text-gray-400 italic text-center mt-2">Nenhum curso filtrado.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Turnos -->
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900/50">
                                    <label class="block mb-2 text-xs font-bold text-gray-800 uppercase border-b border-gray-200 pb-2">
                                        <i class="ph ph-clock text-purpura-500"></i> Turnos
                                    </label>
                                    <div class="space-y-1.5 max-h-40 overflow-y-auto custom-scrollbar">
                                        @forelse($turnosFiltrados as $turno)
                                            <label class="flex items-center gap-2 p-1.5 transition-colors border border-transparent rounded cursor-pointer hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $turno->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300">
                                                <span class="text-xs font-medium text-gray-700">{{ $turno->nome }}</span>
                                            </label>
                                        @empty
                                            <p class="text-[10px] text-gray-400 italic text-center mt-2">Nenhum turno filtrado.</p>
                                        @endforelse
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-600 border-gray-300 hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-purpura-600 hover:bg-purpura-700">
                                Salvar Vínculos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>