<div class="flex min-h-screen bg-white dark:bg-gray-900 w-full">
    
    <!-- Lado Esquerdo: Imagem e Branding -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-ponkan-500 items-center justify-center overflow-hidden">
        <!-- Overlay escurecido para a imagem SVG -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20 mix-blend-multiply" style="background-image: url('{{ Vite::asset('resources/images/bg-hero.svg') }}');"></div>
        
        <div class="relative z-10 text-white text-center px-12 lg:px-24">
            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" alt="Instituto Percorre" class="h-16 mx-auto mb-10 filter drop-shadow-md">
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6 leading-tight">Sua jornada <br>começa agora.</h1>
            <p class="text-lg text-white/90 font-medium">Acesse a sala de aula virtual e acompanhe a sua evolução em nossos cursos.</p>
        </div>
    </div>

    <!-- Lado Direito: Formulário de Login -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-gray-50 dark:bg-gray-950">
        
        <div class="w-full max-w-md space-y-8 animate-fade-in-down">
            
            <div class="text-center pb-2">
                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" alt="Logo" class="h-12 mx-auto mb-6 lg:hidden filter brightness-0 dark:invert">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Portal de Acesso</h2>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Insira seu e-mail e senha para acessar</p>
            </div>
            
            <form wire:submit="authenticate" class="space-y-6 mt-8">
                
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300">E-mail/CPF/CNPJ</label>
                    <div class="relative mt-2">
                        <i class="absolute text-gray-400 transform -translate-y-1/2 left-4 top-1/2 ph ph-envelope-simple text-lg"></i>
                        <input id="email" type="email" wire:model="email" class="w-full pl-12 py-3 bg-white border border-gray-200 rounded-xl shadow-sm focus:border-ponkan-500 focus:ring-2 focus:ring-ponkan-500/20 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all" placeholder="seu@email.com" required autofocus>
                    </div>
                    @error('email') <span class="block mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Senha</label>
                        <a href="#" class="text-xs font-bold text-ponkan-600 hover:text-ponkan-700 dark:text-ponkan-400">Esqueceu a senha?</a>
                    </div>
                    <div class="relative">
                        <i class="absolute text-gray-400 transform -translate-y-1/2 left-4 top-1/2 ph ph-lock-key text-lg"></i>
                        <input id="password" type="password" wire:model="password" class="w-full pl-12 py-3 bg-white border border-gray-200 rounded-xl shadow-sm focus:border-ponkan-500 focus:ring-2 focus:ring-ponkan-500/20 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all" placeholder="••••••••" required>
                    </div>
                    @error('password') <span class="block mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center">
                    <input id="remember" type="checkbox" wire:model="remember" class="w-4 h-4 border-gray-300 rounded text-ponkan-600 focus:ring-ponkan-500 dark:border-gray-600 dark:bg-gray-900">
                    <label for="remember" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Manter conectado</label>
                </div>

                <div class="pt-4">
                    <button type="submit" class="flex items-center justify-center w-full px-4 py-3.5 text-sm font-bold text-gray-900 transition-transform transform rounded-xl shadow-sm bg-ponkan-500 hover:bg-ponkan-600 hover:-translate-y-0.5">
                        Acessar Sala de Aula <i class="ml-2 text-lg ph-bold ph-graduation-cap"></i>
                    </button>
                </div>
            </form>
            
            <div class="text-center pt-8 text-xs text-gray-400 dark:text-gray-500 font-medium">
                &copy; {{ date('Y') }} Instituto Percorre.
            </div>
        </div>
    </div>
</div>