@props([
    'id',
    'tipos' => [], // Ex: ['ciclo_dados' => 'Ciclo', 'ciclo' => 'Formulário', 'ciclo_regras' => 'Regras']
])

@php
    $locksAtivos = \App\Traits\WithEditingLock::obterLocksAtivos((int) $id, $tipos);
@endphp

@if(!empty($locksAtivos))
    <div class="inline-flex flex-col items-center gap-1">
        @foreach($locksAtivos as $lock)
            @php
                $corBadge = match($lock['type']) {
                    'ciclo'        => 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-950/40 dark:text-purpura-300 dark:border-purpura-800',
                    'ciclo_regras' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                    default        => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
                };
            @endphp
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border shadow-2xs whitespace-nowrap {{ $corBadge }}"
                  title="{{ $lock['user_name'] }} está a editar {{ $lock['context_label'] }} neste momento">
                <span class="w-1.5 h-1.5 rounded-full bg-current animate-ping"></span>
                <span>{{ $lock['context_label'] }}: {{ \Illuminate\Support\Str::words($lock['user_name'], 2, '') }}</span>
            </span>
        @endforeach
    </div>
@else
    <span class="text-[11px] text-gray-300 dark:text-gray-600 font-medium">—</span>
@endif