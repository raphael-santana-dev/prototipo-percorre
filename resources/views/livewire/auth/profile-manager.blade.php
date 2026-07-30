<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Detalhes do Perfil</h1>
    </div>

    @if (session()->has('success_profile') || session()->has('success_password'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-6 p-4 rounded-xl text-emerald-800 bg-emerald-100 flex items-center gap-3 border border-emerald-200">
            <i class="text-xl ph-fill ph-check-circle"></i> 
            <span class="font-medium">{{ session('success_profile') ?? session('success_password') }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- ========================================== -->
        <!-- COLUNA ESQUERDA: CARD DO USUÁRIO           -->
        <!-- ========================================== -->
        <div class="w-full lg:w-1/3 space-y-6">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8 flex flex-col items-center justify-center dark:bg-gray-800 dark:border-gray-700">
                
                <!-- Avatar Circular Grande com ícone de edição flutuante -->
                <div class="relative mb-6">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=F4E8FF&color=9B26B6&size=200&bold=true" 
                         alt="Avatar" 
                         class="w-32 h-32 rounded-full object-cover shadow-md">
                    <button class="absolute bottom-0 right-0 p-2 bg-white rounded-full shadow-md text-gray-600 hover:text-purpura-600 transition-colors border border-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <i class="ph-bold ph-pencil-simple"></i>
                    </button>
                </div>

                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white text-center">{{ auth()->user()->name }}</h2>
                <span class="mt-2 px-3 py-1 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-md border border-emerald-100 uppercase tracking-wider dark:bg-emerald-900/30 dark:border-emerald-800">
                    Administrador
                </span>
            </div>
            
            <!-- Cards de Informação Rápida (Formato Pill Vertical) -->
            <div class="space-y-3">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-400 font-medium mb-1">Email</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ auth()->user()->email }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-400 font-medium mb-1">Gênero</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Não Informado</p>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- COLUNA DIREITA: FORMULÁRIOS E DADOS        -->
        <!-- ========================================== -->
        <!-- ========================================== -->
        <!-- COLUNA DIREITA: FORMULÁRIOS E DADOS        -->
        <!-- ========================================== -->
        <!-- Injetamos o AlpineJS aqui para controlar as abas -->
        <div class="w-full lg:w-2/3 space-y-6" x-data="{ abaAtual: 'dados' }">
            
            <!-- Tabs de Navegação Dinâmicas -->
            <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700 px-2">
                <button @click="abaAtual = 'dados'" 
                        :class="abaAtual === 'dados' ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="pb-3 text-sm font-bold border-b-2 transition-colors">
                    Dados da Conta
                </button>
                <button @click="abaAtual = 'permissoes'" 
                        :class="abaAtual === 'permissoes' ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="pb-3 text-sm font-bold border-b-2 transition-colors">
                    Meus Acessos
                </button>
            </div>

            <!-- ========================================== -->
            <!-- CONTEÚDO DA ABA 1: DADOS DA CONTA          -->
            <!-- ========================================== -->
            <div x-show="abaAtual === 'dados'" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="space-y-6">
                 
                <!-- Bloco 1: Informações Pessoais -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 dark:bg-gray-800 dark:border-gray-700">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Informações Pessoais</h3>
                        <p class="text-sm text-gray-500 mt-1">Atualize os dados básicos do seu perfil.</p>
                    </div>
                    
                    <form wire:submit="updateProfile" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nome Completo</label>
                                <input type="text" wire:model="name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2b90ff] focus:border-[#2b90ff] dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                                @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">E-mail</label>
                                <input type="email" wire:model="email" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2b90ff] focus:border-[#2b90ff] dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                                @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-8 py-3 text-sm font-bold text-white rounded-xl bg-gray-900 hover:bg-gray-800 dark:bg-purpura-600 transition-transform transform hover:-translate-y-0.5">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bloco 2: Segurança -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 dark:bg-gray-800 dark:border-gray-700">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Segurança</h3>
                            <p class="text-sm text-gray-500 mt-1">Altere sua senha de acesso.</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-500">
                            <i class="ph-fill ph-lock-key text-xl"></i>
                        </div>
                    </div>
                    
                    <form wire:submit="updatePassword" class="space-y-5 max-w-lg">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Senha Atual</label>
                            <input type="password" wire:model="current_password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                            @error('current_password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nova Senha</label>
                                <input type="password" wire:model="new_password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                                @error('new_password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Confirmar Senha</label>
                                <input type="password" wire:model="new_password_confirmation" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                            </div>
                        </div>
                        
                        <div class="flex justify-start pt-4 mt-2">
                            <button type="submit" class="px-8 py-3 text-sm font-bold text-orange-600 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-transform transform hover:-translate-y-0.5">
                                Atualizar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- CONTEÚDO DA ABA 2: MEUS ACESSOS            -->
            <!-- ========================================== -->
            <div x-show="abaAtual === 'permissoes'" style="display: none;"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 dark:bg-gray-800 dark:border-gray-700">
                
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Meus Acessos e Permissões</h3>
                    <p class="text-sm text-gray-500 mt-1">Esta aba exibe os privilégios vinculados à sua conta. Para alterações, contate um administrador.</p>
                </div>

                <!-- Grupos (Roles) -->
                <div class="mb-8">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="ph-bold ph-shield-check text-[#2b90ff]"></i> Grupos de Acesso
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @forelse(auth()->user()->getRoleNames() as $role)
                            <span class="px-3 py-1.5 text-sm font-bold text-[#2b90ff] bg-blue-50 border border-blue-100 rounded-lg dark:bg-blue-900/30 dark:border-blue-800 uppercase">
                                {{ $role }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-400 italic">Nenhum grupo atribuído.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Permissões Individuais -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="ph-bold ph-key text-orange-500"></i> Permissões Específicas
                    </h4>
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 dark:bg-gray-900 dark:border-gray-700">
                        <div class="flex flex-wrap gap-2">
                            @forelse(auth()->user()->getAllPermissions() as $permission)
                                <span class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                                    {{ $permission->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-400 italic">Nenhuma permissão isolada encontrada.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>