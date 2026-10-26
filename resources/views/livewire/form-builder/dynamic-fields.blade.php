<div class="font-sans flex flex-col transition-all duration-300 bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden w-full relative"
     wire:poll.5s="pingLock"
     x-data="{ modoExpandido: true, navbarOculta: true }"
     x-init="
        $watch('modoExpandido', val => document.body.classList.toggle('modo-expandido', val));
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
        
        document.body.classList.toggle('modo-expandido', modoExpandido);
        document.body.classList.toggle('navbar-oculta', navbarOculta);
     "
     :class="modoExpandido ? 'h-screen border-none rounded-none' : 'min-h-[680px] h-[calc(100vh-140px)]'">

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

        /* Estilos compactos para o Mini-Quill do Construtor */
        .builder-quill .ql-toolbar.ql-snow {
            padding: 4px 6px;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            border-color: #d1d5db;
            background-color: #f9fafb;
        }
        .builder-quill .ql-container.ql-snow {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            border-color: #d1d5db;
            background-color: #ffffff;
            font-family: inherit;
            font-size: 0.78rem;
        }
        .builder-quill .ql-editor {
            min-height: 58px;
            max-height: 150px;
            padding: 8px 10px;
            overflow-y: auto;
        }
        .builder-quill .ql-snow .ql-picker.ql-size { width: 74px; }

        /* Classes de tamanho do Quill renderizadas no preview e formulário */
        .ql-size-small { font-size: 0.75em; }
        .ql-size-large { font-size: 1.25em; }
        .ql-size-huge { font-size: 1.6em; }
    </style>

    {{-- CORTINA DE BLOQUEIO: OUTRO UTILIZADOR A EDITAR --}}
    @if($isLockedByOther)
        <div class="absolute inset-0 z-[200] bg-white/60 dark:bg-gray-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
                
                <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="ph-fill ph-lock-key text-4xl"></i>
                </div>
                
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2">Acesso Restrito</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                    O utilizador <strong>{{ $lockOwnerName }}</strong> está atualmente a editar a estrutura deste formulário. Para evitar conflitos e perda de dados, a edição simultânea está bloqueada.
                </p>

                @if($transferRejected)
                    <div class="mb-6 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs font-bold text-red-700 dark:text-red-400">
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
                        <a href="{{ route('dashboard') }}" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 w-full mt-2 block transition">
                            Voltar ao Dashboard
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <fieldset @if($isLockedByOther) disabled class="pointer-events-none opacity-60 blur-[2px] transition-all duration-300" @endif class="flex flex-col flex-1 h-full w-full overflow-hidden">
        <!-- BARRA SUPERIOR FIXA DO CONSTRUTOR -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-5 py-3 flex items-center justify-between z-[60] shrink-0">
            <div class="flex items-center gap-4">
                <a href="{{ route('formbuilder.hub') }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Voltar para a Central">
                    <i class="ph-bold ph-arrow-left"></i>
                </a>
                <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Construtor de Formulário</h1>
                        @if($badgeContexto === 'Avaliação de Aprendizagem')
                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-[10px] font-bold rounded border border-orange-200 uppercase tracking-wider flex items-center gap-1"><i class="ph-fill ph-tree-structure"></i> {{ $badgeContexto }}</span>
                        @elseif($badgeContexto === 'Pré-Inscrição (Lead)')
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded border border-emerald-200 uppercase tracking-wider flex items-center gap-1"><i class="ph-fill ph-funnel"></i> {{ $badgeContexto }}</span>
                        @elseif($badgeContexto === 'Inscrição Oficial')
                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded border border-indigo-200 uppercase tracking-wider flex items-center gap-1"><i class="ph-fill ph-calendar-check"></i> {{ $badgeContexto }}</span>
                        @else
                            <span class="px-2 py-0.5 bg-purpura-100 text-purpura-700 text-[10px] font-bold rounded border border-purpura-200 uppercase tracking-wider flex items-center gap-1"><i class="ph-fill ph-list-dashes"></i> {{ $badgeContexto }}</span>
                        @endif
                    </div>
                    <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                        Contexto: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $contextoNome }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-lg font-bold border border-gray-200 dark:border-gray-700">
                    <i class="ph-bold ph-squares-four text-purpura-600"></i> {{ $camposCadastrados->count() }} Blocos
                </span>

                <!-- CONTROLES DE ECRÃ INTEIRO E OCULTAR MENUS -->
                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button" @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors" :class="modoExpandido ? 'bg-white dark:bg-gray-900 shadow-sm text-purpura-600' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'" title="Expandir Ecrã">
                        <i class="ph-bold" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                        <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                    </button>
                    <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors text-gray-600 dark:text-gray-300 hover:text-gray-900" :class="navbarOculta ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' : ''" title="Ocultar Menus do Sistema">
                        <i class="ph-bold" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                        <span class="hidden xl:inline ml-1" x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
                    </button>
                    <button type="button" wire:click="abrirModalFases" class="btn btn--secondary btn--medium flex items-center gap-2">
                        <i class="ph-bold ph-calendar-plus text-purpura-600"></i> Agendamento de Fases
                    </button>
                </div>

                @if(auth()->user()->hasAnyRole(['dev', 'admin']) || auth()->user()->can('formulario.testar') || auth()->user()->can('inscricao.criar'))
                    @php
                        $urlTeste = $contextoTipo === 'ciclo' 
                            ? url('/inscricao?modo=teste&ciclo_id=' . $contextoId) 
                            : url('/f/' . $slug . '?modo=teste');
                    @endphp
                    <a href="{{ $urlTeste }}" target="_blank" class="px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold rounded-lg shadow-2xs transition flex items-center gap-1.5">
                        <i class="ph-fill ph-flask text-sm text-amber-600"></i>
                        <span class="hidden md:inline">Testar Formulário</span>
                    </a>
                @endif

                <button type="button" @click="$wire.set('drawerFormSettingsAberto', true)" class="px-3.5 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-2">
                    <i class="ph-bold ph-sliders-horizontal text-sm text-purpura-400"></i>
                    <span>Configurações do Formulário</span>
                </button>
            </div>
        </div>

        <!-- ÁREA DE TRABALHO EM 3 COLUNAS -->
        <div class="flex flex-1 w-full overflow-hidden relative bg-gray-100 dark:bg-gray-950">
            
            <!-- COLUNA 1 (ESQUERDA): PALETA DE TIPOS DE INPUTS -->
            <aside class="w-72 xl:w-80 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between shrink-0">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                        <i class="ph-fill ph-squares-four text-purpura-600 text-base"></i> Tipos de Inputs
                    </span>
                    @if($campoId)
                        <button type="button" wire:click="iniciarNovoCampo" class="text-[10px] font-bold text-purpura-600 hover:underline flex items-center gap-1">
                            <i class="ph-bold ph-plus"></i> Novo
                        </button>
                    @endif
                </div>

                <div class="p-4 overflow-y-auto flex-1 custom-scrollbar space-y-5">
                    
                    <!-- GRUPO 1: TEXTO E DOCUMENTOS -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Texto e Entradas</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" wire:click="setTipo('text', 'text')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'text' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-text-t text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'text' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Texto Curto</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'email')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'email' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-envelope-simple text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'email' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">E-mail</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'number')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'number' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-hash text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'number' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Número</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'money')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'money' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-currency-circle-dollar text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'money' ? 'text-purpura-600' : 'text-emerald-600' }}"></i>
                                <span class="text-[11px] font-bold truncate">Moeda (R$)</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'tel')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'tel' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-device-mobile text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'tel' ? 'text-purpura-600' : 'text-blue-500' }}"></i>
                                <span class="text-[11px] font-bold truncate">Telefone</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'cpf')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cpf' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-identification-card text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cpf' ? 'text-purpura-600' : 'text-indigo-500' }}"></i>
                                <span class="text-[11px] font-bold truncate">CPF</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'cnpj')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cnpj' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-buildings text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cnpj' ? 'text-purpura-600' : 'text-amber-600' }}"></i>
                                <span class="text-[11px] font-bold truncate">CNPJ</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'cep')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cep' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-map-pin-line text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'cep' ? 'text-purpura-600' : 'text-rose-500' }}"></i>
                                <span class="text-[11px] font-bold truncate">CEP (ViaCEP)</span>
                            </button>
                        </div>
                    </div>

                    <!-- GRUPO 2: DATAS E HORAS -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Datas e Horas</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" wire:click="setTipo('text', 'date')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'date' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-calendar-blank text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'date' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Data</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'time')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'time' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-clock text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'time' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Hora</span>
                            </button>

                            <button type="button" wire:click="setTipo('text', 'datetime-local')" class="col-span-2 flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'datetime-local' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-calendar-check text-base {{ $painelCampoAtivo && $tipo == 'text' && $subtipo == 'datetime-local' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Data e Hora Completa</span>
                            </button>
                        </div>
                    </div>

                    <!-- GRUPO 3: UPLOAD E ASSINATURA -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Anexos e Assinatura</p>
                        <div class="grid grid-cols-1 gap-1.5">
                            <button type="button" wire:click="setTipo('signature', 'usuario')" class="flex items-center gap-3 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'signature' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-signature text-xl {{ $painelCampoAtivo && $tipo == 'signature' ? 'text-purpura-600' : 'text-indigo-500' }}"></i>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold">Assinatura à Mão</span>
                                    <span class="text-[10px] text-gray-500 truncate">Desenho (Utilizador/Responsável)</span>
                                </div>
                            </button>

                            <button type="button" wire:click="setTipo('file', 'file')" class="flex items-center gap-3 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'file' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-upload-simple text-xl {{ $painelCampoAtivo && $tipo == 'file' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold">Upload / Anexos</span>
                                    <span class="text-[10px] text-gray-500 truncate">Imagens, vídeos e documentos</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- GRUPO 4: SELEÇÃO E ESCOLHAS -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Seleção e Escolhas</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" wire:click="setTipo('select')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'select' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-caret-circle-down text-base {{ $painelCampoAtivo && $tipo == 'select' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Dropdown</span>
                            </button>
                            <button type="button" wire:click="setTipo('radio')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'radio' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-radio-button text-base {{ $painelCampoAtivo && $tipo == 'radio' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Múltipla Esc.</span>
                            </button>
                            <button type="button" wire:click="setTipo('check')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'check' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-check-square-offset text-base {{ $painelCampoAtivo && $tipo == 'check' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Checkboxes</span>
                            </button>
                            <button type="button" wire:click="setTipo('scale')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'scale' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-linear-scale text-base {{ $painelCampoAtivo && $tipo == 'scale' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Escala Num.</span>
                            </button>
                            <button type="button" wire:click="setTipo('matriz')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'matriz' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-table text-base {{ $painelCampoAtivo && $tipo == 'matriz' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Matriz</span>
                            </button>
                            <button type="button" wire:click="setTipo('rating')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'rating' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-star text-base {{ $painelCampoAtivo && $tipo == 'rating' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Estrelas</span>
                            </button>
                        </div>
                    </div>

                    <!-- GRUPO 5: LINKS, DOCUMENTOS E LAYOUT -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Links, Documentos e Layout</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" wire:click="setTipo('link', 'link')" class="col-span-2 flex items-center gap-3 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'link' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-link-simple text-xl {{ $painelCampoAtivo && $tipo == 'link' ? 'text-purpura-600' : 'text-blue-600' }}"></i>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold">Link externo</span>
                                    <span class="text-[10px] text-gray-500 truncate">URL externa, página ou PDF/Word/Excel</span>
                                </div>
                            </button>

                            <button type="button" wire:click="setTipo('html', 'p')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'html' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-article text-base {{ $painelCampoAtivo && $tipo == 'html' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Texto / Aviso</span>
                            </button>
                            <button type="button" wire:click="setTipo('media', 'image')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'media' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-image text-base {{ $painelCampoAtivo && $tipo == 'media' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Mídia Visual</span>
                            </button>
                            <button type="button" wire:click="setTipo('social')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'social' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-share-network text-base {{ $painelCampoAtivo && $tipo == 'social' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Redes Sociais</span>
                            </button>
                            <button type="button" wire:click="setTipo('divider')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'divider' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-minus text-base {{ $painelCampoAtivo && $tipo == 'divider' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Divisória</span>
                            </button>
                            <button type="button" wire:click="setTipo('spacer', 'spacer')" class="col-span-2 flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'spacer' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-arrows-out-line-horizontal text-base {{ $painelCampoAtivo && $tipo == 'spacer' ? 'text-purpura-500' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold truncate">Espaço em Branco (Colunas)</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-wider">Integração do Sistema</p>
                        <div class="grid grid-cols-1 gap-1.5">
                            <button type="button" wire:click="setTipo('system', 'unidade')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'unidade' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-buildings text-base {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'unidade' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold">Unidades Ativas</span>
                            </button>
                            <button type="button" wire:click="setTipo('system', 'curso')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'curso' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-graduation-cap text-base {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'curso' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold">Cursos Ativos</span>
                            </button>
                            <button type="button" wire:click="setTipo('system', 'turno')" class="flex items-center gap-2 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'turno' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-gray-200 hover:border-purpura-300 text-gray-700' }}">
                                <i class="ph-bold ph-clock text-base {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'turno' ? 'text-purpura-600' : 'text-gray-400' }}"></i>
                                <span class="text-[11px] font-bold">Turnos Ativos</span>
                            </button>
                            <button type="button" wire:click="setTipo('system', 'combo_academico')" class="flex items-center gap-2.5 p-2.5 border rounded-lg text-left transition {{ $painelCampoAtivo && $tipo == 'system' && $subtipo == 'combo_academico' ? 'border-purpura-500 bg-purpura-50 text-purpura-700 ring-1 ring-purpura-500' : 'border-purpura-200 bg-purpura-50/30 hover:border-purpura-400 text-gray-800' }}">
                                <i class="ph-bold ph-tree-structure text-lg text-purpura-600"></i>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[11px] font-extrabold text-purpura-900">Conjunto Unidade / Curso / Turno</span>
                                    <span class="text-[9px] text-gray-500 truncate">Gera os 3 campos com vínculo configurável</span>
                                </div>
                            </button>
                        </div>
                    </div>

                </div>
            </aside>

            <!-- COLUNA 2 (CENTRO): CANVAS DE PRÉ-VISUALIZAÇÃO DO FORMULÁRIO -->
            <div class="flex-1 flex flex-col h-full overflow-hidden relative z-10">
                
                @php
                    $previewBgUrl = null;
                    if ($bg_image_upload) {
                        try { $previewBgUrl = $bg_image_upload->temporaryUrl(); } catch (\Exception $e) {}
                    } elseif (!empty($formSettings['bg_image'])) {
                        $previewBgUrl = asset($formSettings['bg_image']);
                    }
                    
                    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6';
                    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
                    $bgSize = $formSettings['bg_size'] ?? 'cover';
                    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
                    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    
                    $cardClass = $isTranslucent ? 'bg-white/80 backdrop-blur-md shadow-2xl' : 'bg-white shadow-xl';
                    $textoForm = $isTranslucent ? 'text-gray-900 drop-shadow-sm' : 'text-gray-900';

                    if (!function_exists('formatRichFormText')) {
                        function formatRichFormText($text) {
                            if (empty($text)) return '';
                            if ($text !== strip_tags($text)) {
                                return strip_tags($text, '<strong><b><em><i><u><s><del><span><br><p><ul><ol><li><a>');
                            }
                            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                            $text = preg_replace('/\*(.*?)\*/s', '<strong>$1</strong>', $text);
                            $text = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $text);
                            $text = preg_replace('/\~(.*?)\~/s', '<del>$1</del>', $text);
                            return nl2br($text);
                        }
                    }
                @endphp

                @if($previewBgUrl)
                    <div class="absolute inset-0 z-0 bg-center bg-no-repeat pointer-events-none" style="background-image: url('{{ $previewBgUrl }}'); background-size: {{ $bgSize }};"></div>
                @endif
                <div class="absolute inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

                <!-- BARRA DE PÁGINAS / ETAPAS -->
                <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-2.5 flex items-center gap-2 overflow-x-auto custom-scrollbar shrink-0 relative z-20">
                    <button type="button" wire:click="adicionarEtapa" class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 bg-purpura-50 border border-purpura-200 rounded-lg text-xs font-bold text-purpura-700 hover:bg-purpura-100 transition">
                        <i class="ph-bold ph-plus"></i> Nova Página
                    </button>
                    <div class="w-px h-5 bg-gray-200 mx-1 shrink-0"></div>
                    
                    @foreach($etapasDisponiveis as $et)
                        <div class="shrink-0 flex items-center bg-white border {{ $etapa == $et->numero ? 'border-purpura-500 ring-1 ring-purpura-500' : 'border-gray-200' }} rounded-lg text-xs shadow-sm overflow-hidden group transition-all">
                            <button type="button" wire:click="$set('etapa', {{ $et->numero }})" class="px-2.5 py-1.5 border-r border-gray-100 font-bold {{ $etapa == $et->numero ? 'bg-purpura-600 text-white' : 'bg-gray-50 text-gray-500' }}">
                                #{{ $et->numero }}
                            </button>
                            <input type="text" 
                                   wire:change="atualizarNomeEtapa({{ $et->id }}, $event.target.value)" 
                                   value="{{ $et->nome }}" 
                                   class="w-28 border-none focus:ring-0 text-xs font-bold text-gray-700 px-2.5 py-1.5 bg-transparent">
                            @if($etapasDisponiveis->count() > 1)
                                <button type="button" wire:click="excluirEtapa({{ $et->id }})" wire:confirm="Excluir esta página? As páginas seguintes serão renumeradas automaticamente." class="px-2 py-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 transition border-l border-gray-100">
                                    <i class="ph-bold ph-trash"></i>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- BARRA DE MOVIMENTAÇÃO EM LOTE (MULTI-SELECT) -->
                @if(count($camposSelecionados) > 0)
                    <div class="bg-gray-900 text-white px-5 py-2.5 border-b border-gray-800 flex flex-wrap items-center justify-between gap-3 shrink-0 relative z-30 shadow-md">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2 py-0.5 bg-purpura-600 rounded text-xs font-extrabold">{{ count($camposSelecionados) }}</span>
                            <span class="text-xs font-bold">input(s) selecionado(s)</span>
                            <button type="button" wire:click="selecionarTodosDaEtapa({{ $etapa }})" class="text-[11px] font-semibold text-purpura-300 hover:underline ml-2">
                                Selecionar/Desmarcar Página #{{ $etapa }}
                            </button>
                            <button type="button" wire:click="limparSelecaoCampos" class="text-[11px] text-gray-400 hover:text-white ml-2">
                                Limpar seleção
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="moverSelecionadosNaMesmaEtapa('topo')" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-[11px] font-bold flex items-center gap-1 border border-gray-700 transition" title="Mover selecionados para o início da página">
                                <i class="ph-bold ph-arrow-line-up text-purpura-400"></i> Início da Pág.
                            </button>
                            <button type="button" wire:click="moverSelecionadosNaMesmaEtapa('fim')" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-[11px] font-bold flex items-center gap-1 border border-gray-700 transition" title="Mover selecionados para o fim da página">
                                <i class="ph-bold ph-arrow-line-down text-purpura-400"></i> Fim da Pág.
                            </button>

                            @if($etapasDisponiveis->count() > 1)
                                <div class="flex items-center gap-1.5 pl-2 border-l border-gray-700">
                                    <select wire:model="etapaDestinoLote" class="bg-gray-800 border-gray-700 text-white text-xs rounded-lg py-1 pl-2.5 pr-7 focus:ring-purpura-500">
                                        <option value="">Mover para página...</option>
                                        @foreach($etapasDisponiveis as $etDestino)
                                            <option value="{{ $etDestino->numero }}">Página #{{ $etDestino->numero }} - {{ $etDestino->nome }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="moverCamposSelecionadosParaEtapa" class="px-3 py-1 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                        Mover Selecionados
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- CANVAS DE PREVIEW (SCROLLÁVEL) -->
                <div class="relative flex-1 w-full overflow-y-auto custom-scrollbar p-4 md:p-8 flex flex-col items-center z-10">
                    <div class="relative z-10 w-full {{ $formWidth }} {{ $cardClass }} p-6 md:p-10 rounded-xl border-t-4 border-purpura-600 transition-all duration-300">
                        
                        <div class="mb-8 border-b border-gray-200 pb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-extrabold {{ $textoForm }}">{{ $contextoNome }}</h2>
                                <p class="text-xs text-gray-500 mt-0.5">Clique num bloco abaixo para editar ou marque as caixas para mover múltiplos inputs.</p>
                            </div>
                        </div>

                        @forelse($camposPorEtapa as $numEtapa => $camposDaEtapa)
                            <div class="mb-10" wire:key="grupo-etapa-{{ $numEtapa }}">
                                <div class="flex items-center gap-3 mb-5">
                                    <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-purpura-600 rounded-full shadow-sm">{{ $numEtapa }}</span>
                                    @php 
                                        $etapaObj = $etapasDisponiveis->firstWhere('numero', $numEtapa);
                                        $nomeEtapaPreview = $etapaObj ? $etapaObj->nome : "Etapa $numEtapa";

                                        $gruposRender = [];
                                        if ($numEtapa == 1 && $contextoTipo === 'ciclo') {
                                            $camposTopo = $camposDaEtapa->filter(function($c) {
                                                $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                                return isset($cfg['exibir_no_topo']) && $cfg['exibir_no_topo'] == true;
                                            });
                                            $camposRodape = $camposDaEtapa->filter(function($c) {
                                                $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                                return !isset($cfg['exibir_no_topo']) || $cfg['exibir_no_topo'] == false;
                                            });
                                            
                                            if ($camposTopo->count() > 0) {
                                                $gruposRender[] = ['titulo' => 'Informações Preliminares', 'campos' => $camposTopo];
                                            }
                                            $gruposRender[] = ['titulo' => 'placeholder_dados_pessoais', 'campos' => collect()];
                                            if ($camposRodape->count() > 0) {
                                                $gruposRender[] = ['titulo' => 'Informações Adicionais', 'campos' => $camposRodape];
                                            }
                                        } else {
                                            $gruposRender[] = ['titulo' => null, 'campos' => $camposDaEtapa];
                                        }
                                    @endphp
                                    <h3 class="text-lg font-bold {{ $textoForm }}">{{ $nomeEtapaPreview }}</h3>
                                    <button type="button" wire:click="selecionarTodosDaEtapa({{ $numEtapa }})" class="text-[10px] font-bold text-purpura-600 hover:underline px-2 py-0.5 rounded bg-purpura-50 border border-purpura-200">
                                        Selecionar página
                                    </button>
                                    <div class="flex-1 h-px ml-2 bg-gray-200"></div>
                                </div>

                                    @foreach($gruposRender as $grupo)
                                        @if($grupo['titulo'] === 'placeholder_dados_pessoais')
                                        @php
                                            $showPersonal = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_personal'] ?? true, FILTER_VALIDATE_BOOLEAN);
                                            $showAddress  = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_address'] ?? true, FILTER_VALIDATE_BOOLEAN);
                                            $showCourse   = !$isCicloCustomizado || filter_var($formSettings['enable_fixed_course'] ?? true, FILTER_VALIDATE_BOOLEAN);
                                        @endphp
                                        @if($showPersonal || $showAddress || $showCourse)
                                            <div class="w-full my-6 pointer-events-none">
                                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 bg-gray-50/50 opacity-70">
                                                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                                        <i class="ph-fill ph-lock-key"></i> Campos Fixos Ativos:
                                                        @if($showPersonal) <span class="px-2 py-0.5 bg-white border rounded text-[10px]">Dados Pessoais</span> @endif
                                                        @if($showAddress) <span class="px-2 py-0.5 bg-white border rounded text-[10px]">Endereço</span> @endif
                                                        @if($showCourse) <span class="px-2 py-0.5 bg-white border rounded text-[10px]">Unidade/Curso/Turno</span> @endif
                                                    </h4>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div class="h-9 bg-gray-200 rounded-md"></div>
                                                        <div class="h-9 bg-gray-200 rounded-md"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        @if($grupo['titulo'])
                                            <div class="mb-3 mt-2 border-b border-gray-200 pb-1.5 w-full">
                                                <h4 class="text-sm font-bold {{ $textoForm }}">{{ $grupo['titulo'] }}</h4>
                                            </div>
                                        @endif

                                        <div class="grid grid-cols-12 gap-x-5 gap-y-4 w-full">
                                            @php $colunasUsadasLinha = 0; @endphp
                                            @foreach($grupo['campos'] as $c)
                                                @php 
                                                    $isActive = $campoId == $c->id; 
                                                    $isSelecionadoLote = in_array((string) $c->id, $camposSelecionados, true);
                                                    $larguraSolicitada = (int) $c->largura;

                                                    if ($larguraSolicitada === 0) {
                                                        $larguraEfetiva = ($colunasUsadasLinha === 0) ? 12 : (12 - $colunasUsadasLinha);
                                                        $colunasUsadasLinha = 0;
                                                    } else {
                                                        if ($colunasUsadasLinha + $larguraSolicitada > 12) {
                                                            $colunasUsadasLinha = 0;
                                                        }
                                                        $larguraEfetiva = $larguraSolicitada;
                                                        $colunasUsadasLinha = ($colunasUsadasLinha + $larguraSolicitada) % 12;
                                                    }

                                                    $colSpan = "col-span-12 md:col-span-{$larguraEfetiva}";
                                                    $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                                    $layoutOpcoes = ($isActive && isset($configuracoes['layout_opcoes'])) ? $configuracoes['layout_opcoes'] : ($cfg['layout_opcoes'] ?? 'horizontal');

                                                    // Resolve dicas (Tooltip e Legenda) do campo para o preview
                                                    $dicaModoPv = $cfg['dica_modo'] ?? 'nenhum';
                                                    $tooltipTextoPv = null;
                                                    $tooltipTriggerPv = 'hover';
                                                    $legendaTextoPv = null;

                                                    if ($dicaModoPv === 'legenda') {
                                                        $legendaTextoPv = $cfg['dica_1_texto'] ?? null;
                                                    } elseif ($dicaModoPv === 'tooltip') {
                                                        $tooltipTextoPv = $cfg['dica_1_texto'] ?? null;
                                                        $tooltipTriggerPv = $cfg['dica_1_trigger'] ?? 'hover';
                                                    } elseif ($dicaModoPv === 'ambos') {
                                                        if (($cfg['dica_1_tipo'] ?? 'legenda') === 'tooltip') {
                                                            $tooltipTextoPv = $cfg['dica_1_texto'] ?? null;
                                                            $tooltipTriggerPv = $cfg['dica_1_trigger'] ?? 'hover';
                                                            $legendaTextoPv = $cfg['dica_2_texto'] ?? null;
                                                        } else {
                                                            $legendaTextoPv = $cfg['dica_1_texto'] ?? null;
                                                            $tooltipTextoPv = $cfg['dica_2_texto'] ?? null;
                                                            $tooltipTriggerPv = $cfg['dica_2_trigger'] ?? 'hover';
                                                        }
                                                    }
                                                @endphp

                                                <div wire:key="campo-{{ $c->id }}" class="{{ $colSpan }} relative group rounded-lg transition-all duration-200 {{ $isActive ? 'ring-2 ring-purpura-500 shadow-md p-3.5 bg-purpura-50/20' : ($isSelecionadoLote ? 'ring-2 ring-indigo-500 bg-indigo-50/30 p-3' : 'border border-transparent hover:border-gray-200 p-2 -mx-2 cursor-pointer hover:bg-gray-50/60') }}" wire:click="editar({{ $c->id }})">
                                                    
                                                    <div class="absolute right-2 -top-3 {{ $isActive ? 'flex' : 'hidden group-hover:flex' }} gap-1 bg-white border border-gray-200 shadow-md rounded-md overflow-hidden z-20 text-gray-600">
                                                        <button type="button" wire:click.stop="editar({{ $c->id }})" class="p-1.5 hover:bg-purpura-50 hover:text-purpura-600 transition" title="Editar Campo"><i class="ph ph-pencil-simple text-sm"></i></button>
                                                        <button type="button" wire:click.stop="excluir({{ $c->id }})" wire:confirm="Tem certeza de que deseja excluir este campo?" class="p-1.5 hover:bg-red-50 hover:text-red-600 transition border-l border-gray-100" title="Excluir Campo"><i class="ph ph-trash text-sm"></i></button>
                                                    </div>

                                                    <div class="relative z-10">
                                                        <div class="flex justify-between items-start mb-1.5 gap-2">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                {{-- Checkbox para selecionar múltiplos inputs --}}
                                                                <input type="checkbox"
                                                                       wire:click.stop="toggleSelecionarCampo({{ $c->id }})"
                                                                       @checked($isSelecionadoLote)
                                                                       class="w-3.5 h-3.5 rounded border-gray-300 text-purpura-600 focus:ring-purpura-500 cursor-pointer shrink-0"
                                                                       title="Selecionar para mover em lote">

                                                                @if(!in_array($c->tipo, ['html', 'divider', 'media', 'spacer', 'link']))
                                                                    <label class="block text-xs font-bold text-gray-800 cursor-pointer">
                                                                        @if($c->tipo === 'signature')
                                                                            {!! formatRichFormText($c->label ?: (($cfg['tipo_assinante'] ?? $c->subtipo) === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura')) !!}
                                                                        @else
                                                                            {!! formatRichFormText($c->label) !!}
                                                                        @endif
                                                                        @if($c->obrigatorio) <span class="text-red-500">*</span> @endif
                                                                    </label>

                                                                    {{-- Ícone de Tooltip no Preview --}}
                                                                    @if(!empty($tooltipTextoPv))
                                                                        <div x-data="{ showTip: false }" class="relative inline-flex items-center" @click.stop>
                                                                            <button type="button"
                                                                                    @if($tooltipTriggerPv === 'hover')
                                                                                        @mouseenter="showTip = true" @mouseleave="showTip = false"
                                                                                    @else
                                                                                        @click="showTip = !showTip" @click.away="showTip = false"
                                                                                    @endif
                                                                                    class="text-purpura-600 hover:text-purpura-800 focus:outline-none inline-flex items-center justify-center w-4 h-4 rounded-full bg-purpura-50 border border-purpura-200"
                                                                                    title="Dica ({{ $tooltipTriggerPv === 'hover' ? 'Ao passar o rato' : 'Ao clicar' }})">
                                                                                <i class="ph-bold ph-question text-[10px]"></i>
                                                                            </button>
                                                                            <div x-show="showTip" x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl z-50 leading-relaxed">
                                                                                {!! formatRichFormText($tooltipTextoPv) !!}
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                            <span class="text-[10px] font-mono font-bold bg-white text-gray-500 px-1.5 py-0.5 rounded border border-gray-200 shrink-0">#{{ $c->ordem }}</span>
                                                        </div>

                                                        {{-- PREVIEW DOS INPUTS E SUBTIPOS --}}
                                                        @if($c->tipo === 'text')
                                                            <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between gap-2 shadow-sm pointer-events-none">
                                                                <div class="flex items-center gap-2 truncate">
                                                                    @if($c->subtipo == 'email') <i class="ph ph-envelope-simple text-base"></i> <span>exemplo@email.com</span>
                                                                    @elseif($c->subtipo == 'date') <i class="ph ph-calendar-blank text-base"></i> <span>dd/mm/aaaa</span>
                                                                    @elseif($c->subtipo == 'time') <i class="ph ph-clock text-base"></i> <span>00:00</span>
                                                                    @elseif($c->subtipo == 'datetime-local') <i class="ph ph-calendar-check text-base"></i> <span>dd/mm/aaaa 00:00</span>
                                                                    @elseif($c->subtipo == 'number') <i class="ph ph-hash text-base"></i> <span>Número...</span>
                                                                    @elseif($c->subtipo == 'money') <i class="ph ph-currency-circle-dollar text-base text-emerald-600"></i> <span>R$ 0,00</span>
                                                                    @elseif($c->subtipo == 'tel') <i class="ph ph-device-mobile text-base text-blue-500"></i> <span>(00) 00000-0000</span>
                                                                    @elseif($c->subtipo == 'cpf') <i class="ph ph-identification-card text-base text-indigo-500"></i> <span>000.000.000-00</span>
                                                                    @elseif($c->subtipo == 'cnpj') <i class="ph ph-buildings text-base text-amber-600"></i> <span>00.000.000/0000-00</span>
                                                                    @elseif($c->subtipo == 'cep') <i class="ph ph-map-pin-line text-base text-rose-500"></i> <span>00000-000</span>
                                                                    @else <i class="ph ph-text-t text-base"></i> <span>Texto curto...</span> @endif
                                                                </div>
                                                                @if($c->subtipo === 'cep' && !empty($cfg['viacep']))
                                                                    <span class="px-1.5 py-0.5 bg-rose-50 text-rose-600 border border-rose-200 rounded text-[9px] font-bold shrink-0">ViaCEP Ativo</span>
                                                                @elseif($c->subtipo === 'date' && !empty($cfg['calcular_idade']))
                                                                    <span class="px-1.5 py-0.5 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded text-[9px] font-bold shrink-0"><i class="ph-fill ph-cake"></i> Calcula Idade</span>
                                                                @elseif(!empty($cfg['viacep_parent']))
                                                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold shrink-0">Auto (ViaCEP)</span>
                                                                @endif
                                                            </div>

                                                        @elseif($c->tipo === 'signature')
                                                            <div class="w-full border-2 border-dashed border-indigo-200 rounded-lg p-4 bg-white text-center pointer-events-none">
                                                                <i class="ph-duotone ph-signature text-3xl text-indigo-500 mb-1"></i>
                                                                <div class="w-48 max-w-full mx-auto border-b border-gray-400 my-2"></div>
                                                                <p class="text-[11px] font-bold text-gray-600">
                                                                    {{ ($cfg['tipo_assinante'] ?? $c->subtipo) === 'responsavel' ? 'Assinatura do responsável' : 'Sua assinatura' }}
                                                                </p>
                                                                <span class="text-[9px] text-gray-400">Área de desenho com rato / ecrã tátil</span>
                                                            </div>

                                                        @elseif($c->tipo === 'link')
                                                            @php
                                                                $destinoPv = $cfg['tipo_destino'] ?? 'externo';
                                                                $extPv = strtolower($cfg['arquivo_extensao'] ?? '');
                                                                $permDownloadPv = !empty($cfg['permitir_download']);
                                                                $abrirPreviewPv = !empty($cfg['abrir_preview']);
                                                            @endphp
                                                            <div class="w-full p-3.5 bg-blue-50/60 border border-blue-200 rounded-lg flex items-center justify-between gap-3 pointer-events-none">
                                                                <div class="flex items-center gap-3 min-w-0">
                                                                    <div class="w-9 h-9 rounded-lg bg-white border border-blue-200 flex items-center justify-center shrink-0 text-blue-600">
                                                                        @if($destinoPv === 'arquivo')
                                                                            @if($extPv === 'pdf') <i class="ph-fill ph-file-pdf text-xl text-red-500"></i>
                                                                            @elseif(in_array($extPv, ['xls', 'xlsx'])) <i class="ph-fill ph-file-xls text-xl text-emerald-600"></i>
                                                                            @else <i class="ph-fill ph-file-doc text-xl text-blue-600"></i> @endif
                                                                        @elseif($destinoPv === 'interno')
                                                                            <i class="ph-bold ph-compass text-xl text-indigo-600"></i>
                                                                        @else
                                                                            <i class="ph-bold ph-arrow-square-out text-xl text-blue-600"></i>
                                                                        @endif
                                                                    </div>
                                                                    <div class="min-w-0">
                                                                        <p class="text-xs font-bold text-gray-900 truncate">{!! formatRichFormText($c->label ?: ($cfg['arquivo_nome'] ?? 'Link / Documento')) !!}</p>
                                                                        <p class="text-[10px] text-gray-500 truncate">
                                                                            @if($destinoPv === 'arquivo')
                                                                                Arquivo: {{ $cfg['arquivo_nome'] ?? 'Aguardando upload...' }}
                                                                            @else
                                                                                {{ $cfg['url'] ?? 'URL não definida' }}
                                                                            @endif
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center gap-1 shrink-0">
                                                                    @if($destinoPv === 'arquivo')
                                                                        @if($abrirPreviewPv)
                                                                            <span class="px-2 py-0.5 bg-white border border-blue-200 text-blue-700 text-[9px] font-bold rounded">Ver</span>
                                                                        @endif
                                                                        @if($permDownloadPv)
                                                                            <span class="px-2 py-0.5 bg-blue-600 text-white text-[9px] font-bold rounded">Baixar</span>
                                                                        @else
                                                                            <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-[9px] font-bold rounded">S/ Download</span>
                                                                        @endif
                                                                    @else
                                                                        <span class="px-2 py-0.5 bg-white border border-blue-200 text-blue-700 text-[9px] font-bold rounded">Acessar</span>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                        @elseif($c->tipo === 'file')
                                                            @php
                                                                $tipoArquivoPv = $cfg['tipo_arquivo'] ?? 'todos';
                                                                $multiPv = !empty($cfg['aceita_multiplos']);
                                                                $maxMbPv = $cfg['max_size_mb'] ?? 10;
                                                            @endphp
                                                            <div class="w-full border-2 border-dashed border-gray-300 rounded-lg p-3 bg-gray-50/60 text-center pointer-events-none">
                                                                <i class="ph ph-cloud-arrow-up text-xl text-purpura-500"></i>
                                                                <p class="text-xs font-bold text-gray-700">Carregar Anexo ({{ ucfirst($tipoArquivoPv) }})</p>
                                                                <span class="text-[10px] text-gray-400">{{ $multiPv ? 'Múltiplos' : 'Único' }} &middot; Máx: {{ $maxMbPv }}MB</span>
                                                            </div>

                                                        @elseif($c->tipo === 'select')
                                                            <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between shadow-sm pointer-events-none">
                                                                <span>Selecione...</span><i class="ph ph-caret-down text-gray-500"></i>
                                                            </div>

                                                        @elseif($c->tipo === 'scale')
                                                            @php $maxScale = $cfg['escala_maxima'] ?? 10; @endphp
                                                            <div class="mt-1 pointer-events-none">
                                                                <div class="flex w-full border border-gray-300 rounded-md overflow-hidden bg-white">
                                                                    @for($i = 0; $i <= $maxScale; $i++)
                                                                        <div class="flex-1 py-1.5 text-center border-r last:border-r-0 border-gray-200 text-gray-600 text-[11px] font-bold">{{ $i }}</div>
                                                                    @endfor
                                                                </div>
                                                            </div>

                                                        @elseif($c->tipo === 'radio' || $c->tipo === 'check')
                                                            <div class="flex {{ $layoutOpcoes === 'vertical' ? 'flex-col gap-1.5' : 'flex-wrap gap-3' }} mt-1 pointer-events-none">
                                                                <div class="flex items-center gap-1.5 text-gray-600 text-xs">
                                                                    <div class="w-3.5 h-3.5 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 1
                                                                </div>
                                                                <div class="flex items-center gap-1.5 text-gray-600 text-xs">
                                                                    <div class="w-3.5 h-3.5 border border-gray-300 {{ $c->tipo === 'radio' ? 'rounded-full' : 'rounded' }} bg-white"></div> Opção 2
                                                                </div>
                                                            </div>

                                                        @elseif($c->tipo === 'system')
                                                            <div class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-400 text-xs flex items-center justify-between shadow-sm pointer-events-none">
                                                                <span class="flex items-center gap-1.5"><i class="ph ph-database text-purpura-400"></i> Selecione {{ ucfirst($c->subtipo) }}...</span><i class="ph ph-caret-down text-gray-500"></i>
                                                            </div>

                                                        @elseif($c->tipo === 'matriz')
                                                            @php
                                                                $linhasPv = !empty($cfg['linhas']) ? $cfg['linhas'] : ['Item 1', 'Item 2'];
                                                                $colunasPv = !empty($cfg['colunas']) ? $cfg['colunas'] : ['Opção A', 'Opção B'];
                                                            @endphp
                                                            <div class="overflow-x-auto w-full border border-gray-200 rounded-lg pointer-events-none bg-white mt-1">
                                                                <table class="min-w-full text-[11px] text-left">
                                                                    <thead class="bg-gray-50 border-b border-gray-200">
                                                                        <tr>
                                                                            <th class="p-1.5"></th>
                                                                            @foreach($colunasPv as $col)
                                                                                <th class="p-1.5 text-center text-gray-600 font-bold border-l border-gray-100">{{ $col }}</th>
                                                                            @endforeach
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100">
                                                                        @foreach($linhasPv as $linha)
                                                                            <tr>
                                                                                <td class="p-1.5 font-medium text-gray-800">{{ $linha }}</td>
                                                                                @foreach($colunasPv as $col)
                                                                                    <td class="p-1.5 text-center border-l border-gray-100"><div class="w-3 h-3 border border-gray-300 rounded-full inline-block"></div></td>
                                                                                @endforeach
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                        @elseif($c->tipo === 'html')
                                                            <div class="text-gray-800">
                                                                @if($c->subtipo === 'h1') <h1 class="text-2xl font-extrabold">{!! formatRichFormText($c->label) !!}</h1>
                                                                @elseif($c->subtipo === 'h2') <h2 class="text-xl font-bold">{!! formatRichFormText($c->label) !!}</h2>
                                                                @elseif($c->subtipo === 'h3') <h3 class="text-lg font-bold">{!! formatRichFormText($c->label) !!}</h3>
                                                                @elseif($c->subtipo === 'p') <div class="text-xs leading-relaxed">{!! formatRichFormText($c->label) !!}</div>
                                                                @elseif($c->subtipo === 'link') <span class="text-purpura-600 font-bold underline text-xs">{!! formatRichFormText($c->label) !!}</span>
                                                                @elseif($c->subtipo === 'info_card') 
                                                                    <div class="p-2.5 bg-blue-50 border-l-4 border-blue-500 text-blue-800 rounded-r-md">
                                                                        <p class="font-bold text-xs">{!! formatRichFormText($c->label) !!}</p>
                                                                        @if(!empty($cfg['descricao']))
                                                                            <div class="text-[11px] mt-1">{!! formatRichFormText($cfg['descricao']) !!}</div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>

                                                        @elseif($c->tipo === 'divider')
                                                            <hr class="border-t-2 border-dashed border-gray-300 my-2">

                                                        @elseif($c->tipo === 'spacer')
                                                            <div class="w-full h-10 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50/60 text-gray-400 text-[11px] font-semibold select-none">
                                                                <i class="ph ph-arrows-out-line-horizontal mr-1.5"></i> Espaço ({{ $c->largura == 0 ? "Restante: {$larguraEfetiva}/12" : "{$c->largura}/12" }})
                                                            </div>

                                                        @elseif($c->tipo === 'media')
                                                            <div class="w-full bg-gray-50 rounded-md p-3 text-center border border-gray-200 pointer-events-none text-gray-500">
                                                                <i class="ph ph-{{ $c->subtipo == 'video' ? 'video-camera' : 'image' }} text-xl"></i>
                                                                <p class="text-[11px] font-bold">Mídia ({{ ucfirst($c->subtipo) }})</p>
                                                            </div>

                                                        @elseif($c->tipo === 'social')
                                                            @php $redesPreview = $cfg['redes_permitidas'] ?? ['instagram']; @endphp
                                                            <div class="flex flex-col gap-1.5 mt-1 pointer-events-none">
                                                                @foreach($redesPreview as $rede)
                                                                    <div class="flex items-center gap-2">
                                                                        <div class="w-7 h-7 rounded bg-gray-100 flex items-center justify-center text-gray-600 border border-gray-200"><i class="text-sm ph-fill ph-{{ strtolower($rede) }}-logo"></i></div>
                                                                        <div class="flex-1 h-7 bg-gray-50 border border-gray-200 rounded"></div>
                                                                    </div>
                                                                @endforeach
                                                            </div>

                                                        @elseif($c->tipo === 'rating')
                                                            <div class="flex gap-1 text-xl text-yellow-400 pointer-events-none">
                                                                <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-gray-300"></i><i class="ph ph-star text-gray-300"></i>
                                                            </div>
                                                        @endif

                                                        {{-- Legenda / Dica abaixo do input no Preview --}}
                                                        @if(!empty($legendaTextoPv))
                                                            <div class="mt-1.5 text-[11px] text-gray-500 leading-snug">
                                                                {!! formatRichFormText($legendaTextoPv) !!}
                                                            </div>
                                                        @endif

                                                        <div class="mt-2 flex flex-wrap gap-1.5 items-center">
                                                            @if(!in_array($c->tipo, ['html', 'divider', 'social', 'media', 'spacer', 'link']))
                                                                <span class="text-[9px] bg-gray-100 border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-mono font-bold"><i class="ph ph-database"></i> {{ $c->name }}</span>
                                                            @endif
                                                            @if($c->depende_de)
                                                                <span class="inline-flex items-center gap-1 bg-yellow-100 border border-yellow-200 text-yellow-800 text-[9px] px-1.5 py-0.5 rounded font-bold">
                                                                    <i class="ph-fill ph-warning-circle"></i> Condicional
                                                                </span>
                                                            @endif
                                                            @if($dicaModoPv !== 'nenhum')
                                                                <span class="inline-flex items-center gap-1 bg-purpura-50 border border-purpura-200 text-purpura-700 text-[9px] px-1.5 py-0.5 rounded font-bold">
                                                                    <i class="ph-fill ph-info"></i> Dica: {{ ucfirst($dicaModoPv) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3 border border-gray-200">
                                    <i class="ph ph-list-plus text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-1">Formulário Vazio</h3>
                                <p class="text-gray-500 text-xs max-w-xs">Selecione um tipo de input na coluna da esquerda para iniciar a configuração.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- COLUNA 3 (DIREITA): CONFIGURAÇÃO DO INPUT ATIVO (SÓ APARECE QUANDO SELECIONADO) -->
            <aside class="w-80 xl:w-96 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
                @if($painelCampoAtivo || $campoId)
                    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between shrink-0">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                            <i class="ph-fill ph-sliders text-purpura-600 text-base"></i> {{ $campoId ? 'Editar Input' : 'Novo Input' }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if($campoId)
                                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase border border-indigo-200">Em Edição</span>
                            @endif
                            <button type="button" wire:click="cancelarEdicao" class="text-gray-400 hover:text-red-500 p-1 rounded transition" title="Fechar painel">
                                <i class="ph-bold ph-x text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <form wire:submit.prevent="salvar" class="flex-1 flex flex-col overflow-hidden">
                        <div class="p-5 overflow-y-auto flex-1 custom-scrollbar space-y-4">
                            
                            <!-- PÁGINA E ORDEM -->
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1.5 uppercase tracking-wider">Página (Fase) e Posição</label>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <select wire:model.live="etapa" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 bg-gray-50 font-semibold text-gray-700">
                                        @foreach($etapasDisponiveis as $et)
                                            <option value="{{ $et->numero }}">{{ $et->nome }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" wire:model="ordem" min="1" max="{{ $this->limiteOrdem() }}" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 bg-gray-50 font-semibold text-gray-700 text-center">
                                </div>
                                @error('ordem') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror

                                @if($etapa == 1 && $contextoTipo === 'ciclo')
                                    <label class="flex items-center gap-2.5 p-2.5 mt-2.5 border border-indigo-200 bg-indigo-50 rounded-lg cursor-pointer">
                                        <input type="checkbox" wire:model="configuracoes.exibir_no_topo" class="h-4 w-4 text-indigo-600 rounded border-indigo-300">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-indigo-900 font-bold">Exibir no Topo da Etapa 1</span>
                                            <span class="text-[10px] text-indigo-700">Antes dos dados pessoais fixos.</span>
                                        </div>
                                    </label>
                                @endif
                            </div>

                            <hr class="border-gray-100">

                            <!-- TÍTULO / LABEL COM EDITOR QUILL BÁSICO -->
                            @if(!in_array($tipo, ['divider', 'spacer']))
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                        {{ in_array($tipo, ['html', 'media']) ? 'Conteúdo Principal (Texto/Título)' : ($tipo === 'link' ? 'Texto de Exibição do Link / Botão' : 'Título da Pergunta') }} <span class="text-red-500">*</span>
                                    </label>
                                    
                                    <div class="builder-quill"
                                         wire:ignore
                                         x-data="{
                                            val: @entangle('label').live,
                                            quill: null,
                                            init() {
                                                this.quill = new Quill(this.$refs.editorLabel, {
                                                    theme: 'snow',
                                                    placeholder: 'Digite e formate o texto...',
                                                    modules: {
                                                        toolbar: [
                                                            ['bold', 'italic', 'underline', 'strike'],
                                                            [{ 'size': ['small', false, 'large', 'huge'] }],
                                                            [{ 'color': [] }, { 'background': [] }],
                                                            ['clean']
                                                        ]
                                                    }
                                                });
                                                this.quill.root.innerHTML = this.val || '';
                                                this.quill.on('text-change', () => {
                                                    let html = this.quill.root.innerHTML;
                                                    this.val = (html === '<p><br></p>') ? '' : html;
                                                });
                                                window.addEventListener('sincronizar-quill-builder', () => {
                                                    setTimeout(() => {
                                                        if (this.quill.root.innerHTML !== (this.val || '')) {
                                                            this.quill.root.innerHTML = this.val || '';
                                                        }
                                                    }, 50);
                                                });
                                            }
                                         }">
                                        <div x-ref="editorLabel"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- ID NO BANCO E LARGURA -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">ID no Banco <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="name" class="w-full text-[11px] rounded-lg border-gray-300 shadow-sm bg-gray-100 text-gray-600 font-mono">
                                    @error('name') <span class="text-[10px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Largura na Grelha</label>
                                    <select wire:model="largura" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        <option value="12">100% (12/12)</option>
                                        <option value="9">75% (9/12)</option>
                                        <option value="8">66% (8/12)</option>
                                        <option value="6">50% (6/12)</option>
                                        <option value="4">33% (4/12)</option>
                                        <option value="3">25% (3/12)</option>
                                        <option value="2">16% (2/12)</option>
                                        <option value="1">8% (1/12)</option>
                                        <option value="0">Ocupar Restante</option>
                                    </select>
                                </div>
                            </div>

                            <!-- ========================================================= -->
                            <!-- CONFIGURAÇÃO: DATA DE NASCIMENTO (CALCULAR IDADE)         -->
                            <!-- ========================================================= -->
                            @if($tipo === 'text' && $subtipo === 'date')
                                <div class="p-3.5 bg-purpura-50/50 border border-purpura-200 rounded-lg">
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" wire:model.live="configuracoes.calcular_idade" class="mt-0.5 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-900 flex items-center gap-1">
                                                <i class="ph-fill ph-cake text-purpura-600"></i> Data de Nascimento (Calcular Idade)
                                            </span>
                                            <span class="text-[10px] text-gray-600 leading-tight mt-0.5">
                                                Calcula e exibe automaticamente a idade em anos ao preencher esta data no formulário.
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            @endif

                            <!-- ========================================================= -->
                            <!-- DICAS E ORIENTAÇÕES (LEGENDA / TOOLTIP / AMBOS)           -->
                            <!-- ========================================================= -->
                            @if(!in_array($tipo, ['divider', 'spacer']))
                                <div class="p-3.5 bg-purpura-50/40 border border-purpura-200 rounded-lg space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h5 class="font-bold text-xs text-purpura-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="ph-fill ph-lightbulb text-purpura-600 text-base"></i> Dicas e Orientações
                                        </h5>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Exibição da Dica no Input</label>
                                        <select wire:model.live="configuracoes.dica_modo" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 bg-white">
                                            <option value="nenhum">Sem dica / orientação</option>
                                            <option value="legenda">Abaixo do input (Legenda)</option>
                                            <option value="tooltip">Ícone ao lado do título (Tooltip)</option>
                                            <option value="ambos">Ambos (Legenda + Tooltip)</option>
                                        </select>
                                    </div>

                                    @if(($configuracoes['dica_modo'] ?? 'nenhum') !== 'nenhum')
                                        <!-- 1ª DICA / ORIENTAÇÃO -->
                                        <div class="p-3 bg-white border border-gray-200 rounded-lg space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-extrabold text-purpura-700 uppercase">
                                                    {{ ($configuracoes['dica_modo'] ?? '') === 'ambos' ? '1ª Dica / Orientação' : 'Texto da Dica / Orientação' }}
                                                </span>
                                            </div>

                                            @if(($configuracoes['dica_modo'] ?? '') === 'ambos')
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Posição desta 1ª dica</label>
                                                    <select wire:model.live="configuracoes.dica_1_tipo" class="w-full text-xs rounded-md border-gray-300">
                                                        <option value="legenda">Legenda (Abaixo do Input)</option>
                                                        <option value="tooltip">Tooltip (Ícone Interativo)</option>
                                                    </select>
                                                </div>
                                            @endif

                                            @if((($configuracoes['dica_modo'] ?? '') === 'tooltip') || (($configuracoes['dica_modo'] ?? '') === 'ambos' && ($configuracoes['dica_1_tipo'] ?? 'legenda') === 'tooltip'))
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Como acionar o Tooltip?</label>
                                                    <select wire:model.live="configuracoes.dica_1_trigger" class="w-full text-xs rounded-md border-gray-300">
                                                        <option value="hover">Ao passar o rato (Hover)</option>
                                                        <option value="click">Ao clicar no ícone (Click)</option>
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="builder-quill"
                                                 wire:ignore
                                                 x-data="{
                                                    val: @entangle('configuracoes.dica_1_texto').live,
                                                    quill: null,
                                                    init() {
                                                        this.quill = new Quill(this.$refs.editorDica1, {
                                                            theme: 'snow',
                                                            placeholder: 'Escreva a orientação aqui...',
                                                            modules: {
                                                                toolbar: [
                                                                    ['bold', 'italic', 'underline', 'strike'],
                                                                    [{ 'size': ['small', false, 'large', 'huge'] }],
                                                                    [{ 'color': [] }, { 'background': [] }],
                                                                    ['clean']
                                                                ]
                                                            }
                                                        });
                                                        this.quill.root.innerHTML = this.val || '';
                                                        this.quill.on('text-change', () => {
                                                            let html = this.quill.root.innerHTML;
                                                            this.val = (html === '<p><br></p>') ? '' : html;
                                                        });
                                                        window.addEventListener('sincronizar-quill-builder', () => {
                                                            setTimeout(() => {
                                                                if (this.quill.root.innerHTML !== (this.val || '')) {
                                                                    this.quill.root.innerHTML = this.val || '';
                                                                }
                                                            }, 50);
                                                        });
                                                    }
                                                 }">
                                                <div x-ref="editorDica1"></div>
                                            </div>
                                        </div>

                                        <!-- 2ª DICA / ORIENTAÇÃO (APARECE QUANDO MODO = 'AMBOS') -->
                                        @if(($configuracoes['dica_modo'] ?? '') === 'ambos')
                                            <div class="p-3 bg-white border border-gray-200 rounded-lg space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-extrabold text-indigo-700 uppercase">2ª Dica / Orientação</span>
                                                </div>

                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Posição desta 2ª dica</label>
                                                    <select wire:model.live="configuracoes.dica_2_tipo" class="w-full text-xs rounded-md border-gray-300">
                                                        <option value="tooltip">Tooltip (Ícone Interativo)</option>
                                                        <option value="legenda">Legenda (Abaixo do Input)</option>
                                                    </select>
                                                </div>

                                                @if(($configuracoes['dica_2_tipo'] ?? 'tooltip') === 'tooltip')
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-600 mb-1">Como acionar o Tooltip?</label>
                                                        <select wire:model.live="configuracoes.dica_2_trigger" class="w-full text-xs rounded-md border-gray-300">
                                                            <option value="hover">Ao passar o rato (Hover)</option>
                                                            <option value="click">Ao clicar no ícone (Click)</option>
                                                        </select>
                                                    </div>
                                                @endif

                                                <div class="builder-quill"
                                                     wire:ignore
                                                     x-data="{
                                                        val: @entangle('configuracoes.dica_2_texto').live,
                                                        quill: null,
                                                        init() {
                                                            this.quill = new Quill(this.$refs.editorDica2, {
                                                                theme: 'snow',
                                                                placeholder: 'Escreva a segunda orientação aqui...',
                                                                modules: {
                                                                    toolbar: [
                                                                        ['bold', 'italic', 'underline', 'strike'],
                                                                        [{ 'size': ['small', false, 'large', 'huge'] }],
                                                                        [{ 'color': [] }, { 'background': [] }],
                                                                        ['clean']
                                                                    ]
                                                                }
                                                            });
                                                            this.quill.root.innerHTML = this.val || '';
                                                            this.quill.on('text-change', () => {
                                                                let html = this.quill.root.innerHTML;
                                                                this.val = (html === '<p><br></p>') ? '' : html;
                                                            });
                                                            window.addEventListener('sincronizar-quill-builder', () => {
                                                                setTimeout(() => {
                                                                    if (this.quill.root.innerHTML !== (this.val || '')) {
                                                                        this.quill.root.innerHTML = this.val || '';
                                                                    }
                                                                }, 50);
                                                            });
                                                        }
                                                     }">
                                                    <div x-ref="editorDica2"></div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            <!-- ========================================================= -->
                            <!-- CONFIGURAÇÕES ESPECÍFICAS: ASSINATURA À MÃO ('signature') -->
                            <!-- ========================================================= -->
                            @if($tipo === 'signature')
                                <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-lg space-y-3">
                                    <h5 class="font-bold text-xs text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-signature text-indigo-600 text-base"></i> Configuração da Assinatura
                                    </h5>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Quem irá assinar este campo?</label>
                                        <div class="space-y-2">
                                            <label class="flex items-start gap-2.5 p-2.5 bg-white border rounded-lg cursor-pointer transition {{ ($configuracoes['tipo_assinante'] ?? 'usuario') === 'usuario' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200' }}">
                                                <input type="radio" wire:model.live="configuracoes.tipo_assinante" value="usuario" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-900">Assinatura do Utilizador</span>
                                                    <span class="text-[10px] text-gray-500">Padrão: <strong>"Sua assinatura"</strong></span>
                                                </div>
                                            </label>
                                            <label class="flex items-start gap-2.5 p-2.5 bg-white border rounded-lg cursor-pointer transition {{ ($configuracoes['tipo_assinante'] ?? 'responsavel') === 'responsavel' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200' }}">
                                                <input type="radio" wire:model.live="configuracoes.tipo_assinante" value="responsavel" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-900">Assinatura do Responsável</span>
                                                    <span class="text-[10px] text-gray-500">Padrão: <strong>"Assinatura do responsável"</strong></span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- ========================================================= -->
                            <!-- CONFIGURAÇÕES ESPECÍFICAS: LINK / ARQUIVO ('link')        -->
                            <!-- ========================================================= -->
                            @if($tipo === 'link')
                                <div class="p-4 bg-blue-50/60 border border-blue-200 rounded-lg space-y-3.5">
                                    <h5 class="font-bold text-xs text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-link-simple text-blue-600 text-base"></i> Configuração do Link / Arquivo
                                    </h5>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Tipo de Destino</label>
                                        <select wire:model.live="configuracoes.tipo_destino" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="externo">Link Externo (URL Completa)</option>
                                            <option value="interno">Página ou Rota Interna do Sistema</option>
                                            <option value="arquivo">Arquivo Interno (Upload PDF, Word ou Excel)</option>
                                        </select>
                                    </div>

                                    @if(($configuracoes['tipo_destino'] ?? 'externo') === 'arquivo')
                                        <div class="space-y-3 pt-1">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1">Upload do Documento (PDF, Word, Excel)</label>
                                                <input type="file" wire:model="link_file_upload" accept=".pdf,.doc,.docx,.xls,.xlsx" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 bg-white border border-gray-300 rounded-lg p-1">
                                                <div wire:loading wire:target="link_file_upload" class="text-[11px] text-blue-600 font-bold mt-1 flex items-center gap-1">
                                                    <i class="ph-bold ph-spinner animate-spin"></i> A carregar arquivo...
                                                </div>
                                                @error('link_file_upload') <span class="text-[11px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror

                                                @if(!empty($configuracoes['arquivo_nome']) && !$link_file_upload)
                                                    <div class="mt-2 p-2 bg-white border border-blue-200 rounded flex items-center justify-between text-[11px]">
                                                        <span class="font-bold text-gray-700 truncate"><i class="ph-fill ph-check-circle text-emerald-500 mr-1"></i> {{ $configuracoes['arquivo_nome'] }}</span>
                                                        <span class="uppercase font-mono text-[10px] px-1.5 py-0.5 bg-gray-100 rounded">{{ $configuracoes['arquivo_extensao'] ?? '' }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="space-y-2 border-t border-blue-200/70 pt-2.5">
                                                <label class="flex items-center gap-2.5 cursor-pointer">
                                                    <input type="checkbox" wire:model="configuracoes.abrir_preview" class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-bold text-gray-800">Abrir pré-visualização no navegador</span>
                                                        <span class="text-[10px] text-gray-500">Exibe o leitor integrado na própria página.</span>
                                                    </div>
                                                </label>

                                                <label class="flex items-center gap-2.5 cursor-pointer">
                                                    <input type="checkbox" wire:model="configuracoes.permitir_download" class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-bold text-gray-800">Permitir o download do arquivo</span>
                                                        <span class="text-[10px] text-gray-500">Se desmarcado, oculta o botão de transferir.</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                                {{ ($configuracoes['tipo_destino'] ?? 'externo') === 'interno' ? 'Caminho Interno (ex: /portal ou /inscricao)' : 'URL Externa (https://...)' }}
                                            </label>
                                            <input type="text" wire:model="configuracoes.url" placeholder="{{ ($configuracoes['tipo_destino'] ?? 'externo') === 'interno' ? '/portal' : 'https://exemplo.com.br' }}" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-blue-500">
                                            @error('configuracoes.url') <span class="text-[11px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- ========================================================= -->
                            <!-- CONFIGURAÇÕES ESPECÍFICAS: CEP COM VIACEP AUTOMÁTICO      -->
                            <!-- ========================================================= -->
                            @if($tipo === 'text' && $subtipo === 'cep')
                                <div class="p-4 bg-rose-50/60 border border-rose-200 rounded-lg space-y-3">
                                    <h5 class="font-bold text-xs text-rose-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-map-pin-line text-rose-600 text-base"></i> Automação ViaCEP
                                    </h5>
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" wire:model.live="configuracoes.viacep" class="mt-0.5 rounded text-rose-600 border-gray-300 focus:ring-rose-500">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-900">Consultar ViaCEP automaticamente?</span>
                                            <span class="text-[10px] text-gray-600 leading-tight mt-0.5">Ao digitar os 8 dígitos do CEP no formulário, consulta a API e preenche os campos de endereço.</span>
                                        </div>
                                    </label>

                                    @if(!empty($configuracoes['viacep']) && !$campoId)
                                        <label class="flex items-start gap-2.5 cursor-pointer pt-2 border-t border-rose-200/70">
                                            <input type="checkbox" wire:model="configuracoes.criar_campos_endereco" class="mt-0.5 rounded text-rose-600 border-gray-300 focus:ring-rose-500">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-rose-900">Gerar campos de endereço automaticamente</span>
                                                <span class="text-[10px] text-rose-700 leading-tight mt-0.5">Cria automaticamente: Logradouro, Número, Complemento, Bairro, Cidade e Estado (UF).</span>
                                            </div>
                                        </label>
                                    @endif
                                </div>
                            @endif

                            <!-- CONFIGURAÇÕES DE TEXTO / NÚMEROS / MÁSCARAS -->
                            @if($tipo === 'text' && in_array($subtipo, ['text', 'number', 'cpf', 'cnpj', 'tel', 'email']))
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                                    
                                    @if($subtipo === 'number')
                                        <div class="grid grid-cols-2 gap-2.5">
                                            <div>
                                                <label class="block text-[10px] uppercase font-bold text-gray-600 mb-1">Valor Mínimo</label>
                                                <input type="number" wire:model="tamanho_min" class="w-full text-xs rounded-lg border-gray-300">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] uppercase font-bold text-gray-600 mb-1">Valor Máximo</label>
                                                <input type="number" wire:model="tamanho_max" class="w-full text-xs rounded-lg border-gray-300">
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-gray-600 mb-1">Máscara do Input (x-mask)</label>
                                            <input type="text" wire:model="regex_mascara" placeholder="Ex: 999.999.999-99" class="w-full text-xs font-mono rounded-lg border-gray-300">
                                        </div>
                                    @endif

                                    @if(in_array($subtipo, ['cpf', 'email']))
                                        <div class="border-t border-gray-200 pt-3 space-y-2.5">
                                            @if($subtipo === 'cpf')
                                                <label class="flex items-start gap-2 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="configuracoes.validar_cpf" class="mt-0.5 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-bold text-gray-800">Validar CPF (Cálculo Real)</span>
                                                        <span class="text-[10px] text-gray-500 leading-tight">Impede CPFs falsos (ex: 111.111.111-11).</span>
                                                    </div>
                                                </label>
                                            @endif

                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="checkbox" wire:model.live="configuracoes.unico" class="mt-0.5 rounded text-purpura-600 border-gray-300 focus:ring-purpura-500">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-800">Travar Resposta Única</span>
                                                    <span class="text-[10px] text-gray-500 leading-tight">O utilizador só poderá preencher o formulário uma vez com este {{ strtoupper($subtipo) }}.</span>
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- CONFIGURAÇÕES DE UPLOAD DE ARQUIVOS -->
                            @if($tipo === 'file')
                                <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200 space-y-3">
                                    <h5 class="font-bold text-xs text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-upload-simple text-purpura-600"></i> Parâmetros de Upload
                                    </h5>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Categoria de Arquivos</label>
                                        <select wire:model.live="configuracoes.tipo_arquivo" class="w-full text-xs rounded-md border-gray-300">
                                            <option value="todos">Todos os Tipos</option>
                                            <option value="imagem">Apenas Imagens</option>
                                            <option value="video">Apenas Vídeos</option>
                                            <option value="documento">Apenas Documentos</option>
                                            <option value="custom">Personalizado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Extensões Permitidas</label>
                                        <input type="text" wire:model="configuracoes.extensoes_permitidas" class="w-full text-xs font-mono rounded-md border-gray-300">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Máx. (MB)</label>
                                            <input type="number" min="1" max="100" wire:model="configuracoes.max_size_mb" class="w-full text-xs rounded-md border-gray-300">
                                        </div>
                                        @if(!empty($configuracoes['aceita_multiplos']))
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1">Qtd. Máx.</label>
                                                <input type="number" min="2" max="20" wire:model="configuracoes.max_arquivos" class="w-full text-xs rounded-md border-gray-300">
                                            </div>
                                        @endif
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer pt-1">
                                        <input type="checkbox" wire:model.live="configuracoes.aceita_multiplos" class="rounded text-purpura-600 border-gray-300">
                                        <span class="text-xs text-gray-700 font-bold">Permitir múltiplos arquivos?</span>
                                    </label>
                                </div>
                            @endif

                            @if(in_array($tipo, ['select', 'radio', 'check']))
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700">Opções de Resposta (1 por linha)</label>
                                        <textarea wire:model="opcoes" rows="4" placeholder="Sim&#10;Não" class="w-full text-xs rounded-lg border-gray-300 mt-1"></textarea>
                                    </div>
                                    @if(in_array($tipo, ['radio', 'check']))
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Alinhamento</label>
                                            <select wire:model.live="configuracoes.layout_opcoes" class="w-full text-xs rounded-lg border-gray-300">
                                                <option value="horizontal">Horizontal (Lado a Lado)</option>
                                                <option value="vertical">Vertical (Lista)</option>
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            @elseif($tipo === 'matriz')
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Linhas (1 por linha)</label>
                                        <textarea wire:model.live="matriz_linhas" rows="3" class="w-full text-xs rounded-lg border-gray-300"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Colunas (separadas por vírgula)</label>
                                        <input type="text" wire:model.live="matriz_colunas" class="w-full text-xs rounded-lg border-gray-300">
                                    </div>
                                    <p class="text-[10px] text-gray-500 leading-tight">
                                        <i class="ph-fill ph-info text-purpura-600"></i> Se marcado como <strong>Campo Obrigatório</strong> abaixo, o utilizador terá de responder a todas as linhas desta matriz.
                                    </p>
                                </div>
                            @elseif($tipo === 'system')
                                <div class="p-4 bg-purpura-50/50 border border-purpura-200 rounded-lg space-y-3">
                                    <h5 class="font-bold text-xs text-purpura-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-tree-structure text-purpura-600 text-base"></i> Regras de Vínculo do Sistema
                                    </h5>

                                    @if($subtipo === 'combo_academico')
                                        <div class="p-2.5 bg-white border border-purpura-200 rounded-md text-[11px] text-purpura-800 font-medium leading-snug">
                                            <i class="ph-fill ph-sparkle text-purpura-600"></i> Ao inserir este bloco, o sistema criará automaticamente os 3 campos: <strong>Unidade</strong>, <strong>Curso</strong> e <strong>Turno</strong> na página atual.
                                        </div>
                                    @endif

                                    @if(in_array($subtipo, ['unidade', 'combo_academico']))
                                        <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                                            <input type="checkbox" wire:model.live="configuracoes.filtrar_por_uf" class="mt-0.5 h-4 w-4 text-purpura-600 rounded border-gray-300">
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-900 font-bold">Filtrar Unidades pelo Estado (UF) do usuário?</span>
                                                <span class="text-[10px] text-gray-500 leading-tight">Se marcado, exibe apenas as unidades do mesmo estado preenchido no CEP/UF do formulário.</span>
                                            </div>
                                        </label>
                                    @endif

                                    @if(in_array($subtipo, ['curso', 'combo_academico']))
                                        <label class="flex items-start gap-2.5 cursor-pointer pt-1 border-t border-purpura-200/60">
                                            <input type="checkbox" wire:model.live="configuracoes.vincular_curso_unidade" class="mt-0.5 h-4 w-4 text-purpura-600 rounded border-gray-300">
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-900 font-bold">Relacionar Cursos à Unidade escolhida?</span>
                                                <span class="text-[10px] text-gray-500 leading-tight">Se desmarcado, lista todos os cursos ativos independente da unidade.</span>
                                            </div>
                                        </label>
                                    @endif

                                    @if(in_array($subtipo, ['turno', 'combo_academico']))
                                        <label class="flex items-start gap-2.5 cursor-pointer pt-1 border-t border-purpura-200/60">
                                            <input type="checkbox" wire:model.live="configuracoes.vincular_turno_curso" class="mt-0.5 h-4 w-4 text-purpura-600 rounded border-gray-300">
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-900 font-bold">Vincular Turnos ao Curso escolhido?</span>
                                                <span class="text-[10px] text-gray-500 leading-tight">Se desmarcado, lista todos os turnos ativos do sistema.</span>
                                            </div>
                                        </label>
                                    @endif
                                </div>
                            @elseif($tipo === 'html')
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                                    <label class="block text-xs font-bold text-gray-700">Estilo do Texto</label>
                                    <select wire:model.live="subtipo" class="w-full text-xs rounded-lg border-gray-300">
                                        <option value="h1">Título Grande (H1)</option>
                                        <option value="h2">Título Médio (H2)</option>
                                        <option value="h3">Título Pequeno (H3)</option>
                                        <option value="p">Parágrafo Normal</option>
                                        <option value="info_card">Caixa de Aviso (Azul)</option>
                                    </select>
                                    @if($subtipo === 'info_card')
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Texto Adicional do Aviso</label>
                                            <div class="builder-quill"
                                                 wire:ignore
                                                 x-data="{
                                                    val: @entangle('configuracoes.descricao').live,
                                                    quill: null,
                                                    init() {
                                                        this.quill = new Quill(this.$refs.editorAvisoDesc, {
                                                            theme: 'snow',
                                                            placeholder: 'Texto complementar do aviso...',
                                                            modules: {
                                                                toolbar: [
                                                                    ['bold', 'italic', 'underline', 'strike'],
                                                                    [{ 'size': ['small', false, 'large', 'huge'] }],
                                                                    [{ 'color': [] }, { 'background': [] }],
                                                                    ['clean']
                                                                ]
                                                            }
                                                        });
                                                        this.quill.root.innerHTML = this.val || '';
                                                        this.quill.on('text-change', () => {
                                                            let html = this.quill.root.innerHTML;
                                                            this.val = (html === '<p><br></p>') ? '' : html;
                                                        });
                                                        window.addEventListener('sincronizar-quill-builder', () => {
                                                            setTimeout(() => {
                                                                if (this.quill.root.innerHTML !== (this.val || '')) {
                                                                    this.quill.root.innerHTML = this.val || '';
                                                                }
                                                            }, 50);
                                                        });
                                                    }
                                                 }">
                                                <div x-ref="editorAvisoDesc"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($tipo === 'media')
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                                    <label class="block text-xs font-bold text-gray-700">Tipo de Mídia</label>
                                    <select wire:model.live="subtipo" class="w-full text-xs rounded-lg border-gray-300">
                                        <option value="image">Imagem Estática</option>
                                        <option value="video">Vídeo Externo</option>
                                    </select>
                                    <input type="url" wire:model="configuracoes.url" placeholder="URL da Imagem ou YouTube" class="w-full text-xs rounded-lg border-gray-300">
                                </div>
                            @elseif($tipo === 'social')
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg">
                                    <label class="block text-xs font-bold text-gray-900 mb-2">Redes Solicitadas</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @php
                                            $redesOpcoes = [
                                                'instagram' => ['nome' => 'Instagram', 'icon' => 'ph-instagram-logo text-pink-500'],
                                                'facebook' => ['nome' => 'Facebook', 'icon' => 'ph-facebook-logo text-blue-600'],
                                                'youtube' => ['nome' => 'YouTube', 'icon' => 'ph-youtube-logo text-red-600'],
                                                'tiktok' => ['nome' => 'TikTok', 'icon' => 'ph-tiktok-logo text-black'],
                                                'vsco' => ['nome' => 'VSCO', 'icon' => 'ph-aperture text-gray-800'],
                                                'linkedin' => ['nome' => 'LinkedIn', 'icon' => 'ph-linkedin-logo text-blue-700']
                                            ];
                                        @endphp
                                        @foreach($redesOpcoes as $key => $rede)
                                            <label class="flex items-center gap-1.5 cursor-pointer bg-white p-2 rounded border border-gray-200">
                                                <input type="checkbox" wire:model="configuracoes.redes_permitidas" value="{{ $key }}" class="w-3.5 h-3.5 text-purpura-600 rounded">
                                                <i class="ph-fill {{ $rede['icon'] }}"></i>
                                                <span class="text-[11px] font-bold text-gray-700">{{ $rede['nome'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif($tipo === 'rating')
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Total de Estrelas</label>
                                    <input type="number" wire:model="configuracoes.max_stars" min="3" max="10" placeholder="5" class="w-full text-xs rounded-lg border-gray-300">
                                </div>
                            @elseif($tipo === 'scale')
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Escala de 0 a:</label>
                                        <select wire:model="configuracoes.escala_maxima" class="w-full text-xs rounded-lg border-gray-300">
                                            <option value="5">5</option>
                                            <option value="10">10</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" wire:model="configuracoes.label_min" placeholder="Rótulo Mín (0)" class="w-full text-xs rounded-lg border-gray-300">
                                        <input type="text" wire:model="configuracoes.label_max" placeholder="Rótulo Máx" class="w-full text-xs rounded-lg border-gray-300">
                                    </div>
                                </div>
                            @endif

                            <!-- OBRIGATORIEDADE -->
                            @if(!in_array($tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']))
                                <label class="flex items-center gap-2.5 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="checkbox" wire:model="obrigatorio" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-900 font-bold">Campo Obrigatório</span>
                                        <span class="text-[10px] text-gray-500">Impede o avanço sem preenchimento.</span>
                                    </div>
                                </label>
                            @endif

                            <!-- CONDICIONAL -->
                            <div x-data="{ openCond: {{ !empty($depende_de) ? 'true' : 'false' }} }" class="border border-yellow-200 rounded-lg overflow-hidden">
                                <button type="button" @click="openCond = !openCond" class="w-full flex items-center justify-between p-2.5 bg-yellow-50 hover:bg-yellow-100 transition text-xs font-bold text-yellow-800">
                                    <span class="flex items-center gap-1.5"><i class="ph-fill ph-git-branch text-yellow-600 text-base"></i> Exibição Condicional</span>
                                    <i class="ph ph-caret-down transition-transform" :class="openCond ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="openCond" x-collapse x-cloak class="p-3 bg-white border-t border-yellow-200 space-y-2.5">
                                    <div>
                                        <label class="block text-[10px] font-bold text-yellow-700 mb-1">Mostrar SOMENTE SE:</label>
                                        <select wire:model.live="depende_de" class="w-full text-xs rounded-lg border-yellow-300">
                                            <option value="">-- Sempre Visível --</option>
                                            @foreach($camposCadastrados as $c)
                                                @if($c->id !== $campoId && !in_array($c->tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']))
                                                    <option value="{{ $c->name }}">{{ Str::limit(strip_tags($c->label), 28) }} ({{ $c->name }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    @if(!empty($depende_de))
                                        <div class="grid grid-cols-3 gap-1.5">
                                            <select wire:model="depende_operador" class="col-span-1 text-xs rounded-lg border-gray-300">
                                                <option value="=">Igual</option>
                                                <option value="!=">Dif.</option>
                                                <option value=">">Maior</option>
                                                <option value="<">Menor</option>
                                            </select>
                                            <input type="text" wire:model="depende_valor" placeholder="Valor esperado" class="col-span-2 text-xs rounded-lg border-gray-300">
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>

                        <!-- RODAPÉ DE SALVAMENTO DO INPUT -->
                        <div class="p-4 bg-gray-50 border-t border-gray-200 shrink-0 flex gap-2">
                            <button type="button" wire:click="cancelarEdicao" class="flex-1 bg-white border border-gray-300 text-gray-700 py-2 rounded-lg text-xs font-bold hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="flex-1 bg-purpura-600 text-white py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-purpura-700 transition flex justify-center items-center gap-1.5">
                                <i class="ph-bold ph-floppy-disk text-base"></i> {{ $campoId ? 'Atualizar Bloco' : 'Inserir Bloco' }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center mb-3">
                            <i class="ph-duotone ph-cursor-click text-2xl text-purpura-500"></i>
                        </div>
                        <p class="text-xs font-extrabold text-gray-700 dark:text-gray-200">Nenhum Input Selecionado</p>
                        <p class="text-[11px] text-gray-400 mt-1.5 leading-relaxed">
                            Clique em um <strong>Tipo de Input</strong> na coluna da esquerda para incluir um novo bloco, ou clique sobre um <strong>campo existente</strong> no centro para editar suas configurações.
                        </p>
                    </div>
                @endif
            </aside>
        </div>
    </fieldset>

    <!-- ========================================================================= -->
    <!-- NAVIGATION DRAWER: CONFIGURAÇÕES GLOBAIS DO FORMULÁRIO                    -->
    <!-- ========================================================================= -->
    <div class="relative z-[150]" role="dialog" aria-modal="true" x-show="$wire.drawerFormSettingsAberto" x-cloak>
        <div x-show="$wire.drawerFormSettingsAberto" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="$wire.drawerFormSettingsAberto"
                         x-transition:enter="transform transition ease-in-out duration-300"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-300"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="pointer-events-auto w-screen max-w-md h-[100dvh] flex flex-col">
                         
                        <form wire:submit.prevent="salvarFormSettings" class="flex flex-col h-full bg-white shadow-2xl overflow-hidden">
                            <div class="bg-gray-900 text-white px-6 py-4 shrink-0 flex items-center justify-between">
                                <h2 class="text-sm font-bold flex items-center gap-2">
                                    <i class="ph-fill ph-magic-wand text-lg text-purpura-400"></i> Configurações e Aparência do Formulário
                                </h2>
                                <button type="button" @click="$wire.set('drawerFormSettingsAberto', false)" class="rounded-md text-gray-400 hover:text-white p-1.5 transition">
                                    <i class="ph-bold ph-x text-lg"></i>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                                @if($contextoTipo === 'formulario')
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        <label class="block text-xs font-bold text-gray-800 mb-1">URL Amigável (Link Público)</label>
                                        <div class="flex items-center mt-2">
                                            <span class="bg-gray-200 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-xs text-gray-600 font-mono">/f/</span>
                                            <input type="text" wire:model="slug" class="w-full text-xs rounded-r-md border-gray-300 focus:border-purpura-500" placeholder="meu-formulario">
                                        </div>
                                        @error('slug') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                                    <label class="block text-xs font-bold text-gray-800 mb-2">Imagem de Fundo da Página</label>
                                    <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition">
                                        <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                            <i class="ph ph-upload-simple text-2xl text-gray-400 mb-1"></i>
                                            <p class="text-[11px] text-gray-500"><span class="font-bold">Clique para carregar</span> (PNG/JPG)</p>
                                        </div>
                                        <input type="file" wire:model="bg_image_upload" class="hidden" accept="image/*">
                                    </label>

                                    @if($bg_image_upload || !empty($formSettings['bg_image']))
                                        <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-gray-200">
                                            <div class="col-span-2">
                                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Ajuste da Imagem</label>
                                                <select wire:model.live="formSettings.bg_size" class="w-full text-xs rounded-lg border-gray-300 bg-white">
                                                    <option value="cover">Preencher ecrã (Cover)</option>
                                                    <option value="contain">Encaixar (Contain)</option>
                                                    <option value="auto">Original</option>
                                                    <option value="repeat">Repetir Mosaico</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Cor Sobreposição</label>
                                                <input type="color" wire:model.live="formSettings.bg_color" class="w-full h-8 rounded border border-gray-300 cursor-pointer">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Opacidade (0 a 1)</label>
                                                <input type="number" step="0.1" min="0" max="1" wire:model.live="formSettings.bg_opacity" class="w-full text-xs rounded-lg border-gray-300 bg-white">
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-3">
                                    <label class="block text-xs font-bold text-gray-800 mb-2">Cor do Formulário</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Fundo</label>
                                            <input type="color" wire:model.live="formSettings.form_bg_color" class="w-full h-8 rounded border border-gray-300 cursor-pointer">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Texto</label>
                                            <input type="color" wire:model.live="formSettings.form_text_color" class="w-full h-8 rounded border border-gray-300 cursor-pointer">
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-4 dark:bg-gray-800 dark:border-gray-700">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 uppercase">Layout do Cartão</h4>
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Largura Central</label>
                                        <select wire:model.live="formSettings.form_width" class="w-full text-xs rounded-lg border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                            <option value="max-w-4xl">Largo (Padrão Grid)</option>
                                            <option value="max-w-2xl">Médio</option>
                                            <option value="max-w-lg">Estreito</option>
                                        </select>
                                    </div>
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" wire:model.live="formSettings.translucent_card" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-600">
                                        <div class="text-xs">
                                            <span class="font-bold text-gray-900 dark:text-white block">Cartão Translúcido (Efeito Vidro)</span>
                                            <span class="text-[10px] text-gray-500">Fundo branco semi-transparente com blur.</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-3 border-b border-gray-200 dark:border-gray-700 pb-2 uppercase">Regras de Negócio</h4>
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" wire:model.live="formSettings.use_vacancy_limit" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-900 dark:border-gray-600">
                                        <div class="text-xs">
                                            <span class="font-bold text-gray-900 dark:text-white block">Ativar Trava de Vagas</span>
                                            <span class="text-[10px] text-gray-500">Oculta Unidades/Cursos/Turnos com vagas esgotadas.</span>
                                        </div>
                                    </label>
                                </div>

                                @if($contextoTipo === 'ciclo' && $isCicloCustomizado)
                                    <div class="bg-indigo-50/60 dark:bg-indigo-900/20 p-5 rounded-xl border border-indigo-200 dark:border-indigo-800 space-y-3.5">
                                        <div class="border-b border-indigo-200 dark:border-indigo-800 pb-2">
                                            <h4 class="text-xs font-extrabold text-indigo-900 dark:text-indigo-300 uppercase flex items-center gap-1.5">
                                                <i class="ph-fill ph-sliders-horizontal text-indigo-600"></i> Campos Fixos (Projeto Customizado)
                                            </h4>
                                            <p class="text-[10px] text-indigo-700 dark:text-indigo-400 mt-0.5">
                                                Escolha quais blocos padrão do sistema devem aparecer na 1ª Página deste projeto:
                                            </p>
                                        </div>

                                        <label class="flex items-start gap-2.5 cursor-pointer">
                                            <input type="checkbox" wire:model.live="formSettings.enable_fixed_personal" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-indigo-600 focus:ring-indigo-500">
                                            <div class="text-xs">
                                                <span class="font-bold text-gray-900 dark:text-white block">Exibir Dados Pessoais Fixos</span>
                                                <span class="text-[10px] text-gray-500">Nome, CPF, Nascimento, E-mail, Celular e PcD.</span>
                                            </div>
                                        </label>

                                        <label class="flex items-start gap-2.5 cursor-pointer">
                                            <input type="checkbox" wire:model.live="formSettings.enable_fixed_address" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-indigo-600 focus:ring-indigo-500">
                                            <div class="text-xs">
                                                <span class="font-bold text-gray-900 dark:text-white block">Exibir Endereço Fixo (CEP)</span>
                                                <span class="text-[10px] text-gray-500">CEP, Logradouro, Número, Bairro, Cidade, UF e Região.</span>
                                            </div>
                                        </label>

                                        <label class="flex items-start gap-2.5 cursor-pointer">
                                            <input type="checkbox" wire:model.live="formSettings.enable_fixed_course" class="mt-0.5 w-4 h-4 border-gray-300 rounded text-indigo-600 focus:ring-indigo-500">
                                            <div class="text-xs">
                                                <span class="font-bold text-gray-900 dark:text-white block">Exibir Seleção de Curso (Unidade / Curso / Turno)</span>
                                                <span class="text-[10px] text-gray-500">Se desmarcado, oculta os selects nativos de Unidade, Curso e Turno.</span>
                                            </div>
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 shrink-0">
                                <button type="submit" class="w-full bg-purpura-600 hover:bg-purpura-700 text-white py-2.5 rounded-lg text-xs font-bold shadow-sm transition flex justify-center items-center gap-2 border-none">
                                    <i class="ph-bold ph-check text-base"></i> Guardar Aparência do Formulário
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE GESTÃO DE FASES TEMPORAIS --}}
    @if($modalFasesAberto)
        <div class="fixed inset-0 z-[250] flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden backdrop-blur-sm">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 shadow-sm shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-calendar-check text-purpura-600"></i> Gerenciador de Fases e Liberação
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Agrupe páginas do formulário em fases, defina datas de liberação automática e configure disparos de e-mail.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="adicionarFase" class="btn btn--secondary btn--medium bg-white dark:bg-gray-700 dark:text-white dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <i class="ph-bold ph-plus text-purpura-600 dark:text-purpura-400"></i> Nova Fase
                    </button>
                    <button type="button" wire:click="salvarFases" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm">
                        <i class="ph-bold ph-floppy-disk"></i> Salvar Tudo
                    </button>
                    <button type="button" wire:click="$set('modalFasesAberto', false)" class="text-gray-400 hover:text-red-500 transition ml-2">
                        <i class="ph-bold ph-x text-2xl"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4">
                @forelse($fasesConfig as $index => $fase)
                    <div wire:key="fase-item-{{ $index }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm transition-colors hover:border-purpura-300 dark:hover:border-purpura-600">
                        <div class="flex flex-col xl:flex-row gap-5">
                            
                            {{-- Nome e Páginas --}}
                            <div class="flex-1 space-y-4 border-b xl:border-b-0 xl:border-r border-gray-100 dark:border-gray-700 pb-4 xl:pb-0 xl:pr-5">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome da Fase <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="fasesConfig.{{ $index }}.nome" placeholder="Ex: Fase 2 - Envio de Documentos" class="w-full text-sm font-bold border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg focus:ring-purpura-500 focus:border-purpura-500">
                                    @error('fasesConfig.'.$index.'.nome') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">Quais páginas compõem esta fase? <span class="text-red-500">*</span></label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($paginasDisponiveis as $pag)
                                            @php
                                                $paginasSelecionadas = array_map('strval', $fasesConfig[$index]['paginas'] ?? []);
                                                $estaMarcada = in_array((string) $pag, $paginasSelecionadas, true);
                                            @endphp
                                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border cursor-pointer transition-colors {{ $estaMarcada ? 'bg-purpura-50 border-purpura-300 text-purpura-700 dark:bg-purpura-900/40 dark:text-purpura-300 dark:border-purpura-700' : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-900 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                                <input type="checkbox" wire:model.live="fasesConfig.{{ $index }}.paginas" value="{{ (string) $pag }}" class="w-3.5 h-3.5 text-purpura-600 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 focus:ring-purpura-500">
                                                <span class="text-xs font-bold">Página {{ $pag }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('fasesConfig.'.$index.'.paginas') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Datas e Automação --}}
                            <div class="flex-1 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Abre em (Liberação) <span class="text-red-500">*</span></label>
                                        <input type="datetime-local" wire:model="fasesConfig.{{ $index }}.data_liberacao" class="w-full text-xs font-medium border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg focus:ring-purpura-500 focus:border-purpura-500">
                                        @error('fasesConfig.'.$index.'.data_liberacao') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-orange-500">Encerra em (Opcional)</label>
                                        <input type="datetime-local" wire:model="fasesConfig.{{ $index }}.data_encerramento" class="w-full text-xs font-medium border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg focus:ring-purpura-500 focus:border-purpura-500">
                                    </div>
                                </div>

                                <div class="bg-blue-50/50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 p-3 rounded-lg">
                                    <label class="block text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase mb-1 flex items-center gap-1.5">
                                        <i class="ph-bold ph-envelope-simple-open"></i> E-mail Automático de Aviso de Liberação (Opcional)
                                    </label>
                                    <select wire:model="fasesConfig.{{ $index }}.email_template_id" class="w-full text-xs font-medium border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg focus:ring-purpura-500 focus:border-purpura-500">
                                        <option value="">Nenhum e-mail de aviso...</option>
                                        @foreach($templatesDeEmail as $tId => $tNome)
                                            <option value="{{ $tId }}">{{ $tNome }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[9px] text-blue-500 dark:text-blue-300 mt-1.5 leading-tight">Se selecionado, o Cron Job disparará este e-mail no exato momento da "Data de Liberação" para os candidatos que alcançaram a fase anterior.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center xl:pl-4 border-t xl:border-t-0 xl:border-l border-gray-100 dark:border-gray-700 pt-4 xl:pt-0">
                                <button type="button" wire:click="removerFase({{ $index }})" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition" title="Excluir Fase">
                                    <i class="ph-bold ph-trash text-xl"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center p-12 text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-800/50">
                        <i class="ph-fill ph-calendar-plus text-5xl mb-3 text-gray-300 dark:text-gray-600"></i>
                        <p class="font-bold text-sm text-gray-600 dark:text-gray-400">Nenhuma fase temporal configurada.</p>
                        <p class="text-xs text-gray-500 mt-1">Clique em "+ Nova Fase" no canto superior direito para agendar liberações de páginas.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- POPUP DE SOLICITAÇÃO RECEBIDA (PARA O DONO DO LOCK) --}}
    @if(!$isLockedByOther && $hasTransferRequest)
        <div class="fixed bottom-6 right-6 z-[300] max-w-sm w-full bg-white dark:bg-gray-800 border-l-4 border-purpura-500 rounded-lg shadow-2xl p-5 animate-fade-in-up">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-purpura-100 dark:bg-purpura-900/30 text-purpura-600 rounded-full shrink-0">
                    <i class="ph-fill ph-hand-waving text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">Pedido de Edição</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                        O utilizador <strong>{{ $requesterName }}</strong> abriu este formulário e está a solicitar o controlo para efetuar edições. 
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1 italic">
                        Se aceitar, será redirecionado para o dashboard e o seu trabalho não guardado será perdido.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" wire:click="rejectTransfer" class="px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 text-xs font-bold transition">
                    Recusar e Continuar
                </button>
                <button type="button" wire:click="acceptTransfer" class="px-3 py-1.5 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white border-none text-xs font-bold transition">
                    Aceitar e Sair
                </button>
            </div>
        </div>
    @endif
</div>