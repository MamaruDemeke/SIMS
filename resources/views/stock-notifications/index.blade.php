@extends('layouts.app')

@section('title', 'Stock Alerts - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Stock Alerts"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Stock Alerts'],
    ]"
/>

<div class="space-y-6">
    {{-- Out of Stock --}}
    @if($outOfStockItems->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-red-50">
            <h3 class="text-sm font-semibold text-red-700">Out of Stock — Requires Immediate Purchase</h3>
            <p class="text-xs text-red-600">Notify Purchase Officer to create a purchase order</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Current</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Min Required</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outOfStockItems as $product)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $product->product_code }}</td>
                            <td class="px-4 py-3 text-right text-red-600 font-bold">0</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $product->inventory->minimum_stock ?? 0 }} {{ $product->unit }}</td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('stock-notifications.notify') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                        Notify Purchase Officer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Low Stock --}}
    @if($lowStockItems->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-yellow-50">
            <h3 class="text-sm font-semibold text-yellow-700">Low Stock — Needs Restocking</h3>
            <p class="text-xs text-yellow-600">Notify Purchase Officer to create a purchase order</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Current</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Min Required</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockItems as $product)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $product->product_code }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600 font-bold">{{ $product->inventory->quantity ?? 0 }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $product->inventory->minimum_stock ?? 0 }} {{ $product->unit }}</td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('stock-notifications.notify') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition-colors">
                                        Notify Purchase Officer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($outOfStockItems->count() === 0 && $lowStockItems->count() === 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-sm font-medium text-gray-800">All Stock Levels OK</h3>
        <p class="text-xs text-gray-500 mt-1">No products need restocking right now.</p>
    </div>
    @endif

    {{-- Sent Notifications History --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Sent Notifications</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sentNotifications as $notif)
                        <tr class="border-b border-gray-100 {{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : '' }}">
                            <td class="px-4 py-3 text-gray-500">{{ $notif->created_at->format('M d, Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $notif->product->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :label="$notif->type_label" :variant="$notif->type_variant" />
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $notif->current_quantity }}</td>
                            <td class="px-4 py-3">
                                @if($notif->fulfilled)
                                    <x-status-badge label="Fulfilled" variant="success" />
                                @else
                                    <x-status-badge label="Pending" variant="warning" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No notifications sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $sentNotifications->links() }}
        </div>
    </div>
</div>
@endsection
