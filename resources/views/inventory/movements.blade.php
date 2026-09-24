@extends('layouts.app')

@section('title', 'Stock Movements - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Stock Movements"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Stock Movements'],
    ]"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('inventory.movements') }}">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by product name or code..."
                       class="w-full pl-9 pr-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors whitespace-nowrap">
                    <span>Type</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="{{ request()->fullUrlWithQuery(['type' => '']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ !request('type') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        All Types
                    </a>
                    @foreach(['purchase', 'sale'] as $type)
                        <a href="{{ request()->fullUrlWithQuery(['type' => $type]) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('type') === $type ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                            {{ str_replace('_', ' ', ucfirst($type)) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="From">

            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="To">

            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                Search
            </button>

            <a href="{{ request()->boolean('all') ? request()->fullUrlWithQuery(['all' => null]) : request()->fullUrlWithQuery(['all' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors shadow-sm whitespace-nowrap {{ request()->boolean('all') ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100' }}">
                @if(request()->boolean('all'))
                    Paginate (6 per page)
                @else
                    Show in one page
                @endif
            </a>

            @if(request('search') || request('type') || request('date_from') || request('date_to'))
                <a href="{{ route('inventory.movements') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

@php
    $isAdmin = optional(auth()->user())->hasRole('admin');
    $isPaginated = $movements instanceof \Illuminate\Pagination\LengthAwarePaginator;
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($isAdmin)
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 bg-gray-50/50">
            <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Select all">
                    Select all
                </label>
                <p class="text-xs text-gray-500" id="selected-count">0 selected</p>
            </div>
            <button type="submit" form="movements-form" id="delete-selected-btn" disabled
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Delete Selected
            </button>
        </div>
    @endif

    <div class="overflow-x-auto">
        @if($isAdmin)
            <form method="POST" action="{{ route('inventory.movements.deleteSelected') }}" id="movements-form">
                @csrf
        @endif
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    @if($isAdmin)
                        <th scope="col" class="px-2.5 py-2.5 w-10"></th>
                    @endif
                    <th scope="col" class="px-2.5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                    <th scope="col" class="px-2.5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-2.5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-2.5 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-2.5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th scope="col" class="px-2.5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase from/Sales to</th>
                    <th scope="col" class="px-2.5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    @php
                        $isOut = in_array($movement->type, ['sale']);
                        $rowNum = $isPaginated ? $movements->firstItem() + $loop->index : $loop->index + 1;
                    @endphp
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        @if($isAdmin)
                            <td class="px-2.5 py-2.5">
                                <input type="checkbox" name="movement_ids[]" value="{{ $movement->id }}" class="movement-check rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                        @endif
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-gray-400">{{ $rowNum }}</td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-gray-500">
                            {{ $movement->created_at->format('d M Y') }}
                            <span class="text-xs text-gray-400 block">{{ $movement->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap">
                            <div>
                                <span class="font-medium text-gray-800">{{ $movement->product->name ?? '—' }}</span>
                                <span class="ml-1.5 text-xs text-gray-400 font-mono">{{ $movement->product->product_code ?? '' }}</span>
                            </div>
                        </td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-center">
                            <x-status-badge :label="$movement->type_label" :variant="$movement->type_color" />
                        </td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-right font-medium {{ $isOut ? 'text-red-600' : 'text-green-600' }}">
                            {{ $isOut ? '-' : '+' }}{{ $movement->quantity }} {{ $movement->product->unit ?? '' }}
                        </td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-gray-700">
                            @if($movement->party_name)
                                <span class="{{ $movement->type === 'purchase' ? 'text-green-700' : 'text-blue-700' }}">
                                    {{ $movement->type === 'purchase' ? 'From: ' : 'To: ' }}{{ $movement->party_name }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-2.5 py-2.5 whitespace-nowrap text-gray-500">{{ $movement->creator->name ?? '—' }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="$isAdmin ? 8 : 7" message="No stock movements found." description="Movements will appear here once you start processing purchases and sales." icon="arrows-right-left" />
                @endforelse
            </tbody>
        </table>
        @if($isAdmin)
            </form>
        @endif
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        @if($isPaginated)
            <p class="text-xs text-gray-500">
                Showing <span class="font-medium text-gray-700">{{ $movements->firstItem() ?? 0 }}</span>
                to <span class="font-medium text-gray-700">{{ $movements->lastItem() ?? 0 }}</span>
                of <span class="font-medium text-gray-700">{{ $movements->total() }}</span> total movements
            </p>
        @else
            <p class="text-xs text-gray-500">
                Showing all <span class="font-medium text-gray-700">{{ $movements->count() }}</span> movements on one page
            </p>
        @endif
    </div>
</div>

@if($isPaginated)
    <x-pagination :paginator="$movements" label="movements" />
@endif

@if($isAdmin)
<script>
    const selectAllEl = document.getElementById('select-all');
    const movementChecks = document.querySelectorAll('.movement-check');
    const deleteBtn = document.getElementById('delete-selected-btn');
    const countEl = document.getElementById('selected-count');

    function updateSelection() {
        const n = document.querySelectorAll('.movement-check:checked').length;
        if (countEl) countEl.textContent = n + ' selected';
        if (deleteBtn) deleteBtn.disabled = n === 0;
        if (selectAllEl) selectAllEl.checked = n > 0 && n === movementChecks.length;
    }

    if (selectAllEl) {
        selectAllEl.addEventListener('change', function (e) {
            movementChecks.forEach(function (c) { c.checked = e.target.checked; });
            updateSelection();
        });
    }
    movementChecks.forEach(function (c) { c.addEventListener('change', updateSelection); });
    updateSelection();

    document.getElementById('movements-form').addEventListener('submit', function (e) {
        const n = document.querySelectorAll('.movement-check:checked').length;
        if (n === 0) { e.preventDefault(); return; }
        if (!window.confirm('Delete ' + n + ' selected movement(s)? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
</script>
@endif
@endsection
