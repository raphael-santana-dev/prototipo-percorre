<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistema' }}</title>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-gray-900 antialiased">
    
    <nav class="bg-white border-b border-gray-200">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 font-bold text-white bg-blue-600 rounded-lg">S</div>
                        <span class="font-semibold text-gray-800">Sistema</span>
                    </div>
                    <!-- Menu de Navegação -->
                    <div class="hidden md:flex md:gap-4">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100">Dashboard</a>
                        
                        <!-- Protegendo o link do menu também, aparece apenas se for role dev -->
                        @role('dev')
                            <a href="{{ route('users.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100">Usuários</a>
                            <a href="{{ route('roles.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100">Roles</a>
                            <a href="{{ route('permissions.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100">Permissões</a>
                            <a href="{{ route('features.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100">Features</a>
                        @endrole
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Logado como: <strong>{{ auth()->user()->name }}</strong></span>
                    <div class="w-px h-6 bg-gray-200"></div>
                    <livewire:auth.logout-button />
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo da Página -->
    <main class="py-10">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>