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
    
    <nav class="bg-white border-b border-gray-200">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 font-bold text-white bg-blue-600 rounded-lg">S</div>
                        <span class="font-semibold text-gray-800">Sistema</span>
                    </div>
                    <!-- Menu de Navegação -->
                    <div class="hidden md:flex md:items-center md:gap-2">
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
                        @role('dev|admin') <!-- Aparece se for dev OU admin -->
                            <div class="relative" x-data="{ menuOpen: false }">
                                <button @click="menuOpen = !menuOpen" @click.outside="menuOpen = false" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 transition-colors rounded-md dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none">
                                    <span>Engrenagens</span>
                                    <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="menuOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <!-- Menu Flutuante com Animação -->
                                <div x-show="menuOpen" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 z-50 w-48 py-2 mt-2 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700"
                                    x-cloak>
                                    
                                    <!-- Título Interno do Dropdown -->
                                    <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider dark:text-gray-500">
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
                                        <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider dark:text-gray-500">
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
                </div>
                <div class="flex items-center gap-4">
                    <!-- Botão de Tema (Aparece apenas se a Feature estiver ligada) -->
                    @feature('sistema.tema')
                        <button @click="tema = tema === 'light' ? 'dark' : 'light'" 
                                class="p-2 transition-colors rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700" 
                                title="Alternar Tema">
                            <!-- Ícone de Lua (Aparece no tema Light) -->
                            <i class="ph ph-moon text-xl" x-show="tema === 'light'"></i>
                            <!-- Ícone de Sol (Aparece no tema Dark) -->
                            <i class="ph ph-sun text-xl text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                        </button>
                    @endfeature
                    <span class="text-sm text-gray-500">Logado como: <strong>{{ auth()->user()->name }}</strong></span>
                    <div class="w-px h-6 bg-gray-200"></div>
                    <livewire:auth.logout-button />
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo da Página -->
    <main class="py-10">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>