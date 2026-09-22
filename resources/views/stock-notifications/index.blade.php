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
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Suggested Qty</th>
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
                                <input type="number" name="suggested_quantity" form="notify-form-{{ $product->id }}" min="1" value="" required placeholder="0" class="w-24 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form id="notify-form-{{ $product->id }}" method="POST" action="{{ route('stock-notifications.notify') }}" class="inline">
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
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Suggested Qty</th>
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
                                <input type="number" name="suggested_quantity" form="notify-form-{{ $product->id }}" min="1" value="" required placeholder="0" class="w-24 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form id="notify-form-{{ $product->id }}" method="POST" action="{{ route('stock-notifications.notify') }}" class="inline">
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
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-2 flex-wrap">
            <h3 class="text-sm font-semibold text-gray-700">Sent Notifications</h3>
            @if($sentNotifications->count() > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="deleteSelected()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    Delete Selected
                </button>
            </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-10">
                            @if($sentNotifications->count() > 0)
                            <input type="checkbox" id="select-all" onchange="toggleAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Select all">
                            @endif
                        </th>
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
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" class="notif-check rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $notif->id }}" title="Select">
                            </td>
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
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No notifications sent yet.</td>
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

<script>
function toggleAll() {
    const all = document.getElementById('select-all');
    document.querySelectorAll('.notif-check').forEach(cb => cb.checked = all.checked);
}

function deleteSelected() {
    const ids = Array.from(document.querySelectorAll('.notif-check:checked')).map(cb => cb.value);
    if (ids.length === 0) {
        alert('Select at least one notification.');
        return;
    }
    if (!confirm('Delete ' + ids.length + ' selected notification(s)?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('stock-notifications.deleteSelected') }}';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
