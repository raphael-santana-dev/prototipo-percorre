<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Roles (Grupos)</h1>
    </div>

    <!-- Formulário -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <h2 class="text-lg font-medium text-gray-900">
            {{ $isEditMode ? 'Editar Role' : 'Cadastrar Nova Role' }}
        </h2>
        <form wire:submit="save" class="flex items-end gap-4 mt-4">
            <div class="flex-1">
                <label for="name" class="block text-sm font-medium text-gray-700">Nome da Role (ex: gerente, financeiro)</label>
                <input type="text" id="name" wire:model="name" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ $isEditMode ? 'Atualizar' : 'Adicionar' }}
                </button>
                @if($isEditMode)
                    <button type="button" wire:click="cancel" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Lista -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">ID</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nome da Role</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($roles as $role)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $role->id }}</td>
                        <td class="px-6 py-4 font-mono text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ $role->name }}
                            @if(in_array($role->name, ['dev', 'admin']))
                                <span class="ml-2 inline-flex px-2 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full">Nativo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                            <button wire:click="edit({{ $role->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            
                            @if(!in_array($role->name, ['dev', 'admin']))
                                <button wire:click="delete({{ $role->id }})" class="ml-4 text-red-600 hover:text-red-900" onclick="confirm('Tem certeza que deseja excluir esta role?') || event.stopImmediatePropagation()">
                                    Excluir
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>