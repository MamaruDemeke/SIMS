@extends('layouts.app')

@section('title', 'Purchase ' . $purchase->reference_number . ' - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Purchase {{ $purchase->reference_number }}"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases', 'href' => route('purchases.index')],
        ['label' => $purchase->reference_number],
    ]"
/>

<div class="max-w-5xl space-y-6">
    {{-- Workflow Status Bar --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between text-xs font-medium">
            @php
                $steps = ['draft' => 'Draft', 'pending' => 'Submitted', 'approved' => 'Approved', 'received' => 'Received / Stock Updated'];
                $currentIndex = array_search($purchase->status, array_keys($steps));
                if ($purchase->status === 'rejected') $currentIndex = -1;
            @endphp
            @foreach($steps as $key => $label)
                @php $idx = array_search($key, array_keys($steps)); @endphp
                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $currentIndex >= $idx ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                            @if($currentIndex > $idx)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                        <span class="mt-1 text-center {{ $currentIndex >= $idx ? 'text-green-700 font-semibold' : 'text-gray-400' }}">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 mx-2 {{ $currentIndex > $idx ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
            @if($purchase->status === 'rejected')
                <div class="flex flex-col items-center ml-4">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-red-600 text-white">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <span class="mt-1 text-red-700 font-semibold">Rejected</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Action Buttons --}}
    @php $user = Auth::user(); @endphp
    @if(in_array($purchase->status, ['draft', 'pending', 'approved', 'received']))
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600">Actions:</span>

            {{-- Purchase Officer: draft actions --}}
            @if($purchase->status === 'draft' && $user->role?->slug === 'purchase-officer')
                <a href="{{ route('purchases.edit', $purchase) }}" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Edit</a>
                <form method="POST" action="{{ route('purchases.submit', $purchase) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">Submit for Processing</button>
                </form>
                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" class="inline" onsubmit="return confirm('Delete this purchase?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Delete</button>
                </form>
            @endif

            {{-- Finance: approve pending --}}
            @if($purchase->status === 'pending' && $user->role?->slug === 'finance')
                <form method="POST" action="{{ route('purchases.approve', $purchase) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">Approve</button>
                </form>
                <form method="POST" action="{{ route('purchases.financeReject', $purchase) }}" class="inline" onsubmit="return confirm('Reject this purchase?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Reject</button>
                </form>
            @endif

            {{-- Inventory Manager: receive approved stock --}}
            @if($purchase->status === 'approved' && $user->role?->slug === 'inventory-manager')
                <form method="POST" action="{{ route('purchases.receive', $purchase) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">Mark as Received</button>
                </form>
                <form method="POST" action="{{ route('purchases.reject', $purchase) }}" class="inline" onsubmit="return confirm('Reject this purchase?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Reject</button>
                </form>
            @endif
        </div>
    </div>
    @endif

    {{-- Purchase Details --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Purchase Details</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Reference</p>
                <p class="text-sm font-medium text-gray-800">{{ $purchase->reference_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Receipt Number</p>
                <p class="text-sm font-medium text-gray-800">{{ $purchase->receipt_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Supplier</p>
                <p class="text-sm font-medium text-gray-800">{{ $purchase->supplier->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Status</p>
                <x-status-badge :label="$purchase->status_label" :variant="$purchase->status_variant" />
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Quantity</p>
                <p class="text-sm font-medium text-gray-800">{{ $purchase->total_quantity ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Amount</p>
                <p class="text-sm font-bold text-gray-900">ETB {{ number_format($purchase->total_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Created Date</p>
                <p class="text-sm font-medium text-gray-800">{{ $purchase->created_at->format('M d, Y H:i') }}</p>
            </div>
            @if($purchase->notes)
            <div class="col-span-2 sm:col-span-4">
                <p class="text-xs text-gray-500">Notes</p>
                <p class="text-sm text-gray-800">{{ $purchase->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Workflow Timeline --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Workflow History</h3>
        <div class="space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 rounded-full bg-blue-500 mt-2 flex-shrink-0"></div>
                <div>
                    <p class="text-sm text-gray-800">Created by <strong>{{ $purchase->creator->name ?? '—' }}</strong></p>
                    <p class="text-xs text-gray-500">{{ $purchase->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
            @if($purchase->approved_by)
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 rounded-full bg-green-700 mt-2 flex-shrink-0"></div>
                <div>
                    <p class="text-sm text-gray-800">Approved by <strong>{{ $purchase->approver->name ?? '—' }}</strong> — Finance approval</p>
                    <p class="text-xs text-gray-500">{{ $purchase->approved_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
            @endif
            @if($purchase->received_by)
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 rounded-full bg-green-500 mt-2 flex-shrink-0"></div>
                <div>
                    <p class="text-sm text-gray-800">Received by <strong>{{ $purchase->receiver->name ?? '—' }}</strong> — Receipt: <strong>{{ $purchase->receipt_number }}</strong> — Stock updated</p>
                    <p class="text-xs text-gray-500">{{ $purchase->received_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
            @endif
            @if($purchase->status === 'rejected')
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 rounded-full bg-red-500 mt-2 flex-shrink-0"></div>
                <div>
                    <p class="text-sm text-red-600 font-medium">Purchase Rejected</p>
                </div>
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
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Unit Cost</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $item)
                        <tr class="border-b border-gray-100 {{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : '' }}">
                            <td class="px-4 py-3 text-gray-500">{{ $loop->index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $item->product->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->type ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">ETB {{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">ETB {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('purchases.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
            Back to Purchases
        </a>
    </div>
</div>
@endsection
