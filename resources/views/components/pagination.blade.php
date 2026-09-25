@props([
    'paginator',
    'label' => 'records',
])

@php
    // Build the page-number elements manually (Laravel exposes these only in its own
    // links() blade views, so we reconstruct them here for the custom component).
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $elements = [];

    $window = 3; // number of page links either side of the current page

    if ($last <= 1) {
        $elements = [];
    } elseif ($last <= 7) {
        // Few pages: show every number, keyed by page => its URL.
        $links = [];
        foreach (range(1, $last) as $page) {
            $links[$page] = $paginator->url($page);
        }
        $elements[] = $links;
    } else {
        // Many pages: show current window plus first/last with "..." separators.
        $pageRange = range(max(1, $current - $window), min($last, $current + $window));

        if ($pageRange[0] > 1) {
            $elements[] = [1 => $paginator->url(1)];
            if ($pageRange[0] > 2) {
                $elements[] = '...';
            }
        }

        $links = [];
        foreach ($pageRange as $page) {
            $links[$page] = $paginator->url($page);
        }
        $elements[] = $links;

        if ($pageRange[count($pageRange) - 1] < $last) {
            if ($pageRange[count($pageRange) - 1] < $last - 1) {
                $elements[] = '...';
            }
            $elements[] = [$last => $paginator->url($last)];
        }
    }
@endphp

@if($paginator->hasPages())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4 overflow-hidden">
        <div class="px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-200">
            {{-- Info --}}
            <div>
                <p class="text-xs text-gray-500">
                    Showing <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
                    to <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
                    of <span class="font-medium text-gray-700">{{ $paginator->total() }}</span> {{ $label }}
                </p>
            </div>

            {{-- Links --}}
            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-300 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"
                       class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                @endif

                {{-- Pages --}}
                @foreach($elements as $element)
                    @if(is_string($element))
                        <span class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400">{{ $element }}</span>
                    @endif

                    @if(is_array($element))
                        @foreach($element as $page => $url)
                            @if($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg shadow-md">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"
                       class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-300 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif
