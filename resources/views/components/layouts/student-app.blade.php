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

    <script>
        if (localStorage.getItem('tema_sistema') === 'dark' || (!('tema_sistema' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="h-full antialiased text-gray-900 transition-colors duration-300 bg-slate-50 dark:bg-gray-900 dark:text-gray-100">
    
    <div x-data="{ drawerOpen: false }">
        
        <!-- TOPBAR ESCURA -->
        <div class="bg-[#2b0940] border-b border-white/10 relative z-40 dark:bg-gray-950 dark:border-gray-800 transition-colors duration-300">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <div class="flex items-center gap-5">
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 dark:text-gray-300 dark:hover:bg-gray-800 focus:outline-none transition-colors">
                            <i class="text-2xl ph ph-list"></i>
                        </button>
                        
                        <div class="flex-shrink-0 flex items-center gap-4">
                            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                            <span class="hidden sm:inline-block px-3 py-1.5 text-[10px] font-bold tracking-widest text-white uppercase rounded bg-[#461a63] border border-white/10 shadow-inner">
                                Aluno
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 sm:gap-6 text-white dark:text-gray-200">
                        @feature('sistema.tema')
                            <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alternar Tema">
                                <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                            </button>
                        @endfeature
                        
                        <a href="{{ route('student.profile') }}" class="hidden sm:flex items-center gap-1.5 hover:text-ponkan-400 transition-colors">
                            <span class="text-sm font-medium opacity-90">Olá,</span>
                            <span class="text-sm font-bold">{{ auth('student')->user()->name }}</span>
                        </a>
                        
                        <livewire:portal.auth.logout-button />
                    </div>

                </div>
            </div>
        </div>

        <!-- BOTTOMBAR BRANCA (DESKTOP) -->
        <nav class="hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-30 transition-colors duration-300">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center h-12 gap-1 lg:gap-2">
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                        <i class="text-lg ph ph-books"></i> Meus Cursos
                    </a>
                    
                    <a href="{{ route('avaliacoes.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                        <i class="text-lg ph ph-clipboard-text"></i> Avaliações
                    </a>

                    @feature('alunos.biblioteca')
                        <a href="{{ route('student.library') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                            <i class="text-lg ph ph-library"></i> Biblioteca
                        </a>
                    @endfeature

                    <a href="{{ route('student.profile') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400 ml-auto">
                        <i class="text-lg ph ph-user-circle"></i> Meu Perfil
                    </a>
                </div>
            </div>
        </nav>

        <!-- DRAWER MOBILE -->
        <div x-show="drawerOpen" x-transition.opacity.duration.300ms @click="drawerOpen = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" x-cloak></div>

        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="relative flex-shrink-0 h-40 overflow-hidden bg-[#2b0940]">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                <div class="absolute flex items-center gap-3 bottom-4 left-4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth('student')->user()->name) }}&background=fff&color=2b0940&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                    <div class="text-white">
                        <div class="font-bold leading-tight truncate w-44">{{ auth('student')->user()->name }}</div>
                        <div class="text-xs text-white/80 truncate w-44">{{ auth('student')->user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-books"></i> Meus Cursos
                </a>
                <a href="{{ route('avaliacoes.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-clipboard-text"></i> Avaliações
                </a>
                @feature('alunos.biblioteca')
                    <a href="{{ route('student.library') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                        <i class="text-lg ph ph-library"></i> Biblioteca
                    </a>
                @endfeature
                <a href="{{ route('student.profile') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-user-circle"></i> Meu Perfil
                </a>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <button @click="drawerOpen = false" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-300">Fechar</button>
                <livewire:portal.auth.logout-button cssClass="px-4 py-2 w-full text-sm font-bold text-white bg-red-500 rounded-lg hover:bg-red-600 shadow-sm text-center" />
            </div>
        </div>
    </div>

    <main class="py-10">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    <livewire:components.quick-view-drawer />
    @livewireScripts
    <x-toast />
</body>
</html>