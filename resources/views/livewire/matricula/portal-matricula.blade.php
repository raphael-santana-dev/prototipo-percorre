<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-3xl">
        
        <div class="text-center mb-6">
            <h2 class="text-3xl font-black text-gray-900">Portal de Matrícula</h2>
            <p class="mt-2 text-sm text-gray-600">Curso de <b>{{ $inscricao->curso->nome ?? 'Não definido' }}</b></p>
        </div>

        <div class="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100 relative overflow-hidden">
            
            <!-- TELA 1: DESAFIO DE SEGURANÇA -->
            @if(!$autenticado)
                <div class="max-w-md mx-auto py-4">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-purpura-100 text-purpura-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ph-fill ph-lock-key text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Acesso Restrito</h3>
                        <p class="text-sm text-gray-500 mt-2">Para proteger seus dados, confirme sua identidade para acessar o cofre de envio de documentos.</p>
                    </div>

                    <form wire:submit.prevent="verificarIdentidade" class="space-y-5">
                        @if($errors->has('falha_auth'))
                            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200 font-bold text-center">
                                {{ $errors->first('falha_auth') }}
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Seu CPF</label>
                            <input type="text" wire:model="cpf_acesso" class="w-full rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900" placeholder="000.000.000-00">
                            @error('cpf_acesso') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                            <input type="date" wire:model="data_nascimento_acesso" class="w-full rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500 text-gray-900">
                            @error('data_nascimento_acesso') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 rounded-lg shadow-md transition flex items-center justify-center gap-2">
                            <i class="ph-bold ph-shield-check"></i> Acessar Meu Dossiê
                        </button>
                    </form>
                </div>
            
            <!-- TELA 2: PORTAL DE UPLOAD (Só exibe se autenticado) -->
            @else
                
                @if($concluido)
                    <div class="text-center py-12">
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                            <i class="ph-fill ph-check-circle text-6xl text-green-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">Documentação Recebida!</h3>
                        <p class="mt-2 text-gray-500 max-w-sm mx-auto">Sua pasta de matrícula foi fechada e enviada para a nossa Secretaria. Entraremos em contato em breve!</p>
                    </div>
                @else
                    
                    <div class="mb-6 pb-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold">
                            {{ substr($inscricao->nome, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $inscricao->nome }}</h3>
                            <p class="text-xs text-gray-500 border border-green-200 bg-green-50 text-green-700 px-2 py-0.5 rounded-full inline-block mt-1"><i class="ph-fill ph-check-circle"></i> Identidade Confirmada</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @php $podeFinalizar = true; @endphp

                        @foreach($documentosExigidos as $doc)
                            @php
                                $statusInfo = $arquivosEnviados[$doc->id];
                                $status = $statusInfo['status'];
                                
                                if ($doc->is_obrigatorio && in_array($status, ['pendente', 'invalido_ia'])) {
                                    $podeFinalizar = false;
                                }
                            @endphp

                            <div class="border rounded-xl p-5 {{ $status === 'valido_ia' ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white' }} transition-all">
                                <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                            {{ $doc->nome }} 
                                            @if($doc->is_obrigatorio) <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded uppercase tracking-wider">Obrigatório</span> @endif
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">{{ $doc->descricao }}</p>
                                    </div>

                                    <div class="shrink-0 w-full md:w-auto">
                                        @if($status === 'valido_ia')
                                            <div class="flex items-center gap-2 text-green-700 font-bold bg-green-100 px-4 py-2 rounded-lg justify-center">
                                                <i class="ph-fill ph-check-circle text-xl"></i> Documento Aprovado
                                            </div>
                                        @elseif($status === 'analise_manual')
                                            <div class="flex flex-col items-center text-center">
                                                <div class="flex items-center gap-2 text-yellow-700 font-bold bg-yellow-100 px-4 py-2 rounded-lg w-full justify-center">
                                                    <i class="ph-fill ph-clock text-xl"></i> Em Análise Manual
                                                </div>
                                                <span class="text-[10px] text-gray-500 mt-1">Aguardando secretaria.</span>
                                            </div>
                                        @else
                                            <div class="relative">
                                                <label class="flex justify-center items-center px-4 py-2.5 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded-lg cursor-pointer hover:bg-purpura-100 transition font-bold text-sm w-full md:w-auto">
                                                    <i class="ph-bold ph-upload-simple mr-2"></i> Enviar Imagem
                                                    <input type="file" wire:model.live="uploads.{{ $doc->id }}" class="hidden" accept="image/jpeg, image/png">
                                                </label>
                                                <div wire:loading wire:target="uploads.{{ $doc->id }}" class="absolute inset-0 bg-white/90 backdrop-blur-sm rounded-lg flex items-center justify-center text-purpura-600 font-bold text-xs gap-2">
                                                    <i class="ph-bold ph-spinner animate-spin text-lg"></i> Analisando...
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($status === 'invalido_ia')
                                    <div class="mt-4 bg-red-50 border border-red-200 p-3 rounded-lg flex items-start gap-3">
                                        <i class="ph-fill ph-warning-circle text-red-500 text-xl shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="text-xs font-bold text-red-800">Documento Rejeitado (Tentativa {{ $statusInfo['tentativas'] }} de 3)</p>
                                            <p class="text-xs text-red-600 mt-1">{{ $statusInfo['motivo_rejeicao'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10 border-t border-gray-100 pt-6">
                        <button wire:click="finalizarMatricula" 
                                class="w-full py-4 rounded-xl font-black text-lg transition shadow-md flex items-center justify-center gap-2 {{ $podeFinalizar ? 'bg-purpura-600 hover:bg-purpura-700 text-white cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                                @if(!$podeFinalizar) disabled @endif>
                            <i class="ph-bold ph-paper-plane-right"></i> Enviar Dossiê de Matrícula
                        </button>
                        @if(!$podeFinalizar)
                            <p class="text-center text-xs text-gray-500 mt-3">Envie todos os documentos obrigatórios para habilitar este botão.</p>
                        @endif
                    </div>
                @endif

            @endif
        </div>
    </div>
</div>