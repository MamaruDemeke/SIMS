@extends('layouts.app')

@section('title', 'Notifications - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Notifications"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Notifications'],
    ]"
/>

@if($notifications->count() > 0)
    <div class="mb-4 flex items-center justify-between gap-2 flex-wrap">
        <label class="inline-flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
            <input type="checkbox" id="select-all" onchange="toggleAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Select all">
            Select all
        </label>
        <div class="flex items-center gap-2 flex-wrap">
        <button type="button" onclick="deleteSelected()"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
            Delete Selected
        </button>
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Mark All as Read
            </button>
        </form>
        </div>
    </div>
@endif

<div class="space-y-3">
    @forelse($notifications as $notification)
        @php
            // Build the "take me to the action" link for this notification.
            // Purchase workflow alerts open the purchase page — but the route
            // differs by role (Inventory Manager uses inventory/purchases/*,
            // Officer & Finance use purchases/*). That page shows the correct
            // Approve / Receive / Edit button for the viewer's role & status.
            // Stock alerts open the stock-alerts page for that product.
            $target = null;
            if ($notification->purchase_id) {
                $target = Auth::user()->role?->slug === 'inventory-manager'
                    ? route('inventory.purchases.show', $notification->purchase_id)
                    : route('purchases.show', $notification->purchase_id);
            } elseif ($notification->sale_id) {
                $target = route('sales.show', $notification->sale_id);
            } elseif ($notification->product_id) {
                // A stock/purchase-request alert. Purchase Officers go to their
                // "Purchase Requests" form; everyone else goes to the stock-alerts page.
                $target = in_array(Auth::user()->role?->slug, ['purchase-officer', 'admin'])
                    ? route('stock-notifications.pending')
                    : route('stock-notifications.index');
            }
        @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 {{ $notification->is_read ? 'opacity-60' : '' }} {{ $target ? 'transition-colors hover:bg-blue-50/40' : '' }}">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 pt-1">
                    <input type="checkbox" class="notif-check rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $notification->id }}" title="Select">
                </div>
                <div class="flex-shrink-0 mt-0.5">
                    @if($notification->type === 'out_of_stock')
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'low_stock')
                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'approval')
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'rejection')
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'purchase')
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'sale')
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                    @else
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">{{ $notification->title }}</h3>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $notification->message }}</p>
                            @if($notification->sender)
                                <p class="text-xs text-gray-400 mt-1">
                                    From: {{ $notification->sender->name }}
                                </p>
                            @endif
                            @if($notification->product)
                                <p class="text-xs text-gray-400 mt-1">
                                    Product: {{ $notification->product->product_code }} | Stock: {{ $notification->product->name }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                            @if(!$notification->is_read)
                                <form method="POST" action="{{ route('notifications.markRead', $notification) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap">
                                        Mark read
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('notifications.delete', $notification) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this notification?')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if($target)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <a href="{{ $target }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-800">
                        Go to action
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <h3 class="text-sm font-medium text-gray-700">No notifications</h3>
            <p class="text-xs text-gray-500 mt-1">You're all caught up!</p>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <x-pagination :paginator="$notifications" label="notifications" />
@endif

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
    form.action = '{{ route('notifications.deleteSelected') }}';
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
