<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ tema: localStorage.getItem('tema_sistema') || 'light' }" 
      x-init="$watch('tema', valor => localStorage.setItem('tema_sistema', valor))"
      :class="tema === 'dark' ? 'dark h-full bg-gray-900' : 'h-full bg-slate-50'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Instituto Percorre' }}</title>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen text-gray-900 transition-colors duration-300 dark:text-gray-100 dark:bg-gray-900 antialiased">
    
    <!-- Navbar Pública -->
    <header class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 text-white rounded-xl bg-purpura-500">
                        <span class="text-xl font-bold">P</span>
                    </div>
                    <span class="text-2xl font-bold text-purpura-700 dark:text-purpura-400">Percorre</span>
                </div>

                <!-- Ações (Tema + Logins) -->
                <div class="flex items-center gap-4">
                    <!-- Botão de Tema Público -->
                    <button @click="tema = tema === 'light' ? 'dark' : 'light'" class="p-2 text-gray-500 transition-colors rounded-full dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="text-2xl ph ph-moon" x-show="tema === 'light'"></i>
                        <i class="text-2xl ph ph-sun text-ponkan-500" x-show="tema === 'dark'" x-cloak></i>
                    </button>

                    <div class="hidden w-px h-8 bg-gray-200 md:block dark:bg-gray-700"></div>

                    <!-- Botões de Login -->
                    <div class="hidden md:flex gap-3">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-800 transition-colors">
                            Acesso Restrito
                        </a>
                        <a href="{{ route('student.login') }}" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 transition-colors shadow-sm">
                            <i class="ph-bold ph-graduation-cap mr-1"></i> Sou Estudante
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo da Página -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- Rodapé Público -->
    <footer class="py-8 bg-white border-t border-gray-200 dark:bg-gray-900 dark:border-gray-800">
        <div class="px-4 text-center text-gray-500 dark:text-gray-400 max-w-7xl mx-auto">
            <p class="text-sm">© {{ date('Y') }} Instituto Percorre. Todos os direitos reservados.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>