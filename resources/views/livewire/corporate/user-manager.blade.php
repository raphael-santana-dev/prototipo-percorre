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
        <div class="p-4 rounded-md text-pistache-100 bg-pistache-500"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 rounded-md text-red-100 bg-red-500"><i class="ph ph-warning"></i> {{ session('error') }}</div>
    @endif

    <!-- Lista de Usuários -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nome / E-mail</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acesso</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @foreach($user->roles as $role)
                                <span class="inline-flex px-2 text-xs font-bold text-purpura-700 bg-purpura-100 rounded-full uppercase">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                            @if($user->unidade)
                                <div class="text-xs font-semibold text-gray-500 mt-1"><i class="ph ph-map-pin"></i> {{ $user->unidade->nome }}</div>
                            @endif
                        </td>
                        
                        <!-- Coluna de Ações APENAS com Ícones Padronizados -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="showQuickDetails({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50" title="Detalhes Rápidos">
                                    <i class="text-xl ph ph-eye"></i>
                                </button>

                                <a href="{{ route('users.show', $user->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                                    <i class="text-xl ph ph-user-focus"></i>
                                </a>
                                
                                <a href="{{ route('users.extra-permissions', $user->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50" title="Permissões Extras">
                                    <i class="text-xl ph ph-shield-plus"></i>
                                </a>

                                <button wire:click="edit({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50" title="Editar Usuário">
                                    <i class="text-xl ph ph-pencil-simple"></i>
                                </button>
                                
                                @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                    <button wire:click="delete({{ $user->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50" title="Excluir Usuário" onclick="confirm('Excluir este usuário permanentemente?') || event.stopImmediatePropagation()">
                                        <i class="text-xl ph ph-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhum usuário cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal de Cadastro / Edição -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2">
                        {{ $isEditMode ? 'Editar Usuário' : 'Novo Usuário' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nome Completo</label>
                                <input type="text" wire:model="name" class="w-full mt-1">
                                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">E-mail</label>
                                <input type="email" wire:model="email" class="w-full mt-1">
                                @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Senha {{ $isEditMode ? '(Deixe em branco para não alterar)' : '' }}</label>
                                <input type="password" wire:model="password" class="w-full mt-1">
                                @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Grupo (Role)</label>
                                <select wire:model="roleName" class="w-full mt-1">
                                    <option value="">Selecione...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                    @endforeach
                                </select>
                                @error('roleName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Unidade Vinculada</label>
                                <select wire:model="unidade_id" class="w-full mt-1">
                                    <option value="">Acesso Global</option>
                                    @foreach($unidades as $unidade)
                                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                    @endforeach
                                </select>
                                @error('unidade_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Usuário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>