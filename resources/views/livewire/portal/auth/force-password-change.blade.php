<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
        
        {{-- Detalhe visual de topo --}}
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-500 to-purpura-600"></div>

        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-500 mb-4">
                <i class="ph-fill ph-shield-check text-4xl"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white">Atualização de Segurança</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Como este é o seu primeiro acesso (ou sua senha foi redefinida), você precisa cadastrar uma senha pessoal forte para continuar.
            </p>
        </div>

        <form wire:submit.prevent="salvar" class="mt-8 space-y-6">
            
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Nova Senha</label>
                    <div class="mt-1 relative">
                        <input id="password" wire:model.live="password" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purpura-500 focus:border-purpura-500 sm:text-sm dark:bg-gray-700 dark:text-white transition">
                        <i class="ph ph-lock-key absolute right-3 top-3.5 text-gray-400 text-lg"></i>
                    </div>
                    @error('password') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Confirmar Nova Senha</label>
                    <div class="mt-1 relative">
                        <input id="password_confirmation" wire:model.live="password_confirmation" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purpura-500 focus:border-purpura-500 sm:text-sm dark:bg-gray-700 dark:text-white transition">
                        <i class="ph ph-check-circle absolute right-3 top-3.5 text-gray-400 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Sua senha deve conter:</h4>
                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> No mínimo 8 caracteres</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Letras maiúsculas e minúsculas</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Pelo menos um número (0-9)</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-green-500"></i> Pelo menos um símbolo (!@#$%)</li>
                </ul>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purpura-500 transition-colors">
                Atualizar Senha e Entrar
            </button>
            
            {{-- O botão de sair permite que ele desista e saia do sistema --}}
            <div class="text-center mt-4">
                <button type="button" wire:click="$dispatch('logout')" class="text-xs font-bold text-gray-500 hover:text-red-500 transition">
                    Sair e fazer isso depois
                </button>
            </div>
        </form>
    </div>
    
    {{-- O ouvinte invisível de logout do Portal --}}
    <livewire:portal.auth.logout-button />
</div>