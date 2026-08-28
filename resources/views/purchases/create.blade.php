@extends('layouts.app')

@section('title', 'Create Purchase - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Create Purchase"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Purchases', 'href' => route('purchases.index')],
        ['label' => 'Create Purchase'],
    ]"
/>

<div class="max-w-5xl">
    <form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
        @csrf

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            {{-- No notifications warning --}}
            @if($notifications->count() === 0)
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
                        <p class="text-sm font-medium text-yellow-800">No pending stock notifications.</p>
                    </div>
                    <p class="text-xs text-yellow-700 mt-1">The Inventory Manager must send a stock alert before you can create a purchase.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="stock_notification_id" class="block text-sm font-medium text-gray-700 mb-1">Stock Notification <span class="text-red-500">*</span></label>
                    <select name="stock_notification_id" id="stock_notification_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('stock_notification_id') border-red-500 @enderror"
                            {{ $notifications->count() === 0 ? 'disabled' : '' }}>
                        <option value="">Select a Stock Notification</option>
                        @foreach($notifications as $notif)
                            <option value="{{ $notif->id }}"
                                data-product-id="{{ $notif->product_id }}"
                                data-type="{{ $notif->type }}"
                                {{ old('stock_notification_id', request('notification')) == $notif->id ? 'selected' : '' }}>
                                {{ $notif->product->name ?? '—' }} — {{ $notif->type_label }} (Stock: {{ $notif->current_quantity }}, Min: {{ $notif->minimum_stock }})
                            </option>
                        @endforeach
                    </select>
                    @error('stock_notification_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                data-type="{{ $supplier->default_type }}"
                                data-diameter="{{ $supplier->default_diameter }}"
                                data-size="{{ $supplier->default_size }}"
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

            <div id="supplier-defaults" class="hidden mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs font-medium text-blue-700 mb-1">Supplier Default Rebar Specs</p>
                <div class="flex gap-4 text-sm text-blue-800">
                    <span>Type: <strong id="default-type">—</strong></span>
                    <span>Diameter: <strong id="default-diameter">—</strong></span>
                    <span>Size: <strong id="default-size">—</strong></span>
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
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Diameter</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-24">Size</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-20">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                    <tfoot id="items-foot">
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="7" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-gray-900" id="grand-total">ETB 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <a href="{{ route('purchases.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Save as Draft
            </button>
        </div>
    </form>
</div>

<script>
const products = @json($productsJson);
let rowIndex = 0;

function productOptions() {
    let html = '<option value="">Select Product</option>';
    products.forEach(p => {
        html += `<option value="${p.id}" data-price="${p.price}">${p.code} — ${p.name}</option>`;
    });
    return html;
}

function addRow() {
    const tbody = document.getElementById('items-body');
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-100';
    row.id = 'row-' + rowIndex;

    const supplierEl = document.getElementById('supplier_id');
    const selectedOption = supplierEl.options[supplierEl.selectedIndex];
    const defType = selectedOption?.dataset?.type || '';
    const defDiameter = selectedOption?.dataset?.diameter || '';
    const defSize = selectedOption?.dataset?.size || '';

    row.innerHTML = `
        <td class="px-3 py-2 text-gray-400 text-xs">${++rowIndex}</td>
        <td class="px-3 py-2">
            <select name="items[${rowIndex}][product_id]" required onchange="fillPrice(this, ${rowIndex})"
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                ${productOptions()}
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="number" name="items[${rowIndex}][quantity]" min="1" value="1" required oninput="calcRow(${rowIndex})"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="items[${rowIndex}][unit_cost]" min="0" step="0.01" value="0" required oninput="calcRow(${rowIndex})"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[${rowIndex}][type]" value="${defType}"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type">
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[${rowIndex}][diameter]" value="${defDiameter}"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Diameter">
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[${rowIndex}][size]" value="${defSize}"
                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Size">
        </td>
        <td class="px-3 py-2 text-right text-sm text-gray-600" id="total-${rowIndex}">ETB 0.00</td>
        <td class="px-3 py-2">
            <button type="button" onclick="removeRow(${rowIndex})" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
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

document.getElementById('supplier_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const defaultsEl = document.getElementById('supplier-defaults');
    if (opt && opt.value) {
        const type = opt.dataset.type || '—';
        const diameter = opt.dataset.diameter || '—';
        const size = opt.dataset.size || '—';
        document.getElementById('default-type').textContent = type;
        document.getElementById('default-diameter').textContent = diameter;
        document.getElementById('default-size').textContent = size;
        defaultsEl.classList.remove('hidden');

        document.querySelectorAll('#items-body tr').forEach(row => {
            const typeInput = row.querySelector('input[type="text"][placeholder="Type"]');
            const diamInput = row.querySelector('input[type="text"][placeholder="Diameter"]');
            const sizeInput = row.querySelector('input[type="text"][placeholder="Size"]');
            if (typeInput) typeInput.value = type === '—' ? '' : type;
            if (diamInput) diamInput.value = diameter === '—' ? '' : diameter;
            if (sizeInput) sizeInput.value = size === '—' ? '' : size;
        });
    } else {
        defaultsEl.classList.add('hidden');
    }
});

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#items-body tr');
    const errEl = document.getElementById('items-error');
    if (rows.length === 0) {
        e.preventDefault();
        errEl.textContent = 'Please add at least one product.';
        errEl.classList.remove('hidden');
        return;
    }
    let hasError = false;
    rows.forEach(row => {
        const sel = row.querySelector('select');
        if (!sel || !sel.value) {
            hasError = true;
            sel?.classList.add('border-red-500');
        }
    });
    if (hasError) {
        e.preventDefault();
        errEl.textContent = 'Please select a product for all items.';
        errEl.classList.remove('hidden');
    }
});

addRow();

document.getElementById('stock_notification_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.value && opt.dataset.productId) {
        const firstRow = document.querySelector('#items-body tr');
        if (firstRow) {
            const sel = firstRow.querySelector('select');
            if (sel) {
                sel.value = opt.dataset.productId;
                fillPrice(sel, 0);
            }
        }
    }
});
</script>
@endsection
