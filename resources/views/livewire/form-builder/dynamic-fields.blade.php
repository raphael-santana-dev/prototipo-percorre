<div class="font-sans flex flex-col bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden w-full relative"
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
        body.navbar-oculta .js-topnav, body.navbar-oculta header, body.navbar-oculta .js-sidebar { display: none !important; }
        .builder-quill .ql-toolbar.ql-snow { padding: 4px 6px; border-radius: 0.5rem 0.5rem 0 0; border-color: #d1d5db; background: #f9fafb; }
        .builder-quill .ql-container.ql-snow { border-radius: 0 0 0.5rem 0.5rem; border-color: #d1d5db; background: #fff; font-family: inherit; font-size: 0.78rem; }
        .builder-quill .ql-editor { min-height: 56px; max-height: 140px; padding: 8px 10px; overflow-y: auto; }
        .builder-quill .ql-snow .ql-picker.ql-size { width: 74px; }
        .ql-size-small { font-size: 0.75em; } .ql-size-large { font-size: 1.25em; } .ql-size-huge { font-size: 1.6em; }
    </style>

    {{-- BLOQUEIO DE EDIÇÃO SIMULTÂNEA --}}
    @if($isLockedByOther)
        <div class="absolute inset-0 z-[200] bg-white/75 dark:bg-gray-900/80 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center space-y-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Formulário em Edição</h3>
                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                    O usuário <strong>{{ $lockOwnerName }}</strong> está editando este formulário neste momento.
                </p>
                @if($transferRejected)
                    <div class="p-2.5 bg-red-50 border border-red-200 rounded text-xs text-red-700 font-medium">
                        O usuário recusou a transferência e pediu para aguardar.
                    </div>
                @endif
                <div class="flex flex-col gap-2">
                    @php
                        $transferData = \Illuminate\Support\Facades\Cache::get($this->getTransferKey());
                        $isPending = $transferData && ($transferData['status'] ?? '') === 'pending' && ($transferData['requester_id'] ?? null) === auth()->id();
                    @endphp
                    @if($isPending)
                        <button type="button" class="btn btn--secondary btn--medium w-full" disabled>Aguardando resposta...</button>
                        <button type="button" wire:click="cancelTransferRequest" class="text-xs text-gray-500 hover:text-red-600 underline">Cancelar pedido</button>
                    @else
                        <button type="button" wire:click="requestControl" class="btn btn--primary btn--medium w-full">Solicitar Controle</button>
                        <a href="{{ route('dashboard') }}" class="btn btn--secondary btn--medium w-full">Voltar ao Dashboard</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <fieldset @if($isLockedByOther) disabled class="pointer-events-none opacity-60 blur-[2px]" @endif class="flex flex-col flex-1 h-full w-full overflow-hidden">
        
        {{-- BARRA SUPERIOR FIXA --}}
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 z-[60] shrink-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('formbuilder.hub') }}" onclick="document.body.classList.remove('modo-expandido', 'navbar-oculta')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition" title="Voltar">
                    <i class="ph ph-arrow-left"></i>
                </a>
                <div class="border-l border-gray-200 dark:border-gray-700 pl-3">
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-bold text-gray-900 dark:text-white">Construtor de Formulário</h1>
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[10px] font-medium rounded border border-gray-200 dark:border-gray-700">
                            {{ $badgeContexto }}
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $contextoNome }} • {{ $camposCadastrados->count() }} bloco(s)
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button" @click="modoExpandido = !modoExpandido; navbarOculta = modoExpandido;" class="px-2.5 py-1 text-xs font-medium rounded transition-colors" :class="modoExpandido ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-gray-300'">
                        <i class="ph" :class="modoExpandido ? 'ph-corners-in' : 'ph-arrows-out'"></i>
                        <span x-text="modoExpandido ? 'Compactar' : 'Expandir'" class="hidden xl:inline ml-1"></span>
                    </button>
                    <button type="button" x-show="modoExpandido" x-cloak @click="navbarOculta = !navbarOculta" class="px-2.5 py-1 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-300" :class="navbarOculta ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs' : ''">
                        <i class="ph" :class="navbarOculta ? 'ph-eye-slash' : 'ph-eye'"></i>
                        <span class="hidden xl:inline ml-1" x-text="navbarOculta ? 'Mostrar Menus' : 'Ocultar Menus'"></span>
                    </button>
                </div>

                <button type="button" wire:click="abrirModalFases" class="btn btn--secondary btn--small">
                    <i class="ph ph-calendar-plus"></i> Fases
                </button>

                @if(auth()->user()->hasAnyRole(['dev', 'admin']) || auth()->user()->can('formulario.testar') || auth()->user()->can('inscricao.criar'))
                    @php
                        $urlTeste = $contextoTipo === 'ciclo' ? url('/inscricao?modo=teste&ciclo_id=' . $contextoId) : url('/f/' . $slug . '?modo=teste');
                    @endphp
                    <a href="{{ $urlTeste }}" target="_blank" class="btn btn--secondary btn--small">
                        <i class="ph ph-flask"></i> <span class="hidden md:inline">Testar</span>
                    </a>
                @endif

                <button type="button" @click="$wire.set('drawerFormSettingsAberto', true)" class="btn btn--primary btn--small bg-gray-900 hover:bg-black border-none">
                    <i class="ph ph-sliders-horizontal"></i> Aparência
                </button>
            </div>
        </div>

        {{-- WORKSPACE EM 3 COLUNAS --}}
        <div class="flex flex-1 w-full overflow-hidden relative bg-gray-50 dark:bg-gray-950">
            @include('livewire.form-builder.partials.palette-sidebar')
            @include('livewire.form-builder.partials.canvas-preview')
            @include('livewire.form-builder.partials.inspector-sidebar')
        </div>
    </fieldset>

    @include('livewire.form-builder.partials.modals-drawers')
    @include('livewire.form-builder.partials.modal-condicoes-avancadas')
</div>