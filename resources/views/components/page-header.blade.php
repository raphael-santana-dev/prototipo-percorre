@props([
    'title',
    'icon' => null,
    'badge' => null,
    'breadcrumbs' => null,
    'metricas' => null,
    'subtitle' => null
])

<div class="relative mb-6 flex flex-col gap-4">
    
    @if (session()->has('sucesso') || session()->has('success'))
        <div class="flex items-center gap-2 p-3 text-sm font-medium rounded-lg shadow-sm text-emerald-800 bg-emerald-50 border border-emerald-200">
            <i class="text-lg ph-fill ph-check-circle"></i> {{ session('sucesso') ?? session('success') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="flex items-center gap-2 p-3 text-sm font-medium rounded-lg shadow-sm text-red-800 bg-red-50 border border-red-200">
            <i class="text-lg ph-fill ph-warning-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if($breadcrumbs)
        <x-breadcrumb :items="$breadcrumbs" />
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Título, Subtítulo e Barra de Pesquisa Integrada --}}
        <div class="flex flex-col md:flex-row md:items-center gap-4 lg:gap-6 flex-1 min-w-0">
            <div class="shrink-0">
                <h2 class="flex items-center gap-2 text-2xl font-semibold text-gray-900 dark:text-white tracking-tight">
                    {{ $title }}
                </h2>
                @if($subtitle)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            
            @if(isset($search))
                <div class="flex-1 max-w-md">
                    {{ $search }}
                </div>
            @endif
        </div>

        {{-- Botões de Ação --}}
        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if($metricas)
        <div class="mt-2">
            <x-summary-cards :metricas="$metricas" />
        </div>
    @endif

    {{-- Filtros (Estilo Chips Inline) --}}
    @if(isset($filters))
        <div {{ $filters->attributes->merge(['class' => 'flex flex-wrap items-center gap-2 pt-2']) }}>
            {{ $filters }}
        </div>
    @endif
</div>