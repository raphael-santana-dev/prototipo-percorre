<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Permissões</h1>
    </div>

    <!-- Formulário -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <form wire:submit="save" class="flex flex-wrap items-end gap-4">
            <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700">Módulo (ex: role, curso)</label>
                <input type="text" wire:model="module" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('module') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700">Chave (ex: role.listar)</label>
                <input type="text" wire:model="name" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <input type="text" wire:model="description" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">Adicionar</button>
            </div>
        </form>
    </div>

    <!-- Lista Agrupada -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($permissionsByModule as $module => $permissions)
                    <tr class="bg-gray-100">
                        <td colspan="3" class="px-6 py-2 text-sm font-bold text-gray-800 uppercase">Módulo: {{ $module }}</td>
                    </tr>
                    @foreach($permissions as $permission)
                        <tr>
                            <td class="px-6 py-4 font-mono text-sm text-gray-900 w-1/4">{{ $permission->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $permission->description }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                <button wire:click="delete({{ $permission->id }})" class="text-red-600 hover:text-red-900" onclick="confirm('Excluir esta permissão?') || event.stopImmediatePropagation()">Excluir</button>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma permissão cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>