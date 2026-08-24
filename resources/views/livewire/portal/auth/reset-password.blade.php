<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-500 to-purpura-600"></div>

        <div class="text-center">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white mt-4">Criar Nova Senha</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Insira sua nova credencial de acesso seguro.
            </p>
        </div>

        <form wire:submit.prevent="redefinir" class="mt-8 space-y-5">
            
            <input type="hidden" wire:model="token">

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">E-mail</label>
                <input wire:model="email" type="email" required readonly class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 sm:text-sm cursor-not-allowed dark:bg-gray-900/50 dark:border-gray-700">
                @error('email') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Nova Senha</label>
                <div class="mt-1 relative">
                    <input wire:model.live="password" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                </div>
                @error('password') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Confirmar Nova Senha</label>
                <div class="mt-1 relative">
                    <input wire:model.live="password_confirmation" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> No mínimo 8 caracteres</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Letras maiúsculas e minúsculas</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Pelo menos um número (0-9)</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Pelo menos um símbolo (!@#$%)</li>
                </ul>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500">
                Redefinir Senha
            </button>
        </form>
    </div>
</div>