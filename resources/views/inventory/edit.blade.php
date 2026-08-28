@extends('layouts.app')

@section('title', 'Stock Settings - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Stock Settings"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Stock', 'href' => route('inventory.index')],
        ['label' => 'Settings'],
    ]"
/>

<div class="max-w-lg">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="mb-5 pb-5 border-b border-gray-200">
            <p class="text-sm text-gray-500">Product</p>
            <p class="text-lg font-semibold text-gray-800">{{ $inventory->product->name ?? '—' }}</p>
            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $inventory->product->product_code ?? '' }} | {{ $inventory->product->category->name ?? '' }}</p>
        </div>

        <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-700 font-medium">Current Stock: {{ $inventory->quantity }} {{ $inventory->product->unit ?? 'units' }}</p>
            <p class="text-xs text-blue-500 mt-1">Stock is updated automatically through sales and purchase processes.</p>
        </div>

        <form method="POST" action="{{ route('inventory.update', $inventory) }}">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock <span class="text-red-500">*</span></label>
                    <input type="number" name="minimum_stock" id="minimum_stock" value="{{ old('minimum_stock', $inventory->minimum_stock) }}" required min="0"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('minimum_stock') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Alert when stock falls below this number.</p>
                    @error('minimum_stock')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $qty = $inventory->quantity;
                    $min = old('minimum_stock', $inventory->minimum_stock);
                    if ($qty <= 0) { $previewStatus = 'out'; }
                    elseif ($qty < $min) { $previewStatus = 'low'; }
                    else { $previewStatus = 'good'; }
                @endphp
                <div class="p-3 rounded-lg {{ $previewStatus === 'good' ? 'bg-green-50 border border-green-200' : ($previewStatus === 'low' ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200') }}">
                    <p class="text-sm font-medium {{ $previewStatus === 'good' ? 'text-green-700' : ($previewStatus === 'low' ? 'text-yellow-700' : 'text-red-700') }}">
                        Preview: Stock {{ $previewStatus === 'good' ? 'Good' : ($previewStatus === 'low' ? 'Low' : 'Out of Stock') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-200">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    Update Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
