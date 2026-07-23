<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Usuários (Corporativo)</h1>
    </div>

    <!-- Mensagens de Feedback -->
    @if (session()->has('success'))
        <div class="p-4 text-green-700 bg-green-100 border-l-4 border-green-500 rounded-md">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 text-red-700 bg-red-100 border-l-4 border-red-500 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <!-- Formulário -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <h2 class="text-lg font-medium text-gray-900">
            {{ $isEditMode ? 'Editar Usuário' : 'Cadastrar Novo Usuário' }}
        </h2>
        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
                    <input type="text" wire:model="name" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha {{ $isEditMode ? '(Opcional)' : '' }}</label>
                    <input type="password" wire:model="password" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Grupo (Role)</label>
                    <select wire:model="roleName" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione uma Role...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                        @endforeach
                    </select>
                    @error('roleName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800">Unidade Vinculada</label>
                    <select wire:model="unidade_id" class="w-full mt-1">
                        <option value="">Acesso Global (Sem Unidade)</option>
                        @foreach($unidades as $unidade)
                            <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                        @endforeach
                    </select>
                    @error('unidade_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="flex gap-2 pt-2 border-t border-gray-100">
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ $isEditMode ? 'Atualizar Usuário' : 'Adicionar Usuário' }}
                </button>
                @if($isEditMode)
                    <button type="button" wire:click="cancel" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Lista de Usuários -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nome / E-mail</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Role Principal</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @foreach($user->roles as $role)
                                <span class="inline-flex px-2 text-xs font-semibold leading-5 text-indigo-800 bg-indigo-100 rounded-full uppercase">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                            @if($user->roles->isEmpty())
                                <span class="text-sm text-gray-400">Sem role</span>
                            @endif
                            @if($user->unidade)
                                <div class="text-xs font-semibold text-purpura-600 mt-1"><i class="ph ph-map-pin"></i> {{ $user->unidade->nome }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                            <a href="{{ route('users.extra-permissions', $user->id) }}" class="mr-4 font-semibold text-orange-600 hover:text-orange-900">Permissões Extras</a>
                            <button wire:click="edit({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            
                            @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                <button wire:click="delete({{ $user->id }})" class="ml-4 text-red-600 hover:text-red-900" onclick="confirm('Excluir este usuário permanentemente?') || event.stopImmediatePropagation()">
                                    Excluir
                                </button>
                            @endif
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
</div>