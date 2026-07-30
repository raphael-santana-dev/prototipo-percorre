<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ tema: localStorage.getItem('tema_sistema') || 'light' }" 
      x-init="$watch('tema', valor => localStorage.setItem('tema_sistema', valor))"
      :class="{ 'dark': tema === 'dark' }"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistema' }}</title>
    
    <!-- Script Bloqueante: Evita a piscada branca (FOUC) antes do AlpineJS carregar -->
    <script>
        if (localStorage.getItem('tema_sistema') === 'dark' || (!('tema_sistema' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<!-- O background e a transição suave foram movidos para o body -->
<body class="h-full antialiased text-gray-900 transition-colors duration-500 bg-slate-50 dark:bg-gray-900 dark:text-gray-100">
    
    <div x-data="{ drawerOpen: false }">
        
        <nav class="transition-colors duration-500 bg-purpura-600 border-b border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Lado Esquerdo -->
                    <div class="flex items-center gap-4">
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="text-2xl ph ph-list"></i>
                        </button>
                        
                        <div class="flex-shrink-0 flex items-center">
                            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-10 w-auto" alt="Instituto Percorre">
                            <span class="ml-3 text-[10px] uppercase tracking-wider bg-purpura-700 text-purpura-100 px-2 py-1 rounded hidden sm:inline-block border border-purpura-600">
                                {{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}
                            </span>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- VISÃO DESKTOP (Menu Agrupado e Minimalista) -->
                    <!-- ========================================== -->
                    <div class="items-center hidden md:flex">
                        
                        <!-- LINKS AGRUPADOS -->
                        <div class="flex items-center gap-2 lg:gap-4">
                            
                            <!-- 1. Dashboard -->
                            @feature('dashboard')
                                @can('dashboard.visualizar')
                                    <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-white/90 transition-colors rounded-md hover:bg-white/10 hover:text-white">
                                        Dashboard
                                    </a>
                                @endcan
                            @endfeature

                            <!-- 2. Processos Seletivos (Dropdown) -->
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-white/90 transition-colors rounded-md hover:bg-white/10 hover:text-white">
                                    Processos Seletivos <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    <a href="{{ route('ciclos.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ciclos de Ingresso</a>
                                    <a href="{{ route('ciclos.etapas') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Etapas do Funil</a>
                                </div>
                            </div>

                            <!-- 3. Secretaria (Dropdown) -->
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-white/90 transition-colors rounded-md hover:bg-white/10 hover:text-white">
                                    Secretaria <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    <a href="{{ route('inscricoes.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Fichas de Inscrição</a>
                                    
                                    @feature('estudantes')
                                        @can('estudante.listar')
                                            <a href="{{ route('students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Base de Alunos</a>
                                        @endcan
                                    @endfeature
                                    
                                    <a href="{{ route('status-inscricoes.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tags de Status</a>
                                </div>
                            </div>

                            <!-- 4. Instituição (Dropdown) -->
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-white/90 transition-colors rounded-md hover:bg-white/10 hover:text-white">
                                    Instituição <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    <a href="{{ route('cursos.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Portfólio de Cursos</a>
                                    <a href="{{ route('turnos.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Grade de Turnos</a>
                                    <a href="{{ route('unidades.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Unidades Sede</a>
                                </div>
                            </div>

                            <!-- 5. Configurações (Restrito) -->
                            @role('dev|admin')
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-white/90 transition-colors rounded-md hover:bg-white/10 hover:text-white">
                                    Engrenagens <i class="ph ph-gear text-sm"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute right-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    
                                    @feature('usuarios')
                                        @can('usuario.listar')
                                            <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gestão de Usuários</a>
                                        @endcan
                                    @endfeature
                                    
                                    @feature('roles')
                                        @can('role.listar')
                                            <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Perfis (Roles)</a>
                                        @endcan
                                    @endfeature

                                    @role('dev')
                                        <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                        @feature('permissoes')
                                            @can('permissao.listar')
                                                <a href="{{ route('permissions.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tabela de Permissões</a>
                                            @endcan
                                        @endfeature
                                        
                                        @feature('features')
                                            @can('feature.listar')
                                                <a href="{{ route('features.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Feature Toggles</a>
                                            @endcan
                                        @endfeature
                                    @endrole
                                </div>
                            </div>
                            @endrole
                        </div>

                        <!-- DIVISOR E LOGOUT MINIMALISTA -->
                        <div class="flex items-center gap-3 pl-4 ml-2 border-l border-white/20">
                            
                            @feature('sistema.tema')
                                <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-700">
                                    <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                    <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                </button>
                            @endfeature
                            
                            <a href="{{ route('profile.show') }}" class="flex flex-col text-right text-white hover:opacity-80 transition-opacity">
                                <span class="text-[10px] font-medium opacity-80 uppercase tracking-widest">Olá,</span>
                                <span class="text-sm font-bold leading-tight">{{ auth()->user()->name }}</span>
                            </a>
                            
                            <!-- Botão de Logout substituído por ação Alpine+Livewire minimalista -->
                            <button x-data @click="$dispatch('logout')" title="Sair do Sistema" class="flex items-center justify-center p-2 ml-1 text-white/70 transition-colors rounded-full hover:bg-red-500/20 hover:text-red-400">
                                <i class="text-2xl ph ph-power"></i>
                            </button>
                        </div>

                    </div>
                    
                    <!-- Lado Direito Mobile -->
                    <div class="flex md:hidden">
                        @feature('sistema.tema')
                            <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 text-white/90 rounded-full dark:text-gray-300 hover:bg-white/10 dark:hover:bg-gray-700">
                                <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                            </button>
                        @endfeature
                    </div>

                </div>
            </div>
        </nav>

        <!-- ========================================== -->
        <!-- NAVIGATION DRAWER (MOBILE) -->
        <!-- ========================================== -->
        <div x-show="drawerOpen" x-transition.opacity.duration.300ms @click="drawerOpen = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" x-cloak></div>

        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="relative flex-shrink-0 h-40 overflow-hidden bg-gradient-to-br from-petunia-900 to-purpura-500">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                <div class="absolute flex items-center gap-3 bottom-4 left-4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=fff&color=9B26B6&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                    <div class="text-white">
                        <a href="{{ route('profile.show') }}" class="text-white block hover:opacity-80 transition-opacity">
                            <div class="font-bold leading-tight truncate w-44">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-white/80 truncate w-44">{{ auth()->user()->email }}</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- O menu mobile também poderia ser agrupado por categorias visuais para facilitar a rolagem -->
            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <p class="px-3 pt-2 pb-1 text-xs font-bold tracking-wider text-gray-400 uppercase">Principal</p>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-house"></i> Dashboard</a>
                
                <p class="px-3 pt-4 pb-1 mt-2 text-xs font-bold tracking-wider text-gray-400 uppercase border-t border-gray-100 dark:border-gray-700">Operacional</p>
                <a href="{{ route('inscricoes.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-files"></i> Inscrições</a>
                <a href="{{ route('ciclos.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-calendar"></i> Ciclos</a>
                <a href="{{ route('cursos.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-graduation-cap"></i> Cursos</a>
                
                @role('dev|admin')
                    <p class="px-3 pt-4 pb-1 mt-2 text-xs font-bold tracking-wider text-gray-400 uppercase border-t border-gray-100 dark:border-gray-700">Administração</p>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-users"></i> Usuários</a>
                    <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-shield-check"></i> Perfis</a>
                @endrole
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                <livewire:auth.logout-button />
            </div>
        </div>
    </div>

    <!-- Conteúdo da Página -->
    <main class="py-10">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>
    <livewire:components.quick-view-drawer />
    
    @livewireScripts

</body>
</html>