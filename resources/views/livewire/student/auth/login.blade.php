<div class="flex items-center justify-center flex-1 w-full px-4 py-12 sm:px-6 lg:px-8">
    
    <div class="w-full max-w-md p-8 space-y-6 bg-white border border-gray-100 shadow-xl rounded-2xl dark:bg-gray-800 dark:border-gray-700 animate-fade-in-down">
        
        <div class="text-center border-b border-gray-100 pb-4 dark:border-gray-700">
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Portal do Aluno</h2>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">Acesse sua sala de aula virtual</p>
        </div>
        
        <form wire:submit="authenticate" class="space-y-5">
            
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300">E-mail</label>
                <div class="relative mt-1">
                    <i class="absolute text-gray-400 transform -translate-y-1/2 left-3 top-1/2 ph ph-envelope-simple"></i>
                    <input id="email" type="email" wire:model="email" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-ponkan-500 focus:ring-ponkan-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="seu@email.com" required autofocus>
                </div>
                @error('email') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Senha</label>
                <div class="relative mt-1">
                    <i class="absolute text-gray-400 transform -translate-y-1/2 left-3 top-1/2 ph ph-lock-key"></i>
                    <input id="password" type="password" wire:model="password" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-ponkan-500 focus:ring-ponkan-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="••••••••" required>
                </div>
                @error('password') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" type="checkbox" wire:model="remember" class="w-4 h-4 border-gray-300 rounded text-ponkan-600 focus:ring-ponkan-500 dark:border-gray-600 dark:bg-gray-700">
                    <label for="remember" class="block ml-2 text-sm text-gray-700 dark:text-gray-300">Lembrar de mim</label>
                </div>
                
                <div class="text-sm">
                    <a href="#" class="font-bold text-ponkan-600 hover:text-ponkan-500 dark:text-ponkan-400 dark:hover:text-ponkan-300">Esqueceu a senha?</a>
                </div>
            </div>

            <div>
                <button type="submit" class="flex justify-center w-full px-4 py-2.5 text-sm font-bold text-white transition-colors border border-transparent rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ponkan-500">
                    <i class="mr-2 text-lg ph ph-graduation-cap"></i> Acessar Portal
                </button>
            </div>
        </form>
    </div>
</div>