{{-- ============================================================================
     MAIN LAYOUT — the "skeleton" shared by every logged-in page.
     Other pages "@extends('layouts.app')" and fill in the '@yield('content')' section.
     Everything here (sidebar, header, footer) appears on ALL pages.

     @yield('title') lets each page set its own browser-tab title.
     Three front-end tools are loaded:
       - Tailwind CSS (compiled via @vite)   → styling/layout
       - Alpine.js (from CDN)                → lightweight JavaScript for dropdowns/menus
       - a little inline JS for the sidebar & display settings
     ============================================================================ --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- The CSRF token as a meta tag, read by JavaScript when sending fetch requests. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'YEGNA TRADING PLC')</title>
    {{-- @vite compiles & loads the project's compiled CSS/JS files. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine.js — a small JS framework used for menus and toggles below. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .sidebar-transition { transition: width 0.2s ease, transform 0.2s ease; }
        .sidebar-collapsed { width: 0 !important; transform: translateX(-100%); }
        .sidebar-expanded { width: 256px; }
        .sidebar-overlay { display: none; }
        @media (max-width: 1023px) {
            .sidebar-overlay.active { display: block; }
        }

        body.theme-dark {
            background-color: #111827 !important;
        }
        body.theme-dark .bg-white { background-color: #1f2937 !important; }
        body.theme-dark .bg-gray-50 { background-color: #111827 !important; }
        body.theme-dark .bg-gray-100 { background-color: #111827 !important; }
        body.theme-dark .text-gray-800 { color: #f3f4f6 !important; }
        body.theme-dark .text-gray-700 { color: #d1d5db !important; }
        body.theme-dark .text-gray-600 { color: #9ca3af !important; }
        body.theme-dark .text-gray-500 { color: #9ca3af !important; }
        body.theme-dark .text-gray-400 { color: #6b7280 !important; }
        body.theme-dark .border-gray-200 { border-color: #374151 !important; }
        body.theme-dark .border-gray-100 { border-color: #374151 !important; }
        body.theme-dark header { background-color: #1f2937 !important; border-color: #374151 !important; }
        body.theme-dark footer { background-color: #1f2937 !important; border-color: #374151 !important; }
        body.theme-dark footer p.text-gray-500 { color: #9ca3af !important; }
        body.theme-dark footer p.text-gray-400 { color: #6b7280 !important; }
        body.theme-dark .bg-gray-50\/50 { background-color: rgba(31,41,55,0.5) !important; }
        body.theme-dark input[type="text"], body.theme-dark input[type="email"], body.theme-dark input[type="password"], body.theme-dark input[type="number"], body.theme-dark select {
            background-color: #374151 !important; color: #f3f4f6 !important; border-color: #4b5563 !important;
        }

    </style>
</head>
{{-- The page's body. The base background is light gray. --}}
<body class="min-h-screen bg-gray-100 font-sans">

    {{-- Mobile menu backdrop (dark overlay that appears under the sidebar on small screens). --}}
    <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black/50 z-40 lg:hidden" onclick="toggleSidebar()"></div>

    <div class="flex min-h-screen">

        {{-- ==================== SIDEBAR (left navigation) ==================== --}}
        <aside id="sidebar" class="sidebar-transition sidebar-expanded fixed lg:sticky top-0 left-0 z-50 h-screen w-64 bg-gray-900 text-white flex flex-col lg:translate-x-0 overflow-hidden">

            {{-- Sidebar Header: the company logo/title block --}}
            <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-700/50">
                {{-- Show the uploaded company logo if one exists, otherwise the default "YT" mark. --}}
                @php $logo = company_logo(); @endphp
                @if($logo)
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden bg-white">
                        <img src="{{ $logo }}" alt="Company Logo" class="w-full h-full object-contain">
                    </div>
                @else
                    <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-sm">YT</span>
                    </div>
                @endif
                <div class="min-w-0">
                    <h1 class="text-sm font-bold text-white truncate">YEGNA TRADING PLC</h1>
                    <p class="text-[11px] text-gray-400 truncate">Inventory Management System</p>
                </div>
            </div>

            {{-- Sidebar Navigation: the menu links.
                 $perm is a helper function that checks whether the current user's role
                 has the given permission. If not, that menu item is not shown
                 (so each role only sees the pages it is allowed to open). --}}
            <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
                @php
                    $user = Auth::user(); // the currently logged-in user
                    $perm = fn($module) => $user->role?->hasPermission($module); // permission check helper
                @endphp

                {{-- Dashboard link (always visible when logged in). --}}
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                {{-- ═══════ INVENTORY (collapsible sub-menu) ═══════
                     Only shown if the user can access ANY of the inventory features.
                     Alpine.js's x-data/x-show handle opening & closing the sub-menu. --}}
                @if($perm('categories') || $perm('products') || $perm('inventory') || $perm('inventory_movements') )
                <div x-data="{ open: {{ in_array(request()->segment(1), ['categories','products','inventory','inventory-movements']) ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ in_array(request()->segment(1), ['categories','products','inventory','inventory-movements']) ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Inventory</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="ml-4 border-l border-gray-700 pl-3 py-1 space-y-0.5">
                            @if($perm('categories'))
                            <a href="{{ route('categories.index') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('categories.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('categories.*') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Categories
                            </a>
                            @endif
                            @if($perm('products'))
                            <a href="{{ route('products.index') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('products.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('products.*') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Products
                            </a>
                            @endif
                            @if($perm('inventory'))
                            <a href="{{ route('inventory.index') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('inventory.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('inventory.*') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Stock
                            </a>
                            @endif
                            @if($perm('inventory_movements'))
                            @php
                                // Count movements that appeared since this user last viewed the page.
                                $newMoves = App\Models\InventoryMovement::where('created_at', '>', $user->movements_viewed_at ?? now()->subYears(100))->count();
                            @endphp
                            <a href="{{ route('inventory.movements') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('inventory.movements') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('inventory.movements') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Stock Movements
                                @if($newMoves > 0)
                                    <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none">{{ $newMoves > 9 ? '9+' : $newMoves }}</span>
                                @endif
                            </a>
                            @endif
                            @if($perm('stock_alerts'))
                            <a href="{{ route('stock-notifications.index') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('stock-notifications.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('stock-notifications.*') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Stock Alerts
                            </a>
                            @endif
                            @if($perm('stock_receive'))
                            <a href="{{ route('inventory.purchases.index') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request()->routeIs('inventory.purchases.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('inventory.purchases.*') ? 'bg-white' : 'bg-gray-600' }}"></span>
                                Approved - Awaiting Receive
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Purchases - Purchase Officer --}}
                @if($perm('purchases') && Auth::user()->role?->slug === 'purchase-officer')
                <a href="{{ route('stock-notifications.pending') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('stock-notifications.pending') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <span>Purchases</span>
                </a>
                @endif

                {{-- Pending Approvals - Finance --}}
                @if($perm('purchases') && Auth::user()->role?->slug === 'finance')
                <a href="{{ route('purchases.receipts') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('purchases.receipts*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pending Approvals</span>
                </a>
                @endif

                {{-- Sales --}}
                @if($perm('sales'))
                <a href="{{ route('sales.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sales.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                    <span>Sales</span>
                </a>
                @endif

                {{-- Create Sale shortcut (Sales Officer) --}}
                @if($perm('sales') && Auth::user()->role?->slug === 'sales')
                <a href="{{ route('sales.create') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sales.create') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Create Sale</span>
                </a>
                @endif

                {{-- ═══════ STANDALONE LINKS ═══════ --}}
                @if($perm('customers'))
                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('customers.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span>Customers</span>
                </a>
                @endif

                @if($perm('suppliers'))
                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.139-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <span>Suppliers</span>
                </a>
                @endif

                @if($perm('users'))
                {{-- ═══════ SYSTEM ═══════ --}}
                <div class="pt-3 pb-1 px-3">
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">System</p>
                </div>

                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span>Users</span>
                </a>
                @endif

                @if($perm('settings'))
                <a href="{{ route('permissions.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('permissions.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Permissions</span>
                </a>

                {{-- Company Settings: lets the admin change the company logo.
                     Also gated by the 'settings' permission. --}}
                <a href="{{ route('settings.logo') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('settings.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    <span>Settings</span>
                </a>
                @endif
            </nav>

            {{-- Sidebar Footer: shows the logged-in user's name + role. --}}
            <div class="border-t border-gray-700/50 p-3">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xs font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-gray-400 truncate">{{ Auth::user()->role?->name ?? 'No Role' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ==================== MAIN AREA (everything right of the sidebar) ==================== --}}
        <div class="flex-1 flex flex-col min-h-screen">

            {{-- ==================== HEADER (top bar) ==================== --}}
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between h-14 px-4 lg:px-6">

                    {{-- Right: Notifications + User menu (ml-auto pushes it to the right) --}}
                    <div class="flex items-center gap-10 ml-auto">

                        {{-- Notifications icon in the header — links to the notifications page,
                             with a red badge showing the number of unread alerts. --}}
                        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @php $unreadCount = App\Models\Notification::unread()->where('user_id', Auth::id())->count(); @endphp
                            <span id="notif-badge-header" class="absolute top-1 right-1 min-w-[16px] h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 {{ $unreadCount > 0 ? '' : 'hidden' }}">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        </a>

                        {{-- User Dropdown: avatar + name; opens a menu with Profile and Logout. --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-xs font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-sm font-medium text-gray-700 leading-tight">{{ Auth::user()->name }}</p>
                                    <p class="text-[11px] text-gray-500 leading-tight">{{ Auth::user()->role?->name ?? 'No Role' }}</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                </div>
                                <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    My Profile
                                </a>
                                <hr class="my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- ==================== MAIN CONTENT ====================
                 This is where each page's @section('content') is injected.
                 Flash messages (success/error) are shown here above the content. --}}
            <main class="flex-1 p-4 lg:p-6">

                {{-- Green "success" banner — shown when a controller did a successful action
                     and flashed a message via ->with('success', ...). --}}
                @if(session('success'))
                    <div class="mb-4 flex items-center gap-3 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 flex items-center gap-3 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span class="text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                {{-- The actual page content goes here (each page fills this in). --}}
                @yield('content')
            </main>

            {{-- ==================== FOOTER ==================== --}}
            <footer class="border-t border-gray-200 bg-white">
                <div class="px-4 lg:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">&copy; {{ date('Y') }} Yegna Trading PLC. All rights reserved.</p>
                    <p class="text-xs text-gray-400">Inventory Management System v2.0</p>
                </div>
            </footer>
        </div>
    </div>

    {{-- ==== PAGE JAVASCRIPT ====
         Handles: sidebar open/close. --}}
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (window.innerWidth < 1024) {
                sidebar.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('sidebar-expanded');
                overlay.classList.toggle('active');
            }
        }

        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                overlay.classList.remove('active');
            }
        });

    </script>

    {{-- Real-time notification badge: polls the server every few seconds so
         new notifications (and their unread count) appear without a reload. --}}
    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            function updateBadge(count) {
                const sidebarBadge = document.getElementById('notif-badge');
                const headerBadge = document.getElementById('notif-badge-header');

                if (sidebarBadge) {
                    const inner = sidebarBadge.querySelector('span');
                    if (inner) inner.textContent = count;
                    sidebarBadge.classList.toggle('hidden', count < 1);
                }
                if (headerBadge) {
                    headerBadge.textContent = count > 9 ? '9+' : count;
                    headerBadge.classList.toggle('hidden', count < 1);
                }
            }

            async function pollUnread() {
                try {
                    const res = await fetch('{{ route("notifications.unreadCount") }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (typeof data.unread_count === 'number') updateBadge(data.unread_count);
                } catch (e) {
                    // Ignore transient network errors (e.g. server briefly restarting).
                }
            }

            // Poll once on page load, then every 5 seconds.
            pollUnread();
            setInterval(pollUnread, 5000);
        })();
    </script>
</body>
</html>
