<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Usuários" 
        icon="ph ph-users"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        @if(feature('usuario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Usuário
                </button>
            </x-slot>
        @endif
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        
        @forelse($registros as $user)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                </td>
                <td class="px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                        @foreach($user->roles as $role)
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">
                                {{ $role->name }}
                            </span>
                        @endforeach

                        @php
                            $extrasCount = $user->permissions->where('name', '!=', 'financeiro.visao_global')->count();
                        @endphp
                        @if($extrasCount > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded">
                                <i class="ph-bold ph-shield-plus"></i> +{{ $extrasCount }} Extra(s)
                            </span>
                        @endif
                    </div>

                    <div class="text-xs font-semibold text-gray-500 flex flex-wrap gap-1">
                        @if($user->unidades->count() > 0)
                            @foreach($user->unidades as $unidadeVinculada)
                                <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px] dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                    <i class="ph ph-map-pin"></i> {{ $unidadeVinculada->nome }}
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-600 px-1.5 py-0.5 rounded text-[10px] font-bold dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400">
                                <i class="ph ph-globe"></i> Unidades: Global
                            </span>
                        @endif

                        @if($user->permissions->contains('name', 'financeiro.visao_global'))
                            <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 px-1.5 py-0.5 rounded text-[10px] font-bold dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400">
                                <i class="ph ph-wallet"></i> Fin: Global
                            </span>
                        @elseif($user->centros_de_custo->count() > 0)
                            <span class="inline-flex items-center gap-1 bg-purpura-50 border border-purpura-200 text-purpura-700 px-1.5 py-0.5 rounded text-[10px] font-bold dark:bg-purpura-900/30 dark:border-purpura-800 dark:text-purpura-300">
                                <i class="ph ph-wallet"></i> {{ $user->centros_de_custo->count() }} C.Custo
                            </span>
                        @endif
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if(feature('usuario.visualizar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.visualizar')))
                            <button wire:click="showQuickDetails({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>
                            <a href="{{ route('users.show', $user->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Visualizar Perfil">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                        @endif
                        @if(feature('usuario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.editar')))
                            <button wire:click="edit({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Usuário (Wizard)">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endif
                        @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                            <button wire:click="delete({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Usuário" onclick="confirm('Excluir este usuário permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $user)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[180px]">{{ $user->name }}</div>
                            <div class="text-[10px] text-gray-500 truncate max-w-[180px]">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1 my-3">
                        @foreach($user->roles as $role)
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded uppercase">{{ $role->name }}</span>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-[10px] font-bold {{ $user->unidades->count() > 0 ? 'text-gray-500' : 'text-blue-500' }}">
                            <i class="{{ $user->unidades->count() > 0 ? 'ph ph-map-pin' : 'ph ph-globe' }}"></i> {{ $user->unidades->count() > 0 ? $user->unidades->count().' UNIDADES' : 'GLOBAL' }}
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickDetails({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-info"></i></button>
                            <a href="{{ route('users.show', $user->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-eye"></i></a>
                            <button wire:click="edit({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600"><i class="text-lg ph ph-pencil-simple"></i></button>
                            @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                <button wire:click="delete({{ $user->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" onclick="confirm('Excluir este usuário?') || event.stopImmediatePropagation()"><i class="text-lg ph ph-trash"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL WIZARD DE 3 ETAPAS --}}
    @if($showModal)
        <div class="fixed inset-0 z-[120] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm"></div>
                
                <div class="relative z-10 w-full max-w-5xl overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex flex-col max-h-[90vh]">
                    
                    {{-- CABEÇALHO DO MODAL + STEPPER --}}
                    <div class="px-6 py-5 bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 shrink-0">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="ph-fill ph-user-gear text-purpura-600 text-xl"></i>
                                    {{ $isEditMode ? 'Editar Usuário: ' . $name : 'Cadastrar Novo Usuário' }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Configure os dados de acesso, vínculos organizacionais e permissões em 3 etapas.</p>
                            </div>
                            <button type="button" wire:click="$set('showModal', false)" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-200/60 dark:hover:bg-gray-700 transition">
                                <i class="ph-bold ph-x text-lg"></i>
                            </button>
                        </div>

                        {{-- BARRA DE PROGRESSO DO WIZARD (3 ETAPAS) --}}
                        <div class="grid grid-cols-3 gap-3">
                            {{-- Passo 1 --}}
                            <button type="button" wire:click="irParaEtapa(1)" class="flex items-center gap-3 p-3 rounded-xl border text-left transition-all {{ $etapaAtual === 1 ? 'bg-white dark:bg-gray-800 border-purpura-500 shadow-sm ring-1 ring-purpura-500' : ($etapaAtual > 1 ? 'bg-purpura-50/60 dark:bg-gray-800/50 border-purpura-200 dark:border-gray-700 text-purpura-700' : 'bg-gray-100/70 dark:bg-gray-800/30 border-gray-200 dark:border-gray-700 opacity-60') }}">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 {{ $etapaAtual === 1 ? 'bg-purpura-600 text-white' : ($etapaAtual > 1 ? 'bg-emerald-500 text-white' : 'bg-gray-300 text-gray-700') }}">
                                    @if($etapaAtual > 1) <i class="ph-bold ph-check"></i> @else 1 @endif
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Etapa 1</span>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white truncate">Dados Gerais</span>
                                </div>
                            </button>

                            {{-- Passo 2 --}}
                            <button type="button" wire:click="irParaEtapa(2)" class="flex items-center gap-3 p-3 rounded-xl border text-left transition-all {{ $etapaAtual === 2 ? 'bg-white dark:bg-gray-800 border-purpura-500 shadow-sm ring-1 ring-purpura-500' : ($etapaAtual > 2 ? 'bg-purpura-50/60 dark:bg-gray-800/50 border-purpura-200 dark:border-gray-700 text-purpura-700' : 'bg-gray-100/70 dark:bg-gray-800/30 border-gray-200 dark:border-gray-700 opacity-60') }}">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 {{ $etapaAtual === 2 ? 'bg-purpura-600 text-white' : ($etapaAtual > 2 ? 'bg-emerald-500 text-white' : 'bg-gray-300 text-gray-700') }}">
                                    @if($etapaAtual > 2) <i class="ph-bold ph-check"></i> @else 2 @endif
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Etapa 2</span>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white truncate">Grupo (Role) e Vínculos</span>
                                </div>
                            </button>

                            {{-- Passo 3 --}}
                            <button type="button" wire:click="irParaEtapa(3)" class="flex items-center gap-3 p-3 rounded-xl border text-left transition-all {{ $etapaAtual === 3 ? 'bg-white dark:bg-gray-800 border-purpura-500 shadow-sm ring-1 ring-purpura-500' : 'bg-gray-100/70 dark:bg-gray-800/30 border-gray-200 dark:border-gray-700 opacity-60' }}">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 {{ $etapaAtual === 3 ? 'bg-purpura-600 text-white' : 'bg-gray-300 text-gray-700' }}">
                                    3
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Etapa 3</span>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white truncate">Permissões Extras</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- CORPO DO WIZARD COM SCROLL --}}
                    <div class="p-6 overflow-y-auto flex-1 custom-scrollbar space-y-6">

                        {{-- ========================================== --}}
                        {{-- ETAPA 1: DADOS GERAIS                      --}}
                        {{-- ========================================== --}}
                        @if($etapaAtual === 1)
                            <div class="space-y-5">
                                <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <i class="ph-fill ph-identification-badge text-purpura-500 text-lg"></i> Identificação e Credenciais
                                    </h4>
                                    <p class="text-xs text-gray-500">Informe os dados básicos de acesso e vínculo com o ERP Protheus.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="name" placeholder="Ex: Maria Souza" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('name') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">E-mail Corporativo <span class="text-red-500">*</span></label>
                                        <input type="email" wire:model="email" placeholder="usuario@dominio.com.br" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('email') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Código Usuário Protheus (Opcional)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ph-bold ph-identification-card text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="codigo_protheus" placeholder="Ex: 000123" class="w-full pl-9 text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                        @error('codigo_protheus') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                            Senha {{ $isEditMode ? '(Deixe em branco para manter a atual)' : '*' }}
                                        </label>
                                        <input type="password" wire:model="password" placeholder="Mínimo de 6 caracteres" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('password') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ========================================== --}}
                        {{-- ETAPA 2: ROLE, PERMISSÕES DA ROLE E ESCOPO --}}
                        {{-- ========================================== --}}
                        @if($etapaAtual === 2)
                            <div class="space-y-6">
                                
                                {{-- 2.1. SELEÇÃO DE ROLE E EXIBIÇÃO DAS PERMISSÕES DA ROLE --}}
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                                Grupo Principal (Role) <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model.live="roleName" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                <option value="">Selecione um grupo...</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                                @endforeach
                                            </select>
                                            @error('roleName') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <p class="font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                                <i class="ph-fill ph-shield-check text-purpura-500 text-base"></i> Permissões incluídas neste Grupo
                                            </p>
                                            <p class="mt-0.5">Ao selecionar um grupo ao lado, todas as permissões padrão atribuídas a ele são listadas abaixo para conferência.</p>
                                        </div>
                                    </div>

                                    @if(!empty($roleName))
                                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                            @if(!empty($rolePermissionsPorModulo))
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-[11px] font-bold uppercase tracking-wider text-purpura-700 dark:text-purpura-400">
                                                        Permissões herdadas da role "{{ strtoupper($roleName) }}" ({{ count($rolePermissionsIds) }}):
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                                                    @foreach($rolePermissionsPorModulo as $modulo => $perms)
                                                        <div class="p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-xs">
                                                            <div class="text-[10px] font-black uppercase tracking-wider text-gray-500 border-b border-gray-100 dark:border-gray-700 pb-1 mb-1.5 flex items-center justify-between">
                                                                <span>{{ $modulo }}</span>
                                                                <span class="bg-purpura-100 text-purpura-700 px-1.5 py-0.2 rounded text-[9px]">{{ count($perms) }}</span>
                                                            </div>
                                                            <ul class="space-y-1">
                                                                @foreach($perms as $p)
                                                                    <li class="text-[11px] text-gray-700 dark:text-gray-300 flex items-start gap-1.5" title="{{ $p['description'] }}">
                                                                        <i class="ph-bold ph-check text-emerald-500 mt-0.5 shrink-0"></i>
                                                                        <span class="truncate font-medium">{{ $p['name'] }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
                                                    <i class="ph-fill ph-warning-circle text-lg"></i>
                                                    <span>O grupo <strong>{{ strtoupper($roleName) }}</strong> ainda não possui permissões cadastradas (ou tem acesso via super-admin).</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- 2.2. ATRIBUIÇÃO DE UNIDADE / CURSO / TURNO (ESTILO CASCATA DO CICLO) --}}
                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <div class="mb-3">
                                        <h4 class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                            <i class="text-ponkan-500 ph-fill ph-tree-structure text-lg"></i> Vínculos Acadêmicos / Operacionais (Unidades, Cursos e Turnos)
                                        </h4>
                                        <p class="text-xs text-gray-500">Selecione uma ou mais Unidades para listar os Cursos vinculados a ela e, ao selecionar o Curso, escolha os Turnos correspondentes. Deixe tudo desmarcado para visão Global.</p>
                                    </div>

                                    <div class="flex flex-col md:flex-row h-[300px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
                                        
                                        {{-- COLUNA 1: UNIDADES --}}
                                        <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                                <span class="text-[11px] font-bold uppercase text-gray-500">1. Unidades</span>
                                                <span class="text-[10px] text-purpura-600 font-bold">{{ count($unidadesSelecionadas) }} sel.</span>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @foreach($todasUnidades as $unidade)
                                                    <div wire:key="usr-uni-{{ $unidade->id }}"
                                                         wire:click="setActiveUnidade({{ $unidade->id }})"
                                                         class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition {{ $activeUnidadeId == $unidade->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                        <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                            <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $unidade->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $unidade->nome }}</span>
                                                        </label>
                                                        <i class="ph ph-caret-right text-base {{ $activeUnidadeId == $unidade->id ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- COLUNA 2: CURSOS DA UNIDADE ATIVA --}}
                                        <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-800/80">
                                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                                <span class="text-[11px] font-bold uppercase text-gray-500">2. Cursos da Unidade</span>
                                                <span class="text-[10px] text-purpura-600 font-bold">{{ count($cursosSelecionados) }} sel.</span>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @if($activeUnidadeId && in_array((string) $activeUnidadeId, array_map('strval', $unidadesSelecionadas)))
                                                    @forelse($cursosFiltrados as $curso)
                                                        <div wire:key="usr-cur-{{ $activeUnidadeId }}-{{ $curso->id }}"
                                                             wire:click="setActiveCurso({{ $curso->id }})"
                                                             class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition {{ $activeCursoId == $curso->id ? 'bg-purpura-50 dark:bg-purpura-900/40 ring-1 ring-purpura-300' : 'hover:bg-white dark:hover:bg-gray-700' }}">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                                <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $curso->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $curso->nome }}</span>
                                                            </label>
                                                            <i class="ph ph-caret-right text-base {{ $activeCursoId == $curso->id ? 'text-purpura-600' : 'text-gray-300' }}"></i>
                                                        </div>
                                                    @empty
                                                        <div class="h-full flex items-center justify-center text-center p-4 text-[11px] text-gray-400 font-bold">
                                                            Nenhum curso vinculado a esta unidade.
                                                        </div>
                                                    @endforelse
                                                @else
                                                    <div class="h-full flex flex-col items-center justify-center text-center p-4 text-[11px] text-gray-400 font-bold uppercase">
                                                        Marque e selecione uma Unidade
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- COLUNA 3: TURNOS DO CURSO ATIVO --}}
                                        <div class="flex-1 flex flex-col bg-gray-50/80 dark:bg-gray-900/30">
                                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                                <span class="text-[11px] font-bold uppercase text-gray-500">3. Turnos do Curso</span>
                                                <span class="text-[10px] text-purpura-600 font-bold">{{ count($turnosSelecionados) }} sel.</span>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                @if($activeCursoId && in_array((string) $activeCursoId, array_map('strval', $cursosSelecionados)))
                                                    @forelse($turnosFiltrados as $turno)
                                                        <div wire:key="usr-tur-{{ $activeCursoId }}-{{ $turno->id }}" class="flex items-center p-2 rounded-lg hover:bg-white dark:hover:bg-gray-700 transition">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                                <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $turno->id }}" class="w-4 h-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $turno->nome }}</span>
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <div class="h-full flex items-center justify-center text-center p-4 text-[11px] text-gray-400 font-bold">
                                                            Nenhum turno vinculado a este curso.
                                                        </div>
                                                    @endforelse
                                                @else
                                                    <div class="h-full flex flex-col items-center justify-center text-center p-4 text-[11px] text-gray-400 font-bold uppercase">
                                                        Marque e selecione um Curso
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @error('cursosSelecionados') <span class="text-xs text-red-500 font-bold mt-1.5 block">{{ $message }}</span> @enderror
                                    @error('turnosSelecionados') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                {{-- 2.3. ATRIBUIÇÃO DE CENTROS DE CUSTO (QUALQUER ROLE) --}}
                                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div class="mb-3">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <i class="ph-fill ph-wallet text-purpura-500 text-lg"></i> Vínculos de Centros de Custo (Orçamento / Financeiro)
                                        </h4>
                                        <p class="text-xs text-gray-500">Disponível para qualquer Role. Vincule o usuário a um ou mais Centros de Custo ou conceda visão global.</p>
                                    </div>

                                    <label class="flex items-center gap-3 cursor-pointer mb-4 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/50 hover:border-purpura-300 transition-colors">
                                        <input type="checkbox" wire:model.live="acessoGlobalFinanceiro" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-800 dark:border-gray-700">
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 dark:text-white block">Permissão Global de Centros de Custo (Todos os Centros de Custo)</span>
                                            <span class="text-[11px] text-gray-500">Permite visualizar e gerenciar orçamentos de todas as áreas sem restrição por centro de custo.</span>
                                        </div>
                                    </label>

                                    @if(!$acessoGlobalFinanceiro)
                                        <div class="bg-gray-50 dark:bg-gray-900/40 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto custom-scrollbar space-y-2">
                                            <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 px-1">
                                                <span>Centros de Custo Disponíveis</span>
                                                <span class="text-purpura-600">{{ count($centrosCustoSelecionados) }} selecionado(s)</span>
                                            </div>
                                            
                                            @forelse($todosCentrosCusto as $cc)
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 shadow-xs transition-colors hover:border-purpura-300">
                                                    <label class="flex items-center gap-2.5 cursor-pointer flex-1">
                                                        <input type="checkbox" wire:model.live="centrosCustoSelecionados" value="{{ $cc->id }}" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono">
                                                            {{ $cc->codigo }} <span class="font-sans text-gray-600 dark:text-gray-400 font-medium ml-1">- {{ $cc->nome }}</span>
                                                        </span>
                                                    </label>

                                                    @if(in_array((string) $cc->id, $centrosCustoSelecionados))
                                                        <div class="flex items-center gap-2 sm:w-auto w-full bg-gray-50 dark:bg-gray-900/50 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                                            <span class="text-[10px] text-gray-500 font-bold uppercase whitespace-nowrap"><i class="ph-bold ph-hourglass-high"></i> Expira em:</span>
                                                            <input type="date" wire:model="expiracoesCentrosCusto.{{ $cc->id }}" class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-0.5 px-1.5 focus:ring-purpura-500" title="Deixe em branco para vínculo permanente">
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="text-center py-4 text-xs font-bold text-gray-400">
                                                    Nenhum Centro de Custo disponível para orçamento.
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- ========================================== --}}
                        {{-- ETAPA 3: PERMISSÕES EXTRAS                 --}}
                        {{-- ========================================== --}}
                        @if($etapaAtual === 3)
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <i class="ph-fill ph-shield-plus text-purpura-600 text-lg"></i> Permissões Extras (Avulsas)
                                        </h4>
                                        <p class="text-xs text-gray-500">Conceda permissões específicas além daquelas já incluídas na Role <strong>{{ strtoupper($roleName ?: 'N/D') }}</strong>.</p>
                                    </div>

                                    <div class="w-full sm:w-72 relative">
                                        <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                                        <input type="text" wire:model.live.debounce.300ms="buscaPermissaoExtra" placeholder="Filtrar permissão ou módulo..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 max-h-[50vh] overflow-y-auto custom-scrollbar pr-1">
                                    @forelse($permissionsByModule as $module => $permissions)
                                        <div class="p-3.5 border border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700 flex flex-col">
                                            <h5 class="mb-3 font-bold text-gray-700 dark:text-gray-200 uppercase border-b border-gray-200 dark:border-gray-700 pb-1.5 text-[11px] tracking-wider flex items-center justify-between">
                                                <span>{{ $module ?: 'GERAL' }}</span>
                                                <span class="text-[10px] text-gray-400">{{ count($permissions) }}</span>
                                            </h5>
                                            <div class="space-y-2.5">
                                                @foreach($permissions as $permission)
                                                    @if($permission->name === 'financeiro.visao_global')
                                                        @continue
                                                    @endif
                                                    @php
                                                        $isInherited = in_array((int) $permission->id, $rolePermissionsIds);
                                                        $isExtraChecked = in_array((string) $permission->id, array_map('strval', $selectedExtraPermissions));
                                                    @endphp
                                                    
                                                    <div class="flex flex-col gap-1 p-2.5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-xs {{ $isInherited ? 'opacity-75 bg-gray-50 dark:bg-gray-900' : '' }}">
                                                        <label class="flex items-start gap-2.5 {{ $isInherited ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                                            @if($isInherited)
                                                                <input type="checkbox" checked disabled class="w-4 h-4 mt-0.5 text-purpura-500 border-gray-300 rounded bg-gray-200 dark:bg-gray-700">
                                                            @else
                                                                <input type="checkbox" wire:model.live="selectedExtraPermissions" value="{{ $permission->id }}" class="w-4 h-4 mt-0.5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                                            @endif

                                                            <div class="flex flex-col min-w-0 flex-1">
                                                                <span class="text-xs font-bold text-gray-900 dark:text-white flex items-center justify-between gap-1">
                                                                    <span class="truncate">{{ $permission->name }}</span>
                                                                    @if($isInherited)
                                                                        <span class="px-1.5 py-0.5 text-[8px] font-bold text-purpura-700 bg-purpura-100 rounded border border-purpura-200 uppercase tracking-wider shrink-0">
                                                                            Herdada
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                                @if($permission->description)
                                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight mt-0.5">{{ $permission->description }}</span>
                                                                @endif
                                                            </div>
                                                        </label>
                                                        
                                                        @if(!$isInherited && $isExtraChecked)
                                                            <div class="pt-2 mt-1.5 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                                                                <label class="text-[9px] font-bold uppercase text-gray-500 whitespace-nowrap">Válido até:</label>
                                                                <input type="date" wire:model="extraPermissionExpirations.{{ $permission->id }}" class="w-full text-[11px] py-0.5 px-1.5 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded shadow-xs focus:ring-purpura-500 focus:border-purpura-500" title="Vazio = Permanente">
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-span-full text-center text-gray-400 py-8 text-xs font-bold">
                                            Nenhuma permissão encontrada para o filtro informado.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- RODAPÉ DO WIZARD (BOTÕES DE NAVEGAÇÃO E SALVAMENTO) --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/60 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
                        <div>
                            @if($etapaAtual > 1)
                                <button type="button" wire:click="passoAnterior" class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 transition shadow-xs">
                                    <i class="ph-bold ph-arrow-left"></i> Voltar
                                </button>
                            @else
                                <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 transition">
                                    Cancelar
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isEditMode && $etapaAtual < 3)
                                <button type="button" wire:click="save" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded-lg hover:bg-purpura-200 transition">
                                    Salvar Direto
                                </button>
                            @endif

                            @if($etapaAtual < 3)
                                <button type="button" wire:click="proximoPasso" class="flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white rounded-lg bg-purpura-600 hover:bg-purpura-700 transition shadow-sm">
                                    Próxima Etapa <i class="ph-bold ph-arrow-right"></i>
                                </button>
                            @else
                                <button type="button" wire:click="save" wire:loading.attr="disabled" class="flex items-center gap-2 px-6 py-2 text-xs font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 transition shadow-sm">
                                    <i class="ph-bold ph-floppy-disk text-sm"></i>
                                    <span>{{ $isEditMode ? 'Salvar Alterações' : 'Concluir Cadastro' }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>