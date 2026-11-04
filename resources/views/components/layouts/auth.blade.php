<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ tema: localStorage.getItem('tema_sistema') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" 
      x-init="$watch('tema', valor => { 
          localStorage.setItem('tema_sistema', valor); 
          document.documentElement.classList.toggle('dark', valor === 'dark'); 
      })"
      :class="{ 'dark': tema === 'dark' }"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Acesso ao Sistema' }}</title>

    <script>
        (function () {
            var d = document.documentElement;
            var tema = localStorage.getItem('tema_sistema');
            if (!tema) {
                tema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                localStorage.setItem('tema_sistema', tema);
            }
            d.classList.toggle('dark', tema === 'dark');
        })();
    </script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="h-full flex items-center justify-center transition-colors duration-500 bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased overflow-x-hidden">
    
    {{ $slot }}

    <livewire:components.quick-view-drawer />
    @livewireScripts
</body>
</html>