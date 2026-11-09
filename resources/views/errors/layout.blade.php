<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aviso do Sistema') — Instituto Percorre</title>

    <script>
        (function () {
            var d = document.documentElement;
            var tema = localStorage.getItem('tema_sistema');
            if (tema === 'dark') {
                d.classList.add('dark');
            } else {
                d.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="min-h-full font-sans antialiased bg-slate-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 flex flex-col justify-between p-4 sm:p-6 lg:p-8 relative overflow-x-hidden">

    @php
        // Resolve a rota inicial correta de acordo com o Guard autenticado
        $homeUrl = url('/');
        $homeLabel = 'Ir para a Página Inicial';

        if (auth('student')->check() && Route::has('student.dashboard')) {
            $homeUrl = route('student.dashboard');
            $homeLabel = 'Ir para o Meu Painel';
        } elseif (auth('company')->check() && Route::has('company.dashboard')) {
            $homeUrl = route('company.dashboard');
            $homeLabel = 'Ir para o Painel Parceiro';
        } elseif (auth('web')->check() && Route::has('dashboard')) {
            $homeUrl = route('dashboard');
            $homeLabel = 'Ir para o Dashboard';
        }
    @endphp

    {{-- Topo discreto com a marca --}}
    <header class="w-full max-w-4xl mx-auto flex items-center justify-between py-2">
        <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2.5 text-sm font-extrabold tracking-tight text-gray-800 dark:text-white hover:opacity-80 transition">
            <span class="w-8 h-8 rounded-lg bg-purpura-600 text-white flex items-center justify-center shadow-xs">
                <i class="ph-bold ph-Exclude text-lg"></i>
            </span>
            <span>Instituto Percorre</span>
        </a>

        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
            {{ now()->format('d/m/Y H:i') }}
        </span>
    </header>

    {{-- Card Central do Aviso / Erro --}}
    <main class="flex-1 flex items-center justify-center my-6">
        <div class="w-full max-w-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-xl overflow-hidden">
            
            {{-- Faixa superior colorida de acordo com a severidade --}}
            <div class="h-1.5 w-full @yield('accent_bar', 'bg-purpura-600')"></div>

            <div class="p-6 sm:p-8 text-center space-y-5">
                
                {{-- Ícone e Badge --}}
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center border shadow-xs @yield('icon_wrapper', 'bg-purpura-50 text-purpura-600 border-purpura-200 dark:bg-purpura-950/40 dark:text-purpura-400 dark:border-purpura-800')">
                        <i class="@yield('icon', 'ph-fill ph-warning-circle') text-3xl"></i>
                    </div>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider border @yield('badge_classes', 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700')">
                        @yield('badge_label', 'Aviso do Sistema')
                    </span>
                </div>

                {{-- Título e Explicação Amigável --}}
                <div class="space-y-2">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                        @yield('heading', 'Não foi possível concluir sua solicitação')
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        @yield('message')
                    </p>
                </div>

                {{-- Conteúdo Contextual Extra (ex: orientações de perfil no 403) --}}
                @hasSection('extra_content')
                    <div class="pt-1 text-left">
                        @yield('extra_content')
                    </div>
                @endif

                {{-- Botões de Ação Padrão --}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-center gap-3">
                    @hasSection('custom_actions')
                        @yield('custom_actions')
                    @else
                        <button type="button"
                                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ $homeUrl }}'; }"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 transition flex items-center justify-center gap-2 cursor-pointer">
                            <i class="ph-bold ph-arrow-left text-sm"></i>
                            <span>Voltar à Página Anterior</span>
                        </button>

                        <a href="{{ $homeUrl }}"
                           class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition flex items-center justify-center gap-2">
                            <i class="ph-bold ph-house text-sm"></i>
                            <span>{{ $homeLabel }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- Rodapé Institucional --}}
    <footer class="w-full max-w-4xl mx-auto text-center py-2 text-xs text-gray-400 dark:text-gray-500">
        Instituto Percorre • Ambiente Seguro
    </footer>

</body>
</html>