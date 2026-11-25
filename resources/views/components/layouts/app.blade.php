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
        $pode = fn(string $chave) => feature($chave) && $can($chave);
        $podeAny = fn(array $chaves) => collect($chaves)->contains(fn($c) => $pode($c));

        $menuGrupos = [
            [
                'titulo'  => 'Financeiro',
                'icone'   => 'ph-receipt',
                'visivel' => true,
                'itens'   => [
                    ['label' => 'Orçamentos', 'route' => 'financeiro.orcamentos', 'visivel' => $pode('financeiro.orcamento.listar')],
                    ['label' => 'Aprovações', 'route' => 'financeiro.aprovacoes', 'visivel' => $pode('financeiro.orcamento.aprovar')],
                    ['label' => 'Centros de Custo', 'route' => 'financeiro.centro_custo', 'visivel' => $pode('financeiro.centro_custo.listar')],
                    ['label' => 'Naturezas', 'route' => 'financeiro.naturezas', 'visivel' => $pode('financeiro.natureza.listar')],
                ],
            ],
            [
                'titulo'  => 'Processos Seletivos',
                'icone'   => 'ph-calendar-check',
                'visivel' => true,
                'itens'   => [
                    ['label' => 'Ciclos de Inscrição', 'route' => 'ciclos.index', 'visivel' => $pode('inscricao.ciclo.listar')],
                    ['label' => 'Inscrições', 'route' => 'inscricoes.index', 'visivel' => $pode('inscricao.inscritos.listar')],
                    ['label' => 'Kanban (CRM)', 'route' => 'ciclos.crm', 'visivel' => $pode('inscricao.inscritos.listar')],
                    ['label' => 'Acompanhamento (Portal IA)', 'route' => 'matriculas.acompanhamento', 'visivel' => $pode('inscricao.inscritos.validar_docs')],
                    ['label' => 'Configuração IA Matrículas', 'route' => 'matriculas.configuracao', 'visivel' => $pode('ia.configuracao')],
                ],
            ],
            [
                'titulo'  => 'Secretaria',
                'icone'   => 'ph-folder-user',
                'visivel' => true,
                'itens'   => [
                    ['label' => 'Base de Alunos', 'route' => 'students.index', 'visivel' => $podeAny(['secretaria.alunos.listar', 'secretaria.alunos.visualizar'])],
                    ['label' => 'Solicitações', 'route' => 'solicitacoes.index', 'visivel' => $pode('secretaria.solicitacoes.listar')],
                    ['label' => 'Tags de Status', 'route' => 'status-inscricoes.index', 'visivel' => $pode('secretaria.status_inscricao.listar')],
                    ['label' => 'Empresas Parceiras', 'route' => 'empresas.index', 'visivel' => $pode('empresas.listar')],
                ],
            ],
            [
                'titulo'  => 'Instituição',
                'icone'   => 'ph-buildings',
                'visivel' => true,
                'itens'   => [
                    ['label' => 'Unidades', 'route' => 'unidades.index', 'visivel' => $pode('instituicao.unidades.listar')],
                    ['label' => 'Cursos', 'route' => 'cursos.index', 'visivel' => $pode('instituicao.cursos.listar')],
                    ['label' => 'Grade de Turnos', 'route' => 'turnos.index', 'visivel' => $pode('instituicao.turnos.listar')],
                    ['label' => 'Escolas', 'route' => 'escolas.index', 'visivel' => $pode('instituicao.escolas.listar')],
                    ['separador' => 'Formulários', 'visivel' => $podeAny(['instituicao.formularios.listar', 'instituicao.formbuilder.acessar'])],
                    ['label' => 'Formulários Gerais', 'route' => 'formularios.index', 'visivel' => $pode('instituicao.formularios.listar')],
                    ['label' => 'Form Builder (Hub)', 'route' => 'formbuilder.hub', 'visivel' => $pode('instituicao.formbuilder.acessar')],
                ],
            ],
            [
                'titulo'  => 'Comunicação',
                'icone'   => 'ph-paper-plane-tilt',
                'visivel' => true,
                'itens'   => [
                    ['label' => 'Dashboard Comunicação', 'route' => 'comunicacao.dashboard', 'visivel' => $pode('comunicacao.dashboard.visualizar')],
                    ['separador' => 'Mensagens & Automação', 'visivel' => $podeAny(['comunicacao.comunicados.listar', 'comunicacao.esteira_disparo.listar', 'comunicacao.automacao.listar', 'comunicacao.templates.listar', 'comunicacao.monitor_emails.listar'])],
                    ['label' => 'Comunicados', 'route' => 'comunicados.index', 'visivel' => $pode('comunicacao.comunicados.listar')],
                    ['label' => 'Esteiras de Disparo', 'route' => 'esteiras.index', 'visivel' => $pode('comunicacao.esteira_disparo.listar')],
                    ['label' => 'Automações', 'route' => 'automacoes.index', 'visivel' => $pode('comunicacao.automacao.listar')],
                    ['label' => 'Templates', 'route' => 'templates.index', 'visivel' => $pode('comunicacao.templates.listar')],
                    ['label' => 'Monitor de E-mails', 'route' => 'monitor.emails', 'visivel' => $pode('comunicacao.monitor_emails.listar')],
                    ['separador' => 'WhatsApp (Suri)', 'visivel' => $podeAny(['comunicacao.webhook_suri.acessar', 'comunicacao.laboratorio_suri.acessar'])],
                    ['label' => 'Monitor Suri Webhook', 'route' => 'monitor.suri', 'visivel' => $pode('comunicacao.webhook_suri.acessar')],
                    ['label' => 'Laboratório Suri', 'route' => 'suri.dashboard', 'visivel' => $pode('comunicacao.laboratorio_suri.acessar')],
                    ['separador' => 'Portal & Notícias', 'visivel' => $pode('portal.noticias')],
                    ['label' => 'Gestão de Publicações', 'route' => 'conteudo.index', 'visivel' => $pode('portal.noticias')],
                    ['label' => 'Categorias', 'route' => 'conteudo.categorias', 'visivel' => $pode('portal.noticias')],
                    ['label' => 'Ver Portal ↗', 'route' => 'portal.index', 'target' => '_blank', 'visivel' => $pode('portal.noticias')],
                ],
            ],
            [
                'titulo'  => 'Educacional',
                'icone'   => 'ph-graduation-cap',
                'visivel' => true,
                'itens'   => [
                    ['separador' => 'Matrículas & Turmas', 'visivel' => $podeAny(['educacional.matricula.acessar', 'educacional.turmas.acessar', 'educacional.aprendizagem.acessar'])],
                    ['label' => 'Matrículas', 'route' => 'matriculas.index', 'visivel' => $pode('educacional.matricula.acessar')],
                    ['label' => 'Turmas', 'route' => 'turmas.index', 'visivel' => $pode('educacional.turmas.acessar')],
                    ['label' => 'Ciclos de Aprendizagem', 'route' => 'aprendizagem.index', 'visivel' => $pode('educacional.aprendizagem.acessar')],
                    ['separador' => 'Avaliações', 'visivel' => $podeAny(['educacional.avaliacoes.acessar', 'educacional.aprendizagem.acessar'])],
                    ['label' => 'Relatórios', 'route' => 'avaliacoes.relatorios', 'visivel' => $pode('educacional.avaliacoes.acessar')],
                    ['label' => 'Períodos de Avaliação', 'route' => 'avaliacoes.periodos.index', 'visivel' => $pode('educacional.avaliacoes.acessar')],
                    ['label' => 'Avaliações', 'route' => 'avaliacoes.index', 'visivel' => $pode('educacional.avaliacoes.acessar')],
                    ['label' => 'Gerador Mock', 'route' => 'avaliacoes.gerador', 'visivel' => $pode('educacional.avaliacoes.acessar')],
                    ['label' => 'Gerador Aprendizagem', 'route' => 'avaliacoes.gerador-aprendizagem', 'visivel' => $pode('educacional.aprendizagem.acessar')],
                ],
            ],
            [
                'titulo'     => 'Administração',
                'icone'      => 'ph-gear',
                'alignRight' => true,
                'visivel'    => true,
                'itens'      => [
                    ['label' => 'Configurações Base', 'route' => 'configuracoes.index', 'visivel' => $pode('administracao.configuracao.acessar')],
                    ['label' => 'Gestão de Usuários', 'route' => 'users.index', 'visivel' => $pode('administracao.usuarios.listar')],
                    ['label' => 'Perfis (Roles)', 'route' => 'roles.index', 'visivel' => $pode('administracao.perfis.listar')],
                    ['label' => 'Permissões', 'route' => 'permissions.index', 'visivel' => $pode('administracao.permissoes.listar')],
                    ['label' => 'Feature Toggles', 'route' => 'features.index', 'visivel' => $pode('administracao.features.listar')],
                    ['separador' => 'Sistema & Logs', 'visivel' => $podeAny(['administracao.auditoria.listar', 'administracao.hub_integracao.acessar', 'administracao.central_erros.acessar'])],
                    ['label' => 'Auditoria Geral', 'route' => 'auditoria.index', 'visivel' => $pode('administracao.auditoria.listar')],
                    ['label' => 'Motor de Importações', 'route' => 'system_tasks.index', 'visivel' => $pode('administracao.hub_integracao.acessar')],
                    ['label' => 'Hub de Tarefas', 'route' => 'system_tasks.hub', 'visivel' => $pode('administracao.hub_integracao.acessar')],
                    ['label' => 'Central de Erros', 'route' => 'system.errors.index', 'visivel' => $pode('administracao.central_erros.acessar')],
                    ['separador' => 'Dev / Testes', 'visivel' => $podeAny(['administracao.teste_crm.acessar', 'administracao.design_systema.acessar'])],
                    ['label' => 'Teste RD CRM', 'route' => 'teste.rdcrm', 'visivel' => $pode('administracao.teste_crm.acessar')],
                    ['label' => 'Design System', 'route' => 'teste.design', 'visivel' => $pode('administracao.design_systema.acessar')],
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
                setTimeout(() => {
                    this.loaded = true;
                    document.documentElement.classList.add('layout-ready');
                }, 50);
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
            <aside class="js-sidebar hidden md:flex flex-col bg-petunia-900 dark:bg-petunia-1000 z-40 shrink-0 shadow-lg overflow-x-hidden">
                
                <div class="h-16 flex items-center justify-between border-b border-white/10 shrink-0 min-w-[72px]"
                     :class="(loaded ? 'transition-all duration-300 ease-in-out ' : '') + (sidebarMinimized ? 'px-1.5 gap-1' : 'px-4 gap-2')">
                    <a href="{{ route('dashboard') }}"
                       class="relative flex items-center flex-1 min-w-0 h-8 overflow-hidden"
                       title="Instituto Percorre">
                        {{-- Logo completa (apenas sidebar expandida) --}}
                        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}"
                             class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-auto max-w-none"
                             :class="(loaded ? 'transition-all duration-300 ease-in-out ' : '') + (sidebarMinimized ? 'opacity-0 -translate-x-2 pointer-events-none' : 'opacity-100 translate-x-0')"
                             alt="Instituto Percorre">

                        {{-- Novo ícone (apenas sidebar compacta) --}}
                        <img src="{{ asset('images/logo-svg.svg') }}"
                             x-cloak
                             class="absolute left-0.5 top-1/2 -translate-y-1/2 h-5 w-auto max-w-none"
                             :class="(loaded ? 'transition-all duration-300 ease-in-out ' : '') + (sidebarMinimized ? 'opacity-100 scale-100' : 'opacity-0 scale-75 pointer-events-none')"
                             alt="Instituto Percorre">
                    </a>

                    <button @click="toggleMinimize()"
                            class="text-white/70 hover:bg-white/10 hover:text-white rounded-lg shrink-0"
                            :class="(loaded ? 'transition-all duration-300 ease-in-out ' : '') + (sidebarMinimized ? 'p-1' : 'p-1.5')"
                            title="Recolher / Expandir">
                        <i class="ph ph-list transition-all duration-300 ease-in-out" :class="sidebarMinimized ? 'text-lg' : 'text-xl'"></i>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar py-4 space-y-1.5 px-3 min-w-[72px]">
                    
                    {{-- Dashboard --}}
                    @if($pode('principal.dashboard.visualizar'))
                        <a href="{{ route('dashboard') }}" 
                           x-data="{ hover: false }" 
                           @mouseenter="hover = true; if(sidebarMinimized) { $refs.pop.style.top = ($el.getBoundingClientRect().top + 6) + 'px'; $refs.pop.style.left = $el.getBoundingClientRect().right + 'px'; }" 
                           @mouseleave="hover = false" 
                           class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-lg transition-colors relative whitespace-nowrap overflow-hidden {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <i class="text-xl ph ph-squares-four shrink-0"></i>
                            <span class="js-sidebar-label truncate">Dashboard</span>
                            
                            <div x-ref="pop" x-show="sidebarMinimized && hover" x-transition.opacity.duration.200ms x-cloak class="fixed z-[999] pl-2 pointer-events-none">
                                <div class="px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded shadow-lg">
                                    Dashboard
                                </div>
                            </div>
                        </a>
                    @endif

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
                            <button @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold rounded-lg transition-colors overflow-hidden {{ $temLinkAtivo ? 'bg-white/10 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <i class="text-xl ph {{ $grupo['icone'] }} shrink-0"></i>
                                    <span class="js-sidebar-label truncate">{{ $grupo['titulo'] }}</span>
                                </div>
                                <i class="js-sidebar-label ph ph-caret-down text-[10px] shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"></i>
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
                                
                                <a href="{{ route('dashboard') }}" class="hidden md:block overflow-hidden">
                                    <div class="js-topcontrols flex items-center gap-4">
                                        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto max-w-none shrink-0" alt="Instituto Percorre">
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

                                @if($pode('portal.noticias'))
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
                <nav class="js-topnav hidden md:block bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 relative z-[100] shrink-0 origin-top">
                    <div class="sm:px-6 px-8 mx-auto w-full max-w-8xl">
                        <div class="flex flex-wrap items-center min-h-[3rem] py-1 gap-x-2 gap-y-1">
                            @if($pode('principal.dashboard.visualizar'))
                                <a href="{{ route('dashboard') }}" class="whitespace-nowrap flex items-center gap-1.5 px-2.5 py-1.5 text-[13px] font-semibold transition-colors rounded-md {{ request()->routeIs('dashboard') ? 'text-purpura-700 bg-purpura-50 dark:bg-gray-800 dark:text-white' : 'text-gray-600 hover:text-purpura-600 hover:bg-purpura-50 dark:text-gray-300' }}">
                                    <i class="text-base ph ph-squares-four"></i> Dashboard
                                </a>
                            @endif

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
                    @if($pode('principal.dashboard.visualizar'))
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-purpura-50 text-purpura-700 font-bold dark:bg-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-purpura-50 hover:text-purpura-600 dark:hover:bg-gray-700' }}">
                            <i class="text-lg ph ph-squares-four"></i> Dashboard
                        </a>
                    @endif

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