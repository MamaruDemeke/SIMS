@extends('layouts.app')

@section('title', 'Products - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Products"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Products'],
    ]"
    action-label="Add Product"
    :action-href="route('products.create')"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('products.index') }}">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, code, or description..."
                       class="w-full pl-9 pr-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    <span>Category</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="{{ request()->fullUrlWithQuery(['category_id' => '']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ !request('category_id') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        All Categories
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ request()->fullUrlWithQuery(['category_id' => $cat->id]) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('category_id') == $cat->id ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors whitespace-nowrap">
                    <span>Status</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="{{ request()->fullUrlWithQuery(['status' => '']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ !request('status') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        All
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => '1']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('status') == '1' ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        Active
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => '0']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request('status') == '0' ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        Inactive
                    </a>
                </div>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                Search
            </button>

            @if(request('search') || request('category_id') || request('status'))
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Selling</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $products->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-gray-600">{{ $product->product_code }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $product->name }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($product->grade)
                                <x-status-badge :label="$product->grade" variant="info" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $product->category->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $product->unit }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right text-gray-700">{{ number_format($product->purchase_price, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right text-gray-700">{{ number_format($product->selling_price, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                            @if($product->status)
                                <x-status-badge label="Active" variant="success" />
                            @else
                                <x-status-badge label="Inactive" variant="danger" />
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <form method="POST" action="{{ route('products.notifyPurchase', $product) }}" class="inline" onsubmit="return confirm('Notify the Purchase Officer to buy {{ addslashes($product->name) }}?')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Notify Purchase Officer to Buy">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                    </button>
                                </form>
                                <x-table-action :href="route('products.edit', $product)" icon="pencil" variant="primary" tooltip="Edit" />
                                <x-table-action :href="route('products.destroy', $product)" icon="trash" variant="danger" method="DELETE" confirm confirm-message="Delete this product?" tooltip="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="10" message="No products found." description="Create your first product to get started." icon="search">
                        <a href="{{ route('products.create') }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Product
                        </a>
                    </x-table-empty>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $products->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $products->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $products->total() }}</span> products
        </p>
    </div>
</div>

<x-pagination :paginator="$products" label="products" />
@endsection
