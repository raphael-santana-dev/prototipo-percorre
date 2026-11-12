@props([
    'title' => '',
    'subtitle' => null,
    'maxWidth' => '2xl',
    'closeMethod' => 'closeModal',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm'    => 'sm:max-w-sm',
        'md'    => 'sm:max-w-md',
        'lg'    => 'sm:max-w-lg',
        'xl'    => 'sm:max-w-xl',
        '2xl'   => 'sm:max-w-2xl',
        '3xl'   => 'sm:max-w-3xl',
        '4xl'   => 'sm:max-w-4xl',
        '5xl'   => 'sm:max-w-5xl',
        '6xl'   => 'sm:max-w-6xl',
        default => 'sm:max-w-2xl',
    };
@endphp

<div x-data
     @keydown.escape.window="$wire.{{ $closeMethod }}()"
     x-init="document.body.classList.add('overflow-hidden'); $cleanup(() => document.body.classList.remove('overflow-hidden'))">
    
    <template x-teleport="body">
        <div class="fixed inset-0 z-[999] flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
                 wire:click="{{ $closeMethod }}"></div>

            {{-- Painel do Modal --}}
            <div class="relative z-10 bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full {{ $maxWidthClass }} border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                
                @if($title)
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                            @if($subtitle)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $subtitle }}</p>
                            @endif
                        </div>
                        <button type="button"
                                wire:click="{{ $closeMethod }}"
                                class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1.5 rounded-lg transition">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar font-sans">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>