<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            Gerenciar Permissões: <span class="text-blue-600 uppercase">{{ $roleName }}</span>
        </h1>
        <a href="{{ route('roles.index') }}" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            Voltar para Roles
        </a>
    </div>

    @if (session()->has('success'))
        <div class="p-4 text-green-700 bg-green-100 border-l-4 border-green-500 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($permissionsByModule as $module => $permissions)
                    <!-- Card do Módulo -->
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <h3 class="mb-3 font-bold text-gray-800 uppercase border-b border-gray-300 pb-2">{{ $module }}</h3>
                        <div class="space-y-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="w-4 h-4 mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-gray-900">{{ $permission->name }}</span>
                                        <span class="text-xs text-gray-500">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500">
                        Nenhuma permissão cadastrada no sistema ainda.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end mt-8 border-t border-gray-200 pt-6">
                <button type="submit" class="px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Salvar Permissões
                </button>
            </div>
        </form>
    </div>
</div>