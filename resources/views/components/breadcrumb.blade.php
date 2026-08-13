@props(['items' => []])

<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center">
        @foreach($items as $item)
            <li class="inline-flex items-center">
                @if(!$loop->first)
                    <i class="ph ph-caret-right text-gray-400 mx-2 text-[10px]"></i>
                @endif
                
                @if(!$loop->last)
                    <a href="{{ $item['url'] }}" class="text-sm font-medium text-gray-500 hover:text-purpura-600 transition-colors">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-sm font-bold text-gray-900">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>