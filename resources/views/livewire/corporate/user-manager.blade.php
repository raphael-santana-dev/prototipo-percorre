<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ph ph-users text-purpura-500"></i> Usuários
        </h1>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg bg-purpura-500 hover:bg-purpura-600">
            <i class="ph ph-plus"></i> Novo Usuário
        </button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 rounded-md text-pistache-100 bg-pistache-500 font-bold shadow-sm"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 rounded-md text-red-100 bg-red-500 font-bold shadow-sm"><i class="ph ph-warning"></i> {{ session('error') }}</div>
    @endif

    <!-- Lista de Usuários -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nome / E-mail</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acesso / Unidades</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
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
                                        <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px]">
                                            <i class="ph ph-map-pin"></i> {{ $unidadeVinculada->nome }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-600 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                        <i class="ph ph-globe"></i> Acesso Global / Não Restrito
                                    </span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="showQuickDetails({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                                    <i class="text-xl ph ph-info"></i>
                                </button>
                                <a href="{{ route('users.show', $user->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600">
                                    <i class="text-xl ph ph-eye"></i>
                                </a>
                                <button wire:click="edit({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Usuário">
                                    <i class="text-xl ph ph-pencil-simple"></i>
                                </button>
                                @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                    <button wire:click="delete({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Usuário" onclick="confirm('Excluir este usuário permanentemente?') || event.stopImmediatePropagation()">
                                        <i class="text-xl ph ph-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

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