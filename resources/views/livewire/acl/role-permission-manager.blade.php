<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <x-page-header 
        title="Gerenciar Permissões: {{ strtoupper($roleName) }}" 
        icon="ph ph-shield-check"
        badge=""
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="actions">
            <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                <i class="text-lg ph ph-arrow-left"></i> Voltar para Roles
            </a>
        </x-slot>

    </x-page-header>

    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($permissionsByModule as $module => $permissions)
                    <!-- Card do Módulo -->
                    <div class="p-5 border border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700 hover:border-purpura-300 transition-colors">
                        <h3 class="flex items-center gap-2 mb-4 text-xs font-bold tracking-wider text-gray-500 uppercase border-b border-gray-200 pb-2 dark:text-gray-400 dark:border-gray-600">
                            <i class="ph-fill ph-squares-four text-purpura-500 text-lg"></i> {{ $module }}
                        </h3>
                        <div class="space-y-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-start gap-3 p-2 transition-colors border border-transparent rounded-lg cursor-pointer hover:bg-white dark:hover:bg-gray-800">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="w-4 h-4 mt-0.5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $permission->name }}</span>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-gray-500 border border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                        <i class="ph-fill ph-shield-warning text-3xl text-gray-300 mb-2"></i>
                        <p class="font-bold text-gray-700 dark:text-gray-400">Nenhuma permissão cadastrada no sistema ainda.</p>
                        <p class="text-xs mt-1">Crie as permissões no módulo de gerenciamento antes de vinculá-las.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 mt-8 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('roles.index') }}" class="px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancelar
                </a>
                @if(feature('acl.role.permissoes') && (auth()->user()->hasRole('dev') || auth()->user()->can('acl.role.permissoes')))
                    <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white transition-colors rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Permissões
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>