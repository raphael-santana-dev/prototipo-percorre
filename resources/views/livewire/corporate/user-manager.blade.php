<div class="w-full relative font-sans" x-data="{ showModal: @entangle('showModal') }" x-effect="document.body.classList.toggle('overflow-hidden', showModal)">

    <x-page-header 
        title="Usuários" 
        icon="ph ph-users"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">
        
        @if(feature('usuario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.criar')))
            <x-slot name="actions">
                <button wire:click="openModal" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                    <i class="ph ph-plus"></i> Novo Usuário
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
            @php
                $isTravadoVinculo = $user->possuiTravaPorVinculoAtiva();
                $combinacoesAtivasUser = $isTravadoVinculo ? $user->obterCombinacoesVinculoAtivas() : [];
            @endphp
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
                            $extrasCount = $user->permissions->whereNotIn('name', [
                                'financeiro.visao_global',
                                'financeiro.visao_global.edicao',
                                'financeiro.visao_global.aprovacao'
                            ])->count();
                        @endphp
                        @if($extrasCount > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded">
                                <i class="ph-bold ph-shield-plus"></i> +{{ $extrasCount }} Extra(s)
                            </span>
                        @endif
                    </div>

                    <div class="text-xs font-semibold text-gray-500 flex flex-wrap gap-1">
                        @if($isTravadoVinculo)
                            <span class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-800 px-2 py-0.5 rounded text-[10px] font-bold dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
                                <i class="ph-fill ph-lock-key"></i> Travado por Vínculo ({{ count($combinacoesAtivasUser) }} combinação/ões ativa/s)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-600 px-1.5 py-0.5 rounded text-[10px] font-bold dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400">
                                <i class="ph ph-globe"></i> Acadêmico: Global
                            </span>
                        @endif

                        @if($user->possuiVisaoGlobalCentrosCusto('edicao'))
                            <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                <i class="ph-bold ph-pencil-simple"></i> Edição CC: Global
                            </span>
                        @elseif($user->centros_de_custo_edicao->count() > 0)
                            <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                <i class="ph-bold ph-pencil-simple"></i> Edição: {{ $user->centros_de_custo_edicao->count() }} CC
                            </span>
                        @endif

                        @if($user->possuiVisaoGlobalCentrosCusto('aprovacao'))
                            <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                <i class="ph-bold ph-check-square-offset"></i> Aprovação CC: Global
                            </span>
                        @elseif($user->centros_de_custo_aprovacao->count() > 0)
                            <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                <i class="ph-bold ph-check-square-offset"></i> Aprovação: {{ $user->centros_de_custo_aprovacao->count() }} CC
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
                            @if(!$user->hasRole('dev') || auth()->user()->hasRole('dev'))
                                <button type="button"
                                        wire:click="reenviarEmailPrimeiroAcesso({{ $user->id }})"
                                        wire:confirm="Gerar uma nova senha provisória e reenviar o e-mail de primeiro acesso para {{ $user->email }}?"
                                        wire:loading.attr="disabled"
                                        class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-gray-600"
                                        title="Reenviar E-mail de Primeiro Acesso">
                                    <i class="text-lg ph ph-paper-plane-tilt"></i>
                                </button>
                            @endif
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
                @php
                    $isTravadoCard = $user->possuiTravaPorVinculoAtiva();
                    $combinacoesCard = $isTravadoCard ? $user->obterCombinacoesVinculoAtivas() : [];
                @endphp
                <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
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
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-[10px] font-bold {{ $isTravadoCard ? 'text-amber-700' : 'text-blue-600' }}">
                            <i class="{{ $isTravadoCard ? 'ph-fill ph-lock-key' : 'ph ph-globe' }}"></i>
                            {{ $isTravadoCard ? count($combinacoesCard) . ' VÍNCULO(S)' : 'GLOBAL' }}
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickDetails({{ $user->id }})" class="p-1.5 text-gray-400 hover:text-purpura-500 rounded-lg" title="Ficha Rápida"><i class="text-base ph ph-info"></i></button>
                            <a href="{{ route('users.show', $user->id) }}" class="p-1.5 text-gray-400 hover:text-ponkan-500 rounded-lg" title="Visualizar Perfil"><i class="text-base ph ph-eye"></i></a>
                            @if(feature('usuario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('usuario.editar')))
                                @if(!$user->hasRole('dev') || auth()->user()->hasRole('dev'))
                                    <button type="button"
                                            wire:click="reenviarEmailPrimeiroAcesso({{ $user->id }})"
                                            wire:confirm="Gerar uma nova senha provisória e reenviar o e-mail de primeiro acesso para {{ $user->email }}?"
                                            class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg"
                                            title="Reenviar E-mail de Primeiro Acesso">
                                        <i class="text-base ph ph-paper-plane-tilt"></i>
                                    </button>
                                @endif
                                <button wire:click="edit({{ $user->id }})" class="p-1.5 text-gray-400 hover:text-blue-500 rounded-lg" title="Editar Usuário"><i class="text-base ph ph-pencil-simple"></i></button>
                            @endif
                            @if($user->id !== auth()->id() && !$user->hasRole('dev'))
                                <button wire:click="delete({{ $user->id }})" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg" title="Excluir Usuário" onclick="confirm('Excluir este usuário?') || event.stopImmediatePropagation()"><i class="text-base ph ph-trash"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    {{-- MODAL WIZARD EM TELA CHEIA (IDÊNTICO AO DE CICLOS COM X-TELEPORT E Z-[999]) --}}
    @if($showModal)
        <template x-teleport="body">
            <div class="fixed inset-0 z-[999] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden font-sans"
                 @keydown.escape.window="$wire.set('showModal', false)"
                 role="dialog"
                 aria-modal="true">

                {{-- HEADER DO MODAL EM TELA CHEIA --}}
                <div class="flex justify-between items-center px-6 py-3.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $isEditMode ? 'Editar Usuário: ' . $name : 'Assistente de Cadastro de Usuário' }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            Configure as credenciais, a trava por vínculo (Unidade / Curso / Turno) com validade e as permissões.
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showModal', false)" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="text-xl ph ph-x"></i>
                    </button>
                </div>

                {{-- CORPO DO WIZARD (MENU LATERAL ESQUERDO + CONTEÚDO CENTRAL) --}}
                <div class="flex flex-1 overflow-hidden">

                    {{-- MENU LATERAL ESQUERDO (PASSOS) --}}
                    <div class="w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-5 flex flex-col gap-1.5 shrink-0 overflow-y-auto">
                        @php
                            $passosUsuario = [
                                1 => ['titulo' => 'Dados Gerais', 'desc' => 'Nome, e-mail, Protheus e senha'],
                                2 => ['titulo' => 'Grupo e Vínculos', 'desc' => 'Role, trava por vínculo e C.C.'],
                                3 => ['titulo' => 'Permissões Extras', 'desc' => 'Permissões avulsas e validade'],
                            ];
                        @endphp

                        @foreach($passosUsuario as $num => $info)
                            <div wire:click="irParaEtapa({{ $num }})"
                                 class="flex items-start gap-3 p-2.5 rounded-lg transition cursor-pointer {{ $etapaAtual === $num ? 'bg-gray-100 dark:bg-gray-700/70 font-semibold' : ($isEditMode || $num < $etapaAtual ? 'hover:bg-gray-50 dark:hover:bg-gray-700/40' : 'opacity-50') }}">
                                <div class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold shrink-0 {{ $etapaAtual === $num ? 'bg-purpura-600 text-white' : ($num < $etapaAtual ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                                    @if($num < $etapaAtual)
                                        <i class="ph ph-check"></i>
                                    @else
                                        {{ $num }}
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold {{ $etapaAtual === $num ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">{{ $info['titulo'] }}</h4>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight mt-0.5">{{ $info['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- CONTEÚDO PRINCIPAL DO PASSO --}}
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50 dark:bg-gray-900 custom-scrollbar flex flex-col justify-between">
                        <div class="max-w-5xl mx-auto w-full space-y-6">

                            {{-- ========================================== --}}
                            {{-- PASSO 1: DADOS GERAIS                      --}}
                            {{-- ========================================== --}}
                            @if($etapaAtual === 1)
                                @php
                                    $inputUsrClass = 'w-full h-10 px-3.5 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purpura-500/20 focus:border-purpura-500 transition-colors';
                                @endphp
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Passo 1: Identificação e Credenciais</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Informe os dados básicos de acesso do usuário e vínculo opcional com o ERP Protheus.</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model="name" placeholder="Ex: Maria Souza" class="{{ $inputUsrClass }}">
                                            @error('name') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail Corporativo <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model="email" placeholder="usuario@dominio.com.br" class="{{ $inputUsrClass }}">
                                            @error('email') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código Usuário Protheus (Opcional)</label>
                                            <input type="text" wire:model="codigo_protheus" placeholder="Ex: 000123" class="{{ $inputUsrClass }}">
                                            @error('codigo_protheus') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Senha {{ $isEditMode ? '(Deixe em branco para manter a atual)' : '*' }}
                                            </label>
                                            <input type="password" wire:model="password" placeholder="Mínimo de 6 caracteres" class="{{ $inputUsrClass }}">
                                            @error('password') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- ========================================== --}}
                            {{-- PASSO 2: ROLE, TRAVA POR VÍNCULO E C.C.    --}}
                            {{-- ========================================== --}}
                            @if($etapaAtual === 2)
                                {{-- BLOCO 2.1: GRUPO PRINCIPAL (ROLE) --}}
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Grupo Principal (Role) <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model.live="roleName" class="w-full h-10 px-3 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg dark:text-white">
                                                <option value="">Selecione um grupo...</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                                @endforeach
                                            </select>
                                            @error('roleName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 block">Permissões Herdadas do Grupo</span>
                                            <span>Ao selecionar um grupo ao lado, as permissões padrão atribuídas a ele são listadas abaixo.</span>
                                        </div>
                                    </div>

                                    @if(!empty($roleName) && !empty($rolePermissionsPorModulo))
                                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 max-h-40 overflow-y-auto custom-scrollbar pr-1">
                                                @foreach($rolePermissionsPorModulo as $modulo => $perms)
                                                    <div class="p-2.5 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                                                        <div class="text-[10px] font-bold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700 pb-1 mb-1 flex justify-between">
                                                            <span>{{ $modulo }}</span>
                                                            <span>{{ count($perms) }}</span>
                                                        </div>
                                                        <ul class="space-y-0.5">
                                                            @foreach($perms as $p)
                                                                <li class="text-[11px] text-gray-700 dark:text-gray-300 truncate" title="{{ $p['description'] }}">
                                                                    • {{ $p['name'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- BLOCO 2.2: TRAVA POR VÍNCULO (UNIDADE -> CURSO -> TURNO + PRAZO) --}}
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-5">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                                Escopo Acadêmico e Operacional (Unidades, Cursos e Turnos)
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Quando a opção <strong>Travar por vínculo</strong> está marcada, o usuário só poderá visualizar e operar Inscrições, Solicitações e Estudantes das combinações exatas selecionadas abaixo.
                                            </p>
                                        </div>

                                        <label class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-lg border cursor-pointer shrink-0 transition {{ $travar_por_vinculo ? 'bg-amber-50 border-amber-300 text-amber-900 dark:bg-amber-950/30 dark:border-amber-700 dark:text-amber-200' : 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300' }}">
                                            <input type="checkbox" wire:model.live="travar_por_vinculo" class="w-4 h-4 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                            <span class="text-xs font-bold">Travar por vínculo</span>
                                        </label>
                                    </div>

                                    @error('turnosSelecionados')
                                        <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    @if($travar_por_vinculo)
                                        {{-- SELETOR EM 3 COLUNAS IDÊNTICO AO CRUD DE CICLOS --}}
                                        <div class="flex flex-col md:flex-row h-[340px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                            {{-- Coluna 1: Unidades --}}
                                            <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                                <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold uppercase text-gray-500 flex justify-between">
                                                    <span>1. Unidades</span>
                                                    <span class="text-purpura-600">{{ count($unidadesSelecionadas) }}</span>
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                    @foreach($todasUnidades as $u)
                                                        <div wire:key="u-wiz-{{ $u->id }}"
                                                             wire:click="setActiveUnidade({{ $u->id }})"
                                                             class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $activeUnidadeId == $u->id ? 'bg-purpura-50 dark:bg-purpura-900/30 font-semibold text-purpura-700 dark:text-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                                            <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                                                <input type="checkbox" wire:model.live="unidadesSelecionadas" value="{{ $u->id }}" wire:change="setActiveUnidade({{ $u->id }})" class="w-4 h-4 rounded text-purpura-600">
                                                                <span class="text-xs text-gray-800 dark:text-gray-200">{{ $u->nome }}</span>
                                                            </label>
                                                            <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- Coluna 2: Cursos da Unidade --}}
                                            <div class="flex-1 flex flex-col border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                                <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold uppercase text-gray-500">
                                                    2. Cursos da Unidade
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                    @if($activeUnidadeId)
                                                        @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $activeUnidadeId)) as $c)
                                                            @php
                                                                $chaveCursoUsr = "{$activeUnidadeId}-{$c->id}";
                                                                $qtdTurnosUsr = $this->contarTurnosDoCursoNaUnidade((int) $activeUnidadeId, (int) $c->id);
                                                            @endphp
                                                            <div wire:key="c-wiz-{{ $chaveCursoUsr }}"
                                                                 wire:click="setActiveCurso({{ $c->id }})"
                                                                 class="flex items-center justify-between p-2 rounded cursor-pointer transition {{ $activeCursoId == $c->id ? 'bg-purpura-50 dark:bg-purpura-900/30 font-semibold text-purpura-700 dark:text-purpura-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                                                <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" wire:click.stop>
                                                                    <input type="checkbox" wire:model.live="cursosSelecionados" value="{{ $chaveCursoUsr }}" wire:change="setActiveCurso({{ $c->id }})" class="w-4 h-4 rounded text-purpura-600">
                                                                    <span class="text-xs text-gray-800 dark:text-gray-200 truncate">{{ $c->nome }}</span>
                                                                </label>
                                                                <div class="flex items-center gap-1 shrink-0">
                                                                    @if($qtdTurnosUsr > 0)
                                                                        <span class="px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $qtdTurnosUsr }}</span>
                                                                    @endif
                                                                    <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione uma Unidade</div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Coluna 3: Turnos do Curso na Unidade --}}
                                            <div class="flex-1 flex flex-col">
                                                <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[11px] font-semibold uppercase text-gray-500">
                                                    3. Turnos na Unidade
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                                    @if($activeUnidadeId && $activeCursoId)
                                                        @php
                                                            $turnosDaCombinacao = $this->obterTurnosDoCursoNaUnidade((int) $activeUnidadeId, (int) $activeCursoId);
                                                        @endphp
                                                        @forelse($turnosDaCombinacao as $t)
                                                            @php $chaveTurnoUsr = "{$activeUnidadeId}-{$activeCursoId}-{$t->id}"; @endphp
                                                            <div wire:key="t-wiz-{{ $chaveTurnoUsr }}" class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                                <label class="flex items-center gap-2 cursor-pointer flex-1">
                                                                    <input type="checkbox" wire:model.live="turnosSelecionados" value="{{ $chaveTurnoUsr }}" class="w-4 h-4 rounded text-purpura-600">
                                                                    <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $t->nome }}</span>
                                                                </label>
                                                            </div>
                                                        @empty
                                                            <div class="h-full flex items-center justify-center text-gray-400 text-xs p-4 text-center">
                                                                Nenhum turno habilitado para este curso nesta unidade.
                                                            </div>
                                                        @endforelse
                                                    @else
                                                        <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione um Curso</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- LISTA DE COMBINAÇÕES SELECIONADAS COM PRAZO DE VALIDADE OPCIONAL --}}
                                        @if(count($this->combinacoesVinculoDetalhadas) > 0)
                                            <div class="space-y-2 pt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                                        Combinações Vinculadas ({{ count($this->combinacoesVinculoDetalhadas) }}) — Defina um prazo de expiração opcional para cada vínculo:
                                                    </span>
                                                </div>
                                                <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-900 max-h-52 overflow-y-auto custom-scrollbar">
                                                    @foreach($this->combinacoesVinculoDetalhadas as $combItem)
                                                        <div wire:key="comb-exp-{{ $combItem['key'] }}" class="p-2.5 px-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                                                <span class="font-bold text-purpura-700 dark:text-purpura-400">{{ $combItem['unidade_nome'] }}</span>
                                                                <span class="text-gray-400 mx-1">•</span>
                                                                <span>{{ $combItem['curso_nome'] }}</span>
                                                                <span class="text-gray-400 mx-1">•</span>
                                                                <span class="px-2 py-0.5 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-[11px]">{{ $combItem['turno_nome'] }}</span>
                                                            </div>

                                                            <div class="flex items-center gap-2 shrink-0">
                                                                <span class="text-[11px] text-gray-500">Válido até:</span>
                                                                <input type="date"
                                                                       wire:model="expiracoesVinculos.{{ $combItem['key'] }}"
                                                                       class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1 px-2"
                                                                       title="Deixe em branco para vínculo sem prazo (permanente)">
                                                                <button type="button"
                                                                        wire:click="removerCombinacaoVinculo('{{ $combItem['key'] }}')"
                                                                        class="p-1 text-gray-400 hover:text-red-600 rounded"
                                                                        title="Remover combinação">
                                                                    <i class="ph ph-trash text-sm"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="p-4 rounded-lg bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 text-xs text-blue-800 dark:text-blue-300 flex items-center gap-2.5">
                                            <i class="ph-fill ph-globe text-lg text-blue-600 shrink-0"></i>
                                            <span>Este usuário está com <strong>Visão Global Acadêmica</strong> (sem trava por vínculo de Unidade/Curso/Turno). Marque a opção <strong>"Travar por vínculo"</strong> acima caso deseje restringir o acesso dele a turmas específicas.</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- BLOCO 2.3: CENTROS DE CUSTO (EDIÇÃO VS APROVAÇÃO) --}}
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            Vínculos de Centros de Custo (Orçamento / Financeiro)
                                        </h4>
                                        <p class="text-xs text-gray-500">Defina separadamente quais Centros de Custo este usuário pode Editar/Elaborar e quais pode Aprovar/Avaliar.</p>
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        {{-- Edição CC --}}
                                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3">
                                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">1. Centros de Custo para EDIÇÃO</span>
                                                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                                    {{ $acessoGlobalEdicao ? 'TODOS (GLOBAL)' : count($centrosCustoEdicaoSelecionados) . ' sel.' }}
                                                </span>
                                            </div>

                                            <label class="flex items-center gap-2 cursor-pointer p-2 rounded bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                                <input type="checkbox" wire:model.live="acessoGlobalEdicao" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Permissão Global para Edição (Todos os C.C.)</span>
                                            </label>

                                            @if(!$acessoGlobalEdicao)
                                                <div class="max-h-48 overflow-y-auto custom-scrollbar space-y-1.5 pr-1">
                                                    @foreach($todosCentrosCusto as $cc)
                                                        <div wire:key="cc-ed-{{ $cc->id }}" class="p-2 rounded border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 space-y-1">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="checkbox" wire:model.live="centrosCustoEdicaoSelecionados" value="{{ $cc->id }}" class="w-4 h-4 text-blue-600 rounded">
                                                                <span class="text-xs font-mono font-bold text-gray-800 dark:text-gray-200 truncate">{{ $cc->codigo }} - {{ $cc->nome }}</span>
                                                            </label>
                                                            @if(in_array((string) $cc->id, $centrosCustoEdicaoSelecionados))
                                                                <div class="flex items-center justify-between gap-2 pt-1">
                                                                    <span class="text-[10px] text-gray-500">Expira em:</span>
                                                                    <input type="date" wire:model="expiracoesCentrosCustoEdicao.{{ $cc->id }}" class="text-[11px] rounded border-gray-300 py-0.5 px-1.5">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Aprovação CC --}}
                                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3">
                                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">2. Centros de Custo para APROVAÇÃO</span>
                                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                    {{ $acessoGlobalAprovacao ? 'TODOS (GLOBAL)' : count($centrosCustoAprovacaoSelecionados) . ' sel.' }}
                                                </span>
                                            </div>

                                            <label class="flex items-center gap-2 cursor-pointer p-2 rounded bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                                <input type="checkbox" wire:model.live="acessoGlobalAprovacao" class="w-4 h-4 text-emerald-600 rounded">
                                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Permissão Global para Aprovação (Todos os C.C.)</span>
                                            </label>

                                            @if(!$acessoGlobalAprovacao)
                                                <div class="max-h-48 overflow-y-auto custom-scrollbar space-y-1.5 pr-1">
                                                    @foreach($todosCentrosCusto as $cc)
                                                        <div wire:key="cc-ap-{{ $cc->id }}" class="p-2 rounded border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 space-y-1">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="checkbox" wire:model.live="centrosCustoAprovacaoSelecionados" value="{{ $cc->id }}" class="w-4 h-4 text-emerald-600 rounded">
                                                                <span class="text-xs font-mono font-bold text-gray-800 dark:text-gray-200 truncate">{{ $cc->codigo }} - {{ $cc->nome }}</span>
                                                            </label>
                                                            @if(in_array((string) $cc->id, $centrosCustoAprovacaoSelecionados))
                                                                <div class="flex items-center justify-between gap-2 pt-1">
                                                                    <span class="text-[10px] text-gray-500">Expira em:</span>
                                                                    <input type="date" wire:model="expiracoesCentrosCustoAprovacao.{{ $cc->id }}" class="text-[11px] rounded border-gray-300 py-0.5 px-1.5">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- ========================================== --}}
                            {{-- PASSO 3: PERMISSÕES EXTRAS                 --}}
                            {{-- ========================================== --}}
                            @if($etapaAtual === 3)
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Permissões Extras (Avulsas)</h4>
                                            <p class="text-xs text-gray-500">Conceda permissões específicas além daquelas já incluídas no grupo <strong>{{ strtoupper($roleName ?: 'N/D') }}</strong>.</p>
                                        </div>

                                        <div class="w-full sm:w-72 relative">
                                            <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                                            <input type="text" wire:model.live.debounce.300ms="buscaPermissaoExtra" placeholder="Filtrar permissão ou módulo..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 max-h-[55vh] overflow-y-auto custom-scrollbar pr-1">
                                        @forelse($permissionsByModule as $module => $permissions)
                                            <div class="p-3.5 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700 flex flex-col">
                                                <h5 class="mb-2.5 font-bold text-gray-700 dark:text-gray-200 uppercase border-b border-gray-200 dark:border-gray-700 pb-1.5 text-[11px] flex items-center justify-between">
                                                    <span>{{ $module ?: 'GERAL' }}</span>
                                                    <span class="text-[10px] text-gray-400">{{ count($permissions) }}</span>
                                                </h5>
                                                <div class="space-y-2">
                                                    @foreach($permissions as $permission)
                                                        @if(in_array($permission->name, ['financeiro.visao_global', 'financeiro.visao_global.edicao', 'financeiro.visao_global.aprovacao']))
                                                            @continue
                                                        @endif
                                                        @php
                                                            $isInherited = in_array((int) $permission->id, $rolePermissionsIds);
                                                            $isExtraChecked = in_array((string) $permission->id, array_map('strval', $selectedExtraPermissions));
                                                        @endphp
                                                        
                                                        <div class="flex flex-col gap-1 p-2.5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg {{ $isInherited ? 'opacity-70' : '' }}">
                                                            <label class="flex items-start gap-2 {{ $isInherited ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                                                @if($isInherited)
                                                                    <input type="checkbox" checked disabled class="w-4 h-4 mt-0.5 text-purpura-500 border-gray-300 rounded">
                                                                @else
                                                                    <input type="checkbox" wire:model.live="selectedExtraPermissions" value="{{ $permission->id }}" class="w-4 h-4 mt-0.5 text-purpura-600 border-gray-300 rounded">
                                                                @endif

                                                                <div class="flex flex-col min-w-0 flex-1">
                                                                    <span class="text-xs font-bold text-gray-900 dark:text-white flex items-center justify-between gap-1">
                                                                        <span class="truncate">{{ $permission->name }}</span>
                                                                        @if($isInherited)
                                                                            <span class="px-1.5 py-0.2 text-[8px] font-bold text-purpura-700 bg-purpura-100 rounded border border-purpura-200 uppercase shrink-0">Herdada</span>
                                                                        @endif
                                                                    </span>
                                                                    @if($permission->description)
                                                                        <span class="text-[10px] text-gray-500 leading-tight mt-0.5">{{ $permission->description }}</span>
                                                                    @endif
                                                                </div>
                                                            </label>
                                                            
                                                            @if(!$isInherited && $isExtraChecked)
                                                                <div class="pt-1.5 mt-1 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                                                                    <label class="text-[9px] font-bold uppercase text-gray-500">Válido até:</label>
                                                                    <input type="date" wire:model="extraPermissionExpirations.{{ $permission->id }}" class="w-full text-[11px] py-0.5 px-1.5 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-span-full text-center text-gray-400 py-8 text-xs">
                                                Nenhuma permissão encontrada.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                        </div>

                        {{-- BOTÕES DE RODAPÉ DO WIZARD EM TELA CHEIA --}}
                        <div class="max-w-5xl mx-auto w-full pt-5 mt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center shrink-0">
                            <button type="button" wire:click="passoAnterior" @if($etapaAtual === 1) disabled @endif class="btn btn--secondary btn--medium disabled:opacity-40">
                                <i class="ph ph-arrow-left"></i> Anterior
                            </button>

                            <div class="flex items-center gap-2">
                                @if($isEditMode && $etapaAtual < 3)
                                    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn--secondary btn--medium">
                                        Salvar Direto
                                    </button>
                                @endif

                                @if($etapaAtual < 3)
                                    <button type="button" wire:click="proximoPasso" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                                        Próximo Passo <i class="ph ph-arrow-right"></i>
                                    </button>
                                @else
                                    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                                        <i class="ph ph-floppy-disk"></i> {{ $isEditMode ? 'Salvar Alterações' : 'Concluir Cadastro' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    @endif
</div>