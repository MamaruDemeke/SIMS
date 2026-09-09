@extends('layouts.app')

@section('title', 'Create Purchase - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Create Purchase"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases', 'href' => route('purchases.index')],
        ['label' => 'Purchase Requests', 'href' => route('stock-notifications.pending')],
        ['label' => 'Create Purchase'],
    ]"
/>

<div class="max-w-7xl">
    <div class="flex flex-col lg:flex-row gap-6">
    <form method="POST" action="{{ route('purchases.store') }}" id="purchase-form" class="flex-1 min-w-0">
        @csrf
        <input type="hidden" name="stock_notification_id" value="{{ $preselectedItems[0]['notification_id'] }}">
        @foreach($preselectedItems as $pi)
            <input type="hidden" name="stock_notification_ids[]" value="{{ $pi['notification_id'] }}">
        @endforeach

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input type="text" name="notes" id="notes" value="{{ old('notes') }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Optional notes">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Selected Requests ({{ count($preselectedItems) }})</h3>
                <p class="text-xs text-gray-500 mt-1">Products below are fixed — you can change the quantity and unit price in each row.</p>
            </div>

            <div id="items-error" class="hidden mb-3 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-600"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="items-table">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-8">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Product / Grade</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-20">Diameter</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-20">Size</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-32">Unit Cost (ETB)</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preselectedItems as $item)
                            @php($i = $loop->index)
                            @php($p = $item['product'])
                            <tr class="border-b border-gray-100" id="row-{{ $i }}">
                                <td class="px-3 py-2.5 align-top pt-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-3 py-2.5 align-top">
                                    <p class="font-medium text-gray-800">{{ $p['name'] }} <span class="font-mono text-xs text-gray-400">{{ $p['code'] }}</span></p>
                                    @if($p['type'])
                                        <span class="mt-1 inline-block px-2 py-0.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded">{{ $p['type'] }}</span>
                                    @endif
                                    <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $p['id'] }}">
                                    <input type="hidden" name="items[{{ $i }}][type]" value="{{ $p['type'] }}">
                                    <input type="hidden" name="items[{{ $i }}][diameter]" value="{{ $p['diameter'] }}">
                                    <input type="hidden" name="items[{{ $i }}][size]" value="{{ $p['size'] }}">
                                </td>
                                <td class="px-3 py-2.5 align-top text-gray-600">{{ $p['diameter'] ?: '—' }}</td>
                                <td class="px-3 py-2.5 align-top text-gray-600">{{ $p['size'] ?: '—' }}</td>
                                <td class="px-3 py-2.5 align-top">
                                    <input type="number" name="items[{{ $i }}][quantity]" min="1" value="{{ old('items.' . $i . '.quantity', $item['suggested_qty']) }}" required oninput="calcTotal({{ $i }})"
                                           class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <input type="number" name="items[{{ $i }}][unit_cost]" min="0" step="0.01"
                                           value="{{ old('items.' . $i . '.unit_cost', $p['price']) }}" required oninput="calcTotal({{ $i }})"
                                           class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </td>
                                <td class="px-3 py-2.5 align-top pt-3 text-right text-sm font-medium text-gray-800" id="total-{{ $i }}">ETB 0.00</td>
                                <td class="px-3 py-2.5 align-top pt-2">
                                    <button type="button" onclick="removeRow({{ $i }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors" title="Remove this product">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="6" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-gray-900" id="grand-total">ETB 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>

    <div class="lg:w-56 flex-shrink-0">
        <div class="lg:sticky lg:top-20 space-y-2">
            <button type="submit" form="purchase-form" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Save as Draft
            </button>
            <a href="{{ route('stock-notifications.pending') }}" class="inline-flex items-center justify-center gap-1.5 w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Cancel
            </a>
        </div>
    </div>
    </div>
</div>

<script>
function fmt(n) {
    return 'ETB ' + n.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function removeRow(idx) {
    const row = document.getElementById('row-' + idx);
    if (row) row.remove();
    calcGrandTotal();
}

// Recalculate one row from its editable quantity and unit cost:
// row total = qty × cost.
function calcTotal(idx) {
    const row = document.getElementById('row-' + idx);
    if (!row) return;
    const qty = parseFloat(row.querySelector(`input[name="items[${idx}][quantity]"]`)?.value) || 0;
    const cost = parseFloat(row.querySelector(`input[name="items[${idx}][unit_cost]"]`)?.value) || 0;
    const totalEl = document.getElementById('total-' + idx);
    if (totalEl) totalEl.textContent = fmt(qty * cost);
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        const val = parseFloat(el.textContent.replace(/[^0-9.-]/g, '') || 0);
        if (!isNaN(val)) grand += val;
    });
    const gt = document.getElementById('grand-total');
    if (gt) gt.textContent = fmt(grand);
}

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    const supplier = document.getElementById('supplier_id').value;
    const rows = document.querySelectorAll('#items-table tbody tr[id^="row-"]');
    const errEl = document.getElementById('items-error');
    if (!supplier) {
        e.preventDefault();
        errEl.textContent = 'Please select a supplier.';
        errEl.classList.remove('hidden');
        return;
    }
    if (rows.length === 0) {
        e.preventDefault();
        errEl.textContent = 'No products left — select at least one request.';
        errEl.classList.remove('hidden');
        return;
    }
    let hasError = false;
    rows.forEach(tr => {
        const qty = tr.querySelector('input[name$="[quantity]"]');
        const cost = tr.querySelector('input[name$="[unit_cost]"]');
        if (qty && (!qty.value || parseInt(qty.value) < 1)) { hasError = true; qty.classList.add('border-red-500'); }
        if (cost && (cost.value === '' || parseFloat(cost.value) < 0)) { hasError = true; cost.classList.add('border-red-500'); }
    });
    if (hasError) {
        e.preventDefault();
        errEl.textContent = 'Please enter a valid quantity and unit cost for every product.';
        errEl.classList.remove('hidden');
    }
});
</script>
@endsection