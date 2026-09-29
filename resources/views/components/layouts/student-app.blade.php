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

<body class="h-full antialiased text-gray-900 bg-slate-50 dark:bg-gray-900 dark:text-gray-100 overflow-hidden">
    
    <div x-data="{ 
        drawerOpen: false, 
        layoutMode: localStorage.getItem('layoutMode') || 'top',
        sidebarMinimized: localStorage.getItem('sidebarMinimized') === 'true',
        toggleLayout() {
            this.layoutMode = this.layoutMode === 'top' ? 'left' : 'top';
            localStorage.setItem('layoutMode', this.layoutMode);
        },
        toggleMinimize() {
            this.sidebarMinimized = !this.sidebarMinimized;
            localStorage.setItem('sidebarMinimized', this.sidebarMinimized);
        }
    }" class="flex h-screen w-full overflow-hidden transition-all duration-300">
        
        {{-- DESKTOP: SIDEBAR VERTICAL (Transição de Largura) --}}
        <aside class="hidden md:flex flex-col bg-petunia-900 dark:bg-petunia-1000 transition-all duration-300 ease-in-out z-50 shrink-0 shadow-lg overflow-x-hidden" 
               :class="layoutMode === 'left' ? (sidebarMinimized ? 'w-[72px]' : 'w-64') : 'w-0 opacity-0'">
            
            <div class="h-16 flex items-center justify-between px-4 border-b border-white/10 shrink-0 min-w-[72px]">
                <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap" x-show="!sidebarMinimized" x-transition.opacity.duration.300ms>
                    <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                </div>
                <button @click="toggleMinimize()" class="p-1.5 text-white/70 hover:bg-white/10 rounded-lg transition-colors shrink-0" :class="sidebarMinimized ? 'mx-auto' : ''" title="Recolher Menu">
                    <i class="text-xl ph ph-list"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar py-4 space-y-2 px-3 min-w-[72px]">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors group relative whitespace-nowrap" :class="sidebarMinimized ? 'justify-center' : ''">
                    <i class="text-xl ph ph-books shrink-0"></i>
                    <span x-show="!sidebarMinimized" x-transition.opacity>Meu Painel</span>
                    <div x-show="sidebarMinimized" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">Meu Painel</div>
                </a>

                <a href="{{ route('student.solicitacoes') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors group relative whitespace-nowrap" :class="sidebarMinimized ? 'justify-center' : ''">
                    <i class="text-xl ph ph-envelope-open shrink-0"></i>
                    <span x-show="!sidebarMinimized" x-transition.opacity>Minhas Solicitações</span>
                    <div x-show="sidebarMinimized" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">Minhas Solicitações</div>
                </a>
                
                @if(auth('student')->user()->matriculado)
                <a href="{{ route('avaliacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors group relative whitespace-nowrap" :class="sidebarMinimized ? 'justify-center' : ''">
                    <i class="text-xl ph ph-clipboard-text shrink-0"></i>
                    <span x-show="!sidebarMinimized" x-transition.opacity>Avaliações</span>
                    <div x-show="sidebarMinimized" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">Avaliações</div>
                </a>
                @endif
                
                <a href="{{ route('portal.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors group relative whitespace-nowrap" :class="sidebarMinimized ? 'justify-center' : ''">
                    <i class="text-xl ph ph-newspaper shrink-0"></i>
                    <span x-show="!sidebarMinimized" x-transition.opacity>Portal Editorial</span>
                    <div x-show="sidebarMinimized" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">Portal Editorial</div>
                </a>
            </div>
        </aside>

        {{-- ÁREA PRINCIPAL --}}
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">
            
            <header class="bg-petunia-900 border-b border-white/10 relative z-40 dark:bg-petunia-1000 transition-colors duration-300 shrink-0 h-16 w-full">
                <div class="px-4 mx-auto w-full h-full">
                    <div class="flex items-center justify-between h-full w-full">
                        
                        <div class="flex items-center gap-5">
                            <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 focus:outline-none transition-colors">
                                <i class="text-2xl ph ph-list"></i>
                            </button>
                            
                            <div class="flex-shrink-0 items-center gap-4 hidden md:flex transition-all duration-300" :class="layoutMode === 'left' ? 'w-0 opacity-0 overflow-hidden' : 'w-auto opacity-100'">
                                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                            </div>
                        </div>

                        <div class="flex items-center gap-4 sm:gap-5 text-white dark:text-gray-200 ml-auto">
                            @feature('sistema.tema')
                                <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alternar Tema">
                                    <i class="text-lg ph ph-moon" x-show="tema === 'light'"></i>
                                    <i class="text-lg ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                </button>
                            @endfeature
                            
                            <button @click="toggleLayout()" class="hidden md:flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alterar Posição do Menu">
                                <i class="text-lg ph ph-layout" x-show="layoutMode === 'top'"></i>
                                <i class="text-lg ph ph-sidebar-simple" x-show="layoutMode === 'left'" x-cloak></i>
                            </button>
                            
                            <a href="{{ route('student.profile') }}" class="hidden sm:flex items-center gap-1.5 hover:text-ponkan-400 transition-colors shrink-0 ml-1">
                                <span class="text-sm font-medium opacity-90">Olá,</span>
                                <span class="text-sm font-bold">{{ explode(' ', auth('student')->user()->name)[0] }}</span>
                            </a>
                            
                            <livewire:portal.auth.logout-button />
                        </div>
                    </div>
                </div>
            </header>

            <nav class="hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-30 shrink-0 transition-all duration-300 ease-in-out origin-top"
                 :class="layoutMode === 'top' ? 'h-12 opacity-100' : 'h-0 opacity-0 overflow-hidden border-transparent'">
                <div class="px-4 mx-auto w-full">
                    <div class="flex items-center h-12 gap-1 lg:gap-2">
                        <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                            <i class="text-lg ph ph-books"></i> Meu Painel
                        </a>
                        
                        <a href="{{ route('student.solicitacoes') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                            <i class="text-lg ph ph-envelope-open"></i> Minhas Solicitações
                        </a>

                        @if(auth('student')->user()->matriculado)
                        <a href="{{ route('avaliacoes.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                            <i class="text-lg ph ph-clipboard-text"></i> Avaliações
                        </a>
                        @endif

                        <a href="{{ route('portal.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400">
                            <i class="text-lg ph ph-newspaper"></i> Portal Editorial
                        </a>

                        <a href="{{ route('student.profile') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-ponkan-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-ponkan-400 ml-auto">
                            <i class="text-lg ph ph-user-circle"></i> Meu Perfil
                        </a>
                    </div>
                </div>
            </nav>

            <main class="flex-1 w-full overflow-y-auto bg-slate-50 dark:bg-gray-900 custom-scrollbar relative z-10">
                <div class="w-full h-full py-6">
                    <div class="px-4 mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        {{-- MENU MOBILE (DRAWER) --}}
        <div x-show="drawerOpen" x-transition.opacity.duration.300ms @click="drawerOpen = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" x-cloak></div>

        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="relative flex-shrink-0 h-40 overflow-hidden bg-petunia-900">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                <div class="absolute flex items-center gap-3 bottom-4 left-4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth('student')->user()->name) }}&background=fff&color=310B47&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                    <div class="text-white">
                        <div class="font-bold leading-tight truncate w-44">{{ auth('student')->user()->name }}</div>
                        <div class="text-xs text-white/80 truncate w-44">{{ auth('student')->user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-books"></i> Meu Painel
                </a>
                <a href="{{ route('student.solicitacoes') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-envelope-open"></i> Minhas Solicitações
                </a>
                @if(auth('student')->user()->matriculado)
                <a href="{{ route('avaliacoes.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-clipboard-text"></i> Avaliações
                </a>
                @endif
                <a href="{{ route('portal.index') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-bold text-gray-700 rounded-lg dark:text-gray-200 hover:bg-orange-50 hover:text-ponkan-600 dark:hover:bg-gray-700">
                    <i class="text-lg ph ph-newspaper"></i> Portal Editorial
                </a>
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

    <livewire:components.quick-view-drawer />
    @livewireScripts
    <x-toast />
</body>
</html>