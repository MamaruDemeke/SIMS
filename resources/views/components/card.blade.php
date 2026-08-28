@props([
    'title' => null,
    'description' => null,
])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($title)
        <div class="px-5 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">{{ $title }}</h3>
                    @if($description)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $description }}</p>
                    @endif
                </div>
                {{ $header ?? '' }}
            </div>
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
