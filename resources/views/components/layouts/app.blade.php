<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ tema: localStorage.getItem('tema_sistema') || 'light' }" 
      x-init="$watch('tema', valor => localStorage.setItem('tema_sistema', valor))"
      :class="tema === 'dark' ? 'dark h-full bg-gray-900' : 'h-full bg-slate-50'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistema' }}</title>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="h-full text-gray-900 antialiased">
    <!-- Wrapper do Menu com estado do AlpineJS -->
    <div x-data="{ drawerOpen: false }">
        
        <!-- ========================================== -->
        <!-- NAVBAR SUPERIOR -->
        <!-- ========================================== -->
        <nav class="bg-white border-b border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Lado Esquerdo (Logo e Botão Hamburger Mobile) -->
                    <div class="flex items-center gap-4">
                        <!-- Botão Hambúrguer (Oculto no Desktop graças ao 'md:hidden') -->
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-gray-600 rounded-md md:hidden hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="text-2xl ph ph-list"></i>
                        </button>
                        
                        <!-- Logo / Título -->
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 text-white rounded-lg bg-purpura-500">
                                <span class="font-bold">S</span>
                            </div>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">Sistema</span>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- VISÃO DESKTOP (Menu, Dropdown, Tema e Perfil) -->
                    <!-- Oculto no Mobile graças ao 'hidden md:flex' -->
                    <!-- ========================================== -->
                    <div class="items-center hidden gap-6 md:flex">
                        
                        <!-- Links Centrais -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors rounded-md dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Dashboard
                            </a>
                            
                            @feature('turno')
                                @can('turno.listar')
                                    <a href="{{ route('turnos.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors rounded-md dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Turnos
                                    </a>
                                @endcan
                            @endfeature

                            @feature('unidade')
                                @can('unidade.listar')
                                    <a href="{{ route('unidades.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors rounded-md dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Unidades
                                    </a>
                                @endcan
                            @endfeature
                            
                            <!-- Dropdown de Configurações Administrativas -->
                            @role('dev|admin')
                                <div class="relative" x-data="{ menuOpen: false }">
                                    <button @click="menuOpen = !menuOpen" @click.outside="menuOpen = false" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 transition-colors rounded-md dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none">
                                        <span>Engrenagens</span>
                                        <i class="transition-transform duration-200 ph ph-caret-down text-xs" :class="menuOpen ? 'rotate-180' : ''"></i>
                                    </button>

                                    <!-- Menu Flutuante -->
                                    <div x-show="menuOpen" 
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute right-0 z-50 w-48 py-2 mt-2 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700"
                                        x-cloak>
                                        
                                        <div class="px-4 py-2 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                                            Acessos
                                        </div>
                                        <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-purpura-500 dark:hover:text-purpura-400">
                                            <i class="ph ph-users"></i> Usuários
                                        </a>
                                        <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-purpura-500 dark:hover:text-purpura-400">
                                            <i class="ph ph-shield-check"></i> Roles (Grupos)
                                        </a>

                                        @role('dev')
                                            <div class="h-px my-2 bg-gray-100 dark:bg-gray-700"></div>
                                            <div class="px-4 py-2 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                                                Sistema
                                            </div>
                                            <a href="{{ route('permissions.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-purpura-500 dark:hover:text-purpura-400">
                                                <i class="ph ph-key"></i> Permissões
                                            </a>
                                            <a href="{{ route('features.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-purpura-500 dark:hover:text-purpura-400">
                                                <i class="ph ph-toggle-right"></i> Feature Toggles
                                            </a>
                                        @endrole
                                    </div>
                                </div>
                            @endrole
                        </div>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
                        
                        <!-- Controles da Conta Desktop -->
                        <div class="flex items-center gap-4 text-gray-700 dark:text-gray-200">
                            @feature('sistema.tema')
                                <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 transition-colors rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700" title="Alternar Tema">
                                    <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                    <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                </button>
                            @endfeature

                            <span class="text-sm">Olá, <strong>{{ auth()->user()->name }}</strong></span>
                            
                            <livewire:auth.logout-button />
                        </div>
                    </div>
                    
                    <!-- ========================================== -->
                    <!-- VISÃO MOBILE (Apenas o Botão de Tema) -->
                    <!-- Oculto no Desktop graças ao 'md:hidden' -->
                    <!-- ========================================== -->
                    <div class="flex md:hidden">
                        @feature('sistema.tema')
                            <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 text-gray-600 rounded-full dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
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
        
        <!-- Overlay Escuro (Fica atrás do menu) -->
        <div x-show="drawerOpen" 
            x-transition.opacity.duration.300ms 
            @click="drawerOpen = false"
            class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" 
            x-cloak>
        </div>

        <!-- O Drawer Deslizante -->
        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden"
            :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Header do Drawer -->
            <div class="relative flex-shrink-0 h-40 overflow-hidden bg-gradient-to-br from-petunia-900 to-purpura-500">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                <div class="absolute flex items-center gap-3 bottom-4 left-4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=fff&color=9B26B6&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                    <div class="text-white">
                        <div class="font-bold leading-tight truncate w-44">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-white/80 truncate w-44">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            </div>

            <!-- Links do Menu Mobile -->
            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-house"></i> Dashboard
                </a>

                @feature('turno')
                    @can('turno.listar')
                        <a href="{{ route('turnos.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <i class="text-lg ph ph-clock"></i> Turnos
                        </a>
                    @endcan
                @endfeature

                @feature('unidade')
                    @can('unidade.listar')
                        <a href="{{ route('unidades.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <i class="text-lg ph ph-map-pin"></i> Unidades
                        </a>
                    @endcan
                @endfeature

                @role('dev|admin')
                    <div class="pt-4 pb-1 mt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="px-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Administração</p>
                    </div>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                        <i class="text-lg ph ph-users"></i> Usuários
                    </a>
                    <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                        <i class="text-lg ph ph-shield-check"></i> Roles (Grupos)
                    </a>

                    @role('dev')
                        <a href="{{ route('permissions.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <i class="text-lg ph ph-key"></i> Permissões
                        </a>
                        <a href="{{ route('features.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <i class="text-lg ph ph-toggle-right"></i> Feature Toggles
                        </a>
                    @endrole
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

    @livewireScripts
</body>
</html>