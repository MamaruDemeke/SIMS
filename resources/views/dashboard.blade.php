{{-- ============================================================================
     DASHBOARD VIEW — the homepage the user lands on after logging in.
     This is a "child" view. @extends pulls in the main layout (sidebar/header/footer)
     from layouts/app.blade.php, and @section('content') fills the content area.

     NEW BEHAVIOR (role-aware):
     Only cards/sections for modules the current user's ROLE has permission to
     are shown. Anything the role can't access is removed, so each person only
     sees the information relevant to their job.
     ============================================================================ --}}
@extends('layouts.app')

{{-- Set the browser tab title for this page. --}}
@section('title', 'Dashboard - YEGNA TRADING PLC')

@section('content')
{{-- <x-page-header> is a Blade component (a reusable template widget).
     :breadcrumbs passes a PHP array to it so it can draw the breadcrumb trail. --}}
<x-page-header
    title="Dashboard"
    :breadcrumbs="[
        ['label' => 'Home'],
        ['label' => 'Dashboard'],
    ]"
/>

{{-- Show an error banner if the session has a flashed 'error' message
     (e.g. "You do not have permission..."). session() reads flash data. --}}
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        {{ session('error') }}
    </div>
@endif

{{-- ==== PERSONAL WELCOME ====
     Greets the logged-in user by name at the top of their own dashboard. --}}
<div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-500 rounded-lg shadow-sm px-5 py-4 flex items-center gap-3">
    <div class="w-11 h-11 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
    </div>
    <div>
        <p class="text-white font-semibold text-lg leading-tight">Welcome, {{ Auth::user()->name }}! 👋</p>
        <p class="text-blue-100 text-sm">{{ Auth::user()->role?->name ?? '' }} · {{ now()->format('l, F j, Y') }}</p>
    </div>
</div>

{{-- ==== ROLE / PERMISSION CHECK ====
     Same helper used by the sidebar: $perm('module') returns true if the
     logged-in user's role is allowed to access that module.
     We use these below to decide which cards/sections to render. --}}
@php
    $user = Auth::user();                       // the currently logged-in user
    $perm = fn($module) => $user->role?->hasPermission($module);

    // Which sections this user is allowed to see:
    $canSeeProducts     = $perm('products');     // product count card
    $canSeeInventory    = $perm('inventory') || $perm('products') || $perm('stock_alerts');
                                                // low-stock alert card + low-stock table
    $canSeePurchases    = $perm('purchases') || $perm('stock_receive');
                                                // purchase workflow cards
    $canSeeSales        = $perm('sales');       // sales workflow & recent sales
    $canSeeUsers        = $perm('users');       // admin/user management cards

    // When the user sees the admin/user dashboard, hide ALL other module cards
    // (products, suppliers, customers, stock, purchases) so the dashboard shows
    // ONLY user information. Other roles keep their relevant cards.
    $showModuleCards    = ! $canSeeUsers;
@endphp

{{-- ==== DATA GATHERING ====
     Only run the queries needed for the sections this user can actually see.
     (No point counting products for a user who can't view product info.) --}}
@php
    // Inventory-related counts (only needed if the user can see stock info).
    if ($canSeeInventory) {
        // Items that are low on stock: quantity < minimum but still above 0.
        $lowStockItems = App\Models\Inventory::with('product')
            ->whereColumn('quantity', '<', 'minimum_stock')
            ->where('quantity', '>', 0)
            ->get();

        // Items that are completely out of stock (quantity 0 or below).
        $outOfStockItems = App\Models\Inventory::with('product')
            ->where('quantity', '<=', 0)
            ->get();
    }

    // Purchase-related counts (only needed if the user can see purchase info).
    if ($canSeePurchases) {
        $approvedPurchases = App\Models\Purchase::where('status', 'approved')->count(); // awaiting inventory receive
        $receivedPurchases = App\Models\Purchase::where('status', 'received')->count(); // done (stock updated)

        // The 5 most recently approved purchases, newest first (by approval time).
        // We use whereNotNull('approved_at') instead of status = 'approved' because
        // an approved purchase is later moved to 'received' (stock updated), so a
        // status filter would make this list empty. Using approved_at includes both
        // still-approved AND already-received purchases that were approved.
        $recentApprovedPurchases = App\Models\Purchase::with('supplier')
            ->whereNotNull('approved_at')
            ->latest('approved_at')
            ->take(5)
            ->get();

        // Approved purchases currently awaiting physical receipt. The Inventory
        // Manager receives/rejects these directly from the dashboard.
        if ($perm('stock_receive')) {
            $approvedPurchasesList = App\Models\Purchase::with('supplier')
                ->where('status', 'approved')
                ->latest()
                ->get();
        }
    }

    // Single-feature counts.
    if ($canSeeProducts)  { $totalProducts = App\Models\Product::count(); }

    // Sales: the 5 most recent sales, newest first (ordered by sale time).
    if ($canSeeSales) {
        $recentSales = App\Models\Sale::with(['customer', 'items.product.category'])
            ->latest('sold_at')
            ->take(5)
            ->get();

        // Flatten into one row per sale item, so Product / Category / Grade
        // can each be shown for the specific product line actually sold.
        $recentSaleItems = $recentSales->flatMap(function ($sale) {
            return $sale->items->map(function ($item) use ($sale) {
                $item->sale = $sale;
                return $item;
            });
        });
    }

    // User-management stats (only needed if the role can manage users, e.g. Admin).
    if ($canSeeUsers) {
        $totalUsers       = App\Models\User::count();                                 // everyone
        $activeUsers      = App\Models\User::where('is_active', true)->count();       // able to log in
        $deactivatedUsers = App\Models\User::where('is_active', false)->count();      // blocked
        // The 5 most recently created user accounts (newest first).
        $recentUsers      = App\Models\User::with('role')->latest()->take(5)->get();

// ═══ PROFIT & LOSS analytics (approved = realized sales only) ═══
        $plDb = \Illuminate\Support\Facades\DB::class;
        $plCarbon = \Illuminate\Support\Carbon::class;

        // Which day / month / year the admin wants to inspect.
        $selYear   = (int) (request('pl_year') ?: now()->year);
        $selMonth  = (int) (request('pl_month') ?: now()->month);
        $selDayRaw = request('pl_day');
        $selDay = null;
        if (is_string($selDayRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selDayRaw)) {
            try {
                $d = $plCarbon::createFromFormat('Y-m-d', $selDayRaw);
                if ($d && $d->format('Y-m-d') === $selDayRaw) $selDay = $d->setTime(0, 0);
            } catch (\Throwable $e) { $selDay = null; }
        }

        // Year options for the dropdown (every year that has sales + current).
        $plYears = $plDb::table('sales')->whereNotNull('sold_at')
            ->selectRaw('DISTINCT YEAR(sold_at) as y')->pluck('y')
            ->map(fn($y) => (int) $y)->sortDesc()->values();
        if (! $plYears->contains((int) now()->year)) $plYears->prepend((int) now()->year);
        $plYears = $plYears->sortDesc()->values()->all();

        // Revenue (sales.total_amount), cost (qty × current purchase price) and
        // profit for every approved sale whose sold_at falls in the period.
        $periodProfit = function ($from, $to, $label) use ($plDb) {
            $revenue = $plDb::table('sales')->where('status', 'approved')
                ->whereNotNull('sold_at')->whereBetween('sold_at', [$from, $to])
                ->sum('total_amount');
            $cost = $plDb::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'approved')->whereNotNull('sales.sold_at')
                ->whereBetween('sales.sold_at', [$from, $to])
                ->sum(\Illuminate\Support\Facades\DB::raw('sale_items.quantity * products.purchase_price'));
            $count = $plDb::table('sales')->where('status', 'approved')
                ->whereNotNull('sold_at')->whereBetween('sold_at', [$from, $to])->count();
            return [
                'label' => $label,
                'revenue' => (float) $revenue,
                'cost' => (float) $cost,
                'profit' => (float) $revenue - (float) $cost,
                'count' => $count,
            ];
        };

        // Period boundaries for the admin's selection.
        $mStart = $plCarbon::createFromDate($selYear, $selMonth, 1)->startOfMonth();
        $mEnd   = $mStart->copy()->endOfMonth();
        $yStart = $plCarbon::createFromDate($selYear, 1, 1)->startOfYear();
        $yEnd   = $yStart->copy()->endOfYear();

        // Three result cards: the chosen day / month / year.
        $plSelDay = $selDay ? $periodProfit($selDay->copy()->startOfDay(), $selDay->copy()->endOfDay(), $selDay->format('M j, Y')) : null;
        $plSelMonth = $periodProfit($mStart->copy(), $mEnd->copy(), $mStart->format('F Y'));
        $plSelYear  = $periodProfit($yStart->copy(), $yEnd->copy(), (string) $selYear);

        // Monthly breakdown of the SELECTED year (Jan–Dec) for the bar chart.
        $plBar = [];
        for ($month = 1; $month <= 12; $month++) {
            $ms = $plCarbon::createFromDate($selYear, $month, 1)->startOfMonth();
            $me = $ms->copy()->endOfMonth();
            $revenue = $plDb::table('sales')->where('status', 'approved')
                ->whereNotNull('sold_at')->whereBetween('sold_at', [$ms, $me])
                ->sum('total_amount');
            $cost = $plDb::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'approved')->whereNotNull('sales.sold_at')
                ->whereBetween('sales.sold_at', [$ms, $me])
                ->sum(\Illuminate\Support\Facades\DB::raw('sale_items.quantity * products.purchase_price'));
            $plBar[] = [
                'label' => $ms->format('M'),
                'revenue' => (float) $revenue,
                'cost' => (float) $cost,
                'profit' => (float) $revenue - (float) $cost,
            ];
        }

        // Profit split by product category for the SELECTED year (doughnut chart).
        $plPieRows = $plDb::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', 'approved')->whereNotNull('sales.sold_at')
            ->whereBetween('sales.sold_at', [$yStart, $yEnd])
            ->select('categories.name as cat',
                \Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as rev'),
                \Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity * products.purchase_price) as cost'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy(\Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity * sale_items.unit_price) - SUM(sale_items.quantity * products.purchase_price)'), 'desc')
            ->get();

        $plPieLabels = [];
        $plPieValues = [];
        $plPieColors = [];
        $plPalette   = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1', '#14b8a6', '#d946ef', '#e11d48'];
        $plPieIndex  = 0;
        foreach ($plPieRows as $row) {
            $profit = (float) $row->rev - (float) $row->cost;
            if (abs($profit) < 0.01) continue;                          // skip empty lines
            $plPieLabels[] = $row->cat ?? 'Uncategorized';
            $plPieValues[] = round($profit, 2);
            $plPieColors[] = $profit >= 0 ? $plPalette[$plPieIndex % count($plPalette)] : '#ef4444';
            $plPieIndex++;
        }

        // Determine if there is anything at all to chart.
        $plHasData = (bool) App\Models\Sale::where('status', 'approved')->exists();
    }
@endphp

{{-- ==== SUMMARY STAT CARDS (single row) ====
     Consolidated dashboard stats: one straight row of 4 cards.
     Only the cards for modules the user's role can access are rendered. --}}
@if($showModuleCards && ($canSeeProducts || $canSeeInventory || $canSeePurchases))
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @if($canSeeProducts)
    {{-- Card 1: Total Products (only if role has 'products' permission) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalProducts }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-emerald-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    @if($canSeeInventory)
    {{-- Card: Low Stock Alerts (only if role can see stock/inventory info).
         The number turns red if there are any low/out-of-stock items. --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Low Stock Alerts</p>
                <p class="text-2xl font-bold {{ ($lowStockItems->count() + $outOfStockItems->count()) > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $lowStockItems->count() + $outOfStockItems->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-rose-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    @if($canSeePurchases)
    {{-- Approved Awaiting Receive → link to purchases filtered to 'approved' --}}
    <a href="{{ $perm('stock_receive') ? route('inventory.purchases.index') : route('purchases.index', ['status' => 'approved']) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Approved - Awaiting Receive</p>
                {{-- The number turns blue if any purchases are waiting for Inventory. --}}
                <p class="text-2xl font-bold {{ $approvedPurchases > 0 ? 'text-blue-600' : 'text-gray-800' }} mt-1">{{ $approvedPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </a>

    {{-- Received / Stock Updated → link to purchases filtered to 'received' --}}
    <a href="{{ $perm('stock_receive') ? route('inventory.purchases.index') : route('purchases.index', ['status' => 'received']) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Received / Stock Updated</p>
                <p class="text-2xl font-bold {{ $receivedPurchases > 0 ? 'text-green-600' : 'text-gray-800' }} mt-1">{{ $receivedPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-emerald-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </a>
    @endif
</div>
@endif

{{-- ==== APPROVED – AWAITING RECEIVE ACTION TABLE (Inventory Manager) ====
     Rendered only for roles with 'stock_receive' (Inventory Manager). Every
     approved purchase is listed with its Receive / Reject buttons so stock can
     be taken in straight from the dashboard (the sidebar entry was removed). --}}
@if($showModuleCards && $perm('stock_receive'))
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Approved - Awaiting Receive</h3>
            <p class="text-xs text-gray-500">Finance has approved these — receive the goods to update stock</p>
        </div>
        <a href="{{ route('inventory.purchases.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedPurchasesList as $purchase)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium">
                            <a href="{{ route('inventory.purchases.show', $purchase) }}" class="text-blue-600 hover:text-blue-700">{{ $purchase->reference_number }}</a>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $purchase->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->total_quantity ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <form method="POST" action="{{ route('purchases.receive', $purchase) }}" class="inline" onsubmit="return confirm('Receive this purchase and update stock?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors" title="Mark as Received (Update Stock)">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                        Receive
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('purchases.reject', $purchase) }}" class="inline" onsubmit="return confirm('Reject this purchase?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors" title="Reject">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center">
                            <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-sm font-medium text-gray-800">All caught up!</p>
                            <p class="text-xs text-gray-500 mt-1">No purchases awaiting receive.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ==== RECENT SALES TABLE ====
     Only rendered if the role has the 'sales' permission.
     Lists the 5 most recent sales, newest first (ordered by sale time). --}}
@if($showModuleCards && $canSeeSales)
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Recent Sales</h3>
            <p class="text-xs text-gray-500">The 5 most recent sales (newest first)</p>
        </div>
        <a href="{{ route('sales.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSaleItems as $item)
                    @php $sale = $item->sale; @endphp
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">
                            <a href="{{ route('sales.show', $sale) }}" class="text-blue-600 hover:text-blue-700">{{ $sale->reference_number }}</a>
                        </td>
                        <td class="px-4 py-3.5 font-medium text-gray-800">{{ $item->product->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $item->product?->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $item->product?->grade ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $item->quantity }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">ETB {{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($item->line_total, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-status-badge :label="$sale->status_label" :variant="$sale->status_variant" />
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $sale->sold_at?->format('M d, Y') ?? $sale->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">No sales found. Record your first sale to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ==== RECENT APPROVED PURCHASES TABLE ====
     Only rendered if the role has the 'purchases' permission (Finance & Purchase Officer).
     Lists the 5 most recently approved purchases, newest first (by approval time).
     (The Inventory Manager has its own "Approved – Awaiting Receive" action table below
     instead, since it uses different routes.) --}}
@if($showModuleCards && $perm('purchases'))
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Recent Approved Purchases</h3>
            <p class="text-xs text-gray-500">The 5 most recently approved purchases (newest first)</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentApprovedPurchases as $purchase)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600 hover:text-blue-700">{{ $purchase->reference_number }}</a>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $purchase->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->total_quantity }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-status-badge :label="$purchase->status_label" :variant="$purchase->status_variant" />
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $purchase->approved_at?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No approved purchases yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ==== LOW STOCK ALERTS TABLE ====
     Only rendered if the role can see stock info AND there is something low/out.
     Lists what needs restocking. --}}
@if($showModuleCards && $canSeeInventory && ($lowStockItems->count() > 0 || $outOfStockItems->count() > 0))
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    {{-- Table header title --}}
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-800">Low Stock Alerts</h3>
        <p class="text-xs text-gray-500">Products that need restocking</p>
    </div>
    <div class="overflow-x-auto">
        {{-- An HTML table: header row + body rows. --}}
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Current Stock</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Minimum</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- First list all OUT-OF-STOCK items (most urgent, shown first). --}}
                @foreach($outOfStockItems as $item)
                    <tr class="border-b border-gray-100">
                        {{-- $item->product->name → the related product's name (loaded via with('product')). --}}
                        <td class="px-4 py-3 font-medium">{{ $item->product->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-red-600 font-medium">{{ $item->quantity }} {{ $item->product->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->minimum_stock }} {{ $item->product->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-center">
                            {{-- A Blade component that renders a coloured "danger" badge. --}}
                            <x-status-badge label="Out of Stock" variant="danger" />
                        </td>
                    </tr>
                @endforeach
                {{-- Then list all LOW-stock items (still have some, but below minimum). --}}
                @foreach($lowStockItems as $item)
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 font-medium">{{ $item->product->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-yellow-600 font-medium">{{ $item->quantity }} {{ $item->product->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->minimum_stock }} {{ $item->product->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-center">
                            <x-status-badge label="Low Stock" variant="warning" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ==== ADMIN: USER STATS ====
     Total / Active / Deactivated account cards for roles with 'users'. --}}
@if($canSeeUsers)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    {{-- Card 1: Total Users (every account) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Card 2: Active Users (can log in) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Active Users</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $activeUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-emerald-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Card 3: Deactivated Users (blocked from logging in) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Deactivated Users</p>
                <p class="text-2xl font-bold {{ $deactivatedUsers > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $deactivatedUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-rose-50 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ==== ADMIN: PROFIT & LOSS ANALYTICS ====
     Colorful daily / weekly / monthly / yearly profit figures plus charts. --}}
@if($canSeeUsers)
<div class="mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center shadow-sm">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900">Profit &amp; Loss</h3>
            <p class="text-xs text-gray-500">Realized profit on approved sales (cost ≈ current purchase price)</p>
        </div>
    </div>

    {{-- Day / Month / Year picker for the Profit & Loss view --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <form method="GET" action="{{ request()->url() }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="pl_month" class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Month</label>
                <select name="pl_month" id="pl_month" class="w-40 border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selMonth === $m ? 'selected' : '' }}>{{ \Illuminate\Support\Carbon::createFromDate($selYear, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="pl_year" class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Year</label>
                <select name="pl_year" id="pl_year" class="w-32 border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($plYears as $y)
                        <option value="{{ $y }}" {{ $selYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pl_day" class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Day (optional)</label>
                <input type="date" name="pl_day" id="pl_day" value="{{ $selDay ? $selDay->format('Y-m-d') : '' }}" class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all shadow-sm hover:-translate-y-0.5 hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    Apply
                </button>
                <a href="{{ request()->url() }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">Reset</a>
            </div>
        </form>
    </div>

    @php
        $plBarLabels  = array_column($plBar, 'label');
        $plBarRevenue = array_column($plBar, 'revenue');
        $plBarCost    = array_column($plBar, 'cost');
        $plBarProfit  = array_column($plBar, 'profit');

        $selCards = [
            ['title' => 'Day',    'val' => $plSelDay,   'tile' => 'from-amber-100 to-orange-50',  'txt' => 'text-amber-600',  'bar' => 'from-amber-400 to-orange-500', 'sub' => $selDay ? $plSelDay['label'] : 'Pick a day above'],
            ['title' => 'Month',  'val' => $plSelMonth, 'tile' => 'from-violet-100 to-purple-50', 'txt' => 'text-violet-600', 'bar' => 'from-violet-500 to-purple-500','sub' => $plSelMonth['label']],
            ['title' => 'Year',   'val' => $plSelYear,  'tile' => 'from-emerald-100 to-green-50', 'txt' => 'text-emerald-600','bar' => 'from-emerald-500 to-green-500', 'sub' => (string) $selYear],
        ];
    @endphp

    {{-- Result cards for the chosen period --}}
    <div class="grid grid-cols-3 gap-3 mb-4 max-w-4xl mx-auto">
        @foreach($selCards as $f)
            @php
                $profit  = $f['val']['profit'] ?? null;
                $revenue = $f['val']['revenue'] ?? 0;
                $count   = $f['val']['count'] ?? 0;
                $margin  = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
                $meter   = $revenue > 0 ? min(100, max(0, ($profit / $revenue) * 100)) : 0;
            @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 transition-shadow hover:shadow-md">
                @if($profit === null)
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-700">{{ $f['title'] }}</p>
                            <p class="text-[11px] text-gray-400">{{ $f['sub'] }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $f['title'] }}</p>
                            <p class="text-base font-bold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} leading-tight">
                                {{ $profit >= 0 ? '+' : '−' }}{{ number_format(abs($profit), 2) }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ $profit >= 0 ? 'Profit' : 'Loss' }} · {{ $f['sub'] }} · {{ $count }} {{ $count === 1 ? 'sale' : 'sales' }}
                            </p>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-br {{ $f['tile'] }} rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                            <svg class="w-4 h-4 {{ $f['txt'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-100 rounded-full mt-2.5 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r {{ $f['bar'] }} transition-all" style="width: {{ $meter }}%"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">Revenue {{ number_format($revenue, 0) }} · Margin {{ $margin }}%</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Charts: monthly breakdown (bar + line) and profit-by-category doughnut --}}
    @if($plHasData)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 max-w-5xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-b from-gray-50 to-white">
                <h3 class="text-sm font-semibold text-gray-800">Monthly Breakdown — {{ $selYear }}</h3>
                <p class="text-xs text-gray-500">Revenue vs Cost vs Profit</p>
            </div>
            <div class="p-3" style="height:220px; position:relative;"><canvas id="pl-bar-chart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-b from-gray-50 to-white">
                <h3 class="text-sm font-semibold text-gray-800">Profit by Category — {{ $selYear }}</h3>
                <p class="text-xs text-gray-500">Profit split across categories</p>
            </div>
            <div class="p-3" style="height:220px; position:relative;"><canvas id="pl-pie-chart"></canvas></div>
        </div>
    </div>
    @endif

    @if($plHasData)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const money = v => 'ETB ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const barEl = document.getElementById('pl-bar-chart');
            if (barEl && typeof Chart !== 'undefined') {
                new Chart(barEl, {
                    data: {
                        labels: @json($plBarLabels),
                        datasets: [
                            { label: 'Revenue', data: @json($plBarRevenue), type: 'bar', backgroundColor: '#3b82f6', borderRadius: 4, maxBarThickness: 18 },
                            { label: 'Cost',    data: @json($plBarCost),    type: 'bar', backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 18 },
                            { label: 'Profit',  data: @json($plBarProfit),  type: 'line', borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', borderWidth: 3, pointRadius: 3, pointBackgroundColor: '#10b981', tension: 0.35, yAxisID: 'pl-profit' },
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } } },
                            tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + money(c.parsed.y) } }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#64748b' } },
                            y: { ticks: { color: '#64748b', callback: v => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v }, grid: { color: 'rgba(148,163,184,0.15)' } },
                            'pl-profit': { position: 'right', ticks: { color: '#10b981', callback: v => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v }, grid: { drawOnChartArea: false } }
                        }
                    }
                });
            }

            const pieEl = document.getElementById('pl-pie-chart');
            @if(count($plPieLabels) > 0)
            if (pieEl && typeof Chart !== 'undefined') {
                new Chart(pieEl, {
                    type: 'doughnut',
                    data: {
                        labels: @json($plPieLabels),
                        datasets: [{ data: @json($plPieValues), backgroundColor: @json($plPieColors), borderColor: '#ffffff', borderWidth: 3 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '65%',
                        plugins: {
                            legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, color: '#475569', font: { size: 12 } } },
                            tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + money(c.parsed) } }
                        }
                    }
                });
            }
            @endif
        });
    </script>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4 max-w-6xl mx-auto">
        <div class="px-4 py-10 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">No profit &amp; loss data yet</p>
            <p class="text-xs text-gray-400 mt-1">Recorded (approved) sales will appear here automatically.</p>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ==== ADMIN / USER MANAGEMENT SECTION ====
     Only rendered for roles with the 'users' permission (e.g. Administrator).
     Shows the most recently created accounts. --}}
@if($canSeeUsers)

{{-- Recent Users table: the 5 accounts created most recently. --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Recent Users</h3>
            <p class="text-xs text-gray-500">The most recently created accounts</p>
        </div>
        <a href="{{ route('users.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentUsers as $recent)
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 font-medium">{{ $recent->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $recent->email }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $recent->role?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($recent->is_active)
                                <x-status-badge label="Active" variant="success" />
                            @else
                                <x-status-badge label="Deactivated" variant="danger" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
