<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('users.index') }}" class="flex items-center gap-2 text-sm font-bold text-gray-500 transition-colors hover:text-purpura-600 dark:text-gray-400">
            <i class="ph-bold ph-arrow-left"></i> Voltar para a lista
        </a>
    </div>

    <div class="relative overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-gray-2 dark:bg-gray-800 dark:border-gray-700">
        <!-- Padrão Visual do Sistema (Background Sutil) -->
        <div class="h-32 bg-gray-100 sm:h-40 dark:bg-gray-900 relative">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, #9B26B6 1px, transparent 0); background-size: 24px 24px;"></div>
        </div>
        
        <div class="px-6 pb-6 sm:px-8">
            <div class="relative flex flex-col sm:flex-row sm:items-end gap-6 -mt-12 sm:-mt-16">
                <!-- Avatar -->
                <div class="relative p-1 bg-white rounded-full dark:bg-gray-800 w-fit">
                    <img class="object-cover w-24 h-24 border-4 border-gray-100 rounded-full sm:w-32 sm:h-32 dark:border-gray-700" 
                         src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=E5E7EB&color=111827&size=256&bold=true" 
                         alt="Foto do usuário">
                </div>

                <div class="flex-1 pb-2">
                    <h1 class="text-2xl font-extrabold text-gray-900 truncate sm:text-3xl dark:text-white">{{ $user->name }}</h1>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1"><i class="text-lg ph ph-envelope-simple"></i> {{ $user->email }}</span>
                        <span class="flex items-center gap-1"><i class="text-lg ph ph-buildings"></i> Base: {{ $user->unidade?->nome ?? 'Acesso Nível Matriz' }}</span>
                    </div>
                </div>
                
                <div class="pb-2">
                    <a href="{{ route('users.extra-permissions', $user->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm font-bold border rounded-lg text-ponkan-500 border-ponkan-500 hover:bg-ponkan-50 transition-colors">
                        <i class="ph-bold ph-shield-plus"></i> Gerir Permissões
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapa de Acesso -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-gray-1 dark:bg-gray-800 dark:border-gray-700">
        <h2 class="flex items-center gap-2 mb-4 text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-2 dark:border-gray-700">
            <i class="ph ph-shield-check text-purpura-500"></i> Nível de Acesso (ACL)
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-sm font-bold tracking-wider text-gray-500 uppercase mb-3">Roles Pertencentes</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        <span class="inline-flex px-3 py-1 text-sm font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded-full uppercase">
                            <i class="ph-bold ph-users mr-1"></i> {{ $role->name }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500 italic">Usuário sem cargo/grupo definido.</span>
                    @endforelse
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-bold tracking-wider text-gray-500 uppercase mb-3">Permissões Especiais Diretas</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->permissions as $perm)
                        <span class="inline-flex px-3 py-1 text-sm font-bold text-ponkan-700 bg-ponkan-100 border border-ponkan-200 rounded-full">
                            {{ $perm->name }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500 italic">Nenhuma permissão isolada concedida.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>