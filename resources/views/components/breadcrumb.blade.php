@props(['items' => []])

<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        @foreach($items as $item)
            <li class="inline-flex items-center">
                @if(!$loop->first)
                    <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                @endif
                
                @if(!$loop->last)
                    <a href="{{ $item['url'] }}" class="text-sm font-medium text-gray-500 hover:text-indigo-600">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-sm font-bold text-gray-800 ml-1">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>