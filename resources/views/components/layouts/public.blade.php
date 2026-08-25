<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ 
          tema: localStorage.getItem('tema_sistema') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
          toggleTema() {
              this.tema = this.tema === 'light' ? 'dark' : 'light';
              localStorage.setItem('tema_sistema', this.tema);
              if (this.tema === 'dark') {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }" 
      x-init="
          if (tema === 'dark') { document.documentElement.classList.add('dark'); }
          else { document.documentElement.classList.remove('dark'); }
      "
      :class="tema === 'dark' ? 'dark h-full' : 'h-full'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Instituto Percorre' }}</title>

    <!-- Script Bloqueante: Evita FOUC (piscada de tema) -->
    <script>
        (function() {
            const temaSalvo = localStorage.getItem('tema_sistema');
            const prefereEscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (temaSalvo === 'dark' || (!temaSalvo && prefereEscuro)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen text-gray-900 transition-colors duration-300 bg-slate-50 dark:text-gray-100 dark:bg-gray-950 antialiased">
    
    <!-- Wrapper do Menu AlpineJS -->
    <div x-data="{ drawerOpen: false }">
        
        <!-- NAVBAR PÚBLICA -->
        <nav class="transition-colors duration-300 bg-[#310B47] border-b border-white/10 dark:bg-gray-900 dark:border-gray-800 relative z-30">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Lado Esquerdo (Menu Mobile + Logo Oficial) -->
                    <div class="flex items-center gap-4">
                        <button @click="drawerOpen = true" class="p-2 -ml-2 text-white/80 rounded-md md:hidden hover:bg-white/10 dark:text-gray-300 dark:hover:bg-gray-800 focus:outline-none transition-colors">
                            <i class="text-2xl ph ph-list"></i>
                        </button>

                        <a href="/" class="flex-shrink-0 flex items-center transition-opacity hover:opacity-90">
                            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-9 w-auto" alt="Instituto Percorre">
                        </a>
                    </div>

                    <!-- Lado Direito (Ações Desktop) -->
                    <div class="hidden items-center gap-3 md:flex">
                        <!-- Botão de Alternância de Tema -->
                        <button
                            @click="toggleTema()"
                            type="button"
                            title="Alternar Tema"
                            class="p-2 rounded-full flex items-center justify-center text-white/90 transition-colors hover:bg-white/10 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            <i class="ph ph-moon text-xl" x-show="tema === 'light'"></i>
                            <i class="ph ph-sun text-xl text-[#FFA301]" x-show="tema === 'dark'" x-cloak></i>
                        </button>

                        <div class="w-px h-5 bg-white/20 dark:bg-gray-700"></div>

                        <!-- Botões de Login Delicados / Arredondados -->
                        <div class="flex items-center gap-2.5">
                            <a href="{{ route('login') }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition-colors border border-white/25 rounded-full hover:bg-white/10">
                                Acesso Restrito
                            </a>
                            <a href="{{ route('portal.login') }}" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-[#310B47] bg-[#FFA301] hover:bg-[#e08e00] rounded-full transition-colors shadow-sm">
                                <i class="mr-1 ph-bold ph-graduation-cap text-sm"></i> Sou Estudante
                            </a>
                        </div>
                    </div>

                    <!-- Lado Direito (Mobile - Apenas Tema) -->
                    <div class="flex md:hidden">
                        <button @click="toggleTema()" type="button" class="flex items-center justify-center p-2 text-white/90 transition-colors rounded-full hover:bg-white/10 dark:text-gray-300">
                            <i class="text-xl ph ph-moon" x-show="tema === 'light'"></i>
                            <i class="text-xl ph ph-sun text-[#FFA301]" x-show="tema === 'dark'" x-cloak></i>
                        </button>
                    </div>

                </div>
            </div>
        </nav>

        <!-- NAVIGATION DRAWER (MOBILE) -->
        <div x-show="drawerOpen" 
            x-transition.opacity.duration.300ms 
            @click="drawerOpen = false"
            class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" 
            x-cloak>
        </div>

        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-4/5 max-w-sm transition-transform duration-300 ease-in-out transform bg-white shadow-2xl dark:bg-gray-900 md:hidden"
            :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="relative flex-shrink-0 h-36 overflow-hidden bg-[#310B47] flex items-center px-6">
                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" alt="Instituto Percorre" class="h-8 w-auto">
            </div>

            <div class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                <a href="{{ route('login') }}" class="flex items-center justify-center w-full gap-2 px-4 py-3 text-xs font-bold uppercase tracking-wider border rounded-full text-[#310B47] border-[#310B47] dark:text-white dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <i class="text-base ph ph-lock-key"></i> Acesso Restrito
                </a>
                
                <a href="{{ route('portal.login') }}" class="flex items-center justify-center w-full gap-2 px-4 py-3 text-xs font-bold uppercase tracking-wider text-[#310B47] bg-[#FFA301] hover:bg-[#e08e00] rounded-full transition-colors">
                    <i class="text-base ph ph-graduation-cap"></i> Sou Estudante
                </a>
            </div>
            
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button @click="drawerOpen = false" class="px-4 py-2 text-xs font-bold uppercase text-gray-600 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-gray-300 transition-colors hover:bg-gray-200">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Conteúdo da Página -->
    <main class="flex-1 flex flex-col">
        {{ $slot }}
    </main>

    <!-- RODAPÉ INSTITUCIONAL -->
    <footer class="bg-[#1f072e] text-gray-300 pt-16 pb-12 transition-colors duration-300 mt-auto relative z-10 dark:bg-gray-950 dark:border-t dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-white/10">
                
                <!-- Coluna Esquerda -->
                <div class="md:col-span-5 lg:col-span-6 space-y-5">
                    <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" alt="Instituto Percorre" class="h-9 w-auto">
                    <p class="text-sm text-gray-300/90 max-w-sm leading-relaxed font-normal">
                        Formação profissional gratuita e empregabilidade para jovens e PcD desde 1998.
                    </p>
                </div>

                <!-- Colunas da Direita -->
                <div class="md:col-span-2 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Estudante</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Cursos</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Quem somos</a></li>
                    </ul>
                </div>

                <div class="md:col-span-2 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Empresa</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Contratar talentos</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Apoie o Percorre</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3 lg:col-span-2 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Contato</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="mailto:contato@percorre.org.br" class="hover:text-white transition-colors">contato@percorre.org.br</a></li>
                        <li><span class="text-gray-400">(11) 2503-2617</span></li>
                        <li><a href="#" class="hover:text-white transition-colors">Instagram</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">LinkedIn</a></li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-400">
                <p>© {{ date('Y') }} Instituto Percorre - CNPJ 02.449.283/0001-89</p>
                
                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-white transition-colors">Política de Privacidade</a>
                        <span>·</span>
                        <a href="#" class="hover:text-white transition-colors">Termos de Uso</a>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white transition-colors"><i class="ph ph-linkedin-logo text-base"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white transition-colors"><i class="ph ph-instagram-logo text-base"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white transition-colors"><i class="ph ph-facebook-logo text-base"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <livewire:components.quick-view-drawer />
    @livewireScripts
    <x-toast />
</body>
</html>