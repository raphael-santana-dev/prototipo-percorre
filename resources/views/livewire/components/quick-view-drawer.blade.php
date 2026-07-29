<div x-data="{ open: false }" 
     x-on:show-quick-view-drawer.window="open = true" 
     x-on:close-quick-view-drawer.window="open = false"
     @keydown.escape.window="open = false"
     class="relative z-[100]" 
     aria-labelledby="slide-over-title" 
     role="dialog" 
     aria-modal="true"
     x-cloak>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        
        <!-- ========================================== -->
        <!-- OVERLAY ESCURO COM EFEITO BLUR             -->
        <!-- ========================================== -->
        <div x-show="open" 
             x-transition:enter="ease-in-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in-out duration-300" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm pointer-events-auto transition-opacity" 
             aria-hidden="true">
        </div>

        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none sm:pl-16">
                
                <!-- Painel Deslizante (O Navigation Drawer) -->
                <div x-show="open"
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-md pointer-events-auto">
                     
                    <div class="flex flex-col h-full bg-white shadow-2xl dark:bg-gray-800">
                        
                        <!-- Header do Drawer -->
                        <div class="px-6 py-6 bg-purpura-700 sm:px-8">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3 text-white">
                                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                                        <i class="text-2xl ph {{ $icon }}"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold leading-6" id="slide-over-title">
                                            {{ $title }}
                                        </h2>
                                        @if($subtitle)
                                            <p class="mt-1 text-sm text-purpura-100">{{ $subtitle }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Botão Fechar -->
                                <div class="flex items-center ml-3 h-7">
                                    <button @click="open = false" type="button" class="text-purpura-200 hover:text-white focus:outline-none transition-colors">
                                        <i class="text-2xl ph ph-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Corpo (Listagem Dinâmica dos Dados) -->
                        <div class="relative flex-1 px-6 py-6 overflow-y-auto sm:px-8">
                            <div class="space-y-6">
                                @forelse($data as $key => $value)
                                    <div>
                                        <!-- Chave (Nome do campo) -->
                                        <dt class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            {{ $key }}
                                        </dt>
                                        <!-- Valor do campo -->
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {!! $value !!}
                                        </dd>
                                        <!-- Linha divisória -->
                                        @if(!$loop->last)
                                            <div class="mt-4 border-b border-gray-100 dark:border-gray-700"></div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500 italic">Nenhum detalhe disponível.</div>
                                @endforelse
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="flex flex-shrink-0 justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <button @click="open = false" type="button" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white border-gray-300 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                                Fechar Painel
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>