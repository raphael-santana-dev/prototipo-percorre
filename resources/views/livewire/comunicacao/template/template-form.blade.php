<div class="font-sans flex flex-col transition-all duration-300 bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden w-full relative"
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
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            border-color: #e5e7eb;
            background-color: #f9fafb;
            padding: 8px 12px;
            font-family: inherit;
        }
        .template-quill .ql-container.ql-snow {
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
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
            box-shadow: inset 0 0 0 2px rgba(147, 51, 234, 0.2);
            outline: none;
        }
    </style>

    {{-- CORTINA DE BLOQUEIO: OUTRO UTILIZADOR A EDITAR --}}
    @if($templateId && $isLockedByOther)
        <div class="absolute inset-0 z-[200] bg-white/60 dark:bg-gray-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
                <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="ph-fill ph-lock-key text-4xl"></i>
                </div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2">Acesso Restrito</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                    O utilizador <strong>{{ $lockOwnerName }}</strong> está a editar este template neste momento.
                </p>

                @if($transferRejected)
                    <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-bold text-red-700">
                        <i class="ph-fill ph-x-circle mr-1 text-base align-middle"></i>
                        O utilizador recusou a transferência e pediu para aguardar.
                    </div>
                @endif

                <div class="flex flex-col gap-3">
                    @php
                        $transferData = \Illuminate\Support\Facades\Cache::get($this->getTransferKey());
                        $isPending = $transferData && ($transferData['status'] ?? '') === 'pending' && ($transferData['requester_id'] ?? null) === auth()->id();
                    @endphp

                    @if($isPending)
                        <button type="button" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-gray-100 text-gray-500 cursor-not-allowed w-full flex justify-center items-center gap-2 border border-gray-200" disabled>
                            <i class="ph-bold ph-spinner animate-spin"></i> Aguardando resposta...
                        </button>
                        <button type="button" wire:click="cancelTransferRequest" class="text-xs font-bold text-gray-500 hover:text-red-500 underline mt-2 transition">
                            Cancelar Pedido
                        </button>
                    @else
                        <button type="button" wire:click="requestControl" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-amber-500 hover:bg-amber-600 text-white border-none shadow-md w-full flex justify-center items-center gap-2 transition">
                            <i class="ph-bold ph-hand-raising"></i> Solicitar Controle
                        </button>
                        <a href="{{ route('templates.index') }}" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 w-full mt-2 block transition">
                            Voltar para Templates
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <fieldset @if($templateId && $isLockedByOther) disabled class="pointer-events-none opacity-60 blur-[2px] transition-all duration-300" @endif class="flex flex-col flex-1 h-full w-full overflow-hidden">
        
        <!-- BARRA SUPERIOR FIXA DO CONSTRUTOR DE TEMPLATES -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-5 py-3 flex flex-wrap items-center justify-between gap-4 z-[60] shrink-0">
            <div class="flex items-center gap-4">
                <a href="{{ route('templates.index') }}" onclick="document.body.classList.remove('modo-expandido', 'navbar-oculta')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Voltar para a listagem">
                    <i class="ph-bold ph-arrow-left"></i>
                </a>
                <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-base font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $templateId ? 'Editar Template de Comunicação' : 'Construtor de Template' }}
                        </h1>
                        @if($canal === 'email')
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded border border-blue-200 uppercase tracking-wider flex items-center gap-1">
                                <i class="ph-fill ph-envelope-simple"></i> E-mail
                            </span>
                        @elseif($canal === 'whatsapp')
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded border border-emerald-200 uppercase tracking-wider flex items-center gap-1">
                                <i class="ph-fill ph-whatsapp-logo"></i> WhatsApp
                            </span>
                        @else
                            <span class="px-2 py-0.5 bg-purpura-100 text-purpura-700 text-[10px] font-bold rounded border border-purpura-200 uppercase tracking-wider flex items-center gap-1">
                                <i class="ph-fill ph-Chats-circle"></i> Multicanal (E-mail + WhatsApp)
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                        Template: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $nome ?: 'Novo Template Sem Título' }}</span>
                    </p>
                </div>
            </div>

            <!-- SELETOR CENTRAL DE CANAL (E-MAIL | WHATSAPP | AMBOS) -->
            <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="setCanal('email')" class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition flex items-center gap-1.5 {{ $canal === 'email' ? 'bg-white dark:bg-gray-900 text-blue-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    <i class="ph-fill ph-envelope-simple text-sm"></i> Apenas E-mail
                </button>
                <button type="button" wire:click="setCanal('whatsapp')" class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition flex items-center gap-1.5 {{ $canal === 'whatsapp' ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    <i class="ph-fill ph-whatsapp-logo text-sm"></i> Apenas WhatsApp
                </button>
                <button type="button" wire:click="setCanal('ambos')" class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition flex items-center gap-1.5 {{ $canal === 'ambos' ? 'bg-white dark:bg-gray-900 text-purpura-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    <i class="ph-fill ph-intersect text-sm"></i> Ambos (E-mail + WhatsApp)
                </button>
            </div>

            <!-- CONTROLES DE TELA CHEIA E SALVAMENTO -->
            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button" @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-2.5 py-1.5 text-xs font-bold rounded-md transition-colors" :class="modoExpandido ? 'bg-white dark:bg-gray-900 shadow-sm text-purpura-600' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'" title="Expandir Tela">
                        <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                    </button>
                    <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-2.5 py-1.5 text-xs font-bold rounded-md transition-colors text-gray-600 dark:text-gray-300 hover:text-gray-900" :class="navbarOculta ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' : ''" title="Ocultar Menus">
                        <i class="ph-bold" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>

                @if(feature('template.editar') || feature('template.criar'))
                    <button type="button" wire:click="salvar" wire:loading.attr="disabled" class="px-4 py-2 bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-sm"></i>
                        <span>{{ $templateId ? 'Atualizar Template' : 'Salvar Template' }}</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- ÁREA DE TRABALHO EM 3 COLUNAS -->
        <div class="flex flex-1 w-full overflow-hidden relative bg-gray-100 dark:bg-gray-950">
            
            <!-- COLUNA 1 (ESQUERDA): DICIONÁRIO DE VARIÁVEIS DINÂMICAS -->
            <aside class="w-72 xl:w-80 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between shrink-0">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                        <i class="ph-fill ph-brackets-curly text-purpura-600 text-base"></i> Variáveis Dinâmicas
                    </span>
                    <span class="text-[10px] font-semibold text-gray-400">Clique p/ inserir</span>
                </div>

                <div class="p-3 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shrink-0">
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" wire:model.live.debounce.250ms="buscaVariavel" placeholder="Filtrar variável (ex: nome, curso)..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-purpura-500 focus:border-purpura-500">
                    </div>
                </div>

                <div class="p-3 overflow-y-auto flex-1 custom-scrollbar space-y-3">
                    @forelse($dicionario as $categoria => $variaveis)
                        <div x-data="{ open: true }" class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden shadow-2xs">
                            <button type="button" @click="open = !open" class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 dark:bg-gray-800/60 hover:bg-gray-100 transition border-b border-gray-100 dark:border-gray-800">
                                <span class="text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ $categoria }}</span>
                                <i class="ph-bold ph-caret-down text-xs text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            
                            <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($variaveis as $chave => $descricao)
                                    <div class="p-2.5 hover:bg-purpura-50/50 dark:hover:bg-gray-800/50 transition group flex flex-col gap-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <code class="text-[11px] font-bold text-purpura-700 dark:text-purpura-300 bg-purpura-50 dark:bg-purpura-900/40 px-1.5 py-0.5 rounded border border-purpura-200/60 dark:border-purpura-800 cursor-pointer"
                                                  @click="window.dispatchEvent(new CustomEvent('inserir-variavel-quill', { detail: '{{ $chave }}' })); navigator.clipboard.writeText('{{ $chave }}'); $dispatch('sucesso', {msg: 'Variável inserida e copiada!'})"
                                                  title="Clique para inserir no texto">
                                                {{ $chave }}
                                            </code>

                                            <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100">
                                                <button type="button"
                                                        @click="window.dispatchEvent(new CustomEvent('inserir-variavel-quill', { detail: '{{ $chave }}' }))"
                                                        class="px-1.5 py-0.5 text-[10px] font-bold bg-purpura-600 text-white rounded hover:bg-purpura-700 transition"
                                                        title="Inserir no cursor">
                                                    + Inserir
                                                </button>
                                                <button type="button"
                                                        @click="navigator.clipboard.writeText('{{ $chave }}'); $dispatch('sucesso', {msg: 'Variável copiada!'})"
                                                        class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-white rounded"
                                                        title="Apenas copiar">
                                                    <i class="ph-bold ph-copy text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">{{ $descricao }}</p>
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

            <!-- COLUNA 2 (CENTRO): EDITOR DE MENSAGEM E CONSTRUTOR DE BOTÕES -->
            <div class="flex-1 flex flex-col h-full overflow-y-auto custom-scrollbar p-5 md:p-7 space-y-6">
                
                <!-- CARD 1: IDENTIFICAÇÃO E ASSUNTO -->
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-xs border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="grid grid-cols-1 {{ in_array($canal, ['email', 'ambos']) ? 'md:grid-cols-2' : '' }} gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Nome Interno do Template <span class="text-red-500">*</span>
                            </label>
                            <input wire:model.live.debounce.300ms="nome" type="text" placeholder="Ex: 4. Matrícula Finalizada (Com Detalhes)" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm font-bold focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                            @error('nome') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if(in_array($canal, ['email', 'ambos']))
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Assunto do E-mail (Destinatário) <span class="text-red-500">*</span>
                                </label>
                                <input wire:model.live.debounce.300ms="assunto" type="text" placeholder="Ex: Matrícula Concluída! Bem-vindo(a) ao Instituto Percorre 🎉" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                                @error('assunto') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <!-- CARD 2: CORPO DA MENSAGEM (QUILL) -->
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-xs border border-gray-200 dark:border-gray-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Conteúdo da Mensagem <span class="text-red-500">*</span>
                            </label>
                            <p class="text-[11px] text-gray-500">
                                Escreva e formate a mensagem. No WhatsApp, negritos, listas e parágrafos são convertidos automaticamente.
                            </p>
                        </div>
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
                    @error('corpo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- CARD 3: CONSTRUTOR DE BOTÕES / OPÇÕES INTERATIVAS (WHATSAPP & E-MAIL) -->
                @if(in_array($canal, ['whatsapp', 'ambos']))
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-xs border border-gray-200 dark:border-gray-800 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                                    <i class="ph-fill ph-hand-tap text-emerald-600 text-base"></i>
                                    Botões de Ação / Opções Interativas
                                </h3>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    No <strong>WhatsApp</strong> serão exibidos como opções clicáveis; no <strong>E-mail</strong>, você escolhe se devem aparecer como botões de link.
                                </p>
                            </div>

                            <button type="button" wire:click="adicionarBotao" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold flex items-center gap-1.5 transition">
                                <i class="ph-bold ph-plus"></i> Adicionar Botão / Opção
                            </button>
                        </div>

                        <div class="space-y-3">
                            @forelse($botoes as $index => $btn)
                                <div wire:key="tpl-btn-{{ $btn['id'] ?? $index }}" class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/50 space-y-3 relative group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-purpura-600 text-white text-[11px] font-extrabold flex items-center justify-center">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                                Opção / Botão #{{ $index + 1 }}
                                            </span>
                                        </div>

                                        <button type="button" wire:click="removerBotao({{ $index }})" class="text-gray-400 hover:text-red-500 p-1 rounded transition" title="Remover Botão">
                                            <i class="ph-bold ph-trash text-base"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                        <div class="{{ $canal === 'ambos' ? 'md:col-span-5' : 'md:col-span-8' }}">
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">
                                                Texto do Botão / Opção (WhatsApp & E-mail) <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.texto" placeholder="Ex: Assinar Contrato Digital" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            @error("botoes.{$index}.texto") <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                        </div>

                                        @if($canal === 'whatsapp')
                                            <div class="md:col-span-4">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">
                                                    URL ou Ação Opcional
                                                </label>
                                                <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.url" placeholder="Ex: @{{link_assinatura}} ou resposta rápida" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            </div>
                                        @endif

                                        @if($canal === 'ambos')
                                            <div class="md:col-span-7 space-y-2.5 bg-white dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="botoes.{{ $index }}.exibir_email" class="rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                                        Exibir este botão no corpo do E-mail?
                                                    </span>
                                                </label>

                                                @if(!empty($botoes[$index]['exibir_email']))
                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                                        <div class="sm:col-span-2">
                                                            <label class="block text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase mb-1">
                                                                URL de Destino no E-mail <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="text" wire:model.live.debounce.250ms="botoes.{{ $index }}.url" placeholder="Ex: @{{link_assinatura}} ou https://..." class="w-full text-xs font-mono rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                            @error("botoes.{$index}.url") <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">
                                                                Cor no E-mail
                                                            </label>
                                                            <select wire:model.live="botoes.{{ $index }}.cor" class="w-full text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                                                <option value="purpura">Roxo Percorre</option>
                                                                <option value="emerald">Verde</option>
                                                                <option value="blue">Azul</option>
                                                                <option value="dark">Escuro</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                @else
                                                    <p class="text-[10px] text-gray-400 italic">
                                                        Este botão aparecerá apenas como opção interativa no WhatsApp e ficará oculto no E-mail.
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                                    <i class="ph-duotone ph-hand-tap text-3xl text-gray-400 mb-1"></i>
                                    <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Nenhum botão ou opção adicionado.</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Clique em "+ Adicionar Botão / Opção" acima para incluir interações na mensagem.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

            </div>

            <!-- COLUNA 3 (DIREITA): SIMULADOR DE PREVIEW EM TEMPO REAL (E-MAIL & WHATSAPP) -->
            <aside class="w-96 xl:w-[430px] bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
                
                <!-- CABEÇALHO DO PREVIEW COM SELETOR DE ABA -->
                <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between shrink-0">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                        <i class="ph-fill ph-eye text-purpura-600 text-base"></i> Pré-visualização
                    </span>

                    <div class="flex items-center gap-1 bg-gray-200/70 dark:bg-gray-800 p-0.5 rounded-lg">
                        @if(in_array($canal, ['email', 'ambos']))
                            <button type="button" wire:click="$set('abaPreview', 'email')" class="px-2.5 py-1 rounded-md text-[11px] font-bold transition flex items-center gap-1 {{ $abaPreview === 'email' ? 'bg-white dark:bg-gray-900 text-blue-600 shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                                <i class="ph-fill ph-envelope-simple"></i> E-mail
                            </button>
                        @endif
                        @if(in_array($canal, ['whatsapp', 'ambos']))
                            <button type="button" wire:click="$set('abaPreview', 'whatsapp')" class="px-2.5 py-1 rounded-md text-[11px] font-bold transition flex items-center gap-1 {{ $abaPreview === 'whatsapp' ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-2xs' : 'text-gray-600 dark:text-gray-400' }}">
                                <i class="ph-fill ph-whatsapp-logo"></i> WhatsApp
                            </button>
                        @endif
                    </div>
                </div>

                @php
                    // Converte HTML do Quill para formato visual de WhatsApp no Preview
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

                        // Formata negrito/itálico/riscado de volta para tags seguras no balão do WhatsApp
                        $textoSeguro = e($texto);
                        $textoSeguro = preg_replace('/\*(.*?)\*/s', '<strong>$1</strong>', $textoSeguro);
                        $textoSeguro = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $textoSeguro);
                        $textoSeguro = preg_replace('/\~(.*?)\~/s', '<del>$1</del>', $textoSeguro);
                        // Destaca variáveis {{...}}
                        $textoSeguro = preg_replace('/(\{\{.*?\}\})/', '<span class="px-1 py-0.2 bg-emerald-100 text-emerald-800 rounded font-mono text-[11px] font-bold">$1</span>', $textoSeguro);
                        return nl2br($textoSeguro);
                    };
                @endphp

                <!-- CORPO DO SIMULADOR -->
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-100/70 dark:bg-gray-950">
                    
                    @if($abaPreview === 'email' && in_array($canal, ['email', 'ambos']))
                        <!-- SIMULADOR DE CLIENTE DE E-MAIL -->
                        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 space-y-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                                    <span class="ml-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Simulador de E-mail</span>
                                </div>
                                <div class="pt-1">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Assunto:</span>
                                    <p class="text-xs font-extrabold text-gray-900 dark:text-white">
                                        {{ $assunto ?: '(Sem assunto definido)' }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-5 prose prose-sm max-w-none text-gray-800 dark:text-gray-200 text-xs leading-relaxed">
                                @if(!empty(trim(strip_tags($corpo))))
                                    {!! $corpo !!}
                                @else
                                    <p class="text-gray-400 italic">O conteúdo do e-mail aparecerá aqui em tempo real...</p>
                                @endif

                                {{-- Renderização dos Botões que estão marcados para aparecer no E-mail --}}
                                @php
                                    $botoesEmail = collect($botoes)->filter(fn($b) => !empty(trim($b['texto'] ?? '')) && filter_var($b['exibir_email'] ?? false, FILTER_VALIDATE_BOOLEAN));
                                @endphp

                                @if(in_array($canal, ['ambos']) && $botoesEmail->isNotEmpty())
                                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2.5 not-prose">
                                        @foreach($botoesEmail as $bEmail)
                                            @php
                                                $corBtnClass = match($bEmail['cor'] ?? 'purpura') {
                                                    'emerald' => 'bg-emerald-600 text-white',
                                                    'blue'    => 'bg-blue-600 text-white',
                                                    'dark'    => 'bg-gray-900 text-white',
                                                    default   => 'bg-purpura-600 text-white',
                                                };
                                            @endphp
                                            <div class="flex flex-col items-start">
                                                <a href="{{ $bEmail['url'] ?: '#' }}" target="_blank" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold shadow-xs {{ $corBtnClass }}">
                                                    <span>{{ $bEmail['texto'] }}</span>
                                                    <i class="ph-bold ph-arrow-square-out"></i>
                                                </a>
                                                <span class="text-[10px] text-gray-400 font-mono mt-0.5">
                                                    Destino: {{ $bEmail['url'] ?: '(Defina a URL de destino)' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        <!-- SIMULADOR DE WHATSAPP -->
                        <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-300 dark:border-gray-700 bg-[#efeae2] dark:bg-gray-900 flex flex-col min-h-[480px]">
                            <!-- Barra de Topo do WhatsApp -->
                            <div class="bg-[#075e54] text-white px-4 py-3 flex items-center gap-3 shrink-0">
                                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center font-extrabold text-sm">
                                    IP
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold truncate">Instituto Percorre</p>
                                    <p class="text-[10px] text-emerald-100">Conta comercial oficial</p>
                                </div>
                                <i class="ph-fill ph-whatsapp-logo text-xl text-emerald-300"></i>
                            </div>

                            <!-- Área da Conversa -->
                            <div class="p-4 flex-1 flex flex-col justify-start space-y-2">
                                <!-- Balão da Mensagem -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl rounded-tl-none p-3.5 shadow-xs max-w-[92%] text-xs text-gray-800 dark:text-gray-100 leading-relaxed relative">
                                    @if(!empty(trim(strip_tags($corpo))))
                                        <div>{!! $converterParaWhatsAppPreview($corpo) !!}</div>
                                    @else
                                        <span class="text-gray-400 italic">Digite a mensagem para visualizar no WhatsApp...</span>
                                    @endif
                                    <div class="text-[9px] text-gray-400 text-right mt-1.5 flex items-center justify-end gap-1">
                                        <span>{{ now()->format('H:i') }}</span>
                                        <i class="ph-bold ph-checks text-blue-500 text-xs"></i>
                                    </div>
                                </div>

                                <!-- Botões Interativos do WhatsApp (Opções) -->
                                @php
                                    $botoesWpp = collect($botoes)->filter(fn($b) => !empty(trim($b['texto'] ?? '')));
                                @endphp

                                @if($botoesWpp->isNotEmpty())
                                    <div class="space-y-1.5 max-w-[92%]">
                                        @foreach($botoesWpp as $bWpp)
                                            <div class="bg-white dark:bg-gray-800 hover:bg-gray-50 rounded-xl py-2.5 px-3 shadow-xs border border-gray-100 dark:border-gray-700 text-center text-xs font-bold text-[#00a884] flex items-center justify-center gap-1.5 cursor-default">
                                                @if(!empty($bWpp['url']) && (str_starts_with($bWpp['url'], 'http') || str_starts_with($bWpp['url'], '{{')))
                                                    <i class="ph-bold ph-arrow-square-out text-sm"></i>
                                                @else
                                                    <i class="ph-bold ph-arrow-bend-up-left text-sm"></i>
                                                @endif
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
        <div class="fixed bottom-6 right-6 z-[300] max-w-sm w-full bg-white dark:bg-gray-800 border-l-4 border-purpura-500 rounded-lg shadow-2xl p-5 animate-fade-in-up">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-purpura-100 text-purpura-600 rounded-full shrink-0">
                    <i class="ph-fill ph-hand-waving text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">Pedido de Edição</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                        O utilizador <strong>{{ $requesterName }}</strong> abriu este template e está a solicitar o controlo para efetuar edições.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" wire:click="rejectTransfer" class="px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition">
                    Recusar e Continuar
                </button>
                <button type="button" wire:click="acceptTransfer" class="px-3 py-1.5 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white border-none text-xs font-bold transition">
                    Aceitar e Sair
                </button>
            </div>
        </div>
    @endif
</div>