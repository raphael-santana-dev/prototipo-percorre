<div class="min-h-screen flex items-center justify-center bg-white dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        
        <!-- Logo -->
        <div class="flex justify-start">
            <div class="p-3 bg-[#461a63] rounded-xl flex items-center justify-center">
                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
            </div>
        </div>

        <div class="text-left mt-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Segurança</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Como este é o seu primeiro acesso, crie uma senha forte e pessoal para continuar.
            </p>
        </div>

        <form wire:submit.prevent="salvar" class="mt-8 space-y-6">
            
            <div class="space-y-4">
                <div class="relative" x-data="{ show: false }">
                    <input wire:model.live="password" :type="show ? 'text' : 'password'" required placeholder="Digite a nova senha" class="appearance-none block w-full px-4 py-3.5 border border-gray-300 dark:border-gray-700 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purpura-500 focus:border-transparent sm:text-sm dark:bg-gray-800 dark:text-white transition-all">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="ph text-xl" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
                @error('password') <span class="text-xs font-medium text-red-500 mt-1 block">{{ $message }}</span> @enderror

                <div class="relative" x-data="{ show: false }">
                    <input wire:model.live="password_confirmation" :type="show ? 'text' : 'password'" required placeholder="Confirme a nova senha" class="appearance-none block w-full px-4 py-3.5 border border-gray-300 dark:border-gray-700 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purpura-500 focus:border-transparent sm:text-sm dark:bg-gray-800 dark:text-white transition-all">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="ph text-xl" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-lg text-sm font-bold text-white bg-[#461a63] hover:bg-purpura-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500 transition-colors shadow-sm">
                    Atualizar Senha
                </button>
            </div>
            
            <div class="text-left mt-4">
                <button type="button" wire:click="logout" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:hover:text-white transition">
                    Cancelar e sair
                </button>
            </div>
        </form>
    </div>
</div>