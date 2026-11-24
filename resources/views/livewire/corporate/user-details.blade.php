<div class="space-y-6 font-sans">
    <div class="flex items-center justify-between">
        <a href="{{ route('users.index') }}" class="flex items-center gap-2 text-xs font-bold text-gray-500 transition-colors hover:text-purpura-600 dark:text-gray-400">
            <i class="ph ph-arrow-left"></i> Voltar para a lista
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <img class="w-16 h-16 border border-gray-200 rounded-full dark:border-gray-700 shrink-0" 
                 src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=E5E7EB&color=111827&size=128&bold=true" 
                 alt="Foto do usuário">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                    @if($user->possuiTravaPorVinculoAtiva())
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-amber-50 text-amber-800 border border-amber-200">
                            Travado por Vínculo
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-blue-50 text-blue-700 border border-blue-200">
                            Visão Acadêmica Global
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</p>
            </div>
        </div>

        @if(feature('usuario.permissoes_extras') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.permissoes_extras')))
            <a href="{{ route('users.extra-permissions', $user->id) }}" class="btn btn--secondary btn--small">
                <i class="ph ph-shield-plus"></i> Gerir Permissões Extras
            </a>
        @endif
    </div>

    @if($user->possuiTravaPorVinculoAtiva())
        <div class="p-6 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-2 dark:border-gray-700">
                Combinações Vinculadas (Unidade / Curso / Turno)
            </h2>

            @if(count($combinacoesVinculadas) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($combinacoesVinculadas as $comb)
                        <div class="p-3 rounded-lg border {{ $comb['expirado'] ? 'border-red-200 bg-red-50/40 opacity-70' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900' }} flex items-center justify-between gap-2 text-xs">
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white block">{{ $comb['unidade'] }}</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ $comb['curso'] }} • {{ $comb['turno'] }}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                @if($comb['expires_at'])
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $comb['expirado'] ? 'bg-red-100 text-red-700' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        {{ $comb['expirado'] ? 'Expirado em ' : 'Até ' }}{{ $comb['expires_at'] }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Sem prazo
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500">Nenhuma combinação ativa vinculada a este usuário.</p>
            @endif
        </div>
    @endif

    <div class="p-6 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
        <h2 class="mb-4 text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-2 dark:border-gray-700">
            Nível de Acesso (ACL)
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-xs font-bold uppercase text-gray-400 mb-2">Grupo Principal (Role)</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        <span class="inline-flex px-2.5 py-1 text-xs font-bold text-purpura-700 bg-purpura-50 border border-purpura-200 rounded uppercase">
                            {{ $role->name }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-500">Usuário sem grupo definido.</span>
                    @endforelse
                </div>
            </div>
            
            <div>
                <h3 class="text-xs font-bold uppercase text-gray-400 mb-2">Permissões Especiais Diretas</h3>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($user->permissions as $perm)
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-200 rounded dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            {{ $perm->name }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-500">Nenhuma permissão avulsa concedida.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>