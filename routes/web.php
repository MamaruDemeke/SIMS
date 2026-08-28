<?php

// ============================================================================
// ROUTES FILE — the "URL map" of the whole application.
// This is the FIRST place a request is matched when it hits the server.
//
// Each line says: "when the browser visits THIS url, call THIS thing".
//
// Laravel matches the top route to the bottom (group order matters for groups).
//
// Middleware reference:
//   'auth'     → user must be logged in (defined by Laravel)
//   'role:xxx' → user's role must have the 'xxx' permission (custom, see
//                app/Http/Middleware/RoleMiddleware.php).
// ============================================================================

// Import all the controllers this file references.
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockNotificationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// PUBLIC ROUTES (no login required)
// ============================================================================

// GET /login → show the login form. ->name('login') gives it a nickname so we
// can reference it as route('login') elsewhere instead of typing the URL.
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

// POST /login → process the login form submission (email + password).
Route::post('/login', [LoginController::class, 'login']);

// POST /logout → log the user out. (Inside the form's action in the layout.)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================================================
// PROTECTED ROUTES — everything below requires the user to be LOGGED IN
// (the 'auth' middleware redirects guests to /login).
// ============================================================================
Route::middleware('auth')->group(function () {

    // Visiting the site root (/) just sends you to the dashboard.
    // This is a "closure route" (no controller) — it returns a redirect directly.
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // GET /dashboard → shows the dashboard view (statistics & alerts).
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ---- Users (admin only: role must have 'users' permission) ----
    // Route::resource generates all the standard CRUD routes at once:
    //   GET /users, GET /users/create, POST /users,
    //   GET /users/{id}/edit, PUT /users/{id}, DELETE /users/{id}
    // ->except(['show']) removes the individual detail page (not needed).
    Route::middleware('role:users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        // POST /users/{id}/toggle-active → activate/deactivate a user (UserController@toggleActive).
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    });

    // ---- Permissions settings (role must have 'settings' permission) ----
    Route::middleware('role:settings')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'save'])->name('permissions.save');
    });

    // ---- Inventory modules (each gated by its own permission) ----
    Route::middleware('role:categories')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    Route::middleware('role:products')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
    });

    // Inventory (current stock): index + edit minimum stock.
    Route::middleware('role:inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    });

    // Inventory movements (history/ledger) — read only.
    Route::middleware('role:inventory_movements')->group(function () {
        Route::get('/inventory/movements', [InventoryMovementController::class, 'index'])->name('inventory.movements');
    });

    // ---- Suppliers (role must have 'suppliers' permission) ----
    Route::middleware('role:suppliers')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show']);
    });

    // ---- Purchases — Purchase Officer (create/submit/manage own purchases) ----
    Route::middleware('role:purchases')->group(function () {
        Route::resource('purchases', PurchaseController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        // Submit a draft → pending.
        Route::post('/purchases/{purchase}/submit', [PurchaseController::class, 'submit'])->name('purchases.submit');
        // The officer's view of open stock-notification requests.
        Route::get('/purchase-requests', [StockNotificationController::class, 'pending'])->name('stock-notifications.pending');
    });

    // ---- Purchases — Inventory Manager (view pending + receive) ----
    Route::middleware('role:stock_receive')->group(function () {
        Route::get('/inventory/purchases', [PurchaseController::class, 'pendingList'])->name('inventory.purchases.index');
        Route::get('/inventory/purchases/{purchase}', [PurchaseController::class, 'show'])->name('inventory.purchases.show');
        Route::post('/inventory/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('/inventory/purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
    });

    // ---- Purchases — Finance (view receipts + approve/reject) ----
    Route::middleware('role:purchases')->group(function () {
        Route::get('/finance/receipts', [PurchaseController::class, 'receiptList'])->name('purchases.receipts');
        Route::get('/finance/receipts/{purchase}', [PurchaseController::class, 'show'])->name('purchases.receipts.show');
        Route::post('/finance/receipts/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
        Route::post('/finance/receipts/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.financeReject');
    });

    // ---- Customers ----
    Route::middleware('role:customers')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['show']);
    });

    // ---- Stock Notifications — Inventory Manager (see low stock + send alerts) ----
    Route::middleware('role:stock_alerts')->group(function () {
        Route::get('/stock-alerts', [StockNotificationController::class, 'index'])->name('stock-notifications.index');
        Route::post('/stock-alerts/notify', [StockNotificationController::class, 'notify'])->name('stock-notifications.notify');
    });

    // ---- Stock Notifications — Purchase Officer (view open requests to buy) ----
    Route::middleware('role:purchases')->group(function () {
        Route::get('/purchase-requests', [StockNotificationController::class, 'pending'])->name('stock-notifications.pending');
    });

    // ---- App Notifications (custom in-app alerts) ----
    // List notifications (paginated), loading each one's related product.
    Route::get('/notifications', function () {
        $notifications = App\Models\Notification::with('product')->orderBy('created_at', 'desc')->paginate(20);
        return view('notifications.index', compact('notifications'));
    })->name('notifications.index');

    // Mark a single notification as read.
    Route::post('/notifications/{notification}/read', function (App\Models\Notification $notification) {
        $notification->markAsRead();
        return back()->with('success', 'Notification marked as read.');
    })->name('notifications.markRead');

    // Mark ALL unread notifications as read.
    Route::post('/notifications/read-all', function () {
        App\Models\Notification::unread()->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    })->name('notifications.readAll');

    // ---- User display settings (dark mode, font size, etc.) ----
    Route::get('/user/settings', [UserSettingController::class, 'get'])->name('user.settings.get');
    Route::post('/user/settings', [UserSettingController::class, 'save'])->name('user.settings.save');

    // ---- Company settings (logo) — only roles with the 'settings' permission (Admin) ----
    Route::middleware('role:settings')->group(function () {
        Route::get('/settings/logo', [CompanySettingController::class, 'show'])->name('settings.logo');
        Route::post('/settings/logo', [CompanySettingController::class, 'updateLogo'])->name('settings.logo.update');
    });
});
