<div class="font-sans flex flex-col transition-all duration-300 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden w-full relative"
     @if($templateId) wire:poll.5s="pingLock" @endif
     x-data="{ modoExpandido: true, navbarOculta: true }"
     x-init="
        $watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val));
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
        
        document.body.classList.toggle('modo-expandido', modoExpandido);
        document.body.classList.toggle('navbar-oculta', navbarOculta);
     "
     :class="modoExpandido ? 'h-screen border-none rounded-none' : 'min-h-[720px] h-[calc(100vh-140px)]'">

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        main > div.py-6 > div { max-width: 100% !important; }
        body.modo-expandido main > div > div { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
        body.navbar-oculta .js-sidebar { display: none !important; }

        .template-quill .ql-toolbar.ql-snow {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            border-color: #e5e7eb;
            background-color: #f9fafb;
            padding: 8px 12px;
            font-family: inherit;
        }
        .template-quill .ql-container.ql-snow {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            border-color: #e5e7eb;
            background-color: #ffffff;
            font-family: inherit;
            font-size: 0.875rem;
            min-height: 290px;
        }
        .template-quill .ql-editor {
            min-height: 290px;
            max-height: 440px;
            overflow-y: auto;
            line-height: 1.6;
        }
        .template-quill .ql-editor:focus {
            box-shadow: inset 0 0 0 1px rgba(147, 51, 234, 0.35);
            outline: none;
        }
    </style>

    {{-- CORTINA DE BLOQUEIO: OUTRO UTILIZADOR A EDITAR --}}
    @if($templateId && $isLockedByOther)
        <div class="absolute inset-0 z-[200] bg-white/70 dark:bg-gray-900/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Template em Edição</h3>
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-5 leading-relaxed">
                    O utilizador <strong>{{ $lockOwnerName }}</strong> está a editar este template neste momento.
                </p>

                @if($transferRejected)
                    <div class="mb-4 p-2.5 bg-red-50 border border-red-200 rounded text-xs font-medium text-red-700">
                        O utilizador recusou a transferência e pediu para aguardar.
                    </div>
                @endif

                <div class="flex flex-col gap-2">
                    @php
                        $transferData = \Illuminate\Support\Facades\Cache::get($this->getTransferKey());
                        $isPending = $transferData && ($transferData['status'] ?? '') === 'pending' && ($transferData['requester_id'] ?? null) === auth()->id();
                    @endphp

                    @if($isPending)
                        <button type="button" class="py-2 px-4 rounded-lg font-medium text-xs bg-gray-100 text-gray-500 cursor-not-allowed w-full border border-gray-200" disabled>
                            Aguardando resposta...
                        </button>
                        <button type="button" wire:click="cancelTransferRequest" class="text-xs text-gray-500 hover:text-red-600 underline mt-1">
                            Cancelar Pedido
                        </button>
                    @else
                        <button type="button" wire:click="requestControl" class="py-2 px-4 rounded-lg font-medium text-xs bg-gray-900 hover:bg-black text-white w-full transition">
                            Solicitar Controle
                        </button>
                        <a href="{{ route('templates.index') }}" class="py-2 px-4 rounded-lg font-medium text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 w-full block transition">
                            Voltar para Templates
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <fieldset @if($templateId && $isLockedByOther) disabled class="pointer-events-none opacity-60 blur-[2px] transition-all duration-300" @endif class="flex flex-col flex-1 h-full w-full overflow-hidden">
        
        <!-- BARRA SUPERIOR DO CONSTRUTOR -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-5 py-2.5 flex flex-wrap items-center justify-between gap-4 z-[60] shrink-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('templates.index') }}" onclick="document.body.classList.remove('modo-expandido', 'navbar-oculta')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition" title="Voltar para a listagem">
                    <i class="ph ph-arrow-left"></i>
                </a>
                <div class="border-l border-gray-200 dark:border-gray-700 pl-3">
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $templateId ? 'Editar Template' : 'Novo Template' }}
                        </h1>
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[10px] font-medium rounded border border-gray-200 dark:border-gray-700 uppercase">
                            {{ $canal === 'ambos' ? 'Multicanal' : $canal }}
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $nome ?: 'Sem título definido' }}
                    </p>
                </div>
            </div>

            <!-- SELETOR CENTRAL DE CANAL -->
            <div class="inline-flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="setCanal('email')" class="px-3 py-1 rounded-md text-xs font-medium transition {{ $canal === 'email' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Apenas E-mail
                </button>
                <button type="button" wire:click="setCanal('whatsapp')" class="px-3 py-1 rounded-md text-xs font-medium transition {{ $canal === 'whatsapp' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Apenas WhatsApp
                </button>
                <button type="button" wire:click="setCanal('ambos')" class="px-3 py-1 rounded-md text-xs font-medium transition {{ $canal === 'ambos' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Ambos (E-mail + WhatsApp)
                </button>
            </div>

            <!-- CONTROLES DE TELA E SALVAMENTO -->
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button" @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-2 py-1 text-xs rounded transition-colors" :class="modoExpandido ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-500 hover:text-gray-900'" title="Expandir Tela">
                        <i class="ph" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    </button>
                    <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-2 py-1 text-xs rounded transition-colors text-gray-500 hover:text-gray-900" :class="navbarOculta ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : ''" title="Ocultar Menus">
                        <i class="ph" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>

                @if(feature('template.editar') || feature('template.criar'))
                    <button type="button" wire:click="salvar" wire:loading.attr="disabled" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                        {{ $templateId ? 'Atualizar Template' : 'Salvar Template' }}
                    </button>
                @endif
            </div>
        </div>

        <!-- ÁREA DE TRABALHO EM 3 COLUNAS -->
        <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50 dark:bg-gray-950">
            
            <!-- COLUNA 1 (ESQUERDA): DICIONÁRIO DE VARIÁVEIS -->
            <aside class="w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Variáveis Dinâmicas
                    </span>
                    <span class="text-[11px] text-gray-400">Clique p/ inserir</span>
                </div>

                <div class="p-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" wire:model.live.debounce.250ms="buscaVariavel" placeholder="Filtrar variável..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-purpura-500">
                    </div>
                </div>

                <div class="p-3 overflow-y-auto flex-1 custom-scrollbar space-y-2.5">
                    @forelse($dicionario as $categoria => $variaveis)
                        <div x-data="{ open: true }" class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 overflow-hidden">
                            <button type="button" @click="open = !open" class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 dark:bg-gray-800/60 hover:bg-gray-100 transition border-b border-gray-100 dark:border-gray-800">
                                <span class="text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ $categoria }}</span>
                                <i class="ph ph-caret-down text-xs text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            
                            <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($variaveis as $chave => $descricao)
                                    <div class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition group flex flex-col gap-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <code class="text-[11px] font-mono font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700 cursor-pointer"
                                                  @click="window.dispatchEvent(new CustomEvent('inserir-variavel-quill', { detail: '{{ $chave }}' })); navigator.clipboard.writeText('{{ $chave }}'); $dispatch('sucesso', {msg: 'Variável inserida e copiada!'})"
                                                  title="Clique para inserir">
                                                {{ $chave }}
                                            </code>

                                            <div class="flex items-center gap-1 opacity-75 group-hover:opacity-100">
                                                <button type="button"
                                                        @click="window.dispatchEvent(new CustomEvent('inserir-variavel-quill', { detail: '{{ $chave }}' }))"
                                                        class="px-1.5 py-0.5 text-[10px] font-medium bg-gray-900 dark:bg-gray-700 text-white rounded hover:bg-black transition">
                                                    Inserir
                                                </button>
                                                <button type="button"
                                                        @click="navigator.clipboard.writeText('{{ $chave }}'); $dispatch('sucesso', {msg: 'Variável copiada!'})"
                                                        class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-white rounded"
                                                        title="Copiar">
                                                    <i class="ph ph-copy text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">{{ $descricao }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-gray-400">
                            Nenhuma variável encontrada para "{{ $buscaVariavel }}".
                        </div>
                    @endforelse
                </div>
            </aside>

            <!-- COLUNA 2 (CENTRO): EDITOR DE MENSAGEM E CONFIGURAÇÕES -->
            <div class="flex-1 flex flex-col h-full overflow-y-auto custom-scrollbar p-6 space-y-5">
                
                <!-- BLOCO 1: IDENTIFICAÇÃO E ASSUNTO -->
                <div class="bg-white dark:bg-gray-900 p-5 rounded-lg border border-gray-200 dark:border-gray-800">
                    <div class="grid grid-cols-1 {{ in_array($canal, ['email', 'ambos']) ? 'md:grid-cols-2' : '' }} gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nome Interno do Template <span class="text-red-500">*</span>
                            </label>
                            <input wire:model.live.debounce.300ms="nome" type="text" placeholder="Ex: Confirmação de Matrícula" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-purpura-500">
                            @error('nome') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if(in_array($canal, ['email', 'ambos']))
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Assunto do E-mail <span class="text-red-500">*</span>
                                </label>
                                <input wire:model.live.debounce.300ms="assunto" type="text" placeholder="Ex: Matrícula Concluída - Instituto Percorre" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-purpura-500">
                                @error('assunto') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <!-- BLOCO 2: CORPO DA MENSAGEM (QUILL) -->
                <div class="bg-white dark:bg-gray-900 p-5 rounded-lg border border-gray-200 dark:border-gray-800 space-y-2.5">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Conteúdo da Mensagem <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-gray-500">
                            No WhatsApp, negritos, listas e quebras de linha são convertidos automaticamente.
                        </p>
                    </div>

                    <div class="template-quill"
                         wire:ignore
                         x-data="{
                            conteudo: @entangle('corpo').live,
                            quill: null,
                            init() {
                                this.quill = new Quill(this.$refs.quillEditor, {
                                    theme: 'snow',
                                    placeholder: 'Olá, @{{nome}}! Escreva o conteúdo do template aqui...',
                                    modules: {
                                        toolbar: [
                                            [{ 'header': [1, 2, 3, false] }],
                                            ['bold', 'italic', 'underline', 'strike'],
                                            [{ 'color': [] }, { 'background': [] }],
                                            [{ 'align': [] }],
                                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                            ['link', 'image', 'clean']
                                        ]
                                    }
                                });

                                if (this.conteudo) {
                                    this.quill.clipboard.dangerouslyPasteHTML(this.conteudo);
                                }

                                this.quill.on('text-change', () => {
                                    this.conteudo = this.quill.root.innerHTML;
                                });

                                window.addEventListener('inserir-variavel-quill', (e) => {
                                    const variavel = e.detail;
                                    this.quill.focus();
                                    const range = this.quill.getSelection(true);
                                    if (range) {
                                        this.quill.insertText(range.index, variavel, 'user');
                                        this.quill.setSelection(range.index + variavel.length);
                                    } else {
                                        const length = this.quill.getLength();
                                        this.quill.insertText(length - 1, ' ' + variavel, 'user');
                                    }
                                    this.conteudo = this.quill.root.innerHTML;
                                });
                            }
                         }">
                        <div x-ref="quillEditor"></div>
                    </div>
                    @error('corpo') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- BLOCO 3: ESTRUTURA SURI (CABEÇALHO, RODAPÉ E MAPEAMENTO DE VARIÁVEIS) -->
                @if(in_array($canal, ['whatsapp', 'ambos']))
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-lg border border-gray-200 dark:border-gray-800 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div>
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                                    Configurações do Template WhatsApp (Suri / Meta)
                                </h3>
                                <p class="text-[11px] text-gray-500">
                                    Categoria, cabeçalho de mídia, rodapé e mapeamento das variáveis.
                                </p>
                            </div>
                            <button type="button" wire:click="$set('abaPreview', 'json_suri')" class="px-2.5 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-200 transition">
                                { } JSON Suri
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Categoria (category)</label>
                                <select wire:model.live="suriCategory" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="MARKETING">MARKETING</option>
                                    <option value="UTILITY">UTILITY</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cabeçalho (headerType)</label>
                                <select wire:model.live="suriHeaderMode" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="none">Sem Cabeçalho</option>
                                    <option value="image">Imagem (URL)</option>
                                    <option value="document">Documento PDF (Base64)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Rodapé Opcional (footer)</label>
                                <input type="text" wire:model.live.debounce.250ms="suriFooter" maxlength="60" placeholder="Ex: Instituto Percorre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                        </div>

                        @if($suriHeaderMode === 'image')
                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">URL da Imagem (sampleFile.url) *</label>
                                    <input type="url" wire:model.live.debounce.300ms="sampleFileUrl" placeholder="https://..." class="w-full text-xs font-mono rounded-lg border-gray-300">
                                    @error('sampleFileUrl') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Arquivo (fileName) *</label>
                                    <input type="text" wire:model.live.debounce.300ms="sampleFileName" placeholder="imagem.png" class="w-full text-xs font-mono rounded-lg border-gray-300">
                                    @error('sampleFileName') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @elseif($suriHeaderMode === 'document')
                            <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Upload PDF (Converte em Base64) *</label>
                                        <input type="file" wire:model="samplePdfUpload" accept=".pdf" class="w-full text-xs text-gray-600">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Arquivo (fileName) *</label>
                                        <input type="text" wire:model.live.debounce.300ms="sampleFileName" placeholder="documento.pdf" class="w-full text-xs font-mono rounded-lg border-gray-300">
                                        @error('sampleFileName') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Base64 do Documento</label>
                                    <input type="text" wire:model.live.debounce.300ms="sampleFileBase64" placeholder="data:application/pdf;base64,..." class="w-full text-xs font-mono rounded-lg border-gray-300">
                                    @error('sampleFileBase64') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        @if(count($bodyExamples) > 0)
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2.5">
                                <span class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Variáveis do Corpo (Mapeamento de Coluna do Banco & Exemplo para Aprovação)
                                </span>
                                <div class="space-y-2">
                                    @foreach($bodyExamples as $varKey => $exemploVal)
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 items-center bg-gray-50 dark:bg-gray-800 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                            <div class="md:col-span-3">
                                                <span class="px-2 py-1 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded font-mono text-xs font-medium">
                                                    #{{ $loop->iteration }} (&#123;&#123;{{ $varKey }}&#125;&#125;)
                                                </span>
                                            </div>
                                            @if(!empty($colunasBanco))
                                                <div class="md:col-span-5">
                                                    <select wire:model.live="suriVariableMap.{{ $varKey }}" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white py-1.5 px-2">
                                                        @foreach($colunasBanco as $grupoCol => $listaCols)
                                                            <optgroup label="{{ $grupoCol }}">
                                                                @foreach($listaCols as $colKey => $colLabel)
                                                                    <option value="{{ $colKey }}">{{ $colLabel }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="md:col-span-4">
                                                    <input type="text" wire:model.live.debounce.250ms="bodyExamples.{{ $varKey }}" placeholder="Exemplo (Ex: Maria)" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white py-1.5 px-2">
                                                </div>
                                            @else
                                                <div class="md:col-span-9">
                                                    <input type="text" wire:model.live.debounce.250ms="bodyExamples.{{ $varKey }}" placeholder="Exemplo (Ex: Maria)" class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white py-1.5 px-2">
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- BLOCO 4: BOTÕES INTERATIVOS -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-lg border border-gray-200 dark:border-gray-800 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div>
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                                    Botões Interativos (Máx. 10)
                                </h3>
                                <p class="text-[11px] text-gray-500">
                                    Adicione botões de Resposta Rápida, Link/URL, Ligação ou Copiar Código.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" wire:click="adicionarBotao(2)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                    + Resposta Rápida
                                </button>
                                <button type="button" wire:click="adicionarBotao(0)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                    + URL
                                </button>
                                <button type="button" wire:click="adicionarBotao(1)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                    + Ligar
                                </button>
                                <button type="button" wire:click="adicionarBotao(3)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                    + Copiar Código
                                </button>
                            </div>
                        </div>

                        @error('botoes')
                            <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-xs font-medium text-red-700">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="space-y-3">
                            @forelse($botoes as $index => $btn)
                                @php $tipoSuriBtn = (int) ($btn['suri_type'] ?? 2); @endphp
                                <div wire:key="tpl-btn-{{ $btn['id'] ?? $index }}" class="p-3.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 space-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-mono font-semibold">
                                                #{{ $index + 1 }}
                                            </span>
                                            <select wire:model.live="botoes.{{ $index }}.suri_type" class="text-xs font-medium rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-1 pl-2.5 pr-7">
                                                <option value="2">Tipo 2: Resposta Rápida (Quick Reply)</option>
                                                <option value="0">Tipo 0: Acessar URL</option>
                                                <option value="1">Tipo 1: Ligar para Telefone</option>
                                                <option value="3">Tipo 3: Copiar Código</option>
                                            </select>
                                        </div>

                                        <button type="button" wire:click="removerBotao({{ $index }})" class="text-gray-400 hover:text-red-600 p-1 rounded transition" title="Remover Botão">
                                            <i class="ph ph-trash text-sm"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                        <div class="md:col-span-4">
                                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Texto do Botão *</label>
                                            <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.texto" placeholder="Ex: Confirmar" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                            @error("botoes.{$index}.texto") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                        </div>

                                        @if($tipoSuriBtn === 0)
                                            <div class="md:col-span-5">
                                                <label class="block text-[11px] font-medium text-gray-500 mb-1">URL (Dinâmica termina com &#123;&#123;1&#125;&#125;) *</label>
                                                <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.url" placeholder="https://..." class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                @error("botoes.{$index}.url") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Exemplo Final</label>
                                                <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.example" placeholder="detalhe" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                            </div>
                                        @elseif($tipoSuriBtn === 1)
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefone (+DDI + DDD + Número) *</label>
                                                <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.phone" placeholder="+5511999999999" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                @error("botoes.{$index}.phone") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                            </div>
                                        @elseif($tipoSuriBtn === 3)
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Código de Exemplo (example) *</label>
                                                <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.example" placeholder="CODIGO10" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                @error("botoes.{$index}.example") <span class="text-[11px] text-red-600 block mt-1">{{ $message }}</span> @enderror
                                            </div>
                                        @else
                                            <div class="md:col-span-8 flex items-center pt-5 text-xs text-gray-400">
                                                Botão de Resposta Rápida (<code class="font-mono">type: 2</code>).
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="p-5 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-center text-xs text-gray-400">
                                    Nenhum botão interativo configurado.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

            </div>

            <!-- COLUNA 3 (DIREITA): PREVIEW EM TEMPO REAL -->
            <aside class="w-96 xl:w-[420px] bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden">
                
                <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Pré-visualização
                    </span>

                    <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-0.5 rounded-lg border border-gray-200 dark:border-gray-700">
                        @if(in_array($canal, ['email', 'ambos']))
                            <button type="button" wire:click="$set('abaPreview', 'email')" class="px-2.5 py-1 rounded-md text-[11px] font-medium transition {{ $abaPreview === 'email' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-500' }}">
                                E-mail
                            </button>
                        @endif
                        @if(in_array($canal, ['whatsapp', 'ambos']))
                            <button type="button" wire:click="$set('abaPreview', 'whatsapp')" class="px-2.5 py-1 rounded-md text-[11px] font-medium transition {{ $abaPreview === 'whatsapp' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-500' }}">
                                WhatsApp
                            </button>
                            <button type="button" wire:click="$set('abaPreview', 'json_suri')" class="px-2.5 py-1 rounded-md text-[11px] font-medium transition {{ $abaPreview === 'json_suri' ? 'bg-gray-900 text-white' : 'text-gray-500' }}">
                                JSON
                            </button>
                        @endif
                    </div>
                </div>

                @php
                    $converterParaWhatsAppPreview = function (?string $html): string {
                        if (empty($html)) return '';
                        $texto = $html;
                        $texto = preg_replace('/<(strong|b)[^>]*>(.*?)<\/\1>/is', '*$2*', $texto);
                        $texto = preg_replace('/<(em|i)[^>]*>(.*?)<\/\1>/is', '_$2_', $texto);
                        $texto = preg_replace('/<(s|del|strike)[^>]*>(.*?)<\/\1>/is', '~$2~', $texto);
                        $texto = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "• $1\n", $texto);
                        $texto = preg_replace('/<\/(p|div|h1|h2|h3|ul|ol)>/i', "\n", $texto);
                        $texto = preg_replace('/<br\s*\/?>/i', "\n", $texto);
                        $texto = strip_tags($texto);
                        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $texto = preg_replace("/\n{3,}/", "\n\n", trim($texto));

                        $textoSeguro = e($texto);
                        $textoSeguro = preg_replace('/\*(.*?)\*/s', '<strong>$1</strong>', $textoSeguro);
                        $textoSeguro = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $textoSeguro);
                        $textoSeguro = preg_replace('/\~(.*?)\~/s', '<del>$1</del>', $textoSeguro);
                        $textoSeguro = preg_replace('/(\{\{.*?\}\})/', '<span class="px-1 py-0.2 bg-gray-100 text-gray-800 rounded font-mono text-[11px] font-semibold">$1</span>', $textoSeguro);
                        return nl2br($textoSeguro);
                    };
                @endphp

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-50 dark:bg-gray-950">
                    
                    @if($abaPreview === 'email' && in_array($canal, ['email', 'ambos']))
                        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-[11px] text-gray-400 block">Assunto:</span>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {{ $assunto ?: '(Sem assunto definido)' }}
                                </p>
                            </div>

                            <div class="p-4 prose prose-sm max-w-none text-gray-800 dark:text-gray-200 text-xs leading-relaxed">
                                @if(!empty(trim(strip_tags($corpo))))
                                    {!! $corpo !!}
                                @else
                                    <p class="text-gray-400 italic">O conteúdo do e-mail aparecerá aqui...</p>
                                @endif

                                @php
                                    $botoesEmail = collect($botoes)->filter(fn($b) => !empty(trim($b['texto'] ?? '')) && filter_var($b['exibir_email'] ?? false, FILTER_VALIDATE_BOOLEAN));
                                @endphp

                                @if(in_array($canal, ['ambos']) && $botoesEmail->isNotEmpty())
                                    <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2 not-prose">
                                        @foreach($botoesEmail as $bEmail)
                                            <div class="flex flex-col items-start">
                                                <a href="{{ $bEmail['url'] ?: '#' }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium bg-purpura-600 text-white">
                                                    <span>{{ $bEmail['texto'] }}</span>
                                                    <i class="ph ph-arrow-square-out"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif($abaPreview === 'json_suri')
                        <div class="bg-gray-950 text-gray-200 p-4 rounded-lg border border-gray-800 font-mono text-[11px] overflow-x-auto">
                            <div class="text-[10px] uppercase text-gray-400 mb-2 border-b border-gray-800 pb-1.5">
                                Payload POST {{ rtrim(env('SURI_API_URL', ''), '/') }}/api/templates
                            </div>
                            <pre class="leading-relaxed">{{ json_encode($this->payloadSuriPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @else
                        <!-- PREVIEW WHATSAPP -->
                        <div class="rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700 bg-[#efeae2] dark:bg-gray-900 flex flex-col min-h-[460px]">
                            <div class="bg-[#075e54] text-white px-4 py-2.5 flex items-center justify-between shrink-0">
                                <div>
                                    <p class="text-xs font-semibold">Instituto Percorre</p>
                                    <p class="text-[10px] text-emerald-100">Conta comercial</p>
                                </div>
                                <i class="ph ph-whatsapp-logo text-lg text-white/80"></i>
                            </div>

                            <div class="p-4 flex-1 flex flex-col justify-start space-y-2">
                                <div class="bg-white dark:bg-gray-800 rounded-lg rounded-tl-none p-3 shadow-2xs max-w-[92%] text-xs text-gray-800 dark:text-gray-100 leading-relaxed">
                                    @if(!empty(trim(strip_tags($corpo))))
                                        <div>{!! $converterParaWhatsAppPreview($corpo) !!}</div>
                                    @else
                                        <span class="text-gray-400 italic">Digite a mensagem para pré-visualizar...</span>
                                    @endif
                                    <div class="text-[9px] text-gray-400 text-right mt-1.5">
                                        {{ now()->format('H:i') }}
                                    </div>
                                </div>

                                @php
                                    $botoesWpp = collect($botoes)->filter(fn($b) => !empty(trim($b['texto'] ?? '')));
                                @endphp

                                @if($botoesWpp->isNotEmpty())
                                    <div class="space-y-1 max-w-[92%]">
                                        @foreach($botoesWpp as $bWpp)
                                            <div class="bg-white dark:bg-gray-800 rounded-lg py-2 px-3 border border-gray-200 dark:border-gray-700 text-center text-xs font-medium text-[#00a884] flex items-center justify-center gap-1.5">
                                                <span class="truncate">{{ $bWpp['texto'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </aside>

        </div>
    </fieldset>

    {{-- POPUP DE SOLICITAÇÃO RECEBIDA (PARA O DONO DO LOCK) --}}
    @if($templateId && !$isLockedByOther && $hasTransferRequest)
        <div class="fixed bottom-6 right-6 z-[300] max-w-sm w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-4">
            <h4 class="text-xs font-bold text-gray-900 dark:text-white">Pedido de Edição</h4>
            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                O utilizador <strong>{{ $requesterName }}</strong> está a solicitar o controlo para editar este template.
            </p>
            <div class="mt-3 flex gap-2 justify-end">
                <button type="button" wire:click="rejectTransfer" class="btn btn--secondary btn--small">
                    Recusar
                </button>
                <button type="button" wire:click="acceptTransfer" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                    Aceitar e Sair
                </button>
            </div>
        </div>
    @endif
</div>