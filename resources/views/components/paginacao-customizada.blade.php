@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegação da Paginação" class="flex items-center justify-center space-x-1">
        
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="w-8 h-8 flex items-center justify-center text-gray-300 dark:text-gray-600 cursor-not-allowed">
                <i class="ph-bold ph-caret-left text-sm"></i>
            </span>
        @else
            <button wire:click="previousPage" wire:loading.attr="disabled" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
                <i class="ph-bold ph-caret-left text-sm"></i>
            </button>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="w-8 h-8 flex items-center justify-center text-gray-400 text-sm font-medium">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-xs font-bold mx-0.5">
                            {{ $page }}
                        </span>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="w-7 h-7 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white text-xs font-medium transition mx-0.5">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage" wire:loading.attr="disabled" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
                <i class="ph-bold ph-caret-right text-sm"></i>
            </button>
        @else
            <span class="w-8 h-8 flex items-center justify-center text-gray-300 dark:text-gray-600 cursor-not-allowed">
                <i class="ph-bold ph-caret-right text-sm"></i>
            </span>
        @endif
    </nav>
@endif