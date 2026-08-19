<div 
    x-data="{ 
        show: false, 
        message: '', 
        type: 'success', // 'success' ou 'error'
        timeout: null,
        
        init() {
            // 1. Escuta as sessões nativas do Laravel ao carregar a página
            @if(session()->has('sucesso'))
                this.dispararToast('{{ session('sucesso') }}', 'success');
            @endif
            
            @if(session()->has('erro'))
                this.dispararToast('{{ session('erro') }}', 'error');
            @endif
        },

        dispararToast(msg, type) {
            this.message = msg;
            this.type = type;
            this.show = true;
            
            // Limpa o tempo anterior se disparar dois seguidos
            clearTimeout(this.timeout);
            
            // Some automaticamente após 4 segundos
            this.timeout = setTimeout(() => { this.show = false; }, 4000);
        }
    }"
    
    {{-- 2. Escuta os eventos disparados pelo Livewire ($this->dispatch) --}}
    @sucesso.window="dispararToast($event.detail.msg || $event.detail[0], 'success')"
    @erro.window="dispararToast($event.detail.msg || $event.detail[0], 'error')"
    
    {{-- Animações de entrada e saída --}}
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    
    {{-- Design Flutuante no Canto Inferior Direito --}}
    class="fixed bottom-6 right-6 z-[200] flex items-center justify-between w-full max-w-sm p-4 space-x-4 text-white rounded-xl shadow-2xl overflow-hidden"
    :class="{ 'bg-green-600': type === 'success', 'bg-red-600': type === 'error' }"
    style="display: none;"
    x-cloak
>
    <!-- Barra de progresso visual (Opcional, charme extra) -->
    <div class="absolute bottom-0 left-0 h-1 bg-white/30 animate-pulse transition-all duration-1000" style="width: 100%"></div>

    <!-- Conteúdo -->
    <div class="flex items-center gap-3 relative z-10">
        <i class="text-2xl" :class="{ 'ph-fill ph-check-circle': type === 'success', 'ph-fill ph-warning-circle': type === 'error' }"></i>
        <span class="text-sm font-bold leading-tight" x-text="message"></span>
    </div>
    
    <!-- Botão Fechar Manual -->
    <button @click="show = false" class="relative z-10 p-1.5 transition-colors rounded-lg hover:bg-white/20 focus:outline-none">
        <i class="text-lg ph-bold ph-x"></i>
    </button>
</div>