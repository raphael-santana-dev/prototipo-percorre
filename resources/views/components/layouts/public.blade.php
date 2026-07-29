<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ tema: localStorage.getItem('tema_sistema') || 'light' }" 
      x-init="$watch('tema', valor => localStorage.setItem('tema_sistema', valor))"
      :class="tema === 'dark' ? 'dark h-full bg-gray-900' : 'h-full bg-slate-50'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Instituto Percorre' }}</title>

    <!-- Script Bloqueante: Evita a piscada branca (FOUC) antes do AlpineJS carregar -->
    <script>
        if (localStorage.getItem('tema_sistema') === 'dark' || (!('tema_sistema' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen text-gray-900 transition-colors duration-500 bg-slate-50 dark:text-gray-100 dark:bg-gray-900 antialiased">
    
    <!-- Wrapper do Menu AlpineJS -->
    <div x-data="{ drawerOpen: false }">
        
        <!-- Navbar Pública -->
        <header class="transition-colors duration-500 bg-white border-b border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 relative z-30">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Lado Esquerdo (Menu Mobile + Logo) -->
                    <div class="flex items-center gap-4">
                        <!-- Botão Hambúrguer (Oculto no Desktop) -->
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-gray-500 rounded-md md:hidden hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-none transition-colors">
                            <i class="text-2xl ph ph-list"></i>
                        </button>

                        <!-- Logo -->
                        <a href="/" class="flex items-center gap-3 transition-opacity hover:opacity-80">
                            <div class="flex items-center justify-center w-10 h-10 text-white rounded-xl bg-purpura-500 shadow-sm shrink-0">
                                <span class="text-xl font-bold">P</span>
                            </div>
                            <span class="text-2xl font-bold text-purpura-700 dark:text-purpura-400">Percorre</span>
                        </a>
                    </div>

                    <!-- Lado Direito (Ações Desktop) -->
                    <div class="hidden items-center gap-4 md:flex">
                        <!-- Botão de Tema -->
                        <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 text-gray-500 transition-colors rounded-full dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="text-2xl ph ph-moon" x-show="tema === 'light'"></i>
                            <i class="text-2xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                        </button>

                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>

                        <!-- Botões de Login -->
                        <div class="flex gap-3">
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700 transition-colors">
                                Acesso Restrito
                            </a>
                            <a href="{{ route('student.login') }}" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600 transition-colors">
                                <i class="mr-1 ph-bold ph-graduation-cap"></i> Sou Estudante
                            </a>
                        </div>
                    </div>

                    <!-- Lado Direito (Mobile - Apenas Tema) -->
                    <div class="flex md:hidden">
                        <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 text-gray-500 transition-colors rounded-full dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="text-2xl ph ph-moon" x-show="tema === 'light'"></i>
                            <i class="text-2xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                        </button>
                    </div>

                </div>
            </div>
        </header>

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

        <!-- Drawer Deslizante -->
        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden"
            :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Header do Drawer Público -->
            <div class="relative flex items-center gap-3 p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center justify-center w-10 h-10 text-white rounded-xl bg-purpura-500 shadow-sm shrink-0">
                    <span class="text-xl font-bold">P</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-purpura-700 dark:text-purpura-400">Percorre</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Portal Público</p>
                </div>
            </div>

            <!-- Links do Menu Mobile -->
            <div class="flex-1 px-4 py-6 space-y-4 overflow-y-auto">
                
                <a href="{{ route('login') }}" class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="text-lg ph ph-lock-key"></i> Acesso Restrito
                </a>
                
                <a href="{{ route('student.login') }}" class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600 transition-colors">
                    <i class="text-lg ph ph-graduation-cap"></i> Sou Estudante
                </a>

                <div class="pt-6 mt-6 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-center text-gray-500 dark:text-gray-400">
                        O Instituto Percorre oferece cursos e oportunidades para o seu desenvolvimento.
                    </p>
                </div>
            </div>
            
            <!-- Footer do Menu -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button @click="drawerOpen = false" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                    Fechar Menu
                </button>
            </div>
        </div>
    </div> <!-- Fim do Wrapper do Menu -->

    <!-- Conteúdo da Página -->
    <main class="flex-1 flex flex-col">
        {{ $slot }}
    </main>

    <!-- Rodapé Público -->
    <footer class="py-8 bg-white border-t border-gray-200 dark:bg-gray-900 dark:border-gray-800 transition-colors duration-500 mt-auto relative z-10">
        <div class="px-4 text-center text-gray-500 dark:text-gray-400 max-w-7xl mx-auto">
            <p class="text-sm font-medium">© {{ date('Y') }} Instituto Percorre. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Renderiza o QuickViewGlobal caso algum componente público decida usar -->
    <livewire:components.quick-view-drawer />
    @livewireScripts
</body>
</html>