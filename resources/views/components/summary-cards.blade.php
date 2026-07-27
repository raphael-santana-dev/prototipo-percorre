@props(['metricas' => []])

@if(count($metricas) > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach($metricas as $metrica)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex items-center justify-between transition-colors duration-300">
            <div>
                <p class="text-xs font-bold {{ $metrica['color_text'] ?? 'text-gray-500 dark:text-gray-400' }} uppercase tracking-wider">
                    {{ $metrica['label'] }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ $metrica['value'] }}
                </p>
            </div>
            @if(isset($metrica['icon']))
                <div class="p-3 rounded-lg {{ $metrica['color_bg'] ?? 'bg-gray-50 dark:bg-gray-700 text-gray-400' }}">
                    {!! $metrica['icon'] !!}
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif