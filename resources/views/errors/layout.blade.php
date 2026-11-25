<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aviso') — Instituto Percorre</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 flex flex-col font-sans">

    {{-- TOPO SÓBRIO COM A LOGO OFICIAL --}}
    <header class="h-14 bg-petunia-900 border-b border-white/10 shrink-0">
        <div class="max-w-5xl mx-auto h-full px-4 sm:px-6 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center">
                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-7 w-auto" alt="Instituto Percorre">
            </a>
            <span class="text-xs font-mono text-white/60">
                {{ now()->format('d/m/Y H:i') }}
            </span>
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            
            <div class="p-6 sm:p-7 space-y-5">
                {{-- Rótulo discreto --}}
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">
                        @yield('code', '403')
                    </span>
                    <span>·</span>
                    <span>@yield('badge_label', 'Aviso do Sistema')</span>
                </div>

                {{-- Título e mensagem --}}
                <div class="space-y-2">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white leading-snug">
                        @yield('heading')
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        @yield('message')
                    </p>
                </div>

                {{-- Conteúdo complementar --}}
                @hasSection('extra_content')
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        @yield('extra_content')
                    </div>
                @endif
            </div>

            {{-- Rodapé de ações --}}
            @php
                $dashboardUrl = auth('student')->check()
                    ? (Route::has('student.dashboard') ? route('student.dashboard') : url('/'))
                    : (auth('company')->check()
                        ? (Route::has('company.dashboard') ? route('company.dashboard') : url('/'))
                        : (Route::has('dashboard') ? route('dashboard') : url('/')));
            @endphp
            <div class="px-6 sm:px-7 py-4 bg-gray-50 dark:bg-gray-900/60 border-t border-gray-200 dark:border-gray-700 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                <button type="button"
                        onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ $dashboardUrl }}'"
                        class="px-3.5 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 transition text-center">
                    Voltar
                </button>
                <a href="{{ $dashboardUrl }}"
                   class="px-4 py-2 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold transition text-center">
                    Ir para o Dashboard
                </a>
            </div>

        </div>
    </main>

</body>
</html>