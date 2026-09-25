@props([
    'title',
    'breadcrumbs' => [],
    'actionLabel' => null,
    'actionHref' => null,
    'actionIcon' => 'plus',
])

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 after:block after:h-1 after:w-10 after:mt-1.5 after:rounded-full after:bg-gradient-to-r after:from-blue-600 after:to-blue-400">{{ $title }}</h1>
            @if(!empty($breadcrumbs))
                <nav class="mt-1.5 flex items-center gap-1.5 text-sm text-gray-500">
                    @foreach($breadcrumbs as $index => $crumb)
                        @if($index > 0)
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        @endif
                        @if(isset($crumb['href']) && $crumb['href'])
                            <a href="{{ $crumb['href'] }}" class="hover:text-blue-600 transition-colors">{{ $crumb['label'] }}</a>
                        @else
                            <span class="{{ $index === count($breadcrumbs) - 1 ? 'text-gray-800 font-medium' : '' }}">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            @endif
        </div>

        @if($actionLabel && $actionHref)
            <a href="{{ $actionHref }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all shadow-sm hover:-translate-y-0.5 hover:shadow-md">
                @if($actionIcon === 'plus')
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                @elseif($actionIcon === 'download')
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                @endif
                {{ $actionLabel }}
            </a>
        @endif

        {{ $actions ?? '' }}
    </div>
</div>
