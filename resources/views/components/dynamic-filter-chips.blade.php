@props([
    'chips' => [],
    'showToggleSidebar' => false,
    'showToggleDrawer' => false,
    'painelAberto' => true,
])

<div class="flex flex-wrap items-center gap-2 w-full">
    @if($showToggleSidebar)
        <button type="button"
                wire:click="$toggle('painelFiltrosAberto')"
                class="inline-flex items-center gap-2 px-3 py-1.5 h-9 rounded-lg border text-xs font-bold transition shadow-2xs {{ $painelAberto ? 'bg-purpura-50 border-purpura-200 text-purpura-700 dark:bg-purpura-900/30 dark:border-purpura-800 dark:text-purpura-300' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200' }}">
            <i class="ph-bold {{ $painelAberto ? 'ph-Sidebar-simple' : 'ph-funnel' }} text-sm"></i>
            <span>{{ $painelAberto ? 'Ocultar Filtros' : 'Mostrar Filtros' }}</span>
            @if(count($chips) > 0)
                <span class="px-1.5 py-0.2 rounded-full bg-purpura-600 text-white text-[10px]">{{ count($chips) }}</span>
            @endif
        </button>
    @endif

    @if($showToggleDrawer)
        <button type="button"
                @click="$wire.set('drawerFiltrosAberto', true)"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 h-9 rounded-lg border border-purpura-200 bg-purpura-50 hover:bg-purpura-100 text-purpura-700 dark:bg-purpura-900/30 dark:border-purpura-800 dark:text-purpura-300 text-xs font-bold transition shadow-2xs">
            <i class="ph-bold ph-funnel text-sm"></i>
            <span>Filtros Avançados</span>
            @if(count($chips) > 0)
                <span class="px-1.5 py-0.2 rounded-full bg-purpura-600 text-white text-[10px]">{{ count($chips) }}</span>
            @endif
        </button>
    @endif

    @foreach($chips as $chip)
        <div class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs shadow-2xs animate-fade-in">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ $chip['group'] }}:</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 max-w-[180px] truncate" title="{{ $chip['label'] }}">{{ $chip['label'] }}</span>
            <button type="button"
                    wire:click="removerFiltroItem('{{ $chip['key'] }}', {{ $chip['value'] !== null ? "'" . addslashes($chip['value']) . "'" : 'null' }})"
                    class="ml-0.5 text-indigo-400 hover:text-red-500 transition flex items-center justify-center">
                <i class="ph-bold ph-x text-xs"></i>
            </button>
        </div>
    @endforeach

    @if(count($chips) > 0)
        <button type="button"
                wire:click="limparTodosFiltrosAvancados"
                class="text-xs font-bold text-gray-400 hover:text-red-600 transition px-2 h-8 inline-flex items-center gap-1">
            <i class="ph-bold ph-trash"></i> Limpar Todos
        </button>
    @endif
</div>