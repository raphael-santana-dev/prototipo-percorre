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
        <title>{{ $title ?? 'Sistema Administrativo' }}</title>
        
        <script>
            if (localStorage.getItem('tema_sistema') === 'dark' || (!('tema_sistema' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script src="https://unpkg.com/@phosphor-icons/web"></script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>

    <body class="h-full antialiased text-gray-900 transition-colors duration-300 bg-slate-50 dark:bg-gray-900 dark:text-gray-100">
        
        <div x-data="{ drawerOpen: false }">
            
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
                                    {{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 sm:gap-6 text-white dark:text-gray-200">
                            
                            <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alternar Tema">
                                <i class="text-lg ph ph-moon" x-show="tema === 'light'"></i>
                                <i class="text-lg ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                            </button>
                            
                            <a href="{{ route('profile.show') }}" class="hidden sm:flex items-center gap-1.5 hover:text-purpura-300 transition-colors">
                                <span class="text-sm font-medium opacity-90">Olá,</span>
                                <span class="text-sm font-bold">{{ auth()->user()->name }}</span>
                            </a>
                            
                            <livewire:auth.logout-button />
                        </div>

                    </div>
                </div>
            </div>

            <nav class="hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-30 transition-colors duration-300">
                <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex items-center h-12 gap-1 lg:gap-2">
                        
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                            <i class="text-lg ph ph-squares-four"></i> Dashboard
                        </a>

                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-calendar-check"></i> Processos Seletivos <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                <a href="{{ route('ciclos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ciclos de Inscrição</a>
                                <a href="{{ route('ciclos.etapas') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Etapas de Formulário</a>
                                <a href="{{ route('inscricoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Fichas de Inscrição</a>
                            </div>
                        </div>

                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-folder-user"></i> Secretaria <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                <a href="{{ route('students.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Base de Alunos</a>
                                <a href="{{ route('status-inscricoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tags de Status</a>
                                <a href="{{ route('empresas.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Empresas Parceiras</a>
                            </div>
                        </div>

                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-buildings"></i> Instituição <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                <a href="{{ route('cursos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Portfólio de Cursos</a>
                                <a href="{{ route('turnos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Grade de Turnos</a>
                                <a href="{{ route('unidades.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Unidades</a>
                                <a href="{{ route('formularios.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Formulários</a>
                            </div>
                        </div>

                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-calendar-check"></i> Comunicação <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                <a href="{{ route('templates.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Templates</a>
                                <a href="{{ route('comunicados.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Comunicados</a>
                                <a href="{{ route('automacoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Automações</a>
                                <a href="{{ route('monitor.emails') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Agenda de e-mails</a>
                            </div>
                        </div>

                        @role('dev|admin')
                        <div x-data="{ open: false }" @click.away="open = false" class="relative ml-auto">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-gear"></i> Administração <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute right-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gestão de Usuários</a>
                                <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Perfis (Roles)</a>
                                <a href="{{ route('auditoria.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Auditoria</a>
                                <a href="{{ route('importacoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Importações</a>

                                @role('dev')
                                    <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                    <a href="{{ route('permissions.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tabela de Permissões</a>
                                    <a href="{{ route('features.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Feature Toggles</a>
                                @endrole
                            </div>
                        </div>
                        @endrole

                        @livewire(\App\Modules\Importacao\UI\Livewire\ImportProgress::class)
                    </div>
                </div>
            </nav>

            <div x-show="drawerOpen" x-transition.opacity.duration.300ms @click="drawerOpen = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" x-cloak></div>

            <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="relative flex-shrink-0 h-40 overflow-hidden bg-[#2b0940]">
                    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                    <div class="absolute flex items-center gap-3 bottom-4 left-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=fff&color=2b0940&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                        <div class="text-white">
                            <a href="{{ route('profile.show') }}" class="text-white block hover:opacity-80 transition-opacity">
                                <div class="font-bold leading-tight truncate w-44">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-white/80 truncate w-44">{{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}</div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <p class="px-3 pt-2 pb-1 text-xs font-bold tracking-wider text-gray-400 uppercase">Menu</p>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-squares-four"></i> Dashboard</a>

                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-calendar-check"></i> Processos Seletivos</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            <a href="{{ route('ciclos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Ciclos de Inscrição</a>
                            <a href="{{ route('ciclos.etapas') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Etapas de Formulário</a>
                            <a href="{{ route('inscricoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Fichas de Inscrição</a>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-folder-user"></i> Secretaria</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            <a href="{{ route('students.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Base de Alunos</a>
                            <a href="{{ route('status-inscricoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Tags de Status</a>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-buildings"></i> Instituição</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            <a href="{{ route('cursos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Portfólio de Cursos</a>
                            <a href="{{ route('turnos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Grade de Turnos</a>
                            <a href="{{ route('unidades.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Unidades</a>
                            <a href="{{ route('formularios.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Formulários</a>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-buildings"></i> Comunicação</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            <a href="{{ route('templates.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Templates</a>
                            <a href="{{ route('comunicados.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Comunicados</a>
                            <a href="{{ route('automacoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Automações</a>
                            <a href="{{ route('monitor.emails') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Agenda de e-mails</a>
                        </div>
                    </div>

                    <div class="px-3 py-2">
                        @livewire(\App\Modules\Importacao\UI\Livewire\ImportProgress::class)
                    </div>

                    @role('dev|admin')
                        <div x-data="{ open: false }" class="space-y-1">
                            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                                <span class="flex items-center gap-3"><i class="text-lg ph ph-gear"></i> Administração</span>
                                <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" class="pl-8 space-y-1" x-cloak>
                                <a href="{{ route('users.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Gestão de Usuários</a>
                                <a href="{{ route('roles.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Perfis (Roles)</a>
                                <a href="{{ route('permissions.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Permissões</a>
                                <a href="{{ route('importacoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Importações</a>

                                @role('dev')
                                    <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                    <a href="{{ route('permissions.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Tabela de Permissões</a>
                                    <a href="{{ route('features.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Feature Toggles</a>
                                @endrole
                            </div>
                        </div>
                    @endrole
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    <livewire:auth.logout-button />
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