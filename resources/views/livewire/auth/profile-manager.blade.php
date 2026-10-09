<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Detalhes do Perfil</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <div class="w-full lg:w-1/3 space-y-6">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8 flex flex-col items-center justify-center dark:bg-gray-800 dark:border-gray-700">
                
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

        <div class="w-full lg:w-2/3 space-y-6" x-data="{ abaAtual: 'dados' }">
            
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
            <div x-show="abaAtual === 'dados'" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="space-y-6">
                 
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

            <div x-show="abaAtual === 'permissoes'" style="display: none;"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 dark:bg-gray-800 dark:border-gray-700">
                
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Meus Acessos e Permissões</h3>
                    <p class="text-sm text-gray-500 mt-1">Esta aba exibe os privilégios vinculados à sua conta. Para alterações, contate um administrador.</p>
                </div>

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

    <!-- SEÇÃO DE DOCUMENTAÇÃO DE MATRÍCULA -->
    @if($inscricaoAtual && count($documentosExigidos) > 0)
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 mt-6">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                <i class="ph ph-folder text-purpura-500"></i> Documentação de Matrícula
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie e envie os documentos necessários para a sua inscrição no curso de <b class="text-purpura-700 dark:text-purpura-400">{{ $inscricaoAtual->curso->nome ?? 'Não definido' }}</b>.</p>
        </div>
        
        <div class="p-6 sm:p-8">
            @if($documentacaoConcluida)
                <div class="text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 max-w-lg mx-auto">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-50 border border-green-100 dark:bg-green-900/30 dark:border-green-800 mb-5">
                        <i class="ph-fill ph-check-circle text-5xl text-green-500"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Documentação Recebida!</h3>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto leading-relaxed">Sua pasta foi fechada e enviada para a nossa Secretaria. Acompanhe o status pelo seu painel principal.</p>
                </div>
            @else
                <div class="grid grid-cols-1 {{ feature('matricula.upload_multiplo') ? 'lg:grid-cols-12 gap-8' : 'gap-6 max-w-4xl mx-auto' }}">
                    
                    @if(feature('matricula.upload_multiplo'))
                        <div class="flex flex-col gap-4 lg:col-span-5" x-data="loteCompressor()" @lote-concluido.window="finalizarLote()">
                            <div class="flex flex-col h-full p-6 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                                <h3 class="flex items-center gap-2 pb-2 mb-4 text-xs font-bold tracking-wider text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                                    <i class="text-lg ph-fill ph-files text-purpura-500"></i> Upload Automático
                                </h3>
                                
                                <div class="border-2 border-dashed rounded-xl p-8 text-center transition-all relative flex flex-col items-center justify-center min-h-[200px]"
                                     :class="isDragging ? 'border-purpura-400 bg-purpura-50/50 dark:bg-purpura-900/20' : 'border-gray-300 dark:border-gray-600 hover:bg-white dark:hover:bg-gray-800'"
                                     @dragover.prevent="isDragging = true" 
                                     @dragleave.prevent="isDragging = false" 
                                     @drop.prevent="isDragging = false; processarDrop($event)">
                                    
                                    <input type="file" multiple accept="image/jpeg, image/png, image/webp" @change="processarUploadLote($event)" class="absolute inset-0 z-10 w-full h-full opacity-0 cursor-pointer" :disabled="processando">
                                    
                                    <div class="flex items-center justify-center w-12 h-12 mb-3 text-gray-400 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                        <i class="text-2xl ph-bold ph-upload-simple"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Arraste os arquivos aqui</p>
                                    <p class="text-[11px] text-gray-500 mt-1">ou clique para selecionar</p>
                                </div>

                                <div x-show="files.length > 0" x-cloak class="mt-5 space-y-2 overflow-y-auto max-h-60 custom-scrollbar">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Status do Envio</p>
                                    <template x-for="(file, index) in files" :key="index">
                                        <div class="p-3 bg-white border border-gray-100 rounded-lg dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex items-center gap-2 pr-2 overflow-hidden">
                                                    <i class="text-lg text-gray-400 shrink-0 ph-fill ph-image"></i>
                                                    <div class="truncate">
                                                        <p class="text-xs font-bold text-gray-700 truncate dark:text-gray-300" x-text="file.name"></p>
                                                    </div>
                                                </div>
                                                <i class="text-base shrink-0 ph-bold ph-spinner animate-spin text-purpura-500" x-show="file.status !== 'Concluído'"></i>
                                                <i class="text-base text-green-500 shrink-0 ph-fill ph-check-circle" x-show="file.status === 'Concluído'"></i>
                                            </div>
                                            <div class="w-full h-1 mb-1.5 bg-gray-200 rounded-full dark:bg-gray-700">
                                                <div class="h-1 transition-all duration-300 bg-blue-500 rounded-full" :class="file.status === 'Concluído' ? 'w-full' : 'w-2/3 animate-pulse'"></div>
                                            </div>
                                            <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wide">
                                                <span x-text="file.status"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-4 {{ feature('matricula.upload_multiplo') ? 'lg:col-span-7' : 'w-full' }}">
                        @php $podeFinalizar = true; @endphp

                        @foreach($documentosExigidos as $doc)
                            @php
                                $statusInfo = $arquivosEnviados[$doc->id];
                                $status = $statusInfo['status'];
                                if ($doc->is_obrigatorio && in_array($status, ['pendente', 'invalido_ia', 'reprovado_manual'])) { $podeFinalizar = false; }
                            @endphp

                            <div class="flex flex-col p-5 transition-all bg-white border shadow-sm rounded-xl dark:bg-gray-900/50 {{ $status === 'valido_ia' ? 'border-green-200 dark:border-green-800 bg-green-50/20 dark:bg-green-900/10' : 'border-gray-200 dark:border-gray-700' }}">
                                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                    <div class="flex-1">
                                        <h4 class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $doc->nome }} 
                                            @if($doc->is_obrigatorio) 
                                                <span class="text-[9px] bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-0.5 rounded font-bold uppercase tracking-wider border border-red-100 dark:border-red-800">Obrigatório</span> 
                                            @endif
                                        </h4>
                                        <p class="text-[11px] text-gray-500 mt-1 leading-snug">{{ $doc->descricao }}</p>
                                    </div>

                                    <div class="w-full shrink-0 sm:w-auto">
                                        @if($status === 'valido_ia' || $status === 'aprovado_manual')
                                            <span class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] uppercase tracking-wider font-bold rounded-lg border border-green-200 dark:border-green-800">
                                                <i class="text-sm ph-bold ph-check"></i> Aprovado
                                            </span>
                                        @elseif($status === 'reprovado_manual')
                                            <span class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[10px] uppercase tracking-wider font-bold rounded-lg border border-red-200 dark:border-red-800">
                                                <i class="text-sm ph-bold ph-x"></i> Reprovado
                                            </span>
                                        @elseif($status === 'analise_manual')
                                            <span class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[10px] uppercase tracking-wider font-bold rounded-lg border border-yellow-200 dark:border-yellow-800">
                                                <i class="text-sm ph-bold ph-clock"></i> Em Análise
                                            </span>
                                        @else
                                            <div x-data="imageCompressor({{ $doc->id }})" @analise-concluida.window="if($event.detail.docId == {{ $doc->id }}) { isAnalyzing = false; }">
                                                <label class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-purpura-400 hover:text-purpura-600 dark:hover:text-purpura-400 rounded-lg cursor-pointer transition font-bold text-[10px] uppercase tracking-wider shadow-sm w-full" x-show="!isCompressing && !isAnalyzing">
                                                    <i class="text-sm ph-bold ph-upload-simple"></i> Enviar Arquivo
                                                    <input type="file" class="hidden" accept="image/jpeg, image/png, image/webp" @change="processarUpload">
                                                </label>
                                                <div x-show="isCompressing" style="display: none;" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400 rounded-lg font-bold text-[10px] uppercase tracking-wider w-full shadow-sm">
                                                    <i class="text-sm ph-bold ph-arrows-in animate-pulse"></i> Otimizando...
                                                </div>
                                                <div x-show="isAnalyzing" style="display: none;" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 rounded-lg font-bold text-[10px] uppercase tracking-wider w-full shadow-sm">
                                                    <i class="text-sm ph-bold ph-spinner animate-spin"></i> Analisando...
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($status === 'invalido_ia')
                                    <div class="mt-4 bg-red-50/50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 p-3 rounded-lg flex items-start gap-2.5">
                                        <i class="text-base text-red-500 shrink-0 ph-fill ph-warning-circle"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-red-800 dark:text-red-400 uppercase tracking-wider mb-0.5">Rejeitado pela IA (Tentativa {{ $statusInfo['tentativas'] }} de 3)</p>
                                            <p class="text-[11px] text-red-600 dark:text-red-300 leading-tight font-medium">{{ $statusInfo['motivo_rejeicao'] }}</p>
                                        </div>
                                    </div>
                                @elseif($status === 'reprovado_manual')
                                    <div class="mt-4 bg-red-50/50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 p-3 rounded-lg flex items-start gap-2.5">
                                        <i class="text-base text-red-500 shrink-0 ph-fill ph-warning-circle"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-red-800 dark:text-red-400 uppercase tracking-wider mb-0.5">Avaliação da Secretaria</p>
                                            <p class="text-[11px] text-red-600 dark:text-red-300 leading-tight font-medium mb-1">Este documento foi reprovado. Um responsável irá entrar em contato com você em breve para orientações.</p>
                                            <p class="text-[11px] font-bold text-red-700 dark:text-red-400">Motivo: {{ $statusInfo['motivo_rejeicao_humana'] ?? 'Divergência ou falha na documentação.' }}</p>
                                        </div>
                                    </div>
                                @elseif($status === 'valido_ia')
                                    <div class="mt-4 bg-green-50/50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/50 p-3 rounded-lg flex items-start gap-2.5">
                                        <i class="text-base text-green-500 shrink-0 ph-fill ph-check-circle"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-green-800 dark:text-green-400 uppercase tracking-wider mb-0.5">Aprovação Automática (IA)</p>
                                            <p class="text-[11px] font-medium leading-tight text-green-600 dark:text-green-300">Documento validado com sucesso.</p>
                                        </div>
                                    </div>
                                @elseif($status === 'aprovado_manual')
                                    <div class="mt-4 bg-green-50/50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/50 p-3 rounded-lg flex items-start gap-2.5">
                                        <i class="text-base text-green-500 shrink-0 ph-fill ph-user"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-green-800 dark:text-green-400 uppercase tracking-wider mb-0.5">Avaliação da Secretaria</p>
                                            <p class="text-[11px] font-medium leading-tight text-green-600 dark:text-green-300">Documento aprovado manualmente pela equipe.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        <div class="mt-4">
                            <button wire:click="finalizarMatricula" 
                                    class="w-full py-4 rounded-xl font-black text-xs uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2 {{ $podeFinalizar ? 'bg-purpura-600 hover:bg-purpura-700 text-white cursor-pointer shadow-md hover:-translate-y-0.5' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed border border-gray-200 dark:border-gray-600' }}"
                                    @if(!$podeFinalizar) disabled @endif>
                                <i class="text-lg ph-bold ph-paper-plane-right"></i> Enviar para a Secretaria
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

@script
<script>
    Alpine.data('loteCompressor', () => ({
        isDragging: false,
        processando: false,
        files: [],
        processarDrop(event) { this.handleFiles(event.dataTransfer.files); },
        processarUploadLote(event) {
            this.handleFiles(event.target.files);
            event.target.value = '';
        },
        async handleFiles(fileList) {
            const incomingFiles = Array.from(fileList);
            if (incomingFiles.length === 0) return;
            this.processando = true;
            this.files = incomingFiles.map(f => ({
                name: f.name, size: (f.size / 1024 / 1024).toFixed(2) + ' MB',
                status: 'Aguardando', originalFile: f, compressedFile: null
            }));
            let uploadPayload = [];
            for (let i = 0; i < this.files.length; i++) {
                let fileObj = this.files[i];
                fileObj.status = 'Otimizando Imagem...';
                if (!fileObj.originalFile.type.startsWith('image/')) {
                    fileObj.compressedFile = fileObj.originalFile;
                } else {
                    let blob = await this.comprimirImagem(fileObj.originalFile);
                    let novoNome = fileObj.originalFile.name.replace(/\.[^/.]+$/, "") + ".jpg";
                    fileObj.compressedFile = new File([blob], novoNome, { type: 'image/jpeg' });
                }
                fileObj.status = 'Classificando na IA...';
                uploadPayload.push(fileObj.compressedFile);
            }
            $wire.uploadMultiple('uploadsLote', uploadPayload,
                () => {},
                () => {
                    this.files.forEach(f => f.status = 'Erro no envio');
                    this.processando = false;
                    this.$dispatch('erro', {msg: 'Falha na conexão ao enviar o lote.'});
                }
            );
        },
        finalizarLote() {
            this.files.forEach(f => f.status = 'Concluído');
            setTimeout(() => { this.processando = false; this.files = []; }, 3000);
        },
        comprimirImagem(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width, height = img.height;
                        const MAX = 1200;
                        if (width > height && width > MAX) { height *= MAX / width; width = MAX; } 
                        else if (height > MAX) { width *= MAX / height; height = MAX; }
                        canvas.width = width; canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.85);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    }));

    Alpine.data('imageCompressor', (docId) => ({
        isCompressing: false, isAnalyzing: false,
        processarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.isCompressing = true;
            if (!file.type.startsWith('image/')) {
                this.isCompressing = false; this.isAnalyzing = true;
                $wire.upload('uploads.' + docId, file);
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width, height = img.height;
                    const MAX = 1200;
                    if (width > height && width > MAX) { height *= MAX / width; width = MAX; } 
                    else if (height > MAX) { width *= MAX / height; height = MAX; }
                    canvas.width = width; canvas.height = height;
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                    canvas.toBlob((blob) => {
                        const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' });
                        this.isCompressing = false; this.isAnalyzing = true;
                        $wire.upload('uploads.' + docId, newFile);
                    }, 'image/jpeg', 0.85); 
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }));
</script>
@endscript