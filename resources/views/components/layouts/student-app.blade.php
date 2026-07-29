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
    <title>{{ $title ?? 'Portal do Aluno' }}</title>

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

<body class="h-full antialiased text-gray-900 transition-colors duration-500 bg-slate-50 dark:bg-gray-900 dark:text-gray-100">
    
    <!-- Wrapper do Menu com estado do AlpineJS -->
    <div x-data="{ drawerOpen: false }">
        
        <!-- Navbar Superior (com transição suave) -->
        <nav class="transition-colors duration-500 bg-purpura-600 border-b border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Lado Esquerdo (Logo e Botão Hamburger Mobile) -->
                    <div class="flex items-center gap-4">
                        <!-- Botão Hambúrguer (Oculto no Desktop graças ao 'md:hidden') -->
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="text-2xl ph ph-list"></i>
                        </button>
                        
                        <!-- Logo / Título -->
                        <div class="flex-shrink-0 flex items-center">
                            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-10 w-auto" alt="Instituto Percorre">
                            <span class="ml-3 text-[10px] uppercase tracking-wider bg-purpura-700 text-purpura-100 px-2 py-1 rounded hidden sm:inline-block border border-purpura-600">
                                Aluno
                            </span>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- VISÃO DESKTOP (Menu, Tema e Perfil) -->
                    <!-- ========================================== -->
                    <div class="items-center hidden gap-6 md:flex">
                        
                        <!-- Links Centrais -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('student.dashboard') }}" class="px-3 py-2 text-sm font-medium text-white transition-colors rounded-md dark:text-gray-200 hover:bg-white/10 dark:hover:bg-gray-800">
                                Meus Cursos
                            </a>
                            
                            @feature('alunos.biblioteca')
                                <a href="{{ route('student.library') }}" class="px-3 py-2 text-sm font-medium text-white transition-colors rounded-md dark:text-gray-200 hover:bg-white/10 dark:hover:bg-gray-800">
                                    Biblioteca
                                </a>
                            @endfeature

                            <a href="{{ route('student.profile') }}" class="px-3 py-2 text-sm font-medium text-white transition-colors rounded-md dark:text-gray-200 hover:bg-white/10 dark:hover:bg-gray-800">
                                Meu Perfil
                            </a>
                        </div>

                        <!-- Divisor mais sutil -->
                        <div class="w-px h-6 bg-purpura-400 dark:bg-gray-700"></div>
                        
                        <!-- Controles da Conta Desktop -->
                        <div class="flex items-center gap-4 text-white dark:text-gray-200">
                            @feature('sistema.tema')
                                <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 transition-colors rounded-full hover:bg-white/10 dark:bg-gray-800 dark:hover:bg-gray-700" title="Alternar Tema">
                                    <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                    <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                </button>
                            @endfeature

                            <span class="text-sm transition-colors">
                                Olá, <strong>{{ auth('student')->user()->name }}</strong>
                            </span>
                            
                            <livewire:student.auth.logout-button />
                        </div>
                    </div>
                    
                    <!-- ========================================== -->
                    <!-- VISÃO MOBILE (Apenas o Botão de Tema) -->
                    <!-- ========================================== -->
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
        
        <!-- Overlay Escuro -->
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
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth('student')->user()->name) }}&background=fff&color=9B26B6&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                    <div class="text-white">
                        <div class="font-bold leading-tight truncate w-44">{{ auth('student')->user()->name }}</div>
                        <div class="text-xs text-white/80 truncate w-44">{{ auth('student')->user()->email }}</div>
                    </div>
                </div>
            </div>

            <!-- Links do Menu Mobile -->
            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-books"></i> Meus Cursos
                </a>

                @feature('alunos.biblioteca')
                    <a href="{{ route('student.library') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                        <i class="text-lg ph ph-books"></i> Biblioteca
                    </a>
                @endfeature

                <a href="{{ route('student.profile') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-user"></i> Meu Perfil
                </a>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                <livewire:student.auth.logout-button />
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