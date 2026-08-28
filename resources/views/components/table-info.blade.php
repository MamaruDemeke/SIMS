@props([
    'total' => 0,
    'perPage' => 15,
    'currentPage' => 1,
    'label' => 'records',
])

@php
    $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
    $to = min($currentPage * $perPage, $total);
@endphp

<div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
    <p class="text-xs text-gray-500">
        Showing <span class="font-medium text-gray-700">{{ $from }}</span> to <span class="font-medium text-gray-700">{{ $to }}</span> of <span class="font-medium text-gray-700">{{ $total }}</span> {{ $label }}
    </p>
</div>
