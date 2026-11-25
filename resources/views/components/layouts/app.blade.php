<!DOCTYPE html>
@if(app()->environment('production'))
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
          x-data="{ tema: localStorage.getItem('tema_sistema') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" 
          x-init="$watch('tema', valor => { 
              localStorage.setItem('tema_sistema', valor); 
              document.documentElement.classList.toggle('dark', valor === 'dark'); 
          })"
          :class="{ 'dark': tema === 'dark' }"
          class="h-full">
@endif

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Sistema Administrativo' }}</title>
        
        <script>
            (function () {
                var d = document.documentElement;
                @if(!app()->environment('production'))
                    var tema = localStorage.getItem('tema_sistema');
                    if (!tema) {
                        tema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                        localStorage.setItem('tema_sistema', tema);
                    }
                    d.classList.toggle('dark', tema === 'dark');
                @else
                    d.classList.remove('dark');
                @endif

                var layout = localStorage.getItem('layoutMode');
                if (!layout) {
                    layout = 'left';
                    localStorage.setItem('layoutMode', layout);
                }
                d.dataset.layout = layout;
                d.dataset.sidebar = localStorage.getItem('sidebarMinimized') === 'true' ? 'min' : 'full';
            })();
        </script>

        @includeIf('components.partials.analytics-heatmap')

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    @php
        $u = auth()->user();
        $isDev = $u?->hasRole('dev') ?? false;
        $can = fn(string $perm) => $isDev || ($u && $u->can($perm));
        $canAny = fn(array $perms) => $isDev || ($u && $u->canAny($perms));

        $menuGrupos = [
            [
                'titulo' => 'Financeiro',
                'icone'  => 'ph-receipt',
                'visivel' => $canAny([
                    'financeiro.orcamentos.listar',
                    'financeiro.orcamentos.aprovar',
                    'financeiro.centro_custo.listar',
                    'financeiro.natureza.listar',
                    'financeiro.visao_global',
                    'financeiro.visao_global.aprovacao',
                ]),
                'itens' => [
                    ['label' => 'Orçamentos', 'route' => 'financeiro.orcamentos', 'visivel' => $canAny(['financeiro.orcamentos.listar', 'financeiro.visao_global'])],
                    ['label' => 'Aprovações', 'route' => 'financeiro.aprovacoes', 'visivel' => $canAny(['financeiro.orcamentos.aprovar', 'financeiro.visao_global.aprovacao'])],
                    ['label' => 'Centros de Custo', 'route' => 'financeiro.centro_custo', 'visivel' => $can('financeiro.centro_custo.listar')],
                    ['label' => 'Naturezas', 'route' => 'financeiro.naturezas', 'visivel' => $can('financeiro.natureza.listar')],
                ],
            ],
            [
                'titulo' => 'Processos Seletivos',
                'icone'  => 'ph-calendar-check',
                'visivel' => $canAny([
                    'ciclo.listar',
                    'inscricao.listar',
                    'inscricao.crm.acessar',
                    'matricula.acompanhamento.listar',
                    'matricula.ia.configurar',
                ]),
                'itens' => [
                    ['label' => 'Ciclos de Inscrição', 'route' => 'ciclos.index', 'visivel' => $can('ciclo.listar')],
                    ['label' => 'Inscrições', 'route' => 'inscricoes.index', 'visivel' => $can('inscricao.listar')],
                    ['label' => 'Kanban (CRM)', 'route' => 'ciclos.crm', 'visivel' => $can('inscricao.crm.acessar')],
                    ['label' => 'Acompanhamento (Portal IA)', 'route' => 'matriculas.acompanhamento', 'visivel' => $can('matricula.acompanhamento.listar')],
                    ['label' => 'Configuração IA Matrículas', 'route' => 'matriculas.configuracao', 'visivel' => $can('matricula.ia.configurar')],
                ],
            ],
            [
                'titulo' => 'Secretaria',
                'icone'  => 'ph-folder-user',
                'visivel' => $canAny([
                    'estudante.listar',
                    'solicitacao.listar',
                    'status.listar',
                    'empresas.listar',
                ]),
                'itens' => [
                    ['label' => 'Base de Alunos', 'route' => 'students.index', 'visivel' => $can('estudante.listar')],
                    ['label' => 'Solicitações', 'route' => 'solicitacoes.index', 'visivel' => $can('solicitacao.listar')],
                    ['label' => 'Tags de Status', 'route' => 'status-inscricoes.index', 'visivel' => $can('status.listar')],
                    ['label' => 'Empresas Parceiras', 'route' => 'empresas.index', 'visivel' => feature('empresas.listar') && $can('empresas.listar')],
                ],
            ],
            [
                'titulo' => 'Instituição',
                'icone'  => 'ph-buildings',
                'visivel' => $canAny([
                    'unidade.listar',
                    'curso.listar',
                    'turno.listar',
                    'formulario.listar',
                    'form.builder',
                ]),
                'itens' => [
                    ['label' => 'Unidades', 'route' => 'unidades.index', 'visivel' => $can('unidade.listar')],
                    ['label' => 'Cursos', 'route' => 'cursos.index', 'visivel' => $can('curso.listar')],
                    ['label' => 'Grade de Turnos', 'route' => 'turnos.index', 'visivel' => $can('turno.listar')],
                    ['separador' => 'Formulários', 'visivel' => $canAny(['formulario.listar', 'form.builder'])],
                    ['label' => 'Formulários Gerais', 'route' => 'formularios.index', 'visivel' => $can('formulario.listar')],
                    ['label' => 'Form Builder (Hub)', 'route' => 'formbuilder.hub', 'visivel' => $can('form.builder')],
                    ['label' => 'Escolas', 'route' => 'escolas.index', 'visivel' => $can('escola.listar')],
                ],
            ],
            [
                'titulo' => 'Comunicação',
                'icone'  => 'ph-paper-plane-tilt',
                'visivel' => $canAny([
                    'comunicacao.dashboard.acessar',
                    'comunicado.listar',
                    'comunicacao.esteira.listar',
                    'automacao.listar',
                    'template.listar',
                    'email_log.listar',
                    'comunicacao.suri_monitor.acessar',
                    'comunicacao.suri_lab.acessar',
                    'portal.noticias',
                ]),
                'itens' => [
                    ['label' => 'Dashboard Comunicação', 'route' => 'comunicacao.dashboard', 'visivel' => $can('comunicacao.dashboard.acessar')],
                    ['separador' => 'Mensagens & Automação', 'visivel' => $canAny(['comunicado.listar', 'comunicacao.esteira.listar', 'automacao.listar', 'template.listar', 'email_log.listar'])],
                    ['label' => 'Comunicados', 'route' => 'comunicados.index', 'visivel' => $can('comunicado.listar')],
                    ['label' => 'Esteiras de Disparo', 'route' => 'esteiras.index', 'visivel' => $can('comunicacao.esteira.listar')],
                    ['label' => 'Automações', 'route' => 'automacoes.index', 'visivel' => $can('automacao.listar')],
                    ['label' => 'Templates', 'route' => 'templates.index', 'visivel' => $can('template.listar')],
                    ['label' => 'Monitor de E-mails', 'route' => 'monitor.emails', 'visivel' => $can('email_log.listar')],
                    ['separador' => 'WhatsApp (Suri)', 'visivel' => $canAny(['comunicacao.suri_monitor.acessar', 'comunicacao.suri_lab.acessar'])],
                    ['label' => 'Monitor Suri Webhook', 'route' => 'monitor.suri', 'visivel' => $can('comunicacao.suri_monitor.acessar')],
                    ['label' => 'Laboratório Suri', 'route' => 'suri.dashboard', 'visivel' => $can('comunicacao.suri_lab.acessar')],
                    ['separador' => 'Portal & Notícias', 'visivel' => feature('portal.noticias') && $can('portal.noticias')],
                    ['label' => 'Gestão de Publicações', 'route' => 'conteudo.index', 'visivel' => feature('portal.noticias') && $can('portal.noticias')],
                    ['label' => 'Categorias', 'route' => 'conteudo.categorias', 'visivel' => feature('portal.noticias') && $can('portal.noticias')],
                    ['label' => 'Ver Portal ↗', 'route' => 'portal.index', 'target' => '_blank', 'visivel' => feature('portal.noticias') && $can('portal.noticias')],
                ],
            ],
            [
                'titulo' => 'Educacional',
                'icone'  => 'ph-graduation-cap',
                'visivel' => $canAny([
                    'matricula.listar',
                    'turma.listar',
                    'aprendizagem.listar',
                    'relatorio.acessar',
                    'periodo_avaliacao.listar',
                    'avaliacao.listar',
                    'ferramenta.mock',
                ]),
                'itens' => [
                    ['separador' => 'Matrículas & Turmas', 'visivel' => $canAny(['matricula.listar', 'turma.listar', 'aprendizagem.listar'])],
                    ['label' => 'Matrículas', 'route' => 'matriculas.index', 'visivel' => $can('matricula.listar')],
                    ['label' => 'Turmas', 'route' => 'turmas.index', 'visivel' => $can('turma.listar')],
                    ['label' => 'Ciclos de Aprendizagem', 'route' => 'aprendizagem.index', 'visivel' => feature('aprendizagem.listar') && $can('aprendizagem.listar')],
                    ['separador' => 'Avaliações', 'visivel' => $canAny(['relatorio.acessar', 'periodo_avaliacao.listar', 'avaliacao.listar', 'ferramenta.mock'])],
                    ['label' => 'Relatórios', 'route' => 'avaliacoes.relatorios', 'visivel' => $can('relatorio.acessar')],
                    ['label' => 'Períodos de Avaliação', 'route' => 'avaliacoes.periodos.index', 'visivel' => $can('periodo_avaliacao.listar')],
                    ['label' => 'Avaliações', 'route' => 'avaliacoes.index', 'visivel' => $can('avaliacao.listar')],
                    ['label' => 'Gerador Mock', 'route' => 'avaliacoes.gerador', 'visivel' => $can('ferramenta.mock')],
                    ['label' => 'Gerador Aprendizagem', 'route' => 'avaliacoes.gerador-aprendizagem', 'visivel' => $can('ferramenta.mock')],
                ],
            ],
            [
                'titulo' => 'Administração',
                'icone'  => 'ph-gear',
                'alignRight' => true,
                'visivel' => $canAny([
                    'configuracoes.editar',
                    'usuario.listar',
                    'acl.role.listar',
                    'acl.permissao.listar',
                    'feature.listar',
                    'auditoria.listar',
                    'tarefas.acessar',
                    'sistema.erros.listar',
                    'dev.rd_crm.acessar',
                    'dev.design_system.acessar',
                ]),
                'itens' => [
                    ['label' => 'Configurações Base', 'route' => 'configuracoes.index', 'visivel' => $can('configuracoes.editar')],
                    ['label' => 'Gestão de Usuários', 'route' => 'users.index', 'visivel' => $can('usuario.listar')],
                    ['label' => 'Perfis (Roles)', 'route' => 'roles.index', 'visivel' => $can('acl.role.listar')],
                    ['label' => 'Permissões', 'route' => 'permissions.index', 'visivel' => $can('acl.permissao.listar')],
                    ['label' => 'Feature Toggles', 'route' => 'features.index', 'visivel' => $can('feature.listar')],
                    ['separador' => 'Sistema & Logs', 'visivel' => $canAny(['auditoria.listar', 'tarefas.acessar', 'sistema.erros.listar'])],
                    ['label' => 'Auditoria Geral', 'route' => 'auditoria.index', 'visivel' => $can('auditoria.listar')],
                    ['label' => 'Motor de Importações', 'route' => 'system_tasks.index', 'visivel' => $can('tarefas.acessar')],
                    ['label' => 'Hub de Tarefas', 'route' => 'system_tasks.hub', 'visivel' => $can('tarefas.acessar')],
                    ['label' => 'Central de Erros', 'route' => 'system.errors.index', 'visivel' => $can('sistema.erros.listar')],
                    ['separador' => 'Dev / Testes', 'visivel' => $canAny(['dev.rd_crm.acessar', 'dev.design_system.acessar'])],
                    ['label' => 'Teste RD CRM', 'route' => 'teste.rdcrm', 'visivel' => $can('dev.rd_crm.acessar')],
                    ['label' => 'Design System', 'route' => 'teste.design', 'visivel' => $can('dev.design_system.acessar')],
                ],
            ],
        ];
    @endphp

    <body class="h-full antialiased text-gray-900 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 overflow-hidden">
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NCLCLKXJ"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <div x-data="{ 
            drawerOpen: false, 
            layoutMode: localStorage.getItem('layoutMode') || 'left',
            sidebarMinimized: localStorage.getItem('sidebarMinimized') === 'true',
            loaded: false,
            init() {
                document.documentElement.dataset.layout = this.layoutMode;
                document.documentElement.dataset.sidebar = this.sidebarMinimized ? 'min' : 'full';
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
                    <a href="{{ route('dashboard') }}">
                        <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap" x-show="!sidebarMinimized" x-transition.opacity.duration.300ms>
                            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                        </div>
                    </a>   
                    <button @click="toggleMinimize()" class="p-1.5 text-white/70 hover:bg-white/10 rounded-lg transition-colors shrink-0" :class="sidebarMinimized ? 'mx-auto' : ''" title="Recolher / Expandir">
                        <i class="text-xl ph ph-list"></i>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar py-4 space-y-1.5 px-3 min-w-[72px]">
                    
                    {{-- Dashboard --}}
                    <a href="{{ route('dashboard') }}" 
                    x-data="{ hover: false }" 
                    @mouseenter="hover = true; if(sidebarMinimized) { $refs.pop.style.top = ($el.getBoundingClientRect().top + 6) + 'px'; $refs.pop.style.left = $el.getBoundingClientRect().right + 'px'; }" 
                    @mouseleave="hover = false" 
                    class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold rounded-lg transition-colors relative whitespace-nowrap {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}" 
                    :class="sidebarMinimized ? 'justify-center' : ''">
                        <i class="text-xl ph ph-squares-four shrink-0"></i>
                        <span x-show="!sidebarMinimized" x-transition.opacity>Dashboard</span>
                        
                        <div x-ref="pop" x-show="sidebarMinimized && hover" x-transition.opacity.duration.200ms x-cloak class="fixed z-[999] pl-2 pointer-events-none">
                            <div class="px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg">
                                Dashboard
                            </div>
                        </div>
                    </a>

                    {{-- Grupos Dinâmicos --}}
                    @foreach($menuGrupos as $grupo)
                        @continue(!$grupo['visivel'])
                        @php
                            $itensVisiveis = collect($grupo['itens'])->filter(fn($i) => $i['visivel'] ?? false);
                            $temLinkAtivo = $itensVisiveis->contains(fn($i) => isset($i['route']) && request()->routeIs($i['route']));
                        @endphp
                        @continue($itensVisiveis->whereNotNull('route')->isEmpty())

                        <div x-data="{ open: {{ $temLinkAtivo ? 'true' : 'false' }}, hover: false }" 
                            @mouseenter="hover = true; if(sidebarMinimized) { $refs.pop.style.top = $el.getBoundingClientRect().top + 'px'; $refs.pop.style.left = $el.getBoundingClientRect().right + 'px'; }" 
                            @mouseleave="hover = false" 
                            class="relative whitespace-nowrap">
                            <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-colors {{ $temLinkAtivo ? 'bg-white/10 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}" :class="sidebarMinimized ? 'justify-center' : ''">
                                <div class="flex items-center gap-3">
                                    <i class="text-xl ph {{ $grupo['icone'] }} shrink-0"></i>
                                    <span x-show="!sidebarMinimized">{{ $grupo['titulo'] }}</span>
                                </div>
                                <i x-show="!sidebarMinimized" class="ph ph-caret-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            
                            {{-- Submenu Expandido --}}
                            <div x-show="open && !sidebarMinimized" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                @foreach($itensVisiveis as $item)
                                    @if(isset($item['separador']))
                                        <div class="pt-2 pb-0.5 text-[9px] font-bold text-white/40 uppercase tracking-wider">{{ $item['separador'] }}</div>
                                    @elseif(Route::has($item['route']))
                                        <a href="{{ route($item['route']) }}" @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                                           class="block py-1.5 text-xs transition-colors {{ request()->routeIs($item['route']) ? 'text-white font-bold' : 'text-white/70 hover:text-white' }}">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                            
                            {{-- Popover Minimizado --}}
                            <div x-ref="pop" x-show="sidebarMinimized && hover" x-transition.opacity.duration.200ms x-cloak class="fixed z-[999] pl-2">
                                <div class="w-56 max-h-[80vh] overflow-y-auto custom-scrollbar py-2 bg-gray-800 border border-gray-700 rounded-lg shadow-xl">
                                    <div class="px-4 py-1 text-[10px] font-bold text-gray-400 uppercase border-b border-gray-700 mb-1">{{ $grupo['titulo'] }}</div>
                                    @foreach($itensVisiveis as $item)
                                        @if(isset($item['separador']))
                                            <div class="px-4 pt-2 pb-0.5 text-[9px] font-bold text-gray-500 uppercase">{{ $item['separador'] }}</div>
                                        @elseif(Route::has($item['route']))
                                            <a href="{{ route($item['route']) }}" @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                                               class="block px-4 py-1.5 text-xs transition-colors {{ request()->routeIs($item['route']) ? 'bg-gray-700 text-white font-bold' : 'text-white/80 hover:text-white hover:bg-gray-700' }}">
                                                {{ $item['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
            
            {{-- ÁREA PRINCIPAL --}}
            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">
                
                {{-- TOP HEADER (Z-INDEX 110) --}}
                <header class="bg-petunia-900 border-b border-white/10 relative z-[110] dark:bg-petunia-1000 transition-colors duration-300 shrink-0 h-16 w-full">
                    <div class="h-full w-full mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between h-full w-full">
                            
                            <div class="flex items-center gap-5">
                                <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 focus:outline-none transition-colors">
                                    <i class="text-2xl ph ph-list"></i>
                                </button>
                                
                                <a href="{{ route('dashboard') }}">
                                    <div class="js-topcontrols flex-shrink-0 items-center gap-4 hidden md:flex" 
                                        :class="loaded ? 'transition-all duration-300' : ''">
                                        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
                                    </div>
                                </a>
                            </div>

                            <div class="flex items-center gap-3 sm:gap-4 text-white dark:text-gray-200 ml-auto">
                                @if(strtolower(trim(auth()->user()?->email ?? '')) === 'dev@percorre.com')
                                    @php
                                        $envRaw = strtolower((string) app()->environment());
                                        if (in_array($envRaw, ['production', 'prod', 'producao', 'produção'], true)) {
                                            $envNome = 'Produção';
                                            $envClasses = 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40';
                                            $envDot = 'bg-emerald-400 animate-pulse';
                                        } elseif (in_array($envRaw, ['staging', 'homolog', 'homologacao', 'homologação', 'hml', 'testing'], true)) {
                                            $envNome = 'Homologação';
                                            $envClasses = 'bg-amber-500/20 text-amber-300 border-amber-400/40';
                                            $envDot = 'bg-amber-400';
                                        } else {
                                            $envNome = 'Local';
                                            $envClasses = 'bg-sky-500/20 text-sky-300 border-sky-400/40';
                                            $envDot = 'bg-sky-400';
                                        }
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider border {{ $envClasses }} shrink-0 select-none"
                                          title="Ambiente atual do sistema (APP_ENV: {{ app()->environment() }}) — Visível apenas para dev@percorre.com">
                                        <span class="w-2 h-2 rounded-full {{ $envDot }}"></span>
                                        <span>{{ $envNome }}</span>
                                    </span>
                                @endif

                                @if(feature('portal.noticias') && auth()->user()->can('portal.noticias'))
                                    <a href="{{ route('portal.index') }}" target="_blank" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white/90 bg-white/10 hover:bg-white/20 border border-white/10 rounded-lg transition-colors shadow-sm whitespace-nowrap shrink-0" title="Abrir Portal Público">
                                        <i class="ph ph-newspaper text-sm"></i> <span>Ver Portal</span> <i class="ph ph-arrow-up-right text-[10px] opacity-70"></i>
                                    </a>
                                @endif

                                @if(!app()->environment('production'))
                                    <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alternar Tema">
                                        <i class="text-lg ph ph-moon" x-show="tema === 'light'"></i>
                                        <i class="text-lg ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                                    </button>
                                @endif
                                
                                <button @click="toggleLayout()" class="hidden md:flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-400 dark:hover:bg-gray-800" title="Alterar Posição do Menu">
                                    <i class="text-lg ph ph-layout" x-show="layoutMode === 'top'"></i>
                                    <i class="text-lg ph ph-sidebar-simple" x-show="layoutMode === 'left'" x-cloak></i>
                                </button>
                                
                                @livewire(\App\Modules\SystemTasks\UI\Livewire\SystemTaskProgress::class)

                                @if(feature('portal.noticias'))
                                    @livewire(\App\Modules\Admin\UI\Livewire\NotificationBadge::class)
                                @endif
                                
                                <a href="{{ route('profile.show') }}" class="hidden sm:flex items-center gap-1.5 hover:text-purpura-300 transition-colors shrink-0 ml-1">
                                    <span class="text-sm font-bold whitespace-nowrap">{{ explode(' ', auth()->user()->name)[0] }}</span>
                                </a>
                                <livewire:auth.logout-button />
                            </div>
                        </div>
                    </div>
                </header>

                {{-- DESKTOP: TOPNAV HORIZONTAL --}}
                <nav class="js-topnav hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-[100] shrink-0 origin-top"
                     :class="loaded ? 'transition-all duration-300 ease-in-out' : ''">
                    <div class="sm:px-6 px-8 mx-auto w-full max-w-8xl">
                        <div class="flex flex-wrap items-center min-h-[3rem] py-1 gap-x-2 gap-y-1">
                            <a href="{{ route('dashboard') }}" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold transition-colors rounded-md {{ request()->routeIs('dashboard') ? 'text-purpura-700 bg-purpura-50 dark:bg-gray-800 dark:text-white' : 'text-gray-600 hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300' }}">
                                <i class="text-base ph ph-squares-four"></i> Dashboard
                            </a>

                            @foreach($menuGrupos as $grupo)
                                @continue(!$grupo['visivel'])
                                @php
                                    $itensVisiveis = collect($grupo['itens'])->filter(fn($i) => $i['visivel'] ?? false);
                                    $temLinkAtivo = $itensVisiveis->contains(fn($i) => isset($i['route']) && request()->routeIs($i['route']));
                                @endphp
                                @continue($itensVisiveis->whereNotNull('route')->isEmpty())

                                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                    <button @click="open = !open" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold transition-colors rounded-md {{ $temLinkAtivo ? 'text-purpura-700 bg-purpura-50 dark:bg-gray-800 dark:text-white' : 'text-gray-600 hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300' }}">
                                        <i class="text-base ph {{ $grupo['icone'] }}"></i> {{ $grupo['titulo'] }}
                                        <i class="ph ph-caret-down text-[10px] opacity-70 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                    </button>
                                    <div x-show="open" x-transition.opacity class="absolute {{ !empty($grupo['alignRight']) ? 'right-0' : 'left-0' }} w-56 max-h-[75vh] overflow-y-auto custom-scrollbar py-2 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 z-[110]" x-cloak>
                                        @foreach($itensVisiveis as $item)
                                            @if(isset($item['separador']))
                                                <div class="px-4 pt-2 pb-1 text-[9px] font-bold text-gray-400 uppercase tracking-wider">{{ $item['separador'] }}</div>
                                            @elseif(Route::has($item['route']))
                                                <a href="{{ route($item['route']) }}" @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                                                   class="block px-4 py-1.5 text-xs transition-colors {{ request()->routeIs($item['route']) ? 'bg-purpura-50 text-purpura-700 font-bold dark:bg-gray-700 dark:text-white' : 'font-medium text-gray-700 hover:bg-purpura-50 hover:text-purpura-600 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                                                    {{ $item['label'] }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </nav>

                <main class="flex-1 w-full overflow-y-auto bg-gray-50 dark:bg-gray-900 custom-scrollbar">
                    <div class="w-full min-h-full pt-6">
                        <div class="sm:px-6 px-8 mx-auto w-full max-w-8xl">
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
            
            {{-- MOBILE: DRAWER LATERAL --}}
            <div x-show="drawerOpen" x-transition.opacity.duration.300ms @click="drawerOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" x-cloak></div>
            <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-800 md:hidden" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="relative flex-shrink-0 h-36 overflow-hidden bg-petunia-900">
                    <div class="absolute flex items-center gap-3 bottom-4 left-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=fff&color=310B47&bold=true" alt="Avatar" class="w-12 h-12 border-2 border-white rounded-full">
                        <div class="text-white">
                            <a href="{{ route('profile.show') }}" class="text-white block hover:opacity-80 transition-opacity">
                                <div class="font-bold leading-tight truncate w-44">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-white/80 truncate w-44">{{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}</div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-purpura-50 text-purpura-700 font-bold dark:bg-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700' }}">
                        <i class="text-lg ph ph-squares-four"></i> Dashboard
                    </a>

                    @foreach($menuGrupos as $grupo)
                        @continue(!$grupo['visivel'])
                        @php
                            $itensVisiveis = collect($grupo['itens'])->filter(fn($i) => $i['visivel'] ?? false);
                            $temLinkAtivo = $itensVisiveis->contains(fn($i) => isset($i['route']) && request()->routeIs($i['route']));
                        @endphp
                        @continue($itensVisiveis->whereNotNull('route')->isEmpty())

                        <div x-data="{ open: {{ $temLinkAtivo ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg {{ $temLinkAtivo ? 'bg-purpura-50 text-purpura-700 font-bold dark:bg-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700' }}">
                                <div class="flex items-center gap-3"><i class="text-lg ph {{ $grupo['icone'] }}"></i> {{ $grupo['titulo'] }}</div>
                                <i class="ph ph-caret-down transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" class="pl-10 pr-3 py-1 space-y-1" x-collapse>
                                @foreach($itensVisiveis as $item)
                                    @if(isset($item['separador']))
                                        <div class="pt-2 pb-0.5 text-[10px] font-bold text-gray-400 uppercase">{{ $item['separador'] }}</div>
                                    @elseif(Route::has($item['route']))
                                        <a href="{{ route($item['route']) }}" @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                                           class="block py-1.5 text-sm {{ request()->routeIs($item['route']) ? 'text-purpura-700 font-bold dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-purpura-600' }}">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    <livewire:auth.logout-button />
                </div>
            </div>
        </div>

        <livewire:components.quick-view-drawer />
        @livewireScripts
        <x-toast />
        @if(!session('manter_conectado') && !auth('web')->viaRemember())
            <x-session-timeout />
        @endif
    </body>
</html>