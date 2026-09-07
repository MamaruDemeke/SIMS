@extends('layouts.app')

@section('title', 'Inventory - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Inventory"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Stock'],
    ]"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('inventory.index') }}">
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
                    <span>Stock Status</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="{{ request()->fullUrlWithQuery(['stock_status' => '']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ !request('stock_status') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        All
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'good']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('stock_status') == 'good' ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        Good
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'low']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('stock_status') == 'low' ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        Low
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'out']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('stock_status') == 'out' ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        Out of Stock
                    </a>
                </div>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                Search
            </button>

            @if(request('search') || request('stock_status'))
                <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Minimum Stock</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventory as $item)
                    @php
                        $status = $item->stock_status;
                    @endphp
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $inventory->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div>
                                <span class="font-medium text-gray-800">{{ $item->product->name ?? '—' }}</span>
                                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $item->product->product_code ?? '' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($item->product?->grade)
                                <x-status-badge :label="'Grade ' . $item->product->grade" variant="info" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $item->product->category->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-medium {{ $status === 'out' ? 'text-red-600' : ($status === 'low' ? 'text-yellow-600' : 'text-gray-800') }}">
                            {{ $item->quantity }} {{ $item->product->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right text-gray-600">
                            {{ $item->minimum_stock }} {{ $item->product->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                            @if($status === 'good')
                                <x-status-badge label="Good" variant="success" />
                            @elseif($status === 'low')
                                <x-status-badge label="Low" variant="warning" />
                            @else
                                <x-status-badge label="Out of Stock" variant="danger" />
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-table-action :href="route('inventory.edit', $item)" icon="pencil" variant="primary" tooltip="Update Stock" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="8" message="No inventory records found." description="Products will appear here once added." icon="inbox" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $inventory->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $inventory->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $inventory->total() }}</span> items
        </p>
    </div>
</div>

<x-pagination :paginator="$inventory" label="items" />
@endsection
