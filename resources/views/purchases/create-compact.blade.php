@extends('layouts.app')

@section('title', 'Create Purchase - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Create Purchase"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases', 'href' => route('purchases.index')],
        ['label' => 'Purchase Requests', 'href' => route('stock-notifications.pending')],
        ['label' => 'Create Purchase'],
    ]"
/>

<div class="max-w-5xl">
    <div class="flex flex-col lg:flex-row gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex-1 min-w-0">
        <form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
            @csrf
            <input type="hidden" name="stock_notification_id" value="{{ $preselected['notification_id'] }}">

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Product</p>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $product ? $product->name : '' }} <span class="font-mono text-xs text-gray-400">{{ $product ? $product->product_code : '' }}</span>
                        @if($product && $product->grade)
                            <span class="ml-1 text-xs text-blue-600 font-semibold">G{{ $product->grade }}</span>
                        @endif
                    </p>
                    <input type="hidden" name="items[0][product_id]" value="{{ $product ? $product->id : '' }}">
                    <input type="hidden" name="items[0][type]" value="{{ $product ? $product->grade : '' }}">
                    <input type="hidden" name="items[0][diameter]" value="{{ $product ? $product->diameter : '' }}">
                    <input type="hidden" name="items[0][size]" value="{{ $product ? $product->length : '' }}">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="qty" class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" name="items[0][quantity]" id="qty" min="1" value="{{ old('items.0.quantity', $preselected['suggested_qty'] ?? '') }}" placeholder="0" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('items.0.quantity') border-red-500 @enderror">
                        @error('items.0.quantity')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-1">Unit Price (ETB) <span class="text-red-500">*</span></label>
                        <input type="number" name="items[0][unit_cost]" id="unit_cost" min="0" step="0.01"
                               value="{{ old('items.0.unit_cost', $product ? $product->purchase_price : 0) }}" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('items.0.unit_cost') border-red-500 @enderror">
                        @error('items.0.unit_cost')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input type="text" name="notes" id="notes" value="{{ old('notes') }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Optional notes">
                </div>
            </div>

        </form>
    </div>

    <div class="lg:w-56 flex-shrink-0">
        <div class="lg:sticky lg:top-20 space-y-2">
            <button type="submit" form="purchase-form" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Save as Draft
            </button>
            <a href="{{ route('stock-notifications.pending') }}" class="inline-flex items-center justify-center gap-1.5 w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Cancel
            </a>
        </div>
    </div>
    </div>
</div>
@endsection