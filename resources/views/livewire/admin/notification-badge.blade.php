<!-- O wire:poll.30s faz com que o sino atualize sozinho a cada 30 segundos -->
<div wire:poll.30s="atualizarContador" class="relative inline-flex items-center">
    <a href="{{ route('solicitacoes.index') }}" class="p-2 text-gray-500 transition-colors rounded-full hover:bg-gray-100 hover:text-purpura-600 dark:hover:bg-gray-800 dark:text-gray-400 relative" title="Central de Solicitações">
        <i class="text-[22px] ph ph-bell"></i>
        
        @if($count > 0)
            <span class="absolute top-1.5 right-1.5 flex items-center justify-center w-4 h-4 text-[9px] font-bold text-white bg-red-500 border-[1.5px] border-white rounded-full dark:border-gray-900 animate-pulse">
                {{ $count > 99 ? '99+' : $count }}
            </span>
        @endif
    </a>
</div>