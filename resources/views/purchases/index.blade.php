@extends('layouts.app')

@section('title', 'Purchases - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Purchases"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases'],
    ]"
    @if(Auth::user()->role?->slug === 'purchase-officer')
    action-label="Create Purchase"
    :action-href="route('purchases.create')"
    @endif
/>

<x-search-filter
    search="{{ request('search') }}"
    search-placeholder="Search by reference, receipt, or supplier..."
    :filters="[
        [
            'label' => 'Status',
            'name' => 'status',
            'options' => [
                ['label' => 'All', 'value' => ''],
                ['label' => 'Draft', 'value' => 'draft'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Received', 'value' => 'received'],
                ['label' => 'Rejected', 'value' => 'rejected'],
            ],
        ],
    ]"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Receipt</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Qty</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    @php $role = Auth::user()->role?->slug; @endphp
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $purchases->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $purchase->reference_number }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->receipt_number ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $purchase->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->total_quantity ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-status-badge :label="$purchase->status_label" :variant="$purchase->status_variant" />
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-table-action :href="route('purchases.show', $purchase)" icon="eye" variant="info" tooltip="View" />

                                {{-- Purchase Officer: draft actions --}}
                                @if($purchase->status === 'draft' && $role === 'purchase-officer')
                                    <x-table-action :href="route('purchases.edit', $purchase)" icon="pencil" variant="primary" tooltip="Edit" />
                                    <x-table-action :href="route('purchases.destroy', $purchase)" icon="trash" variant="danger" method="DELETE" confirm confirm-message="Delete this purchase?" tooltip="Delete" />
                                    <form method="POST" action="{{ route('purchases.submit', $purchase) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Submit for Processing">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </button>
                                    </form>
                                @endif

                                {{-- Inventory Manager: receive approved --}}
                                @if($purchase->status === 'approved' && $role === 'inventory-manager')
                                    <form method="POST" action="{{ route('purchases.receive', $purchase) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Mark as Received">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('purchases.reject', $purchase) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Reject">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                @endif

                                {{-- Finance: approve/reject pending --}}
                                @if($purchase->status === 'pending' && $role === 'finance')
                                    <form method="POST" action="{{ route('purchases.approve', $purchase) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Approve">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('purchases.financeReject', $purchase) }}" class="inline" onsubmit="return confirm('Reject this purchase?')">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Reject">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="9" message="No purchases found." description="No purchases available." icon="search" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $purchases->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $purchases->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $purchases->total() }}</span> purchases
        </p>
    </div>
</div>

<x-pagination :paginator="$purchases" label="purchases" />
@endsection
