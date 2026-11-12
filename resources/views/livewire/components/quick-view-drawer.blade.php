<div x-data="{ open: false }" 
     x-on:show-quick-view-drawer.window="open = true" 
     x-on:close-quick-view-drawer.window="open = false"
     @keydown.escape.window="open = false"
     x-cloak>

    <template x-teleport="body">
        <div x-show="open"
             class="fixed inset-0 z-[999] overflow-hidden" 
             aria-labelledby="slide-over-title" 
             role="dialog" 
             aria-modal="true"
             x-cloak>

            {{-- BACKDROP ESCURO --}}
            <div x-show="open" 
                 x-transition:enter="ease-out duration-200" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-150" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 aria-hidden="true">
            </div>

            @php
                $maxWidthClass = match($maxWidth ?? 'md') {
                    'sm'    => 'sm:max-w-sm',
                    'md'    => 'sm:max-w-md',
                    'lg'    => 'sm:max-w-lg',
                    'xl'    => 'sm:max-w-xl',
                    '2xl'   => 'sm:max-w-2xl',
                    '3xl'   => 'sm:max-w-3xl',
                    '4xl'   => 'sm:max-w-4xl',
                    default => 'sm:max-w-md',
                };
            @endphp

            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
                <div x-show="open"
                     x-transition:enter="transform transition ease-out duration-250"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen {{ $maxWidthClass }} pointer-events-auto">
                     
                    <div class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-2xl border-l border-gray-200 dark:border-gray-700">
                        
                        {{-- HEADER MINIMALISTA DO DRAWER --}}
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0 flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate" id="slide-over-title">
                                    {{ $title }}
                                </h2>
                                @if($subtitle)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $subtitle }}</p>
                                @endif
                            </div>
                            
                            <button @click="open = false" type="button" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700 transition">
                                <i class="text-lg ph ph-x"></i>
                            </button>
                        </div>

                        {{-- CONTEÚDO --}}
                        <div class="relative flex-1 px-6 py-5 overflow-y-auto custom-scrollbar">
                            <div class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                @forelse($data as $key => $value)
                                    <div class="py-3.5 first:pt-0 last:pb-0">
                                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                            {{ $key }}
                                        </dt>
                                        <dd class="mt-1.5 text-sm text-gray-900 dark:text-gray-100">
                                            {!! $value !!}
                                        </dd>
                                    </div>
                                @empty
                                    <div class="py-8 text-center text-xs text-gray-400">Nenhum detalhe disponível.</div>
                                @endforelse
                            </div>
                        </div>
                        
                        {{-- FOOTER --}}
                        <div class="flex shrink-0 justify-end px-6 py-3.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <button @click="open = false" type="button" class="btn btn--secondary btn--small">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>