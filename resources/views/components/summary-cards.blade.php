@props(['metricas' => []])

@if(count($metricas) > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach($metricas as $metrica)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex items-center justify-between transition-colors duration-300">
            
            {{-- Container restrito para impedir que o texto empurre o ícone --}}
            <div class="overflow-hidden pr-3 w-full">
                <p class="text-xs font-bold {{ $metrica['color_text'] ?? 'text-gray-500 dark:text-gray-400' }} uppercase tracking-wider truncate" title="{{ $metrica['label'] }}">
                    {{ $metrica['label'] }}
                </p>
                
                {{-- Aceita tamanho dinâmico via array, mas mantém o text-2xl como padrão para os números --}}
                <p class="{{ $metrica['value_size'] ?? 'text-2xl' }} font-bold text-gray-900 dark:text-white mt-1 truncate" title="{{ $metrica['value'] }}">
                    {{ $metrica['value'] }}
                </p>
            </div>

            @if(isset($metrica['icon']))
                {{-- flex-shrink-0 blinda o ícone contra achatamentos --}}
                <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center rounded-lg {{ $metrica['color_bg'] }}">
                    {!! $metrica['icon'] !!}
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif