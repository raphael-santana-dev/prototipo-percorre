<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-500 to-purpura-600"></div>

        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-purpura-50 dark:bg-purpura-900/30 text-purpura-600 dark:text-purpura-400 mb-4">
                <i class="ph-fill ph-key text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white">Recuperar Acesso</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Digite seu e-mail cadastrado e enviaremos um link seguro para você redefinir sua senha.
            </p>
        </div>

        @if ($status)
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm font-bold flex items-center gap-2">
                <i class="ph-fill ph-check-circle text-lg"></i>
                <span>{{ __($status) }}</span>
            </div>
        @endif

        @if ($errorMessage)
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm font-bold flex items-center gap-2">
                <i class="ph-fill ph-warning-circle text-lg"></i>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <form wire:submit.prevent="enviarLink" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300">E-mail Cadastrado</label>
                <div class="mt-1 relative">
                    <input id="email" wire:model="email" type="email" required placeholder="seu.email@exemplo.com" class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purpura-500 focus:border-purpura-500 sm:text-sm dark:bg-gray-700 dark:text-white transition">
                    <i class="ph ph-envelope-simple absolute right-3 top-3.5 text-gray-400 text-lg"></i>
                </div>
                @error('email') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500 transition-colors">
                Enviar Link de Recuperação
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('portal.login') }}" wire:navigate class="text-xs font-bold text-gray-500 hover:text-purpura-600 transition flex items-center justify-center gap-1">
                    <i class="ph-bold ph-arrow-left"></i> Voltar para a tela de login
                </a>
            </div>
        </form>
    </div>
</div>