<div class="min-h-screen bg-gray-50 flex flex-col py-12 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full max-w-6xl">
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black text-gray-900">Portal de Matrícula</h2>
            <p class="mt-2 text-sm text-gray-600">Curso de <b class="text-purpura-700">{{ $inscricao->curso->nome ?? 'Não definido' }}</b></p>
        </div>

        <div class="py-4 px-4 sm:px-0 relative">
            
            @if(!$autenticado)
                <!-- TELA 1: DESAFIO DE SEGURANÇA -->
                <div class="max-w-md mx-auto py-4 bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-purpura-100 text-purpura-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ph-fill ph-lock-key text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Acesso Restrito</h3>
                        <p class="text-sm text-gray-500 mt-2">Para proteger seus dados, confirme sua identidade para acessar o cofre de documentos.</p>
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

                        <button type="submit" class="w-full bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                            <i class="ph-bold ph-shield-check"></i> Acessar Meu Dossiê
                        </button>
                    </form>
                </div>
            
            @else
                
                @if($concluido)
                    <!-- TELA DE SUCESSO -->
                    <div class="text-center py-12 bg-white rounded-2xl shadow-sm border border-gray-200 p-8 max-w-lg mx-auto">
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                            <i class="ph-fill ph-check-circle text-6xl text-green-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">Documentação Recebida!</h3>
                        <p class="mt-2 text-gray-500 max-w-sm mx-auto">Sua pasta de matrícula foi fechada e enviada para a nossa Secretaria. Entraremos em contato em breve!</p>
                    </div>
                @else
                    
                    <!-- IDENTIFICAÇÃO DO ALUNO -->
                    <div class="mb-8 pb-4 border-b border-gray-200 flex items-center gap-4">
                        <div class="w-12 h-12 bg-white border border-gray-200 shadow-sm rounded-full flex items-center justify-center text-gray-600 font-bold text-lg shrink-0">
                            {{ substr($inscricao->nome, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg uppercase tracking-wide">{{ $inscricao->nome }}</h3>
                            <p class="text-[10px] text-green-700 font-bold uppercase tracking-wider mt-0.5 flex items-center gap-1">
                                <i class="ph-fill ph-check-circle text-sm"></i> Identidade Confirmada
                            </p>
                        </div>
                    </div>

                    <!-- LAYOUT: DROPZONE E LISTA INDIVIDUAL -->
                    <div class="grid grid-cols-1 {{ feature('matricula.upload_multiplo') ? 'lg:grid-cols-12 gap-8' : 'gap-4 max-w-4xl mx-auto' }}">
                        
                        @if(feature('matricula.upload_multiplo'))
                            <!-- COLUNA ESQUERDA: DROPZONE DE LOTE -->
                            <div class="lg:col-span-5 flex flex-col gap-4" x-data="loteCompressor()" @lote-concluido.window="finalizarLote()">
                                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col h-full">
                                    <h3 class="font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                                        <i class="ph-bold ph-files text-purpura-500 text-xl"></i> Upload Automático
                                    </h3>
                                    
                                    <div class="border-2 border-dashed rounded-xl p-8 text-center transition-all relative flex flex-col items-center justify-center min-h-[200px]"
                                        :class="isDragging ? 'border-purpura-500 bg-purpura-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                                        @dragover.prevent="isDragging = true" 
                                        @dragleave.prevent="isDragging = false" 
                                        @drop.prevent="isDragging = false; processarDrop($event)">
                                        
                                        <input type="file" multiple accept="image/jpeg, image/png, image/webp" @change="processarUploadLote($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :disabled="processando">
                                        
                                        <div class="w-12 h-12 bg-white border border-gray-200 text-gray-400 rounded-lg flex items-center justify-center mb-3 shadow-sm">
                                            <i class="ph-bold ph-upload-simple text-2xl"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">Arraste os arquivos aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">ou clique para selecionar</p>
                                        
                                        <div class="mt-4 px-4 py-1.5 bg-green-500 text-white text-xs font-bold rounded shadow-sm relative z-0 pointer-events-none">
                                            Escolher Arquivos
                                        </div>
                                    </div>

                                    <div x-show="files.length > 0" x-cloak class="mt-4 space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Status do Envio</p>
                                        <template x-for="(file, index) in files" :key="index">
                                            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex items-center gap-2 overflow-hidden pr-2">
                                                        <i class="ph-fill ph-image text-gray-400 text-xl shrink-0"></i>
                                                        <div class="truncate">
                                                            <p class="text-xs font-bold text-gray-700 truncate" x-text="file.name"></p>
                                                            <p class="text-[10px] text-gray-500" x-text="file.size"></p>
                                                        </div>
                                                    </div>
                                                    <i class="ph-bold ph-spinner animate-spin text-purpura-500 text-lg shrink-0" x-show="file.status !== 'Concluído'"></i>
                                                    <i class="ph-fill ph-check-circle text-green-500 text-lg shrink-0" x-show="file.status === 'Concluído'"></i>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-1 mb-1">
                                                    <div class="bg-blue-500 h-1 rounded-full transition-all duration-300" :class="file.status === 'Concluído' ? 'w-full' : 'w-2/3 animate-pulse'"></div>
                                                </div>
                                                <div class="flex justify-between items-center text-[9px] text-gray-500 font-bold uppercase tracking-wide">
                                                    <span x-text="file.status"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- COLUNA DIREITA: LISTA DE DOCUMENTOS ESPECÍFICOS -->
                        <div class="{{ feature('matricula.upload_multiplo') ? 'lg:col-span-7' : 'w-full' }} flex flex-col gap-4">
                            @php $podeFinalizar = true; @endphp

                            @foreach($documentosExigidos as $doc)
                                @php
                                    $statusInfo = $arquivosEnviados[$doc->id];
                                    $status = $statusInfo['status'];
                                    if ($doc->is_obrigatorio && in_array($status, ['pendente', 'invalido_ia'])) {
                                        $podeFinalizar = false;
                                    }
                                @endphp

                                <div class="bg-white border rounded-xl p-4 {{ $status === 'valido_ia' ? 'border-green-300 shadow-sm' : 'border-gray-200 shadow-sm' }} transition-all flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                                {{ $doc->nome }} 
                                                @if($doc->is_obrigatorio) 
                                                    <span class="text-[9px] bg-red-50 text-red-600 px-2 py-0.5 rounded font-bold uppercase tracking-wider">Obrigatório</span> 
                                                @endif
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1">{{ $doc->descricao }}</p>
                                        </div>

                                        <div class="shrink-0 w-full sm:w-auto">
                                            @if($status === 'valido_ia')
                                                <span class="flex items-center justify-center gap-1.5 px-4 py-2 bg-green-50 text-green-700 text-xs font-bold rounded-lg border border-green-200">
                                                    <i class="ph-bold ph-check text-base"></i> Aprovado
                                                </span>
                                            @elseif($status === 'analise_manual')
                                                <span class="flex items-center justify-center gap-1.5 px-4 py-2 bg-yellow-50 text-yellow-700 text-xs font-bold rounded-lg border border-yellow-200">
                                                    <i class="ph-bold ph-clock text-base"></i> Em Análise Manual
                                                </span>
                                            @else
                                                <div x-data="imageCompressor({{ $doc->id }})" @analise-concluida.window="if($event.detail.docId == {{ $doc->id }}) { isAnalyzing = false; }">
                                                    <label class="flex items-center justify-center gap-2 px-5 py-2 bg-white border border-purpura-200 text-purpura-700 hover:bg-purpura-50 rounded-lg cursor-pointer transition font-bold text-xs shadow-sm w-full" x-show="!isCompressing && !isAnalyzing">
                                                        <i class="ph-bold ph-upload-simple text-base"></i> Enviar Arquivo
                                                        <input type="file" class="hidden" accept="image/jpeg, image/png, image/webp" @change="processarUpload">
                                                    </label>
                                                    <div x-show="isCompressing" style="display: none;" class="flex items-center justify-center gap-2 px-5 py-2 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg font-bold text-xs w-full shadow-sm">
                                                        <i class="ph-bold ph-arrows-in animate-pulse text-base"></i> Otimizando...
                                                    </div>
                                                    <div x-show="isAnalyzing" style="display: none;" class="flex items-center justify-center gap-2 px-5 py-2 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg font-bold text-xs w-full shadow-sm">
                                                        <i class="ph-bold ph-spinner animate-spin text-base"></i> IA Analisando...
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($status === 'invalido_ia')
                                        <div class="mt-3 bg-red-50/50 border border-red-100 p-2.5 rounded-lg flex items-start gap-2">
                                            <i class="ph-fill ph-warning-circle text-red-500 text-base shrink-0"></i>
                                            <div>
                                                <p class="text-[11px] font-bold text-red-800">Rejeitado pela IA (Tentativa {{ $statusInfo['tentativas'] }} de 3)</p>
                                                <p class="text-[11px] text-red-600 leading-tight mt-0.5">{{ $statusInfo['motivo_rejeicao'] }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <button wire:click="finalizarMatricula" 
                                        class="w-full py-4 rounded-xl font-black text-sm uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2 {{ $podeFinalizar ? 'bg-gray-900 hover:bg-black text-white cursor-pointer shadow-md' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' }}"
                                        @if(!$podeFinalizar) disabled @endif>
                                    <i class="ph-bold ph-paper-plane-right text-lg"></i> Enviar Dossiê Completo
                                </button>
                                @if(!$podeFinalizar)
                                    <p class="text-center text-[10px] font-bold text-gray-400 mt-3 uppercase tracking-wider">Pendências obrigatórias bloqueiam o envio</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@script
<script>
    // 1. MOTOR DO DROPZONE DE MÚLTIPLOS ARQUIVOS
    Alpine.data('loteCompressor', () => ({
        isDragging: false,
        processando: false,
        files: [], // Array visual para exibir o progresso

        processarDrop(event) {
            this.handleFiles(event.dataTransfer.files);
        },

        processarUploadLote(event) {
            this.handleFiles(event.target.files);
            event.target.value = ''; // Reseta o input visualmente
        },

        async handleFiles(fileList) {
            const incomingFiles = Array.from(fileList);
            if (incomingFiles.length === 0) return;

            this.processando = true;
            
            // Popula a lista visual
            this.files = incomingFiles.map(f => ({
                name: f.name,
                size: (f.size / 1024 / 1024).toFixed(2) + ' MB',
                status: 'Aguardando',
                originalFile: f,
                compressedFile: null
            }));

            let uploadPayload = [];

            // Fase 1: Compressão
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

            // Fase 2: Upload para o Livewire
            $wire.uploadMultiple('uploadsLote', uploadPayload,
                (uploadedFilename) => {
                    // O Livewire terminará no PHP e emitirá o evento @lote-concluido.window
                },
                () => {
                    this.files.forEach(f => f.status = 'Erro no envio');
                    this.processando = false;
                    this.$dispatch('erro', {msg: 'Falha na conexão ao enviar o lote.'});
                }
            );
        },

        finalizarLote() {
            this.files.forEach(f => f.status = 'Concluído');
            setTimeout(() => {
                this.processando = false;
                this.files = []; // Limpa a lista visual após 3 segundos
            }, 3000);
        },

        comprimirImagem(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        const MAX_SIZE = 1200;

                        if (width > height && width > MAX_SIZE) {
                            height *= MAX_SIZE / width;
                            width = MAX_SIZE;
                        } else if (height > MAX_SIZE) {
                            width *= MAX_SIZE / height;
                            height = MAX_SIZE;
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.85);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    }));

    // 2. MOTOR DE UPLOAD INDIVIDUAL (DIREITA)
    Alpine.data('imageCompressor', (docId) => ({
        isCompressing: false,
        isAnalyzing: false,

        processarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.isCompressing = true;

            // Se for PDF (caso libere futuramente), manda direto
            if (!file.type.startsWith('image/')) {
                this.isCompressing = false;
                this.isAnalyzing = true;
                $wire.upload('uploads.' + docId, file);
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const MAX_SIZE = 1200;

                    if (width > height && width > MAX_SIZE) {
                        height *= MAX_SIZE / width; width = MAX_SIZE;
                    } else if (height > MAX_SIZE) {
                        width *= MAX_SIZE / height; height = MAX_SIZE;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        const novoNome = file.name.replace(/\.[^/.]+$/, "") + ".jpg";
                        const newFile = new File([blob], novoNome, { type: 'image/jpeg' });
                        
                        this.isCompressing = false;
                        this.isAnalyzing = true; // Aciona o layout "Analisando IA..."
                        
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