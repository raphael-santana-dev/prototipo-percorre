<div class="min-h-screen flex items-center justify-center bg-white dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        
        <!-- Logo -->
        <div class="flex justify-start">
            <div class="p-3 bg-[#461a63] rounded-xl flex items-center justify-center">
                <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="h-8 w-auto" alt="Instituto Percorre">
            </div>
        </div>

        <div class="text-left mt-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Recuperar Acesso</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Digite seu e-mail e enviaremos um link para redefinir a senha.
            </p>
        </div>

        @if ($status)
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm font-medium flex items-center gap-3">
                <i class="ph-fill ph-check-circle text-xl"></i>
                <span>{{ __($status) }}</span>
            </div>
        @endif

        @if ($errorMessage)
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm font-medium flex items-center gap-3">
                <i class="ph-fill ph-warning-circle text-xl"></i>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <form wire:submit.prevent="enviarLink" class="mt-8 space-y-6">
            
            <div>
                <input wire:model="email" type="email" required placeholder="E-mail cadastrado" class="appearance-none block w-full px-4 py-3.5 border border-gray-300 dark:border-gray-700 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purpura-500 focus:border-transparent sm:text-sm dark:bg-gray-800 dark:text-white transition-all">
                @error('email') <span class="text-xs font-medium text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <button type="submit" class="w-full sm:w-auto flex justify-center py-3.5 px-6 border border-transparent rounded-lg text-sm font-bold text-white bg-[#461a63] hover:bg-purpura-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500 transition-colors shadow-sm">
                    Enviar Link
                </button>

                <a href="{{ route('portal.login') }}" wire:navigate class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:hover:text-white transition flex items-center gap-2">
                    Voltar para o login
                </a>
            </div>
        </form>
    </div>
</div>