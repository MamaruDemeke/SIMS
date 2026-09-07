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

<div class="max-w-6xl">
    <form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
        @csrf
        <input type="hidden" name="stock_notification_id" value="{{ \Illuminate\Support\Arr::get($preselected ?? [], 'notification_id') }}">

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required onchange="onSupplierChange()"
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

            <div id="select-banner" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm font-medium text-yellow-800">Select a supplier to continue.</p>
                <p class="text-xs text-yellow-700 mt-1">A supplier is required to save the purchase. You can add products below first.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <div>
                    <label for="catFilter" class="block text-sm font-medium text-gray-700 mb-1">Select Category</label>
                    <select id="catFilter" onchange="filterProducts()"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="prodFilter" class="block text-sm font-medium text-gray-700 mb-1">Select Product (by type/code)</label>
                    <select id="prodFilter" onchange="addSelectedProduct()"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Product</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Choosing a product opens a line where you can add one or more grade variants.</p>
                </div>
            </div>

            <div id="items-error" class="hidden mb-3 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-600"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="items-table">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-8">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-40">Product / Grade</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-16">Stock</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-16">Qty</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Unit Cost (ETB)</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Grade</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-20">Diameter</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-16">Size</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                    <tfoot id="items-foot">
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="9" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-gray-900" id="grand-total">ETB 0.00</td>
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
const allProducts = @json($productsJson);
const selectedProducts = new Set();
const rowIndex = {current: 0};

// If the officer clicked "Create Purchase" on a pending notification, this
// holds the pre-selected supplier + product so the form starts with a row
// already filled in (only quantity remains editable).
const preselected = @json($preselected ?? null);

// Products are no longer tied to suppliers, so every active product is
// available to add to the purchase no matter which supplier is chosen.
function getSupplierProducts() {
    return allProducts;
}

// -- Group the flat product list by product_code (base product) --
function buildGroups(catId) {
    const products = getSupplierProducts().filter(p => !catId || String(p.category_id) === String(catId));
    const map = new Map();
    products.forEach(p => {
        const key = p.code || p.name;
        if (!map.has(key)) map.set(key, { code: key, variants: [] });
        map.get(key).variants.push(p);
    });
    return Array.from(map.values());
}

function filterProducts() {
    const catFilter = document.getElementById('catFilter');
    const prodFilter = document.getElementById('prodFilter');
    const catId = catFilter ? catFilter.value : '';
    const groups = buildGroups(catId);
    let html = '<option value="">Select Product</option>';
    groups.forEach(g => {
        const label = g.variants.length > 0 ? g.code + ' — ' + g.variants[0].name : g.code;
        html += `<option value="${g.code}">${label}</option>`;
    });
    prodFilter.innerHTML = html;
}

function onSupplierChange() {
    const supplierEl = document.getElementById('supplier_id');

    // Update supplier defaults display
    const opt = supplierEl.options[supplierEl.selectedIndex];
    const defaultsEl = document.getElementById('supplier-defaults');
    if (opt && opt.value) {
        const type = opt.dataset.type || '—';
        const diameter = opt.dataset.diameter || '—';
        const size = opt.dataset.size || '—';
        document.getElementById('default-type').textContent = type;
        document.getElementById('default-diameter').textContent = diameter;
        document.getElementById('default-size').textContent = size;
        defaultsEl.classList.remove('hidden');
    } else {
        defaultsEl.classList.add('hidden');
    }

    // Clear existing rows when supplier changes
    document.querySelectorAll('#items-body > tr').forEach(r => r.remove());
    selectedProducts.clear();

    // Category + product filters are always available (all products shown).
    const catFilter = document.getElementById('catFilter');
    const prodFilter = document.getElementById('prodFilter');
    const banner = document.getElementById('select-banner');

    catFilter.disabled = false;
    catFilter.classList.remove('bg-gray-100');
    prodFilter.disabled = false;
    prodFilter.classList.remove('bg-gray-100');
    banner.classList.add('hidden');
    filterProducts();
    calcGrandTotal();
}

function addSelectedProduct() {
    const prodFilter = document.getElementById('prodFilter');
    const code = prodFilter.value;
    if (!code) return;
    const catId = document.getElementById('catFilter').value;
    const group = buildGroups(catId).find(g => g.code === code);
    if (group) addLineGroup(group);
    prodFilter.value = '';
}

function addRow() {
    const catId = document.getElementById('catFilter').value;
    const groups = buildGroups(catId);
    if (groups.length === 0) { alert('No products available. Select a category first.'); return; }
    addLineGroupWithPicker(groups);
}

function addLineGroupWithPicker(groups) {
    const tbody = document.getElementById('items-body');
    const groupIdx = ++rowIndex.current;
    const container = document.createElement('tr');
    container.id = 'grp-' + groupIdx;
    container.className = 'align-top';
    const pickOptions = groups.map(g =>
        `<option value="${g.code}">${g.code} — ${g.variants[0].name}</option>`
    ).join('');
    container.innerHTML = `
        <td class="px-3 py-2 align-top pt-3">
            <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">${groupIdx}</span>
        </td>
        <td colspan="9" class="px-3 py-2">
            <div class="mb-2 flex items-center gap-2">
                <select onchange="setLineGroup(${groupIdx}, this.value)" class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select product (type/code)</option>
                    ${pickOptions}
                </select>
                <button type="button" onclick="addGrade(${groupIdx})" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add grade
                </button>
                <button type="button" onclick="removeLineGroup(${groupIdx})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
            </div>
            <table class="w-full">
                <tbody id="grades-${groupIdx}"></tbody>
            </table>
        </td>
    `;
    container._pickGroups = Object.fromEntries(groups.map(g => [g.code, g]));
    tbody.appendChild(container);
    document.getElementById('items-error').classList.add('hidden');
}

function setLineGroup(groupIdx, code) {
    const container = document.getElementById('grp-' + groupIdx);
    if (!container || !code) return;
    container._group = container._pickGroups[code];
    document.getElementById('grades-' + groupIdx).innerHTML = '';
}

// A line group holds several grade-variant rows of one product.
function addLineGroup(group) {
    const tbody = document.getElementById('items-body');
    const groupIdx = ++rowIndex.current;
    const container = document.createElement('tr');
    container.id = 'grp-' + groupIdx;
    container.className = 'align-top';
    container.innerHTML = `
        <td class="px-3 py-2 align-top pt-3">
            <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">${groupIdx}</span>
        </td>
        <td colspan="9" class="px-3 py-2">
            <div class="mb-2 flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-800">${group.code}</span>
                <button type="button" onclick="addGrade(${groupIdx})" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add grade
                </button>
                <button type="button" onclick="removeLineGroup(${groupIdx})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
            </div>
            <table class="w-full">
                <tbody id="grades-${groupIdx}"></tbody>
            </table>
        </td>
    `;
    tbody.appendChild(container);
    addGrade(groupIdx, group);
    document.getElementById('items-error').classList.add('hidden');
}

function addGrade(groupIdx, group) {
    const tbody = document.getElementById('grades-' + groupIdx);
    const container = document.getElementById('grp-' + groupIdx);
    const stored = container._group || group;
    container._group = stored;

    const itemIdx = ++rowIndex.current;
    const options = stored.variants.map(v =>
        `<option value="${v.id}" data-price="${v.price||0}" data-type="${v.type||''}" data-diameter="${v.diameter||''}" data-size="${v.size||''}">${v.name}${v.type?' — G '+v.type:''}</option>`
    ).join('');

    const row = document.createElement('tr');
    row.id = 'grade-' + itemIdx;
    row.className = 'border-t border-gray-100';
    row.innerHTML = `
        <td class="px-2 py-2">
            <select name="items[${itemIdx}][product_id]" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="fillDefaultsFromProduct(this, ${itemIdx})">
                <option value="">Select grade</option>
                ${options}
            </select>
        </td>
        <td class="px-2 py-2 text-right text-xs text-gray-500 stock-cell" id="stock-${itemIdx}">—</td>
        <td class="px-2 py-2">
            <input type="number" name="items[${itemIdx}][quantity]" min="1" value="1" required oninput="calcRow(${itemIdx})"
                   class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${itemIdx}][unit_cost]" min="0" step="0.01" value="" required oninput="calcRow(${itemIdx})"
                   class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        </td>
        <td class="px-2 py-2">
            <input type="text" name="items[${itemIdx}][type]" readonly tabindex="-1"
                   class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none">
        </td>
        <td class="px-2 py-2">
            <input type="text" name="items[${itemIdx}][diameter]" readonly tabindex="-1"
                   class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none">
        </td>
        <td class="px-2 py-2">
            <input type="text" name="items[${itemIdx}][size]" readonly tabindex="-1"
                   class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none">
        </td>
        <td class="px-2 py-2 text-right text-sm text-gray-600" id="total-${itemIdx}">ETB 0.00</td>
        <td class="px-2 py-2">
            <button type="button" onclick="removeGrade(${itemIdx})" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Remove grade">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    calcGrandTotal();
}

function fillDefaultsFromProduct(el, idx) {
    const opt = el.options[el.selectedIndex];
    if (!opt || !opt.value) return;
    const row = document.getElementById('grade-' + idx);
    const priceInput = row.querySelector(`input[name="items[${idx}][unit_cost]"]`);
    const typeInput = row.querySelector(`input[name="items[${idx}][type]"]`);
    const diamInput = row.querySelector(`input[name="items[${idx}][diameter]"]`);
    const sizeInput = row.querySelector(`input[name="items[${idx}][size]"]`);
    const stock = row.querySelector(`#stock-${idx}`);
    if (priceInput) priceInput.value = opt.dataset.price || 0;
    if (typeInput) typeInput.value = opt.dataset.type || '';
    if (diamInput) diamInput.value = opt.dataset.diameter || '';
    if (sizeInput) sizeInput.value = opt.dataset.size || '';
    if (stock) stock.textContent = opt.dataset.stock ?? '—';
    calcRow(idx);
}

function removeGrade(idx) {
    const row = document.getElementById('grade-' + idx);
    if (row) { row.remove(); calcGrandTotal(); }
}

function removeLineGroup(gid) {
    const cont = document.getElementById('grp-' + gid);
    if (cont) { cont.remove(); calcGrandTotal(); }
}

function calcRow(idx) {
    const qty = parseFloat(document.querySelector(`#grade-${idx} input[name="items[${idx}][quantity]"]`)?.value) || 0;
    const cost = parseFloat(document.querySelector(`#grade-${idx} input[name="items[${idx}][unit_cost]"]`)?.value) || 0;
    const total = qty * cost;
    const el = document.getElementById('total-' + idx);
    if (el) el.textContent = 'ETB ' + total.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        const val = parseFloat(el.textContent.replace(/[^0-9.-]/g, '') || 0);
        if (!isNaN(val)) grand += val;
    });
    document.getElementById('grand-total').textContent = 'ETB ' + grand.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.getElementById('purchase-form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#items-body tr[id^="grade-"]');
    const errEl = document.getElementById('items-error');
    const supplier = document.getElementById('supplier_id').value;
    if (!supplier) {
        e.preventDefault();
        errEl.textContent = 'Please select a supplier.';
        errEl.classList.remove('hidden');
        return;
    }
    if (rows.length === 0) {
        e.preventDefault();
        errEl.textContent = 'Please add at least one product/grade.';
        errEl.classList.remove('hidden');
        return;
    }
    let hasError = false;
    rows.forEach(row => {
        const sel = row.querySelector('select[name$="[product_id]"]');
        const qty = row.querySelector('input[name$="[quantity]"]');
        const cost = row.querySelector('input[name$="[unit_cost]"]');
        if (sel && !sel.value) { hasError = true; sel.classList.add('border-red-500'); }
        if (qty && (!qty.value || parseInt(qty.value) < 1)) { hasError = true; qty.classList.add('border-red-500'); }
        if (cost && (!cost.value || parseFloat(cost.value) < 0)) { hasError = true; cost.classList.add('border-red-500'); }
    });
    if (hasError) {
        e.preventDefault();
        errEl.textContent = 'Please fill every grade row (product, quantity and cost).';
        errEl.classList.remove('hidden');
    }
});

onSupplierChange();

// If a notification was opened, pre-add the notified product as a single
// grade variant (its attributes are auto-filled from the product). The officer
// selects any supplier, then adjusts only the quantity and cost.
if (preselected && preselected.product) {
    const prod = preselected.product;
    const catFilter = document.getElementById('catFilter');
    if (prod.category_id) {
        catFilter.value = String(prod.category_id);
        filterProducts();
    }
    const productObj = {
        id: prod.id, name: prod.name, code: prod.code, price: prod.price || 0,
        category_id: prod.category_id, unit: prod.unit,
        type: prod.type || '', diameter: prod.diameter || '', size: prod.size || '',
    };
    addLineGroup({ code: productObj.code, variants: [productObj] });
    selectedProducts.add(String(prod.id));
    document.getElementById('items-body').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

</script>
@endsection
