<div class="max-w-4xl mx-auto px-4 py-6 space-y-6 pb-12 font-sans">
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Meu Perfil</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Gerencie seus dados cadastrais, senha de acesso e documentação.</p>
        </div>
        <a href="{{ route('student.dashboard') }}" wire:navigate class="px-3.5 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition">
            Voltar ao Painel
        </a>
    </div>

    {{-- SEÇÃO 1: DADOS CADASTRAIS --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Dados Cadastrais</h3>
            <p class="text-xs text-gray-500">Mantenha seu nome e e-mail atualizados para receber os comunicados da instituição.</p>
        </div>

        <form wire:submit="updateProfile" class="p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome Completo</label>
                    <input type="text" wire:model="name" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2 px-3">
                    @error('name') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" wire:model="email" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2 px-3">
                    @error('email') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
                @if(feature('estudante.perfil'))
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white rounded-lg bg-purpura-600 hover:bg-purpura-700 transition">
                        Salvar Dados
                    </button>
                @else
                    <span class="text-xs font-semibold text-red-600">A edição de perfil está temporariamente desativada.</span>
                @endif
            </div>
        </form>
    </div>

    {{-- SEÇÃO 2: SEGURANÇA DE ACESSO --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Segurança de Acesso</h3>
            <p class="text-xs text-gray-500">Altere sua senha periodicamente para manter sua conta protegida.</p>
        </div>

        <form wire:submit="updatePassword" class="p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Senha Atual</label>
                    <input type="password" wire:model="current_password" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2 px-3">
                    @error('current_password') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
                    <input type="password" wire:model="new_password" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2 px-3">
                    @error('new_password') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Confirmar Nova Senha</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-2 px-3">
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
                @if(feature('estudante.perfil'))
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white rounded-lg bg-gray-900 hover:bg-black dark:bg-purpura-600 dark:hover:bg-purpura-700 transition">
                        Atualizar Senha
                    </button>
                @else
                    <span class="text-xs font-semibold text-red-600">A alteração de senha está bloqueada.</span>
                @endif
            </div>
        </form>
    </div>

    {{-- SEÇÃO 3: DOCUMENTAÇÃO E LAUDO MÉDICO --}}
    @if($this->inscricaoAtual)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden" id="secao-documentos">
            <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Documentação de Matrícula e Laudo</h3>
                    <p class="text-xs text-gray-500">
                        Curso: <strong class="text-gray-800 dark:text-gray-200">{{ $this->inscricaoAtual->curso->nome ?? 'Não definido' }}</strong>
                    </p>
                </div>
                @if($this->uploadHabilitadoPeloStatus)
                    <a href="{{ route('student.student.documentos') }}" wire:navigate class="text-xs font-semibold text-purpura-600 hover:underline">
                        Abrir Central de Documentos →
                    </a>
                @endif
            </div>

            <div class="p-5">
                @if(!$this->uploadHabilitadoPeloStatus)
                    <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200">
                        O envio de documentos e laudo será liberado quando sua inscrição atingir a etapa
                        <strong>{{ $this->nomeStatusExigido ?: 'de matrícula' }}</strong>.
                    </div>
                @else
                    @php
                        $insc = $this->inscricaoAtual;
                        $isPcd = in_array(strtolower((string) ($insc->possui_deficiencia ?? 'nao')), ['sim', '1', 'true', 's'], true);
                        $din = is_array($insc->dados_dinamicos) ? $insc->dados_dinamicos : (json_decode($insc->dados_dinamicos ?? '{}', true) ?: []);
                        $laudoSalvo = $din['laudo_pcd'] ?? null;
                    @endphp

                    @if($isPcd)
                        <div class="mb-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold text-gray-900 dark:text-white block">
                                    Laudo Médico PcD ({{ ucfirst($insc->natureza_deficiencia ?: 'Deficiência declarada') }})
                                </span>
                                @if($laudoSalvo)
                                    <a href="{{ asset('storage/' . $laudoSalvo) }}" target="_blank" class="text-xs text-purpura-600 hover:underline font-mono">
                                        {{ basename($laudoSalvo) }} (Visualizar ↗)
                                    </a>
                                @else
                                    <span class="text-xs text-amber-700">Laudo pendente de envio.</span>
                                @endif
                            </div>
                            <label class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 cursor-pointer w-max">
                                <span wire:loading.remove wire:target="laudoPcdUpload">{{ $laudoSalvo ? 'Substituir Laudo' : 'Enviar Laudo' }}</span>
                                <span wire:loading wire:target="laudoPcdUpload">Enviando...</span>
                                <input type="file" wire:model="laudoPcdUpload" accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
                            </label>
                        </div>
                    @endif

                    <div class="space-y-2.5">
                        @foreach($this->documentosExigidos as $doc)
                            @php
                                $statusInfo = $arquivosEnviados[$doc->id] ?? ['status' => 'pendente'];
                                $status = $statusInfo['status'];
                            @endphp
                            <div class="p-3.5 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $doc->nome }}</span>
                                    @if($doc->descricao)
                                        <p class="text-[11px] text-gray-500">{{ $doc->descricao }}</p>
                                    @endif
                                </div>
                                <div>
                                    @if(in_array($status, ['valido_ia', 'aprovado_manual']))
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded">Aprovado</span>
                                    @else
                                        <label class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 cursor-pointer inline-flex items-center gap-1.5">
                                            <i class="ph ph-upload-simple"></i>
                                            <span wire:loading.remove wire:target="uploads.{{ $doc->id }}">Enviar Arquivo</span>
                                            <span wire:loading wire:target="uploads.{{ $doc->id }}">Enviando...</span>
                                            <input type="file" wire:model="uploads.{{ $doc->id }}" accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>