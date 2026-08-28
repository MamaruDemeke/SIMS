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

{{-- ==== ROLE / PERMISSION CHECK ====
     Same helper used by the sidebar: $perm('module') returns true if the
     logged-in user's role is allowed to access that module.
     We use these below to decide which cards/sections to render. --}}
@php
    $user = Auth::user();                       // the currently logged-in user
    $perm = fn($module) => $user->role?->hasPermission($module);

    // Which sections this user is allowed to see:
    $canSeeProducts     = $perm('products');     // product count card
    $canSeeSuppliers    = $perm('suppliers');    // supplier count card
    $canSeeCustomers    = $perm('customers');    // customer count card
    $canSeeInventory    = $perm('inventory') || $perm('products') || $perm('stock_alerts');
                                                // low-stock alert card + low-stock table
    $canSeePurchases    = $perm('purchases') || $perm('stock_receive');
                                                // purchase workflow cards
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
        $totalPurchases  = App\Models\Purchase::count();                          // all orders
        $pendingPurchases  = App\Models\Purchase::where('status', 'pending')->count();  // awaiting receive
        $receivedPurchases = App\Models\Purchase::where('status', 'received')->count(); // finance review
        $approvedPurchases = App\Models\Purchase::where('status', 'approved')->count(); // done
    }

    // Single-feature counts.
    if ($canSeeProducts)  { $totalProducts = App\Models\Product::count(); }
    if ($canSeeSuppliers) { $totalSuppliers = App\Models\Supplier::count(); }
    if ($canSeeCustomers) { $totalCustomers = App\Models\Customer::count(); }

    // User-management stats (only needed if the role can manage users, e.g. Admin).
    if ($canSeeUsers) {
        $totalUsers       = App\Models\User::count();                                 // everyone
        $activeUsers      = App\Models\User::where('is_active', true)->count();       // able to log in
        $deactivatedUsers = App\Models\User::where('is_active', false)->count();      // blocked
        // The 5 most recently created user accounts (newest first).
        $recentUsers      = App\Models\User::with('role')->latest()->take(5)->get();
    }
@endphp

{{-- ==== SUMMARY STAT CARDS (top row, 4 across) ====
     Only the cards for modules the user's role can access are rendered. --}}
@if($showModuleCards && ($canSeeProducts || $canSeeSuppliers || $canSeeCustomers || $canSeeInventory))
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @if($canSeeProducts)
    {{-- Card 1: Total Products (only if role has 'products' permission) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalProducts }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    @if($canSeeSuppliers)
    {{-- Card 2: Suppliers (only if role has 'suppliers' permission) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Suppliers</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalSuppliers }}</p>
            </div>
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.139-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    @if($canSeeCustomers)
    {{-- Card 3: Customers (only if role has 'customers' permission) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Customers</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalCustomers }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    @if($canSeeInventory)
    {{-- Card 4: Low Stock Alerts (only if role can see stock/inventory info).
         The number turns red if there are any low/out-of-stock items. --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Low Stock Alerts</p>
                <p class="text-2xl font-bold {{ ($lowStockItems->count() + $outOfStockItems->count()) > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $lowStockItems->count() + $outOfStockItems->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ==== PURCHASE WORKFLOW CARDS (second row) ====
     Only rendered if the user's role can access purchases (purchases or stock_receive).
     Each card links to the purchases page filtered by status. --}}
@if($showModuleCards && $canSeePurchases)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Total Purchases → link to all purchases --}}
    <a href="{{ route('purchases.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Purchases</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
            </div>
        </div>
    </a>

    {{-- Awaiting Receive → link to purchases filtered to 'pending' --}}
    <a href="{{ route('purchases.index', ['status' => 'pending']) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Awaiting Receive</p>
                {{-- The number turns yellow if any purchases are waiting. --}}
                <p class="text-2xl font-bold {{ $pendingPurchases > 0 ? 'text-yellow-600' : 'text-gray-800' }} mt-1">{{ $pendingPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
        </div>
    </a>

    {{-- Finance Review → link to purchases filtered to 'received' --}}
    <a href="{{ route('purchases.index', ['status' => 'received']) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Finance Review</p>
                <p class="text-2xl font-bold {{ $receivedPurchases > 0 ? 'text-blue-600' : 'text-gray-800' }} mt-1">{{ $receivedPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </a>

    {{-- Approved → link to purchases filtered to 'approved' --}}
    <a href="{{ route('purchases.index', ['status' => 'approved']) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Approved</p>
                <p class="text-2xl font-bold {{ $approvedPurchases > 0 ? 'text-green-600' : 'text-gray-800' }} mt-1">{{ $approvedPurchases }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </a>
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

{{-- ==== ADMIN / USER MANAGEMENT SECTION ====
     Only rendered for roles with the 'users' permission (e.g. Administrator).
     Shows user statistics + the most recently created accounts. --}}
@if($canSeeUsers)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    {{-- Card 1: Total Users (every account) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Card 2: Active Users (can log in) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Active Users</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $activeUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Card 3: Deactivated Users (blocked from logging in) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Deactivated Users</p>
                <p class="text-2xl font-bold {{ $deactivatedUsers > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $deactivatedUsers }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>
    </div>
</div>

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
