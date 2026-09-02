<div class="min-h-screen bg-gray-50 flex flex-col py-12 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full max-w-5xl">
        
        <div class="text-center mb-10">
            <h2 class="text-3xl font-black text-gray-900">Portal de Matrícula</h2>
            <p class="mt-2 text-sm text-gray-600">Curso de <b class="text-purpura-700">{{ $inscricao->curso->nome ?? 'Não definido' }}</b></p>
        </div>

        <div class="px-4 sm:px-0 relative">
            
            @if(!$autenticado)
                <div class="max-w-md mx-auto py-8 bg-white px-8 rounded-xl shadow-sm border border-gray-200">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-purpura-50 text-purpura-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-purpura-100">
                            <i class="ph-fill ph-lock-key text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Acesso Restrito</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Confirme sua identidade para acessar o cofre de documentos.</p>
                    </div>

                    <form wire:submit.prevent="verificarIdentidade" class="space-y-5">
                        @if($errors->has('falha_auth'))
                            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200 font-bold text-center shadow-sm">
                                {{ $errors->first('falha_auth') }}
                            </div>
                        @endif
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Seu CPF</label>
                            <input type="text" wire:model="cpf_acesso" class="w-full px-4 py-2.5 text-sm border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500" placeholder="000.000.000-00">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Data de Nascimento</label>
                            <input type="date" wire:model="data_nascimento_acesso" class="w-full px-4 py-2.5 text-sm border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                        </div>
                        <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 rounded-lg shadow-sm transition flex items-center justify-center gap-2 mt-2">
                            <i class="ph-bold ph-shield-check text-lg"></i> Acessar Meu Dossiê
                        </button>
                    </form>
                </div>
            @else
                @if($concluido)
                    <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200 p-8 max-w-lg mx-auto">
                        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-50 border border-green-100 mb-5">
                            <i class="ph-fill ph-check-circle text-5xl text-green-500"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">Documentação Recebida!</h3>
                        <p class="mt-3 text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">Sua pasta foi fechada e enviada para a nossa Secretaria. Entraremos em contato em breve.</p>
                    </div>
                @else
                    <div class="mb-8 pb-4 border-b border-gray-200 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white border border-gray-200 shadow-sm rounded-full flex items-center justify-center text-gray-600 font-bold text-lg shrink-0">
                                {{ substr($inscricao->nome, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-base uppercase tracking-wide">{{ $inscricao->nome }}</h3>
                                <p class="text-[10px] text-green-600 font-bold uppercase tracking-wider mt-0.5 flex items-center gap-1">
                                    <i class="ph-fill ph-check-circle text-sm"></i> Identidade Confirmada
                                </p>
                            </div>
                        </div>
                        <button wire:click="sair" class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors shadow-sm">
                            <i class="ph-bold ph-sign-out text-sm"></i> Sair
                        </button>
                    </div>

                    <div class="grid grid-cols-1 {{ feature('matricula.upload_multiplo') ? 'lg:grid-cols-12 gap-8' : 'gap-6 max-w-4xl mx-auto' }}">
                        
                        @if(feature('matricula.upload_multiplo'))
                            <div class="lg:col-span-5 flex flex-col gap-4" x-data="loteCompressor()" @lote-concluido.window="finalizarLote()">
                                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col h-full">
                                    <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                                        <i class="ph-fill ph-files text-lg text-purpura-500"></i> Upload Automático
                                    </h3>
                                    
                                    <div class="border-2 border-dashed rounded-xl p-8 text-center transition-all relative flex flex-col items-center justify-center min-h-[200px]"
                                         :class="isDragging ? 'border-purpura-400 bg-purpura-50/50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100/50'"
                                         @dragover.prevent="isDragging = true" 
                                         @dragleave.prevent="isDragging = false" 
                                         @drop.prevent="isDragging = false; processarDrop($event)">
                                        
                                        <input type="file" multiple accept="image/jpeg, image/png, image/webp" @change="processarUploadLote($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :disabled="processando">
                                        
                                        <div class="w-12 h-12 bg-white border border-gray-200 text-gray-400 rounded-lg flex items-center justify-center mb-3 shadow-sm">
                                            <i class="ph-bold ph-upload-simple text-2xl"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">Arraste os arquivos aqui</p>
                                        <p class="text-[11px] text-gray-500 mt-1">ou clique para selecionar</p>
                                    </div>

                                    <div x-show="files.length > 0" x-cloak class="mt-5 space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Status do Envio</p>
                                        <template x-for="(file, index) in files" :key="index">
                                            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex items-center gap-2 overflow-hidden pr-2">
                                                        <i class="ph-fill ph-image text-gray-400 text-lg shrink-0"></i>
                                                        <div class="truncate">
                                                            <p class="text-xs font-bold text-gray-700 truncate" x-text="file.name"></p>
                                                        </div>
                                                    </div>
                                                    <i class="ph-bold ph-spinner animate-spin text-purpura-500 text-base shrink-0" x-show="file.status !== 'Concluído'"></i>
                                                    <i class="ph-fill ph-check-circle text-green-500 text-base shrink-0" x-show="file.status === 'Concluído'"></i>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-1 mb-1.5">
                                                    <div class="bg-blue-500 h-1 rounded-full transition-all duration-300" :class="file.status === 'Concluído' ? 'w-full' : 'w-2/3 animate-pulse'"></div>
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

                        <div class="{{ feature('matricula.upload_multiplo') ? 'lg:col-span-7' : 'w-full' }} flex flex-col gap-4">
                            @php $podeFinalizar = true; @endphp

                            @foreach($documentosExigidos as $doc)
                                @php
                                    $statusInfo = $arquivosEnviados[$doc->id];
                                    $status = $statusInfo['status'];
                                    if ($doc->is_obrigatorio && in_array($status, ['pendente', 'invalido_ia'])) { $podeFinalizar = false; }
                                @endphp

                                <div class="bg-white border rounded-xl p-5 shadow-sm {{ $status === 'valido_ia' ? 'border-green-200 bg-green-50/20' : 'border-gray-200' }} transition-all flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                                {{ $doc->nome }} 
                                                @if($doc->is_obrigatorio) 
                                                    <span class="text-[9px] bg-red-50 text-red-600 px-2 py-0.5 rounded font-bold uppercase tracking-wider border border-red-100">Obrigatório</span> 
                                                @endif
                                            </h4>
                                            <p class="text-[11px] text-gray-500 mt-1 leading-snug">{{ $doc->descricao }}</p>
                                        </div>

                                        <div class="shrink-0 w-full sm:w-auto">
                                            @if($status === 'valido_ia')
                                                <span class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-green-50 text-green-700 text-[10px] uppercase tracking-wider font-bold rounded-lg border border-green-200">
                                                    <i class="ph-bold ph-check text-sm"></i> Aprovado
                                                </span>
                                            @elseif($status === 'analise_manual')
                                                <span class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-yellow-50 text-yellow-700 text-[10px] uppercase tracking-wider font-bold rounded-lg border border-yellow-200">
                                                    <i class="ph-bold ph-clock text-sm"></i> Em Análise
                                                </span>
                                            @else
                                                <div x-data="imageCompressor({{ $doc->id }})" @analise-concluida.window="if($event.detail.docId == {{ $doc->id }}) { isAnalyzing = false; }">
                                                    <label class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 hover:border-purpura-400 hover:text-purpura-600 rounded-lg cursor-pointer transition font-bold text-[10px] uppercase tracking-wider shadow-sm w-full" x-show="!isCompressing && !isAnalyzing">
                                                        <i class="ph-bold ph-upload-simple text-sm"></i> Enviar Arquivo
                                                        <input type="file" class="hidden" accept="image/jpeg, image/png, image/webp" @change="processarUpload">
                                                    </label>
                                                    <div x-show="isCompressing" style="display: none;" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg font-bold text-[10px] uppercase tracking-wider w-full shadow-sm">
                                                        <i class="ph-bold ph-arrows-in animate-pulse text-sm"></i> Otimizando...
                                                    </div>
                                                    <div x-show="isAnalyzing" style="display: none;" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg font-bold text-[10px] uppercase tracking-wider w-full shadow-sm">
                                                        <i class="ph-bold ph-spinner animate-spin text-sm"></i> Analisando...
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($status === 'invalido_ia')
                                        <div class="mt-4 bg-red-50/50 border border-red-100 p-3 rounded-lg flex items-start gap-2.5">
                                            <i class="ph-fill ph-warning-circle text-red-500 text-base shrink-0"></i>
                                            <div>
                                                <p class="text-[10px] font-bold text-red-800 uppercase tracking-wider mb-0.5">Rejeitado pela IA (Tentativa {{ $statusInfo['tentativas'] }} de 3)</p>
                                                <p class="text-[11px] text-red-600 leading-tight font-medium">{{ $statusInfo['motivo_rejeicao'] }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="mt-4">
                                <button wire:click="finalizarMatricula" 
                                        class="w-full py-4 rounded-xl font-black text-xs uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2 {{ $podeFinalizar ? 'bg-gray-900 hover:bg-black text-white cursor-pointer shadow-md hover:-translate-y-0.5' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' }}"
                                        @if(!$podeFinalizar) disabled @endif>
                                    <i class="ph-bold ph-paper-plane-right text-lg"></i> Enviar Dossiê Completo
                                </button>
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