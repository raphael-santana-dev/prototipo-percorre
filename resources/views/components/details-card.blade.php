@props([
    'title',
    'subtitle' => null,
    'backUrl' => '#',
    'backLabel' => 'Voltar à Lista',
    'avatarInitials' => 'US',
    'itemName' => '',
    'itemDescription' => '',
])

<div class="font-sans mb-8">
    
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-800 text-sm font-bold rounded-lg border border-gray-200 transition-colors shadow-sm shrink-0">
            <i class="ph ph-arrow-left text-lg"></i> {{ $backLabel }}
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        
        <div class="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-extrabold text-xl shrink-0">
                    {{ $avatarInitials }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $itemName }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $itemDescription }}</p>
                </div>
            </div>

            @if(isset($badge))
                <div class="shrink-0">
                    {{ $badge }}
                </div>
            @endif
        </div>

        <div class="border-t border-gray-100"></div>

        <div class="p-6 bg-white">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                {{ $slot }}
            </div>
        </div>

    </div>
</div>