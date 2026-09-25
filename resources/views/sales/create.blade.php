@extends('layouts.app')

@section('title', 'Create Sale - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Create Sale"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Sales', 'href' => route('sales.index')],
        ['label' => 'Create Sale'],
    ]"
/>

<div class="max-w-6xl">
    <form method="POST" action="{{ route('sales.store') }}" id="sale-form">
        @csrf

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                    <select name="customer_id" id="customer_id" required
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('customer_id') border-red-500 @enderror">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')
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
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Sale Items</h3>
                <button type="button" onclick="addRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Product
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <div>
                    <label for="catFilter" class="block text-sm font-medium text-gray-700 mb-1">Select Category</label>
                    <select id="catFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
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
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Unit Price (ETB)</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-28">Grade</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-32">Brand</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                    <tfoot id="items-foot">
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="8" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-gray-900" id="grand-total">ETB 0.00</td>
                        </tr>
                        <tr class="bg-gray-50 border-t border-gray-200">
                            <td colspan="8" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Estimated Profit</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-emerald-600" id="profit-total">ETB 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <a href="{{ route('sales.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Record Sale
            </button>
        </div>
    </form>
</div>

<script>
    const allProducts = @json($productsJson);
    const rowIndex = {current: 0};

    const catFilter = document.getElementById('catFilter');

    // -- Step 1: group the flat product list by product_code (base product) --
    function buildGroups(catId) {
        const products = allProducts.filter(p => !catId || String(p.category_id) === catId);
        const map = new Map();
        products.forEach(p => {
            const key = p.code || p.name;
            if (!map.has(key)) map.set(key, { code: key, variants: [] });
            map.get(key).variants.push(p);
        });
        return Array.from(map.values());
    }

    // "Add Product" button: opens an empty line group with a base-product picker.
    function addRow() {
        const catId = catFilter ? catFilter.value : '';
        const groups = buildGroups(catId);
        if (groups.length === 0) { alert('No products available. Select a category first.'); return; }
        addLineGroupWithPicker(groups);
    }

    function addLineGroupWithPicker(groups) {
        const tbody = document.getElementById('items-body');
        const groupIdx = ++rowIndex.current;
        const container = document.createElement('tr');
        container.id = 'grp-' + groupIdx;
        container.dataset.group = groupIdx;
        container.className = 'bg-gray-50/60';
        const pickOptions = groups.map(g =>
            `<option value="${g.code}">${g.code} — ${g.variants[0].name}</option>`
        ).join('');
        container.innerHTML = `
            <td colspan="9" class="px-3 py-2">
                <div class="flex flex-wrap items-center gap-2">
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
            </td>
        `;
        container._pickGroups = Object.fromEntries(groups.map(g => [g.code, g]));
        tbody.appendChild(container);
        document.getElementById('items-error').classList.add('hidden');
        renumberGroups();
    }

    function setLineGroup(groupIdx, code) {
        const container = document.getElementById('grp-' + groupIdx);
        if (!container || !code) return;
        container._group = container._pickGroups[code];
        groupGradeRows(groupIdx).forEach(r => r.remove());
        renumberGroups();
    }

    // Grade rows that belong to one product group (they follow its header row).
    function groupGradeRows(groupIdx) {
        return [...document.querySelectorAll(`#items-body tr[id^="grade-"][data-group="${groupIdx}"]`)];
    }

    function insertGradeRow(groupIdx, row) {
        const header = document.getElementById('grp-' + groupIdx);
        const grades = groupGradeRows(groupIdx);
        const ref = grades.length ? grades[grades.length - 1] : header;
        if (ref) ref.after(row);
    }

    function addGrade(groupIdx, group) {
        // group is only passed on first call; otherwise read from the stored variant list.
        const container = document.getElementById('grp-' + groupIdx);
        const stored = container._group || group;
        container._group = stored;

        const itemIdx = ++rowIndex.current;
        const options = stored.variants.map(v =>
            `<option value="${v.id}" data-price="${v.price||0}" data-cost="${v.cost||0}" data-stock="${v.stock||0}" data-type="${v.type||''}" data-brand="${v.brand||''}">${v.name}${v.type? ' — G '+v.type : ''}</option>`
        ).join('');

        const row = document.createElement('tr');
        row.id = 'grade-' + itemIdx;
        row.dataset.group = groupIdx;
        row.className = 'border-t border-gray-100 align-top';
        row.innerHTML = `
            <td class="px-2 py-2">
                <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">${itemIdx}</span>
            </td>
            <td class="px-2 py-2">
                <select name="items[${itemIdx}][product_id]" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="fillDefaults(this, ${itemIdx})">
                    <option value="">Select grade</option>
                    ${options}
                </select>
            </td>
            <td class="px-2 py-2 text-left text-xs text-gray-500 stock-cell" id="stock-${itemIdx}">—</td>
            <td class="px-2 py-2">
                <input type="number" name="items[${itemIdx}][quantity]" min="1" value="1" required oninput="calcRow(${itemIdx})"
                       class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </td>
            <td class="px-2 py-2">
                <input type="number" name="items[${itemIdx}][unit_price]" min="0" step="0.01" value="" required oninput="calcRow(${itemIdx})"
                       class="w-full px-2 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="items[${itemIdx}][type]" readonly tabindex="-1"
                       class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="items[${itemIdx}][brand]" readonly tabindex="-1"
                       class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none">
            </td>
            <td class="px-2 py-2 text-right text-sm text-gray-600" id="total-${itemIdx}">ETB 0.00</td>
            <td class="px-2 py-2">
                <button type="button" onclick="removeGrade(${itemIdx})" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Remove grade">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </td>
        `;
        insertGradeRow(groupIdx, row);
        calcGrandTotal();
        renumberGroups();
    }

    function fillDefaults(select, idx) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        const row = document.getElementById('grade-' + idx);
        const price = row.querySelector(`input[name="items[${idx}][unit_price]"]`);
        const type = row.querySelector(`input[name="items[${idx}][type]"]`);
        const brand = row.querySelector(`input[name="items[${idx}][brand]"]`);
        const stock = row.querySelector(`#stock-${idx}`);
        if (price) price.value = opt.dataset.price || 0;
        if (type) type.value = opt.dataset.type || '';
        if (brand) brand.value = opt.dataset.brand || '';
        if (stock) stock.textContent = opt.dataset.stock ?? '—';
        calcRow(idx);
    }

    function removeGrade(idx) {
        const row = document.getElementById('grade-' + idx);
        if (row) { row.remove(); renumberGroups(); calcGrandTotal(); }
    }

    function removeLineGroup(gid) {
        groupGradeRows(gid).forEach(r => r.remove());
        const cont = document.getElementById('grp-' + gid);
        if (cont) cont.remove();
        renumberGroups();
        calcGrandTotal();
    }

    function calcRow(idx) {
        const qty = parseFloat(document.querySelector(`#grade-${idx} input[name="items[${idx}][quantity]"]`)?.value) || 0;
        const price = parseFloat(document.querySelector(`#grade-${idx} input[name="items[${idx}][unit_price]"]`)?.value) || 0;
        const total = qty * price;
        const el = document.getElementById('total-' + idx);
        if (el) el.textContent = 'ETB ' + total.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        calcGrandTotal();
    }

    function calcGrandTotal() {
        let grand = 0;
        let profit = 0;
        document.querySelectorAll('tr[id^="grade-"]').forEach(row => {
            const sel = row.querySelector('select[name$="[product_id]"]');
            const qty = parseFloat(row.querySelector('input[name$="[quantity]"]')?.value) || 0;
            const price = parseFloat(row.querySelector('input[name$="[unit_price]"]')?.value) || 0;
            const cost = parseFloat(sel?.options[sel.selectedIndex]?.dataset.cost) || 0;
            grand += qty * price;
            profit += qty * (price - cost);
        });
        document.getElementById('grand-total').textContent = 'ETB ' + grand.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const profitEl = document.getElementById('profit-total');
        if (profitEl) {
            profitEl.textContent = 'ETB ' + profit.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            profitEl.className = 'px-3 py-3 text-right text-sm font-bold ' + (profit >= 0 ? 'text-emerald-600' : 'text-red-600');
        }
    }

    document.getElementById('sale-form').addEventListener('submit', function(e) {
        const errEl = document.getElementById('items-error');
        const customer = document.getElementById('customer_id').value;
        if (!customer) {
            e.preventDefault();
            errEl.textContent = 'Please select a customer.';
            errEl.classList.remove('hidden');
            return;
        }
        const rows = document.querySelectorAll('#items-body tr[id^="grade-"]');
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
            const price = row.querySelector('input[name$="[unit_price]"]');
            if (sel && !sel.value) { hasError = true; sel.classList.add('border-red-500'); }
            if (qty && (!qty.value || parseInt(qty.value) < 1)) { hasError = true; qty.classList.add('border-red-500'); }
            if (price && (!price.value || parseFloat(price.value) < 0)) { hasError = true; price.classList.add('border-red-500'); }
            const stock = row.querySelector('.stock-cell')?.textContent;
            if (sel && sel.value && stock && stock !== '—' && parseInt(stock) < parseInt(qty?.value || 0)) {
                hasError = true;
                if (qty) qty.classList.add('border-red-500');
            }
        });
        if (hasError) {
            e.preventDefault();
            errEl.textContent = 'Please fill every grade row (product, quantity and price) and make sure quantity does not exceed stock.';
            errEl.classList.remove('hidden');
        }
    });

    function renumberGroups() {
        document.querySelectorAll('#items-body tr[id^="grade-"]').forEach((row, i) => {
            const badge = row.querySelector('td:first-child span');
            if (badge) badge.textContent = i + 1;
        });
    }
</script>
@endsection
