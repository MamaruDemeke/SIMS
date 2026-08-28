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
                    @foreach(['purchase', 'sale', 'customer_return', 'supplier_return', 'adjustment'] as $type)
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

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Note</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    @php
                        $isOut = in_array($movement->type, ['sale']);
                    @endphp
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $movements->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">
                            {{ $movement->created_at->format('d M Y') }}
                            <span class="text-xs text-gray-400 block">{{ $movement->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div>
                                <span class="font-medium text-gray-800">{{ $movement->product->name ?? '—' }}</span>
                                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $movement->product->product_code ?? '' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                            <x-status-badge :label="$movement->type_label" :variant="$movement->type_color" />
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-medium {{ $isOut ? 'text-red-600' : 'text-green-600' }}">
                            {{ $isOut ? '-' : '+' }}{{ $movement->quantity }} {{ $movement->product->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 max-w-[200px] truncate">{{ $movement->note ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $movement->creator->name ?? '—' }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7" message="No stock movements found." description="Movements will appear here once you start processing purchases and sales." icon="arrows-right-left" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $movements->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $movements->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $movements->total() }}</span> movements
        </p>
    </div>
</div>

<x-pagination :paginator="$movements" label="movements" />
@endsection
