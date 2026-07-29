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
    <title>{{ $title ?? 'Acesso ao Sistema' }}</title>

    <!-- Script Bloqueante (FOUC) Consolidado -->
    <script>
        if (localStorage.getItem('tema_sistema') === 'dark' || (!('tema_sistema' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="h-full flex items-center justify-center transition-colors duration-500 bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    
    {{ $slot }}

    <!-- Componente Global embutido por segurança -->
    <livewire:components.quick-view-drawer />
    @livewireScripts
</body>
</html>