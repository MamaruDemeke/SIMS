@extends('layouts.app')

@section('title', 'Purchase Approvals - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Purchases Awaiting Approval"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Finance'],
        ['label' => 'Approvals'],
    ]"
/>

<x-search-filter
    search="{{ request('search') }}"
    search-placeholder="Search by reference, receipt, or supplier..."
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Products &amp; Grades</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Qty</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $purchases->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $purchase->reference_number }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $purchase->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3.5">
                            @php $itemCount = $purchase->items_count ?? $purchase->items->count(); @endphp
                            @if($itemCount === 0)
                                <span class="text-gray-400">—</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($purchase->items->take(4) as $item)
                                        <span class="inline-flex items-center text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-md px-2 py-0.5">
                                            {{ $item->product->name ?? '—' }}
                                            @if($item->product?->grade)
                                                <span class="ml-1 text-blue-600 font-semibold">G{{ $item->product->grade }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                    @if($itemCount > 4)
                                        <span class="inline-flex items-center text-xs text-gray-500 px-1">+{{ $itemCount - 4 }} more</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->total_quantity ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('purchases.receipts.show', $purchase) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center">
                            <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-800">No purchases awaiting approval</p>
                            <p class="text-xs text-gray-500 mt-1">All purchases have been processed.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $purchases->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $purchases->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $purchases->total() }}</span> receipts
        </p>
    </div>
</div>

<x-pagination :paginator="$purchases" label="receipts" />
@endsection
