@extends('layouts.app')

@section('title', 'Edit Purchase - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Edit Purchase {{ $purchase->reference_number }}"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases', 'href' => route('purchases.index')],
        ['label' => $purchase->reference_number, 'href' => route('purchases.show', $purchase)],
        ['label' => 'Edit'],
    ]"
/>

<div class="max-w-7xl">
    <div class="flex flex-col lg:flex-row gap-6">
    <form method="POST" action="{{ route('purchases.update', $purchase) }}" id="purchase-form" class="flex-1 min-w-0">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
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
                    <input type="text" name="notes" id="notes" value="{{ old('notes', $purchase->notes) }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Optional notes">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Purchase Items</h3>
                <button type="button" onclick="addRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Product
                </button>
            </div>

            <div id="items-error" class="hidden mb-3 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-600"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="items-table">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-8">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Unit Cost</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-32">Brand</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-20">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="6" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-gray-900" id="grand-total">ETB 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        </div>
        </div>
    </form>

    <div class="lg:w-56 flex-shrink-0">
        <div class="lg:sticky lg:top-20 space-y-2">
            <button type="submit" form="purchase-form" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Update Purchase
            </button>
            <a href="{{ route('purchases.index') }}" class="inline-flex items-center justify-center gap-1.5 w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Cancel
            </a>
        </div>
    </div>
    </div>
</div>

<script>
const products = @json($productsJson);
const existingItems = @json($existingItemsJson);
let rowIndex = 0;

function productOptions(selectedId) {
    let html = '<option value="">Select Product</option>';
    products.forEach(p => {
        const sel = p.id == selectedId ? 'selected' : '';
        html += `<option value="${p.id}" data-price="${p.price}" ${sel}>${p.code} — ${p.name}</option>`;
    });
    return html;
}

function productBrand(id) {
    const p = products.find(p => String(p.id) === String(id));
    return p ? (p.brand || '') : '';
}

function addRow(data) {
    const tbody = document.getElementById('items-body');
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-100';
    row.id = 'row-' + rowIndex;
    const idx = rowIndex;

    row.innerHTML = `
        <td class="px-3 py-2 text-gray-400 text-xs">${++rowIndex}</td>
        <td class="px-3 py-2">
            <select name="items[${idx}][product_id]" required onchange="fillPrice(this, ${idx})"
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                ${productOptions(data ? data.product_id : null)}
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="number" name="items[${idx}][quantity]" min="1" value="${data ? data.quantity : 1}" required oninput="calcRow(${idx})"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="items[${idx}][unit_cost]" min="0" step="0.01" value="${data ? data.unit_cost : 0}" required oninput="calcRow(${idx})"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[${idx}][type]" value="${data ? (data.type || '') : ''}"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type">
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[${idx}][brand]" readonly tabindex="-1"
                   value="${data ? productBrand(data.product_id) : ''}"
                   class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none" placeholder="Brand">
        </td>
        <td class="px-3 py-2 text-right text-sm text-gray-600" id="total-${idx}">ETB 0.00</td>
        <td class="px-3 py-2">
            <button type="button" onclick="removeRow(${idx})" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    calcRow(idx);
    document.getElementById('items-error').classList.add('hidden');
}

function removeRow(idx) {
    const row = document.getElementById('row-' + idx);
    if (row) row.remove();
    calcGrandTotal();
}

function fillPrice(el, idx) {
    const opt = el.options[el.selectedIndex];
    if (opt && opt.dataset.price) {
        const costInput = document.querySelector(`#row-${idx} input[name="items[${idx}][unit_cost]"]`);
        if (costInput) costInput.value = opt.dataset.price;
        const brandInput = document.querySelector(`#row-${idx} input[name="items[${idx}][brand]"]`);
        if (brandInput) brandInput.value = productBrand(el.value);
        calcRow(idx);
    }
}

function calcRow(idx) {
    const qty = parseFloat(document.querySelector(`#row-${idx} input[name="items[${idx}][quantity]"]`)?.value) || 0;
    const cost = parseFloat(document.querySelector(`#row-${idx} input[name="items[${idx}][unit_cost]"]`)?.value) || 0;
    const total = qty * cost;
    const el = document.getElementById('total-' + idx);
    if (el) el.textContent = 'ETB ' + total.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        const val = parseFloat(el.textContent.replace(/[^0-9.-]/g, ''));
        if (!isNaN(val)) grand += val;
    });
    document.getElementById('grand-total').textContent = 'ETB ' + grand.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#items-body tr');
    const errEl = document.getElementById('items-error');
    if (rows.length === 0) {
        e.preventDefault();
        errEl.textContent = 'Please add at least one product.';
        errEl.classList.remove('hidden');
        return;
    }
});

existingItems.forEach(item => addRow(item));
</script>
@endsection
