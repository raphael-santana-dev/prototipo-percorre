@props(['metricas' => []])

@if(count($metricas) > 0)
<div class="flex flex-wrap gap-4 w-full">
    @foreach($metricas as $metrica)
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 flex flex-col justify-center">
            
            <div class="flex items-center gap-2 mb-2 text-gray-500 dark:text-gray-400">
                @if(isset($metrica['icon']))
                    <div class="text-[16px]">
                        {!! $metrica['icon'] !!}
                    </div>
                @endif
                <p class="text-xs font-medium truncate" title="{{ $metrica['label'] }}">
                    {{ $metrica['label'] }}
                </p>
            </div>

            <div class="flex items-end gap-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none truncate" title="{{ $metrica['value'] }}">
                    {{ $metrica['value'] }}
                </p>
                
                {{-- Caso queira no futuro adicionar tendência (ex: +8.4%) como no mockup --}}
                @if(isset($metrica['trend']))
                    <span class="text-[10px] font-bold {{ $metrica['trend'] > 0 ? 'text-emerald-600' : 'text-red-500' }} flex items-center mb-0.5">
                        <i class="ph-bold ph-arrow-{{ $metrica['trend'] > 0 ? 'up' : 'down' }}-right mr-0.5"></i>
                        {{ abs($metrica['trend']) }}%
                    </span>
                @endif
            </div>

        </div>
    @endforeach
</div>
@endif