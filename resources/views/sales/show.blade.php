@extends('layouts.app')

@section('title', 'Sale ' . $sale->reference_number . ' - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Sale {{ $sale->reference_number }}"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Sales', 'href' => route('sales.index')],
        ['label' => $sale->reference_number],
    ]"
/>

<div class="max-w-5xl space-y-6">
    {{-- Finance actions for pending sales --}}
    @if($sale->status === 'pending' && Auth::user()->role?->slug === 'finance')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600">Actions:</span>
            <form method="POST" action="{{ route('sales.approve', $sale) }}" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">Approve & Deduct Stock</button>
            </form>
            <form method="POST" action="{{ route('sales.reject', $sale) }}" class="inline" onsubmit="return confirm('Reject this sale?')">
                @csrf
                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Reject</button>
            </form>
        </div>
        <p class="mt-2 text-xs text-gray-500">Approving this sale will deduct the listed quantities from stock.</p>
    </div>
    @endif

    {{-- Sale Details --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Sale Details</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Reference</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->reference_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Customer</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->customer->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Status</p>
                <x-status-badge :label="$sale->status_label" :variant="$sale->status_variant" />
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Quantity</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->total_quantity }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Amount</p>
                <p class="text-sm font-bold text-gray-900">ETB {{ number_format($sale->total_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Sold Date</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->sold_at?->format('M d, Y H:i') ?? $sale->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Recorded By</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->creator->name ?? '—' }}</p>
            </div>
            @if($sale->status === 'approved')
            <div>
                <p class="text-xs text-gray-500">Approved By</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->approver->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Approved At</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->approved_at?->format('M d, Y H:i') }}</p>
            </div>
            @endif
            @if($sale->status === 'rejected')
            <div>
                <p class="text-xs text-gray-500">Rejected At</p>
                <p class="text-sm font-medium text-gray-800">{{ $sale->approved_at?->format('M d, Y H:i') }}</p>
            </div>
            @endif
            @if($sale->notes)
            <div class="col-span-2 sm:col-span-4">
                <p class="text-xs text-gray-500">Notes</p>
                <p class="text-sm text-gray-800">{{ $sale->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Items</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Diameter</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Size</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr class="border-b border-gray-100 {{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : '' }}">
                            <td class="px-4 py-3 text-gray-500">{{ $loop->index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $item->product->name ?? '—' }}
                                @if($item->product?->product_code)
                                    <span class="text-xs text-gray-400">({{ $item->product->product_code }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->type ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->diameter ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->size ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">ETB {{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">ETB {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="7" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">ETB {{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('sales.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
            Back to Sales
        </a>
    </div>
</div>
@endsection
