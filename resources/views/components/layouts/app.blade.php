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
                                <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                                <i class="text-xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
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
                        
                        <!-- Dashboard (Geralmente aberto a todos logados) -->
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                            <i class="text-lg ph ph-squares-four"></i> Dashboard
                        </a>

                        <!-- Processos Seletivos -->
                        @canany(['ciclo.listar', 'etapa.listar', 'inscricao.listar'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-calendar-check"></i> Processos Seletivos <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                @can('ciclo.listar') <a href="{{ route('ciclos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ciclos de Inscrição</a> @endcan
                                @can('etapa.listar') <a href="{{ route('ciclos.etapas') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Etapas de Formulário</a> @endcan
                                @can('inscricao.listar') <a href="{{ route('inscricoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Fichas de Inscrição</a> @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Secretaria -->
                        @canany(['estudante.listar', 'status.listar'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-folder-user"></i> Secretaria <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                @can('estudante.listar') <a href="{{ route('students.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Base de Alunos</a> @endcan
                                @can('status.listar') <a href="{{ route('status-inscricoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tags de Status</a> @endcan
                                @role('dev') <a href="{{ route('empresas.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Empresas Parceiras</a> @endrole
                            </div>
                        </div>
                        @endcanany

                        <!-- Instituição -->
                        @canany(['curso.listar', 'turno.listar', 'unidade.listar', 'formulario.listar'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-buildings"></i> Instituição <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                @can('curso.listar') <a href="{{ route('cursos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Portfólio de Cursos</a> @endcan
                                @can('turno.listar') <a href="{{ route('turnos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Grade de Turnos</a> @endcan
                                @can('unidade.listar') <a href="{{ route('unidades.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Unidades</a> @endcan
                                @can('formulario.listar') <a href="{{ route('formularios.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Formulários</a> @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Comunicação -->
                        @canany(['template.listar', 'comunicado.listar', 'automacao.listar', 'email_log.listar'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-paper-plane-tilt"></i> Comunicação <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                @can('template.listar') <a href="{{ route('templates.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Templates</a> @endcan
                                @can('comunicado.listar') <a href="{{ route('comunicados.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Comunicados</a> @endcan
                                @can('automacao.listar') <a href="{{ route('automacoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Automações</a> @endcan
                                @can('email_log.listar') <a href="{{ route('monitor.emails') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Agenda de e-mails</a> @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Educacional (Aninhado) -->
                        @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'matricula.listar', 'turma.listar', 'ferramenta.mock'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-graduation-cap"></i> Educacional <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute left-0 w-56 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                
                                @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'ferramenta.mock'])
                                <div x-data="{ subOpen: false }" @click.away="subOpen = false" class="relative">
                                    <button @click="subOpen = !subOpen" class="w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Avaliações <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="{'rotate-90': subOpen}"></i>
                                    </button>
                                    <div x-show="subOpen" class="absolute top-0 py-2 mt-0 bg-white border border-gray-100 rounded-lg shadow-xl left-full ml-1 w-48 dark:bg-gray-800 dark:border-gray-700" x-cloak>
                                        @can('periodo_avaliacao.listar') <a href="{{ route('avaliacoes.periodos.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Listar Períodos</a> @endcan
                                        @can('periodo_avaliacao.criar') <a href="{{ route('avaliacoes.periodos.create') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Novo Período</a> @endcan
                                        @can('relatorio.acessar') <a href="{{ route('avaliacoes.relatorios') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Relatórios</a> @endcan
                                        @can('ferramenta.mock') <a href="{{ route('avaliacoes.gerador') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gerador Mock</a> @endcan
                                    </div>
                                </div>
                                @endcanany

                                @can('matricula.listar')
                                <div x-data="{ subOpen: false }" @click.away="subOpen = false" class="relative">
                                    <button @click="subOpen = !subOpen" class="w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Matrículas <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="{'rotate-90': subOpen}"></i>
                                    </button>
                                    <div x-show="subOpen" class="absolute top-0 py-2 mt-0 bg-white border border-gray-100 rounded-lg shadow-xl left-full ml-1 w-48 dark:bg-gray-800 dark:border-gray-700" x-cloak>
                                        <a href="{{ route('matriculas.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ver Matrículas</a>
                                        @can('matricula.criar') <a href="{{ route('matriculas.create') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Nova Matrícula</a> @endcan
                                    </div>
                                </div>
                                @endcan

                                @can('turma.listar')
                                <div x-data="{ subOpen: false }" @click.away="subOpen = false" class="relative">
                                    <button @click="subOpen = !subOpen" class="w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Turmas <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="{'rotate-90': subOpen}"></i>
                                    </button>
                                    <div x-show="subOpen" class="absolute top-0 py-2 mt-0 bg-white border border-gray-100 rounded-lg shadow-xl left-full ml-1 w-48 dark:bg-gray-800 dark:border-gray-700" x-cloak>
                                        <a href="{{ route('turmas.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ver Turmas</a>
                                        @can('turma.criar') <a href="{{ route('turmas.create') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Nova Turma</a> @endcan
                                    </div>
                                </div>
                                @endcan
                            </div>
                        </div>
                        @endcanany

                        <!-- Administração (Apenas quem tem permissão para os módulos ou é dev) -->
                        @canany(['usuario.listar', 'acl.role.listar', 'acl.permissao.listar', 'auditoria.listar', 'importacao.acessar'])
                        <div x-data="{ open: false }" @click.away="open = false" class="relative ml-auto">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-purpura-400">
                                <i class="text-lg ph ph-gear"></i> Administração <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition.opacity class="absolute right-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                @can('usuario.listar') <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gestão de Usuários</a> @endcan
                                @can('acl.role.listar') <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Perfis (Roles)</a> @endcan
                                @can('auditoria.listar') <a href="{{ route('auditoria.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Auditoria</a> @endcan
                                @can('importacao.acessar') <a href="{{ route('importacoes.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Importações</a> @endcan

                                <!-- Restrito EXCLUSIVAMENTE ao papel de 'dev' -->
                                @role('dev')
                                    <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                    <a href="{{ route('permissions.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tabela de Permissões</a>
                                    <a href="{{ route('features.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Feature Toggles</a>
                                @endrole
                            </div>
                        </div>
                        @endcanany

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

                    @canany(['ciclo.listar', 'etapa.listar', 'inscricao.listar'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-calendar-check"></i> Processos Seletivos</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            @can('ciclo.listar') <a href="{{ route('ciclos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Ciclos de Inscrição</a> @endcan
                            @can('etapa.listar') <a href="{{ route('ciclos.etapas') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Etapas de Formulário</a> @endcan
                            @can('inscricao.listar') <a href="{{ route('inscricoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Fichas de Inscrição</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['estudante.listar', 'status.listar'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-folder-user"></i> Secretaria</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            @can('estudante.listar') <a href="{{ route('students.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Base de Alunos</a> @endcan
                            @can('status.listar') <a href="{{ route('status-inscricoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Tags de Status</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['curso.listar', 'turno.listar', 'unidade.listar', 'formulario.listar'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-buildings"></i> Instituição</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            @can('curso.listar') <a href="{{ route('cursos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Portfólio de Cursos</a> @endcan
                            @can('turno.listar') <a href="{{ route('turnos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Grade de Turnos</a> @endcan
                            @can('unidade.listar') <a href="{{ route('unidades.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Unidades</a> @endcan
                            @can('formulario.listar') <a href="{{ route('formularios.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Formulários</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['template.listar', 'comunicado.listar', 'automacao.listar', 'email_log.listar'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-paper-plane-tilt"></i> Comunicação</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            @can('template.listar') <a href="{{ route('templates.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Templates</a> @endcan
                            @can('comunicado.listar') <a href="{{ route('comunicados.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Comunicados</a> @endcan
                            @can('automacao.listar') <a href="{{ route('automacoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Automações</a> @endcan
                            @can('email_log.listar') <a href="{{ route('monitor.emails') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Agenda de e-mails</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'matricula.listar', 'turma.listar', 'ferramenta.mock'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-graduation-cap"></i> Educacional</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-6 space-y-2 py-1" x-cloak>
                            
                            @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'ferramenta.mock'])
                            <div x-data="{ subOpen: false }" class="space-y-1">
                                <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                                    <span>Avaliações</span>
                                    <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': subOpen}"></i>
                                </button>
                                <div x-show="subOpen" class="pl-4 space-y-1" x-cloak>
                                    @can('periodo_avaliacao.listar') <a href="{{ route('avaliacoes.periodos.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Listar Períodos</a> @endcan
                                    @can('periodo_avaliacao.criar') <a href="{{ route('avaliacoes.periodos.create') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Novo Período</a> @endcan
                                    @can('relatorio.acessar') <a href="{{ route('avaliacoes.relatorios') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Relatórios</a> @endcan
                                    @can('ferramenta.mock') <a href="{{ route('avaliacoes.gerador') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Gerador Mock</a> @endcan
                                </div>
                            </div>
                            @endcanany

                            @can('matricula.listar')
                            <div x-data="{ subOpen: false }" class="space-y-1">
                                <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                                    <span>Matrículas</span>
                                    <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': subOpen}"></i>
                                </button>
                                <div x-show="subOpen" class="pl-4 space-y-1" x-cloak>
                                    <a href="{{ route('matriculas.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Ver Matrículas</a>
                                    @can('matricula.criar') <a href="{{ route('matriculas.create') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Nova Matrícula</a> @endcan
                                </div>
                            </div>
                            @endcan

                            @can('turma.listar')
                            <div x-data="{ subOpen: false }" class="space-y-1">
                                <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                                    <span>Turmas</span>
                                    <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="rotate-180': subOpen}"></i>
                                </button>
                                <div x-show="subOpen" class="pl-4 space-y-1" x-cloak>
                                    <a href="{{ route('turmas.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Ver Turmas</a>
                                    @can('turma.criar') <a href="{{ route('turmas.create') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 rounded-lg dark:text-gray-400 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Nova Turma</a> @endcan
                                </div>
                            </div>
                            @endcan

                        </div>
                    </div>
                    @endcanany

                    @canany(['usuario.listar', 'acl.role.listar', 'acl.permissao.listar', 'auditoria.listar', 'importacao.acessar'])
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-3 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-3"><i class="text-lg ph ph-gear"></i> Administração</span>
                            <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" class="pl-8 space-y-1" x-cloak>
                            @can('usuario.listar') <a href="{{ route('users.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Gestão de Usuários</a> @endcan
                            @can('acl.role.listar') <a href="{{ route('roles.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Perfis (Roles)</a> @endcan
                            @can('acl.permissao.listar') <a href="{{ route('permissions.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Permissões</a> @endcan
                            @can('importacao.acessar') <a href="{{ route('importacoes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Importações</a> @endcan

                            @role('dev')
                                <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                <a href="{{ route('features.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-600 rounded-lg dark:text-gray-300 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700">Feature Toggles</a>
                            @endrole
                        </div>
                    </div>
                    @endcanany

                    <div class="px-3 py-2">
                        @livewire(\App\Modules\Importacao\UI\Livewire\ImportProgress::class)
                    </div>
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