<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-3xl">
        <!-- Logo e Cabeçalho -->
        <div class="text-center mb-6">
            <h2 class="text-3xl font-black text-gray-900">Portal de Matrícula</h2>
            <p class="mt-2 text-sm text-gray-600">Olá, <b>{{ explode(' ', $inscricao->nome)[0] }}</b>! Complete o envio dos seus documentos para o curso de <b>{{ $inscricao->curso->nome }}</b>.</p>
        </div>

        <div class="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100 relative overflow-hidden">
            
            @if($concluido)
                <div class="text-center py-12">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                        <i class="ph-fill ph-check-circle text-6xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900">Documentação Recebida!</h3>
                    <p class="mt-2 text-gray-500 max-w-sm mx-auto">Sua pasta de matrícula foi fechada e enviada para a nossa Secretaria Acadêmica. Entraremos em contato em breve!</p>
                </div>
            @else
                
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
                                        <!-- Formulário de Upload -->
                                        <div class="relative">
                                            <label class="flex justify-center items-center px-4 py-2.5 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded-lg cursor-pointer hover:bg-purpura-100 transition font-bold text-sm w-full md:w-auto">
                                                <i class="ph-bold ph-upload-simple mr-2"></i> Enviar Imagem
                                                <input type="file" wire:model.live="uploads.{{ $doc->id }}" class="hidden" accept="image/jpeg, image/png">
                                            </label>

                                            <!-- Spinner de Carregamento da IA -->
                                            <div wire:loading wire:target="uploads.{{ $doc->id }}" class="absolute inset-0 bg-white/90 backdrop-blur-sm rounded-lg flex items-center justify-center text-purpura-600 font-bold text-xs gap-2">
                                                <i class="ph-bold ph-spinner animate-spin text-lg"></i> Analisando...
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Alerta de Rejeição da IA -->
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
        </div>
    </div>
</div>