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
            (function () {
                var d = document.documentElement;
                d.dataset.layout  = localStorage.getItem('layoutMode') || 'left';
                d.dataset.sidebar = localStorage.getItem('sidebarMinimized') === 'true' ? 'min' : 'full';
            })();
        </script>

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="h-full antialiased text-gray-900 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 overflow-hidden">
        
        <div x-data="{ 
            drawerOpen: false, 
            layoutMode: localStorage.getItem('layoutMode') || 'left',
            sidebarMinimized: localStorage.getItem('sidebarMinimized') === 'true',
            loaded: false,
            init() {
                setTimeout(() => this.loaded = true, 50);
            },
            toggleLayout() {
                this.layoutMode = this.layoutMode === 'top' ? 'left' : 'top';
                localStorage.setItem('layoutMode', this.layoutMode);
                document.documentElement.dataset.layout = this.layoutMode;
            },
            toggleMinimize() {
                this.sidebarMinimized = !this.sidebarMinimized;
                localStorage.setItem('sidebarMinimized', this.sidebarMinimized);
                document.documentElement.dataset.sidebar = this.sidebarMinimized ? 'min' : 'full';
            }
        }" class="flex h-screen w-full overflow-hidden">
            
            {{-- DESKTOP: SIDEBAR VERTICAL --}}
            <aside class="js-sidebar hidden md:flex flex-col bg-petunia-900 dark:bg-petunia-1000 z-40 shrink-0 shadow-lg overflow-x-hidden" 
                   :class="loaded ? 'transition-all duration-300 ease-in-out' : ''">
                
                <div class="h-16 flex items-center justify-between px-4 border-b border-white/10 shrink-0 min-w-[72px]">
                    <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap" x-show="!sidebarMinimized" x-transition.opacity.duration.300ms>
                        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                    </div>
                    <button @click="toggleMinimize()" class="p-1.5 text-white/70 hover:bg-white/10 rounded-lg transition-colors shrink-0" :class="sidebarMinimized ? 'mx-auto' : ''" title="Recolher / Expandir">
                        <i class="text-xl ph ph-list"></i>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar py-4 space-y-1.5 px-3 min-w-[72px]">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors group relative whitespace-nowrap" :class="sidebarMinimized ? 'justify-center' : ''">
                        <i class="text-xl ph ph-squares-four shrink-0"></i>
                        <span x-show="!sidebarMinimized" x-transition.opacity>Dashboard</span>
                        <div x-show="sidebarMinimized" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">Dashboard</div>
                    </a>

                    @if(feature('financeiro.orcamentos.listagem') && auth()->user()->can('financeiro.orcamentos.listagem'))
                        @canany(['financeiro.orcamentos.listagem'])
                        <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                            <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                                <div class="flex items-center gap-3">
                                    <i class="text-xl ph ph-receipt shrink-0"></i>
                                    <span x-show="!sidebarMinimized">Financeiro</span>
                                </div>
                                <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                <a href="{{ route('financeiro.orcamentos') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Orçamentos</a>
                            </div>
                            <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                <a href="{{ route('financeiro.aprovacoes') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Aprovações</a>
                            </div>
                            <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                <a href="{{ route('financeiro.centro_custo') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Centro de Custos</a>
                            </div>
                            <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                <a href="{{ route('financeiro.naturezas') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Naturezas</a>
                            </div>
                            <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Financeiro</div>
                                <a href="{{ route('financeiro.orcamentos') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Orçamentos</a>
                            </div>
                            <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Financeiro</div>
                                <a href="{{ route('financeiro.aprovacoes') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Aprovações</a>
                            </div>
                            <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Financeiro</div>
                                <a href="{{ route('financeiro.centro_custo') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Centro de Custos</a>
                            </div>
                            <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Financeiro</div>
                                <a href="{{ route('financeiro.naturezas') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Naturezas</a>
                            </div>
                        </div>
                        @endcanany
                    @endif


                    @canany(['ciclo.listar', 'etapa.listar', 'inscricao.listar'])
                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-calendar-check shrink-0"></i>
                                <span x-show="!sidebarMinimized">Processos Seletivos</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            @can('ciclo.listar') <a href="{{ route('ciclos.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Ciclos de Inscrição</a> @endcan
                            @can('inscricao.listar') <a href="{{ route('inscricoes.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Inscrições</a> @endcan
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Processos Seletivos</div>
                            @can('ciclo.listar') <a href="{{ route('ciclos.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Ciclos de Inscrição</a> @endcan
                            @can('inscricao.listar') <a href="{{ route('inscricoes.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Inscrições</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['estudante.listar', 'status.listar'])
                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-folder-user shrink-0"></i>
                                <span x-show="!sidebarMinimized">Secretaria</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            @can('estudante.listar') <a href="{{ route('students.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Base de Alunos</a> @endcan
                            @can('status.listar') <a href="{{ route('status-inscricoes.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Tags de Status</a> @endcan
                            
                            @if(feature('empresas.listar') && auth()->user()->can('empresas.listar'))
                                @role('dev') <a href="{{ route('empresas.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Empresas Parceiras</a> @endrole
                            @endif

                            <a href="{{ route('solicitacoes.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Solicitações</a>
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Secretaria</div>
                            @can('estudante.listar') <a href="{{ route('students.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Base de Alunos</a> @endcan
                            @can('status.listar') <a href="{{ route('status-inscricoes.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Tags de Status</a> @endcan
                            @if(feature('empresas.listar') && auth()->user()->can('empresas.listar'))@role('dev') <a href="{{ route('empresas.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Empresas Parceiras</a> @endrole@endif
                            <a href="{{ route('solicitacoes.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Solicitações</a>
                        </div>
                    </div>
                    @endcanany

                    @canany(['curso.listar', 'turno.listar', 'unidade.listar', 'formulario.listar'])
                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-buildings shrink-0"></i>
                                <span x-show="!sidebarMinimized">Instituição</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            @can('curso.listar') <a href="{{ route('cursos.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Cursos</a> @endcan
                            @can('turno.listar') <a href="{{ route('turnos.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Grade de Turnos</a> @endcan
                            @can('unidade.listar') <a href="{{ route('unidades.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Unidades</a> @endcan
                            @can('formulario.listar') <a href="{{ route('formularios.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Formulários</a> @endcan
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-48 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Instituição</div>
                            @can('curso.listar') <a href="{{ route('cursos.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Cursos</a> @endcan
                            @can('turno.listar') <a href="{{ route('turnos.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Grade de Turnos</a> @endcan
                            @can('unidade.listar') <a href="{{ route('unidades.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Unidades</a> @endcan
                            @can('formulario.listar') <a href="{{ route('formularios.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Formulários</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-paper-plane-tilt shrink-0"></i>
                                <span x-show="!sidebarMinimized">Comunicação</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            @if(feature('portal.noticias') && auth()->user()->can('portal.noticias'))
                                <div class="py-1 text-[9px] font-bold text-gray-500 uppercase">Portal & Notícias</div>
                                <a href="{{ route('conteudo.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Gestão de Publicações</a>
                                <a href="{{ route('conteudo.categorias') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Categorias</a>
                                <a href="{{ route('portal.index') }}" target="_blank" class="block py-1.5 text-xs text-purpura-400 hover:text-purpura-300">Ver Portal ↗</a>
                            @endif
                            <div class="py-1 text-[9px] font-bold text-gray-500 uppercase mt-2">Mensagens & Envio</div>
                            @can('comunicado.listar') <a href="{{ route('comunicados.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Comunicados</a> @endcan
                            @can('template.listar') <a href="{{ route('templates.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Templates</a> @endcan
                            @can('automacao.listar') <a href="{{ route('automacoes.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Automações</a> @endcan
                            @can('email_log.listar') <a href="{{ route('monitor.emails') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Agenda de E-mails</a> @endcan
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-52 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Comunicação</div>
                            <a href="{{ route('conteudo.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Publicações</a>
                            <a href="{{ route('conteudo.categorias') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Categorias</a>
                            @can('comunicado.listar') <a href="{{ route('comunicados.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Comunicados</a> @endcan
                            @can('template.listar') <a href="{{ route('templates.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Templates</a> @endcan
                            @can('automacao.listar') <a href="{{ route('automacoes.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Automações</a> @endcan
                            @can('email_log.listar') <a href="{{ route('monitor.emails') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Agenda de E-mails</a> @endcan
                        </div>
                    </div>

                    @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'matricula.listar', 'turma.listar', 'ferramenta.mock'])
                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-graduation-cap shrink-0"></i>
                                <span x-show="!sidebarMinimized">Educacional</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            <div class="py-1 text-[9px] font-bold text-gray-500 uppercase">Avaliações</div>
                            @can('relatorio.acessar') <a href="{{ route('avaliacoes.relatorios') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Relatórios</a> @endcan
                            @can('periodo_avaliacao.listar') <a href="{{ route('avaliacoes.periodos.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Listar Períodos</a> @endcan
                            @can('ferramenta.mock') <a href="{{ route('avaliacoes.gerador') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Gerador Mock</a> @endcan
                            <div class="py-1 text-[9px] font-bold text-gray-500 uppercase mt-2">Matrículas e Turmas</div>
                            @can('matricula.listar') <a href="{{ route('matriculas.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Ver Matrículas</a> @endcan
                            @can('turma.listar') <a href="{{ route('turmas.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Ver Turmas</a> @endcan
                            <a href="{{ route('matriculas.acompanhamento') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Portal IA</a>
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-52 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Educacional</div>
                            @can('relatorio.acessar') <a href="{{ route('avaliacoes.relatorios') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Relatórios de Avaliação</a> @endcan
                            @can('periodo_avaliacao.listar') <a href="{{ route('avaliacoes.periodos.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Períodos de Avaliação</a> @endcan
                            @can('matricula.listar') <a href="{{ route('matriculas.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Matrículas</a> @endcan
                            @can('turma.listar') <a href="{{ route('turmas.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Turmas</a> @endcan
                        </div>
                    </div>
                    @endcanany

                    @canany(['usuario.listar', 'acl.role.listar', 'auditoria.listar', 'tarefas.acessar'])
                    <div x-data="{ open: false }" class="relative group whitespace-nowrap">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold text-white/80 rounded-lg hover:bg-white/10 hover:text-white transition-colors" :class="sidebarMinimized ? 'justify-center' : ''">
                            <div class="flex items-center gap-3">
                                <i class="text-xl ph ph-gear shrink-0"></i>
                                <span x-show="!sidebarMinimized">Administração</span>
                            </div>
                            <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                            @if (auth()->user()->hasRole('dev') || auth()->user()->can('configuracoes.editar')) 
                                <a href="{{ route('configuracoes.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Configurações Base</a>
                            @endif
                            @can('usuario.listar') <a href="{{ route('users.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Gestão de Usuários</a> @endcan
                            @can('acl.role.listar') <a href="{{ route('roles.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Perfis (Roles)</a> @endcan
                            @can('auditoria.listar') <a href="{{ route('auditoria.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Auditoria Geral</a> @endcan
                            @can('tarefas.acessar') <a href="{{ route('system_tasks.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Motor de Importações</a> @endcan
                            @can('form.builder') <a href="{{ route('formbuilder.hub') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Form Builder</a> @endcan
                            <a href="{{ route('system.errors.index') }}" class="block py-1.5 text-xs text-white/70 hover:text-white">Central de Erros</a>
                        </div>
                        <div x-show="sidebarMinimized" class="absolute left-full top-0 ml-3 w-52 py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50">
                            <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">Administração</div>
                            @if (auth()->user()->hasRole('dev') || auth()->user()->can('configuracoes.editar')) <a href="{{ route('configuracoes.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Configurações</a> @endif
                            @can('usuario.listar') <a href="{{ route('users.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Usuários</a> @endcan
                            @can('acl.role.listar') <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Perfis (Roles)</a> @endcan
                            @can('form.builder') <a href="{{ route('formbuilder.hub') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Form Builder</a> @endcan
                            @can('auditoria.listar') <a href="{{ route('auditoria.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Auditoria</a> @endcan
                            <a href="{{ route('system.errors.index') }}" class="block px-4 py-2 text-xs text-white/80 hover:text-white hover:bg-gray-700">Central de Erros</a>
                        </div>
                    </div>
                    @endcanany
                </div>
            </aside>
            
            {{-- ÁREA PRINCIPAL --}}
            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">
                
                {{-- TOP HEADER --}}
                <header class="bg-petunia-900 border-b border-white/10 relative z-30 dark:bg-petunia-1000 transition-colors duration-300 shrink-0 h-16 w-full">
                    <div class="h-full w-full mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between h-full w-full">
                            
                            <div class="flex items-center gap-5">
                                <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 focus:outline-none transition-colors">
                                    <i class="text-2xl ph ph-list"></i>
                                </button>
                                
                                <div class="js-topcontrols flex-shrink-0 items-center gap-4 hidden md:flex" 
                                     :class="loaded ? 'transition-all duration-300' : ''">
                                    <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:gap-4 text-white dark:text-gray-200 ml-auto">
                                <a href="{{ route('portal.index') }}" target="_blank" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white/90 bg-white/10 hover:bg-white/20 border border-white/10 rounded-lg transition-colors shadow-sm whitespace-nowrap shrink-0" title="Abrir Portal Público">
                                    <i class="ph ph-newspaper text-sm"></i> <span>Ver Portal</span> <i class="ph ph-arrow-up-right text-[10px] opacity-70"></i>
                                </a>

                                {{-- Troca de tema protegida por feature e permissão --}}
                                @if(feature('tema.alterar') && auth()->user()->can('tema.alterar'))
                                <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alternar Tema">
                                    <i class="text-lg ph ph-moon" x-show="tema === 'light'"></i>
                                    <i class="text-lg ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                </button>
                                @endif
                                
                                <button @click="toggleLayout()" class="hidden md:flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alterar Posição do Menu">
                                    <i class="text-lg ph ph-layout" x-show="layoutMode === 'top'"></i>
                                    <i class="text-lg ph ph-sidebar-simple" x-show="layoutMode === 'left'" x-cloak></i>
                                </button>
                                
                                {{-- Componente System Task Progress adicionado aqui --}}
                                @livewire(\App\Modules\SystemTasks\UI\Livewire\SystemTaskProgress::class)
                                
                                @livewire(\App\Modules\Admin\UI\Livewire\NotificationBadge::class)
                                
                                <a href="{{ route('profile.show') }}" class="hidden sm:flex items-center gap-1.5 hover:text-purpura-300 transition-colors shrink-0 ml-1">
                                    <span class="text-sm font-bold whitespace-nowrap">{{ explode(' ', auth()->user()->name)[0] }}</span>
                                </a>
                                <livewire:auth.logout-button />
                            </div>
                        </div>
                    </div>
                </header>

                {{-- NAVBAR HORIZONTAL --}}
                <nav class="js-topnav hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-40 shrink-0 origin-top"
                     :class="loaded ? 'transition-all duration-300 ease-in-out' : ''">
                    <div class="w-full mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="flex flex-wrap items-center min-h-[3rem] py-1 gap-x-2 gap-y-1">
                            <a href="{{ route('dashboard') }}" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                <i class="text-base ph ph-squares-four"></i> Dashboard
                            </a>
                            
                            @if(feature('financeiro.orcamentos.listagem') && auth()->user()->can('financeiro.orcamentos.listagem'))
                                @canany(['financeiro.orcamentos.listagem'])
                                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                    <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                        <i class="text-base ph ph-receipt"></i> Financeiro <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                    </button>
                                    <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                        <a href="{{ route('financeiro.orcamentos') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Orçamentos</a>
                                    </div>
                                    <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                        <a href="{{ route('financeiro.aprovacoes') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Aprovações</a>
                                    </div>
                                    <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                        <a href="{{ route('financeiro.centro_custo') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Centro de Custos</a>
                                    </div>
                                    <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                        <a href="{{ route('financeiro.naturezas') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Naturezas</a>
                                    </div>
                                </div>
                                @endcanany
                            @endif

                            @canany(['ciclo.listar', 'etapa.listar', 'inscricao.listar'])
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-calendar-check"></i> Processos Seletivos <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    @can('ciclo.listar') <a href="{{ route('ciclos.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ciclos de Inscrição</a> @endcan
                                    @can('inscricao.listar') <a href="{{ route('inscricoes.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Inscrições</a> @endcan
                                    @can('inscricao.listar') <a href="{{ route('ciclos.crm') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Kanban (CRM)</a> @endcan
                                </div>
                            </div>
                            @endcanany

                            @canany(['estudante.listar', 'status.listar'])
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-folder-user"></i> Secretaria <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    @can('estudante.listar') <a href="{{ route('students.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Base de Alunos</a> @endcan
                                    @can('status.listar') <a href="{{ route('status-inscricoes.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Tags de Status</a> @endcan
                                    
                                    @if(feature('empresas.listar') && auth()->user()->can('empresas.listar'))
                                    @role('dev') <a href="{{ route('empresas.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Empresas Parceiras</a> @endrole
                                    @endif
                                    <a href="{{ route('solicitacoes.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Solicitações</a>
                                </div>
                            </div>
                            @endcanany

                            @canany(['curso.listar', 'turno.listar', 'unidade.listar', 'formulario.listar'])
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-buildings"></i> Instituição <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    @can('curso.listar') <a href="{{ route('cursos.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Cursos</a> @endcan
                                    @can('turno.listar') <a href="{{ route('turnos.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Grade de Turnos</a> @endcan
                                    @can('unidade.listar') <a href="{{ route('unidades.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Unidades</a> @endcan
                                    @can('formulario.listar') <a href="{{ route('formularios.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Formulários</a> @endcan
                                </div>
                            </div>
                            @endcanany

                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-paper-plane-tilt"></i> Comunicação <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-52 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    
                                    @if(feature('portal.noticias') && auth()->user()->can('portal.noticias'))<div class="py-1 px-4 text-[9px] font-bold text-gray-500 uppercase">Portal & Notícias</div>
                                        <a href="{{ route('conteudo.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gestão de Publicações</a>
                                        <a href="{{ route('conteudo.categorias') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Categorias</a>
                                        <a href="{{ route('portal.index') }}" target="_blank" class="block px-4 py-1.5 text-xs font-medium text-purpura-600 hover:bg-purpura-50 dark:text-purpura-400 dark:hover:bg-gray-700">Ver Portal ↗</a>
                                    @endif
                                    <div class="py-1 px-4 text-[9px] font-bold text-gray-500 uppercase mt-2">Mensagens & Envio</div>
                                    @can('comunicado.listar') <a href="{{ route('comunicados.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Comunicados</a> @endcan
                                    @can('template.listar') <a href="{{ route('templates.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Templates</a> @endcan
                                    @can('automacao.listar') <a href="{{ route('automacoes.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Automações</a> @endcan
                                    @can('email_log.listar') <a href="{{ route('monitor.emails') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Agenda de E-mails</a> @endcan
                                </div>
                            </div>

                            @canany(['periodo_avaliacao.listar', 'relatorio.acessar', 'matricula.listar', 'turma.listar', 'ferramenta.mock'])
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-graduation-cap"></i> Educacional <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute left-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    @if(feature('ferramenta.mock') && auth()->user()->can('relatorio.acessar'))
                                <div class="px-4 py-1 text-[9px] font-bold text-gray-500 uppercase">Avaliações</div>
                                    @can('relatorio.acessar') <a href="{{ route('avaliacoes.relatorios') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Relatórios</a> @endcan
                                    @endif
                                    @if(feature('ferramenta.mock') && auth()->user()->can('periodo_avaliacao.listar'))
                                        @can('periodo_avaliacao.listar') <a href="{{ route('avaliacoes.periodos.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Listar Períodos</a> @endcan
                                    @endif
                                    @if(feature('ferramenta.mock') && auth()->user()->can('ferramenta.mock'))
                                        @can('ferramenta.mock') <a href="{{ route('avaliacoes.gerador') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Gerador Mock</a> @endcan
                                    @endif
                                    <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                    <div class="px-4 py-1 text-[9px] font-bold text-gray-500 uppercase">Matrículas e Turmas</div>
                                    @can('matricula.listar') <a href="{{ route('matriculas.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ver Matrículas</a> @endcan
                                    @if(feature('turma.listar') && auth()->user()->can('turma.listar'))
                                        @can('turma.listar') <a href="{{ route('turmas.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ver Turmas</a> @endcan
                                    @endif
                                    <a href="{{ route('matriculas.acompanhamento') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Portal IA</a>
                                    @if(feature('aprendizagem.listar') && auth()->user()->can('aprendizagem.listar'))
                                        @can('aprendizagem.listar') <a href="{{ route('aprendizagem.index') }}" class="block px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Ciclos de Aprendizagem</a> @endcan
                                    @endif
                                </div>
                            </div>
                            @endcanany

                            @canany(['usuario.listar', 'acl.role.listar', 'auditoria.listar', 'tarefas.acessar'])
                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold text-gray-600 transition-colors rounded-md hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300">
                                    <i class="text-base ph ph-gear"></i> Administração <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </button>
                                <div x-show="open" x-transition.opacity class="absolute right-0 w-48 py-2 mt-1 bg-white border border-gray-100 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-50" x-cloak>
                                    @if (auth()->user()->hasRole('dev') || auth()->user()->can('configuracoes.editar')) 
                                        <a href="{{ route('configuracoes.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Configurações Base</a>
                                        <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                    @endif    
                                    @can('usuario.listar') <a href="{{ route('users.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Usuários</a> @endcan
                                    @can('acl.role.listar') <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Perfis (Roles)</a> @endcan
                                    @can('form.builder') <a href="{{ route('formbuilder.hub') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Form Builder</a> @endcan
                                    @can('auditoria.listar') <a href="{{ route('auditoria.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Auditoria Geral</a> @endcan
                                    @can('tarefas.acessar') <a href="{{ route('system_tasks.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Motor de Importações</a> @endcan
                                    <a href="{{ route('system.errors.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Central de Erros</a>
                                    @can('feature.listar') <a href="{{ route('features.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Feature Toggles</a> @endcan
                                    @can('acl.permission.listar') <a href="{{ route('permissions.index') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Permissões</a> @endcan
                                    @can('tarefas.acessar') <a href="{{ route('system_tasks.hub') }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700">Hub de Tarefas</a> @endcan
                                </div>
                            </div>
                            @endcanany
                            
                        </div>
                    </div>
                </nav>

                {{-- MAIN CONTEÚDO --}}
                <main class="flex-1 w-full overflow-y-auto bg-gray-50 dark:bg-gray-900 custom-scrollbar">
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
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=fff&color=310B47&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full shadow-md">
                        <div class="text-white">
                            <a href="{{ route('profile.show') }}" class="text-white block hover:opacity-80 transition-opacity">
                                <div class="font-bold leading-tight truncate w-44">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-white/80 truncate w-44">{{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}</div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-squares-four"></i> Dashboard</a>
                    <a href="{{ route('solicitacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700"><i class="text-lg ph ph-envelope-open"></i>Solicitações</a>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    <livewire:auth.logout-button />
                </div>
            </div>
        </div>

        <livewire:components.quick-view-drawer />
        @livewireScripts
        <x-toast />
    </body>
</html>