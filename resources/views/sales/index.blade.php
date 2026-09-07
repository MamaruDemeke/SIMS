@extends('layouts.app')

@section('title', 'Sales - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Sales"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Sales'],
    ]"
    @if(Auth::user()->role?->slug === 'sales')
    action-label="Create Sale"
    :action-href="route('sales.create')"
    @endif
/>

<x-search-filter
    search="{{ request('search') }}"
    search-placeholder="Search by reference or customer..."
    :filters="[
        [
            'label' => 'Status',
            'name' => 'status',
            'options' => [
                ['label' => 'All', 'value' => ''],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Qty</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $sales->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $sale->reference_number }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $sale->customer->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $sale->total_quantity }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-status-badge :label="$sale->status_label" :variant="$sale->status_variant" />
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $sale->sold_at?->format('M d, Y') ?? $sale->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-table-action :href="route('sales.show', $sale)" icon="eye" variant="info" tooltip="View" />

                                {{-- Finance: approve/reject pending sales --}}
                                @if($sale->status === 'pending' && Auth::user()->role?->slug === 'finance')
                                    <form method="POST" action="{{ route('sales.approve', $sale) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Approve & deduct stock">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('sales.reject', $sale) }}" class="inline" onsubmit="return confirm('Reject this sale?')">
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
                    <x-table-empty :colspan="8" message="No sales found." description="Record your first sale to get started." icon="inbox" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $sales->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $sales->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $sales->total() }}</span> sales
        </p>
    </div>
</div>

<x-pagination :paginator="$sales" label="sales" />
@endsection
