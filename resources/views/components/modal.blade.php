@props(['title' => '', 'maxWidth' => 'md', 'closeMethod' => '$set(\'showModal\', false)'])

@php
    $maxWidthClass = match($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        default => 'sm:max-w-md',
    };
@endphp

<div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        
        <!-- Fundo escuro com Blur -->
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="{{ $closeMethod }}"></div>
        
        <!-- Truque para centralizar o modal no meio da tela -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <!-- Caixa do Modal -->
        <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle {{ $maxWidthClass }} sm:w-full sm:p-6 dark:bg-gray-800">
            
            @if($title)
                <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                    {{ $title }}
                </h3>
            @endif

            <!-- Onde o conteúdo do seu formulário vai aparecer -->
            {{ $slot }}

        </div>
    </div>
</div>