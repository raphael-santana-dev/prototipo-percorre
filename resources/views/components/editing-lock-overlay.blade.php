@props([
    'isLockedByOther' => false,
    'lockOwnerName' => '',
    'transferRejected' => false,
    'hasTransferRequest' => false,
    'requesterName' => '',
    'transferKey' => '',
    'backUrl' => null,
    'backLabel' => 'Voltar para a Listagem',
    'isModal' => false,
    'contextoDescricao' => 'este registo',
])

<div x-data="{
        released: false,
        releaseLockOnLeave() {
            if (this.released || @js($isLockedByOther)) return;
            const type = $wire.resourceLockType;
            const id = $wire.resourceLockId;
            if (!type || !id || Number(id) <= 0) return;

            this.released = true;
            const data = new FormData();
            data.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}');
            data.append('type', type);
            data.append('id', id);

            if (navigator.sendBeacon) {
                navigator.sendBeacon('{{ route('session.release-edit-lock') }}', data);
            } else {
                fetch('{{ route('session.release-edit-lock') }}', { method: 'POST', body: data, keepalive: true });
            }
        }
     }"
     x-init="
        window.addEventListener('beforeunload', () => releaseLockOnLeave());
        window.addEventListener('pagehide', () => releaseLockOnLeave());
        document.addEventListener('livewire:navigating', () => releaseLockOnLeave());
     ">

    {{-- CORTINA DE BLOQUEIO QUANDO OUTRO USUÁRIO ESTÁ EDITANDO --}}
    @if($isLockedByOther)
        <div class="{{ $isModal ? 'absolute' : 'fixed' }} inset-0 z-[220] bg-white/70 dark:bg-gray-900/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-7 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-500"></div>

                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ph-fill ph-lock-key text-3xl"></i>
                </div>

                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white mb-1.5">Edição Simultânea Bloqueada</h3>
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-5 leading-relaxed">
                    O utilizador <strong>{{ $lockOwnerName }}</strong> está a editar {{ $contextoDescricao }} neste momento. Para evitar conflitos e perda de alterações, aguarde ou solicite o controlo.
                </p>

                @if($transferRejected)
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs font-bold text-red-700 dark:text-red-400">
                        <i class="ph-fill ph-x-circle mr-1 text-sm align-middle"></i>
                        O utilizador recusou a transferência e pediu para aguardar.
                    </div>
                @endif

                <div class="flex flex-col gap-2.5">
                    @php
                        $transferData = $transferKey ? \Illuminate\Support\Facades\Cache::get($transferKey) : null;
                        $isPending = $transferData && ($transferData['status'] ?? '') === 'pending' && ($transferData['requester_id'] ?? null) === auth()->id();
                    @endphp

                    @if($isPending)
                        <button type="button" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-gray-100 text-gray-500 cursor-not-allowed w-full flex justify-center items-center gap-2 border border-gray-200" disabled>
                            <i class="ph-bold ph-spinner animate-spin"></i> Aguardando resposta de {{ $lockOwnerName }}...
                        </button>
                        <button type="button" wire:click="cancelTransferRequest" class="text-xs font-bold text-gray-500 hover:text-red-500 underline mt-1 transition">
                            Cancelar Pedido
                        </button>
                    @else
                        <button type="button" wire:click="requestControl" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white shadow-sm w-full flex justify-center items-center gap-2 transition">
                            <i class="ph-bold ph-hand-raising text-sm"></i> Solicitar Controlo de Edição
                        </button>

                        @if($isModal)
                            <button type="button" wire:click="fecharModal" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 w-full transition">
                                Fechar Janela
                            </button>
                        @elseif($backUrl)
                            <a href="{{ $backUrl }}" @click="releaseLockOnLeave()" class="py-2.5 px-4 rounded-xl font-bold text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 w-full block transition">
                                {{ $backLabel }}
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- POPUP NO CANTO DA TELA QUANDO OUTRO USUÁRIO PEDE O CONTROLE PARA VOCÊ --}}
    @if(!$isLockedByOther && $hasTransferRequest)
        <div class="fixed bottom-6 right-6 z-[300] max-w-sm w-full bg-white dark:bg-gray-800 border-l-4 border-purpura-500 rounded-xl shadow-2xl p-5 animate-fade-in-up">
            <div class="flex items-start gap-3.5">
                <div class="p-2 bg-purpura-100 dark:bg-purpura-900/30 text-purpura-600 rounded-full shrink-0">
                    <i class="ph-fill ph-hand-waving text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">Pedido de Controlo</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                        <strong>{{ $requesterName }}</strong> abriu {{ $contextoDescricao }} e está a solicitar o controlo para editar.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" wire:click="rejectTransfer" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs font-bold transition">
                    Recusar e Continuar
                </button>
                <button type="button" wire:click="acceptTransfer" class="px-3 py-1.5 rounded-lg bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-bold transition">
                    Aceitar e Sair
                </button>
            </div>
        </div>
    @endif
</div>