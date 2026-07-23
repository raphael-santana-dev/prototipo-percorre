<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal do Aluno' }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-900 antialiased">
    <nav class="bg-indigo-600 border-b border-indigo-700">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-8">
                    <span class="font-bold text-white text-lg">🎓 Portal do Aluno</span>
                    
                    <!-- Menu Principal -->
                    <div class="hidden md:flex gap-4">
                        <a href="{{ route('student.dashboard') }}" class="px-3 py-2 text-sm font-medium text-white bg-indigo-700 rounded-md">Meus Cursos</a>
                        
                        <!-- Link protegido pela feature -->
                        @feature('alunos.biblioteca')
                            <a href="{{ route('student.library') }}" class="px-3 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500 rounded-md transition-colors">
                                Biblioteca
                            </a>
                        @endfeature
                    </div>
                </div>
                <div class="flex items-center gap-4 text-white">
                    <span class="text-sm text-indigo-100">Olá, <strong>{{ auth('student')->user()->name }}</strong></span>
                    <div class="w-px h-6 bg-indigo-400"></div>
                    <livewire:student.auth.logout-button />
                </div>
            </div>
        </div>
    </nav>
    <main class="py-10">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>
    @livewireScripts
</body>
</html>