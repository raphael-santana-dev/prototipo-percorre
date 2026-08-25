<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-8">
        <h1 class="flex items-center gap-3 text-3xl font-extrabold text-gray-900 dark:text-white">
            <i class="ph-fill ph-student text-ponkan-500"></i> Meu Perfil (Aluno)
        </h1>
    </div>

    <!-- Bloco 1: Informações Pessoais -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dados Cadastrais</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Mantenha seu nome e e-mail atualizados para não perder comunicados da instituição.</p>
        </div>
        
        <div class="p-6 sm:p-8">
            @if (session()->has('success_profile'))
                <div class="flex items-center gap-2 p-4 mb-6 rounded-lg text-pistache-700 bg-pistache-100">
                    <i class="text-lg ph-bold ph-check-circle"></i> {{ session('success_profile') }}
                </div>
            @endif

            <form wire:submit="updateProfile" class="flex flex-col gap-6 sm:flex-row">
                <div class="flex flex-col items-center justify-start sm:w-1/4">
                    <!-- Avatar usando o nome do aluno -->
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth('student')->user()->name) }}&background=FFF7E6&color=F97316&size=128&bold=true" 
                         alt="Avatar" 
                         class="w-32 h-32 border-4 border-white rounded-full shadow-md dark:border-gray-700">
                    
                    @if(auth('student')->user()->unidade)
                        <span class="mt-4 text-xs font-bold text-center text-gray-500 uppercase">
                            <i class="ph-fill ph-map-pin text-purpura-500"></i><br>
                            {{ auth('student')->user()->unidade->nome }}
                        </span>
                    @endif
                </div>

                <div class="flex-1 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nome do Aluno</label>
                        <input type="text" wire:model="name" class="w-full mt-1">
                        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">E-mail Escolar / Pessoal</label>
                        <input type="email" wire:model="email" class="w-full mt-1">
                        @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end pt-2">
                        @if(feature('estudante.perfil'))
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 transition-colors shadow-sm">
                                Atualizar Meus Dados
                            </button>
                        @else
                            <span class="text-xs font-bold text-red-500 py-2.5">A edição de perfil está bloqueada pelo sistema.</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bloco 2: Segurança -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                <i class="ph ph-lock-key text-purpura-500"></i> Segurança de Acesso
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Proteja seu histórico acadêmico mantendo uma senha segura e que só você conheça.</p>
        </div>
        
        <div class="p-6 sm:p-8">
            @if (session()->has('success_password'))
                <div class="flex items-center gap-2 p-4 mb-6 rounded-lg text-pistache-700 bg-pistache-100">
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
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Repita a Nova Senha</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full mt-1">
                </div>
                
                <div class="flex justify-start pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                    @if(feature('estudante.perfil'))
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white transition-colors rounded-lg bg-gray-900 hover:bg-gray-800 dark:bg-purpura-600 dark:hover:bg-purpura-500">
                            Gravar Nova Senha
                        </button>
                    @else
                        <span class="text-xs font-bold text-red-500 py-2.5">A edição de senha está bloqueada. Procure a secretaria.</span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>