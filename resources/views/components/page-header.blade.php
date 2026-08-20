@props([
    'title',
    'icon' => null,
    'badge' => null,
    'breadcrumbs' => null,
    'metricas' => null,
])

<div class="relative mb-6">
    
    <!-- 1. Alertas Globais Unificados -->
    @if (session()->has('sucesso') || session()->has('success'))
        <div class="flex items-center gap-2 p-4 mb-4 font-bold rounded-lg shadow-sm text-pistache-100 bg-pistache-500">
            <i class="text-xl ph ph-check-circle"></i> {{ session('sucesso') ?? session('success') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="flex items-center gap-2 p-4 mb-4 font-bold text-red-100 bg-red-500 rounded-lg shadow-sm">
            <i class="text-xl ph ph-warning"></i> {{ session('error') }}
        </div>
    @endif

    <!-- 2. Breadcrumbs -->
    @if($breadcrumbs)
        <x-breadcrumb :items="$breadcrumbs" />
    @endif

    <!-- 3. Título, Badge e Ações -->
    <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
                @if($icon) <i class="{{ $icon }} text-purpura-500"></i> @endif
                {{ $title }}
            </h2>

            @if($badge)
                <span class="hidden px-4 py-1.5 text-sm font-semibold border rounded-full md:inline-block bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800">
                    {{ $badge }}
                </span>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    <!-- 4. Cards de Métricas -->
    @if($metricas)
        <x-summary-cards :metricas="$metricas" />
    @endif

    <!-- 5. Barra de Filtros (Slot com CSS customizável) -->
    @if(isset($filters))
        <div {{ $filters->attributes->merge(['class' => 'py-4 mb-4 border-b border-gray-200 dark:border-gray-700']) }}>
            {{ $filters }}
        </div>
    @endif
</div>