<div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-900 font-sans">
    
    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white dark:bg-gray-800 shadow-xl overflow-hidden sm:rounded-2xl border border-gray-200 dark:border-gray-700">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-purpura-100 dark:bg-purpura-900/30 text-purpura-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-purpura-200 dark:border-purpura-800">
                <i class="ph-fill ph-lock-key text-3xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Retomar Inscrição</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Você está prestes a continuar o preenchimento de onde parou. Por segurança, confirme seus dados.</p>
        </div>

        @if($tokenInvalido)
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 flex items-start gap-3">
                <i class="ph-fill ph-warning-circle text-xl mt-0.5"></i>
                <span class="font-medium">Este link de retomada é inválido, sofreu adulteração ou já expirou. Por favor, solicite um novo link à administração.</span>
            </div>
        @else
            <form wire:submit.prevent="validar" class="space-y-5">
                
                <!-- CPF -->
                <div>
                    <label for="cpf" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Seu CPF</label>
                    <input id="cpf" type="text" wire:model="cpf" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                        placeholder="000.000.000-00" 
                        x-data x-mask="999.999.999-99" required autofocus>
                    @error('cpf') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Data de Nascimento -->
                <div>
                    <label for="data_nascimento" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Data de Nascimento</label>
                    <input id="data_nascimento" type="date" wire:model="data_nascimento" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                        required>
                    @error('data_nascimento') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                    <button type="submit" wire:loading.attr="disabled" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 transition-colors">
                        <span wire:loading.remove class="flex items-center gap-2">Acessar Formulário <i class="ph-bold ph-arrow-right"></i></span>
                        <span wire:loading class="flex items-center gap-2"><i class="ph-bold ph-spinner animate-spin"></i> Validando credenciais...</span>
                    </button>
                </div>
            </form>
        @endif
        
    </div>
</div>