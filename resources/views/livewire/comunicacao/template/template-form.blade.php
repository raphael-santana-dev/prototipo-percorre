<div class="p-6 max-w-[1400px] mx-auto font-sans relative">
    
    <!-- Quill.js CDN (CSS e JS) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <!-- Ajustes visuais para o Quill encaixar no nosso layout Tailwind -->
    <style>
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            border-color: #e5e7eb;
            background-color: #f9fafb;
            font-family: inherit;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            border-color: #e5e7eb;
            min-height: 400px;
            font-family: inherit;
            font-size: 0.875rem;
        }
        .ql-editor {
            min-height: 400px;
        }
        .ql-editor:focus {
            box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.25); /* Foco roxo (purpura-500) */
            border-color: #9333ea;
            outline: none;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
    </style>

    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('templates.index') }}" class="text-purpura-600 hover:text-purpura-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Listagem
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">{{ $templateId ? 'Editar' : 'Criar Novo' }} Template de E-mail</h2>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- COLUNA DA ESQUERDA: O FORMULÁRIO E O QUILL -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <form wire:submit.prevent="salvar" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1">Nome do Template (Interno) <span class="text-red-500">*</span></label>
                            <input wire:model="nome" type="text" placeholder="Ex: Boas Vindas ao Aluno" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm">
                            @error('nome') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1">Assunto do E-mail (Destinatário) <span class="text-red-500">*</span></label>
                            <input wire:model="assunto" type="text" placeholder="Ex: Olá @{{estudante.nome}}, sua vaga está garantida!" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm">
                            @error('assunto') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- CORPO DO E-MAIL (QUILL.JS) -->
                    <div class="w-full">
                        <label class="block text-sm font-bold text-gray-800 mb-2">Corpo da Mensagem <span class="text-red-500">*</span></label>
                        
                        <div wire:ignore>
                            <div x-data="{
                                quill: null,
                                init() {
                                    // Inicializa o Quill
                                    this.quill = new Quill(this.$refs.editor, {
                                        theme: 'snow',
                                        placeholder: 'Escreva a mensagem do e-mail aqui...',
                                        modules: {
                                            toolbar: [
                                                [{ 'header': [1, 2, 3, false] }],
                                                ['bold', 'italic', 'underline', 'strike'],
                                                [{ 'color': [] }, { 'background': [] }],
                                                [{ 'align': [] }],
                                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                                ['link', 'image', 'video'],
                                                ['clean']
                                            ]
                                        }
                                    });

                                    // Seta o conteúdo que já veio do banco (se for edição)
                                    this.quill.root.innerHTML = @this.get('corpo') || '';

                                    // Ouve as mudanças no editor e passa para o Livewire
                                    this.quill.on('text-change', () => {
                                        @this.set('corpo', this.quill.root.innerHTML);
                                    });
                                }
                            }">
                                <div x-ref="editor" class="bg-white"></div>
                            </div>
                        </div>
                        @error('corpo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('templates.index') }}" class="px-5 py-2.5 text-sm font-bold border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancelar</a>
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                            <i class="ph-bold ph-floppy-disk text-lg"></i> {{ $templateId ? 'Atualizar Template' : 'Salvar Template' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- COLUNA DA DIREITA: DICIONÁRIO DE VARIÁVEIS -->
        <div class="lg:col-span-4 bg-gray-50 rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden">
            <div class="p-5 border-b border-gray-200 bg-white flex items-center gap-3">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <i class="ph-fill ph-brackets-curly text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Variáveis Disponíveis</h3>
                    <p class="text-[10px] text-gray-500">Clique para copiar e colar no texto.</p>
                </div>
            </div>

            <div class="p-4 max-h-[600px] overflow-y-auto custom-scrollbar space-y-4">
                @foreach($dicionario as $categoria => $variaveis)
                    <div x-data="{ open: true }" class="border border-gray-200 rounded-lg bg-white overflow-hidden shadow-sm">
                        <button type="button" @click="open = !open" class="w-full flex justify-between items-center p-3 bg-gray-50 hover:bg-gray-100 transition border-b border-gray-200">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $categoria }}</span>
                            <i class="ph ph-caret-down text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="open" x-collapse class="divide-y divide-gray-100 bg-white">
                            @foreach($variaveis as $chave => $descricao)
                                <div class="p-3 hover:bg-blue-50/50 transition group flex flex-col gap-1 cursor-pointer" 
                                     @click="navigator.clipboard.writeText('{{ $chave }}'); $dispatch('sucesso', {msg: 'Variável copiada!'})">
                                    <div class="flex items-center justify-between">
                                        <code class="text-xs font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">{{ $chave }}</code>
                                        <i class="ph ph-copy text-gray-400 group-hover:text-blue-500 opacity-0 group-hover:opacity-100 transition"></i>
                                    </div>
                                    <p class="text-[10px] text-gray-500">{{ $descricao }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="p-4 border-t border-gray-200 bg-white text-[10px] text-gray-500 text-center leading-relaxed">
                As variáveis são substituídas automaticamente no momento do disparo. Use-as tanto no assunto quanto no corpo do e-mail.
            </div>
        </div>

    </div>

    {{-- TOAST PARA A COPIA DE VARIÁVEL --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 2000);"
        x-show="show" x-transition
        class="fixed bottom-8 right-8 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-xl z-[200] flex items-center gap-2 font-bold text-sm" x-cloak>
        <i class="ph-bold ph-check text-green-400 text-lg"></i>
        <span x-text="msg"></span>
    </div>
</div>