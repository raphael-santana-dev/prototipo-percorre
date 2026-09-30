@props(['metricas' => []])

@if(isset($metricas['is_progress_view']))
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 mb-6">
        
        <div class="mb-4">
            <div class="flex items-baseline gap-2">
                <span class="text-[26px] font-black text-gray-700 dark:text-white leading-none">{{ $metricas['total_inscritos'] }}</span>
                <span class="text-xl font-bold text-gray-400">/ {{ $metricas['total_vagas'] }}</span>
            </div>
            <div class="text-[13px] font-bold text-gray-500 mt-1.5">Inscrições Base (vs. Vagas Ofertadas)</div>
        </div>
        
        {{-- Progress Bar Segmentada --}}
        @php $baseTotal = max($metricas['total_vagas'], $metricas['total_inscritos'], 1); @endphp
        <div class="flex h-3 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden gap-1 mb-6">
            @foreach($metricas['statuses'] as $st)
                @if($st['total'] > 0)
                    <div style="width: {{ ($st['total'] / $baseTotal) * 100 }}%; background-color: {{ $st['cor'] }}" class="h-full rounded-full transition-all duration-500" title="{{ $st['nome'] }}: {{ $st['total'] }}"></div>
                @endif
            @endforeach
        </div>

        <div class="w-full h-px bg-gray-100 dark:bg-gray-800 mb-6"></div>

        {{-- Status Grid em Rolagem Horizontal (3 Linhas de Altura) --}}
        <div class="overflow-x-auto custom-scrollbar pb-2">
            <div class="grid grid-rows-3 grid-flow-col gap-x-12 gap-y-6 w-max min-w-full">
                @foreach($metricas['statuses'] as $st)
                    <div class="flex items-center gap-4 w-56 shrink-0">
                        <div class="w-1.5 h-10 rounded-full shrink-0" style="background-color: {{ $st['cor'] }}"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-bold text-gray-700 dark:text-gray-200 truncate" title="{{ $st['nome'] }}">{{ $st['nome'] }}</div>
                            <div class="text-[11px] font-medium text-gray-500 truncate">{{ $st['percent'] }}% do total de vagas</div>
                        </div>
                        <div class="text-xl font-black text-gray-900 dark:text-white shrink-0">{{ sprintf('%02d', $st['total']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
@elseif(count($metricas) > 0)
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
                </div>

            </div>
        @endforeach
    </div>
@endif