<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            Permissões Extras: <span class="text-blue-600 uppercase">{{ $userName }}</span>
        </h1>
        <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            Voltar para Usuários
        </a>
    </div>

    @if (session()->has('success'))
        <div class="p-4 text-green-700 bg-green-100 border-l-4 border-green-500 rounded-md">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 text-red-700 bg-red-100 border-l-4 border-red-500 rounded-md">{{ session('error') }}</div>
    @endif

    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="mb-4 text-sm text-gray-600 bg-blue-50 p-3 rounded border border-blue-100">
            <strong>Atenção:</strong> Estas são permissões concedidas diretamente ao usuário, ignorando sua Role. Deixe a data vazia para uma permissão extra permanente.
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($permissionsByModule as $module => $permissions)
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <h3 class="mb-3 font-bold text-gray-800 uppercase border-b border-gray-300 pb-2">{{ $module }}</h3>
                        <div class="space-y-4">
                            @foreach($permissions as $permission)
                                <div class="flex flex-col gap-1 p-2 bg-white border border-gray-100 rounded shadow-sm">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}" class="w-4 h-4 mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900">{{ $permission->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $permission->description }}</span>
                                        </div>
                                    </label>
                                    
                                    <!-- Input de Validade condicionado ao checkbox usando AlpineJS nativo do Livewire -->
                                    <div x-data="{ isChecked: @entangle('selectedPermissions') }" x-show="isChecked.includes('{{ $permission->id }}')" class="pt-2 mt-1 border-t border-gray-100">
                                        <label class="block text-xs text-gray-600">Válido até (Opcional):</label>
                                        <input type="date" wire:model="expirations.{{ $permission->id }}" class="w-full mt-1 text-xs border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500">Nenhuma permissão cadastrada.</div>
                @endforelse
            </div>

            <div class="flex justify-end pt-6 mt-8 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Salvar Permissões Extras
                </button>
            </div>
        </form>
    </div>
</div>