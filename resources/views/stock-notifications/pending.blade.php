@extends('layouts.app')

@section('title', 'Purchase Requests - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Purchase Requests"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases'],
        ['label' => 'Purchase Requests'],
    ]"
/>

<div class="space-y-6">
    @if($notifications->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-blue-50 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="text-sm font-semibold text-blue-700">Pending Stock Notifications from Inventory Manager</h3>
                <p class="text-xs text-blue-600">Tick the requests you want, then create ONE purchase order for all of them. Create a purchase order for each notification to fulfill the request</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="createPurchaseForSelected()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Create Purchase for Selected
                </button>
                <button type="button" onclick="clearSelection()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancel
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-10">
                            <input type="checkbox" id="select-all" onchange="toggleAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Select all">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Current Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Min Required</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notified By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notif)
                        <tr class="border-b border-gray-100 {{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : '' }}">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" class="notif-check rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $notif->id }}">
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $notifications->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $notif->product->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :label="$notif->type_label" :variant="$notif->type_variant" />
                            </td>
                            <td class="px-4 py-3 text-right {{ $notif->type === 'out_of_stock' ? 'text-red-600 font-bold' : 'text-yellow-600 font-bold' }}">
                                {{ $notif->current_quantity }} {{ $notif->product->unit ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $notif->minimum_stock }} {{ $notif->product->unit ?? '' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $notif->notifier->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $notif->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('purchases.create') }}?notification={{ $notif->id }}"
                                   class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                    Create Purchase
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $notifications->links() }}
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        <h3 class="text-sm font-medium text-gray-800">No Pending Requests</h3>
        <p class="text-xs text-gray-500 mt-1">No stock notifications from Inventory Manager yet.<br>You'll see purchase requests here when they send notifications.</p>
    </div>
    @endif
</div>

<script>
function toggleAll() {
    const all = document.getElementById('select-all');
    document.querySelectorAll('.notif-check').forEach(cb => cb.checked = all.checked);
}

function clearSelection() {
    const all = document.getElementById('select-all');
    if (all) all.checked = false;
    document.querySelectorAll('.notif-check').forEach(cb => cb.checked = false);
}

function createPurchaseForSelected() {
    const ids = Array.from(document.querySelectorAll('.notif-check:checked')).map(cb => cb.value);
    if (ids.length === 0) {
        alert('Select at least one purchase request.');
        return;
    }
    const qs = ids.map(id => 'notifications[]=' + encodeURIComponent(id)).join('&');
    window.location.href = '{{ route('purchases.create') }}' + '?' + qs;
}
</script>
@endsection
