@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegação da Paginação" class="flex items-center justify-center space-x-2 mt-4">
        
        {{-- Botão Voltar (Seta) --}}
        @if ($paginator->onFirstPage())
            <span class="p-2 rounded-lg text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </span>
        @else
            <button wire:click="previousPage" wire:loading.attr="disabled" class="p-2 rounded-lg text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 hover:text-brand-purple transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
        @endif

        {{-- Números das Páginas (Ex: 1 2 3 ... 5 6) --}}
        @foreach ($elements as $element)
            {{-- Os 3 pontinhos --}}
            @if (is_string($element))
                <span class="px-4 py-2 text-gray-500 font-bold dark:text-gray-400">{{ $element }}</span>
            @endif

            {{-- Os Números --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-4 py-2 rounded-lg bg-brand-purple text-white font-bold shadow-sm">{{ $page }}</span>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-brand-purple font-medium transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Botão Avançar (Seta) --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage" wire:loading.attr="disabled" class="p-2 rounded-lg text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 hover:text-brand-purple transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        @else
            <span class="p-2 rounded-lg text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </span>
        @endif
    </nav>
@endif