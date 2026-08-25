<div class="p-6 max-w-7xl mx-auto font-sans relative">

    @if (session()->has('success'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-pistache-100 bg-pistache-500 font-medium shadow-sm">
            <i class="ph ph-check-circle text-lg"></i> {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-red-100 bg-red-500 font-medium shadow-sm">
            <i class="ph ph-warning text-lg"></i> {{ session('error') }}
        </div>
    @endif

    <x-page-header 
        title="Permissões Extras" 
        icon="ph ph-shield-plus"
        badge="{{ strtoupper($userName) }}">

        <x-slot name="actions">
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                Voltar para Usuários
            </a>
        </x-slot>
    </x-page-header>

    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-6 text-sm text-blue-800 bg-blue-50 p-4 rounded-lg border border-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
            <strong>Atenção:</strong> Estas são permissões concedidas diretamente ao usuário, ignorando sua Role. Deixe a data vazia para uma permissão extra permanente.
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($permissionsByModule as $module => $permissions)
                    <div class="p-4 border border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700">
                        <h3 class="mb-4 font-bold text-gray-800 dark:text-gray-200 uppercase border-b border-gray-200 dark:border-gray-700 pb-2 text-xs tracking-wider">{{ $module }}</h3>
                        <div class="space-y-4">
                            @foreach($permissions as $permission)
                                @php $isInherited = in_array($permission->id, $rolePermissions); @endphp
                                
                                <div class="flex flex-col gap-1 p-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm {{ $isInherited ? 'opacity-70 bg-gray-50 dark:bg-gray-900' : '' }}">
                                    <label class="flex items-start gap-2 {{ $isInherited ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        
                                        @if($isInherited)
                                            <input type="checkbox" checked disabled class="w-4 h-4 mt-1 text-purpura-500 border-gray-300 rounded bg-gray-200 dark:bg-gray-700">
                                        @else
                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}" class="w-4 h-4 mt-1 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                        @endif

                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                                {{ $permission->name }}
                                                @if($isInherited)
                                                    <span class="px-2 py-0.5 text-[9px] font-bold text-purpura-700 bg-purpura-100 rounded border border-purpura-200 uppercase tracking-wider dark:bg-purpura-900/50 dark:text-purpura-400 dark:border-purpura-800">
                                                        Herdada
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $permission->description }}</span>
                                        </div>
                                    </label>
                                    
                                    @if(!$isInherited)
                                        <div x-data="{ isChecked: @entangle('selectedPermissions') }" x-show="isChecked.includes('{{ $permission->id }}')" class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700">
                                            <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Válido até (Opcional):</label>
                                            <input type="date" wire:model="expirations.{{ $permission->id }}" class="w-full text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500 py-8">Nenhuma permissão cadastrada no sistema.</div>
                @endforelse
            </div>

            <div class="flex justify-end pt-6 mt-8 border-t border-gray-200 dark:border-gray-700">
                @if(feature('usuario.permissoes_extras') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.permissoes_extras')))
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition flex items-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Permissões Extras
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>