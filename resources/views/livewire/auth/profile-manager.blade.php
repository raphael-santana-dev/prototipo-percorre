<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
            <i class="ph-fill ph-user-circle text-purpura-500"></i> Meu Perfil
        </h1>
    </div>

    <!-- Bloco 1: Informações Pessoais -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Informações Pessoais</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atualize seu nome e endereço de e-mail de acesso.</p>
        </div>
        
        <div class="p-6 sm:p-8">
            @if (session()->has('success_profile'))
                <div class="p-4 mb-6 rounded-lg text-pistache-700 bg-pistache-100 flex items-center gap-2">
                    <i class="text-lg ph-bold ph-check-circle"></i> {{ session('success_profile') }}
                </div>
            @endif

            <form wire:submit="updateProfile" class="flex flex-col gap-6 sm:flex-row">
                <!-- Avatar Visual (Apenas leitura por enquanto) -->
                <div class="flex flex-col items-center justify-start sm:w-1/4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=F4E8FF&color=9B26B6&size=128&bold=true" 
                         alt="Avatar" 
                         class="w-32 h-32 border-4 border-white rounded-full shadow-md dark:border-gray-700">
                    <span class="mt-3 text-xs font-semibold text-gray-400 uppercase">Foto gerada automaticamente</span>
                </div>

                <!-- Campos -->
                <div class="flex-1 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nome Completo</label>
                        <input type="text" wire:model="name" class="w-full mt-1">
                        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">E-mail</label>
                        <input type="email" wire:model="email" class="w-full mt-1">
                        @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 transition-colors shadow-sm">
                            Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bloco 2: Segurança -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-lock-key text-purpura-500"></i> Segurança da Conta
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Garanta que sua conta esteja usando uma senha longa e aleatória para se manter seguro.</p>
        </div>
        
        <div class="p-6 sm:p-8">
            @if (session()->has('success_password'))
                <div class="p-4 mb-6 rounded-lg text-pistache-700 bg-pistache-100 flex items-center gap-2">
                    <i class="text-lg ph-bold ph-check-circle"></i> {{ session('success_password') }}
                </div>
            @endif

            <form wire:submit="updatePassword" class="space-y-4 max-w-xl">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Senha Atual</label>
                    <input type="password" wire:model="current_password" class="w-full mt-1">
                    @error('current_password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nova Senha</label>
                    <input type="password" wire:model="new_password" class="w-full mt-1">
                    @error('new_password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Confirmar Nova Senha</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full mt-1">
                </div>
                
                <div class="flex justify-start pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white transition-colors rounded-lg bg-gray-900 hover:bg-gray-800 dark:bg-purpura-600 dark:hover:bg-purpura-500">
                        Atualizar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>