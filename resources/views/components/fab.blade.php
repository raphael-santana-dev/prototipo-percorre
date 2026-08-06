@props([
    'actions' => [],
    'mainIcon' => 'ph-plus',
    'mainColor' => 'bg-purpura-500 hover:bg-purpura-600',
    'iconColor' => 'text-white'
])

<div x-data="{ open: false }" class="fixed bottom-8 right-8 z-50 flex flex-col items-end gap-3" @click.away="open = false">
    
    <!-- Menu Secundário (Ações) -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="flex flex-col items-end gap-3 mb-2" x-cloak>

        @foreach($actions as $action)
            <div class="flex items-center gap-3 group">
                <!-- Tooltip / Label -->
                <span class="bg-gray-800 text-white text-[11px] uppercase font-bold px-3 py-1.5 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                    {{ $action['label'] }}
                </span>

                <!-- Botão de Ação -->
                @if(isset($action['href']))
                    <a href="{{ $action['href'] }}" 
                       class="flex items-center justify-center w-11 h-11 bg-white border border-gray-200 text-gray-600 rounded-full shadow-sm hover:bg-gray-50 hover:text-purpura-600 transition-colors">
                        <i class="{{ $action['icon'] }} text-lg"></i>
                    </a>
                @elseif(isset($action['wire_click']))
                    <button type="button" 
                            wire:click="{{ $action['wire_click'] }}" 
                            @click="open = false" 
                            class="flex items-center justify-center w-11 h-11 bg-white border border-gray-200 text-gray-600 rounded-full shadow-sm hover:bg-gray-50 hover:text-purpura-600 transition-colors">
                        <i class="{{ $action['icon'] }} text-lg"></i>
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Botão Principal (Gatilho) -->
    <button type="button" 
            @click="open = !open" 
            class="flex items-center justify-center w-14 h-14 rounded-full shadow-xl transition-all duration-300 transform focus:outline-none {{ $mainColor }} {{ $iconColor }}" 
            :class="open ? 'rotate-45 !bg-gray-800' : ''">
        <i class="{{ $mainIcon }} text-2xl font-bold"></i>
    </button>
</div>