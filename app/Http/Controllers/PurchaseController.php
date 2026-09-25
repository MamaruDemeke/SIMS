<?php

namespace App\Http\Controllers;

// Imports: all models and helpers used in this controller.
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockNotification;
use App\Models\Supplier;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles the Purchase workflow — the heart of how new stock gets bought.
 *
 * The workflow (statuses) is:
 *   draft     → Purchase Officer creates & edits
 *   pending   → Purchase Officer "submits"; waits for FINANCE approval
 *   approved  → Finance "approves" (financial sign-off only — stock is NOT
 *               changed at this step)
 *   received  → Inventory Manager "receives" the physical goods → generates
 *               Receipt # and ONLY NOW does stock actually increase
 *   rejected  → cancelled at any of the pending/approved stages
 *
 * Access is split by role (see routes/web.php):
 *   Purchase Officer   → index, create, store, show, edit, update, destroy, submit
 *   Inventory Manager  → pendingList, show, receive, reject
 *   Finance            → receiptList, show, approve, reject
 */
class PurchaseController extends Controller
{
    /**
     * Purchase Officer: list purchases, with search + status filter.
     */
    public function index(Request $request)
    {
        // Get purchases newest-first, pre-loading supplier and creator.
        $query = Purchase::with('supplier', 'creator')->latest();

        // Search by reference number, receipt number, or supplier name.
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  // orWhereHas searches through the related supplier table.
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by status (e.g. only 'pending') if chosen.
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    /**
     * Inventory Manager: list purchases that are "approved" (awaiting physical receipt).
     */
    public function pendingList(Request $request)
    {
        // Only status = approved (Finance has approved, inventory will receive now).
        $query = Purchase::with('supplier', 'creator')->where('status', 'approved')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15);

        return view('purchases.pending-receive', compact('purchases'));
    }

    /**
     * Finance: list purchases that are "pending" (awaiting financial approval).
     */
    public function receiptList(Request $request)
    {
        $query = Purchase::with('supplier', 'creator', 'items.product')->where('status', 'pending')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15);

        return view('purchases.receipts', compact('purchases'));
    }

    /**
     * Purchase Officer: show the "Create Purchase" form.
     * Select supplier → category → product → fill quantity.
     *
     * If the form was reached from a pending stock notification
     * (?notification=ID), the notified product (and its supplier) is
     * pre-selected and its attributes auto-filled into the first item row.
     */
    public function create()
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can create purchases.');
        }

        // Active suppliers to choose from.
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        // Active categories for the category filter.
        $categories = Category::where('status', true)->orderBy('name')->get();

        // All active products (with their categories). Any product can be bought
        // from any supplier — the purchase form is NOT limited by supplier.
        $products = Product::where('status', true)->with(['category'])->orderBy('name')->get();

        // Convert to JSON for JavaScript so the picker can list every product.
        $productsJson = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->product_code,
            'price' => $p->purchase_price,
            'category_id' => $p->category_id,
            'unit' => $p->unit,
            'type' => $p->grade,
            'brand' => $p->brand,
        ])->values();

        // Pending stock notifications (optional — can still use them).
        $notifications = StockNotification::with('product')->pending()->latest()->get();
        $notificationsJson = $notifications->map(fn($n) => [
            'id' => $n->id,
            'product_id' => $n->product_id,
            'type' => $n->type,
            'message' => $n->message,
            'current_quantity' => $n->current_quantity,
        ])->values();

        // ---- Preselect from stock notification(s) if the officer came from
        //      the pending "Create Purchase" button(s). ----
        $preselected = null;
        $preselectedItems = [];

        if ($notificationIds = request()->query('notifications')) {
            // Multi-select: several notified products bundled into ONE order.
            $notificationIds = (array) $notificationIds;
            $notifs = StockNotification::with('product')->pending()->whereIn('id', $notificationIds)->get();
            foreach ($notifs as $notification) {
                if (!$notification->product) continue;
                $product = $notification->product;
                // Suggested order qty = what the inventory manager typed when
                // notifying; fall back to the flagged shortfall (min stock −
                // current stock) when no quantity was given.
                $preselectedItems[] = [
                    'notification_id' => $notification->id,
                    'suggested_qty' => $notification->suggested_quantity
                        ?: max((int) $notification->minimum_stock - (int) $notification->current_quantity, 1),
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->product_code,
                        'price' => $product->purchase_price,
                        'category_id' => $product->category_id,
                        'unit' => $product->unit,
                        'type' => $product->grade,
                        'brand' => $product->brand,
                    ],
                ];
            }
            $preselected = $preselectedItems[0] ?? null;
        }

        if (!$preselected && ($notificationId = request()->query('notification'))) {
            $notification = StockNotification::with('product')->pending()->find($notificationId);
            if ($notification && $notification->product) {
                $product = $notification->product;
                $preselected = [
                    'notification_id' => $notification->id,
                    'suggested_qty' => $notification->suggested_quantity
                        ?: max((int) $notification->minimum_stock - (int) $notification->current_quantity, 1),
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->product_code,
                        'price' => $product->purchase_price,
                        'category_id' => $product->category_id,
                        'unit' => $product->unit,
                        'type' => $product->grade,
                        'brand' => $product->brand,
                    ],
                ];
                $preselectedItems = [$preselected];
            }
        }

        if ($preselected && count($preselectedItems) <= 1) {
            // Opened from a single notification → show a single compact form:
            // just pick a supplier and set the quantity + price for the one
            // pre-selected product.
            if (!isset($product)) {
                $product = StockNotification::with('product')->pending()
                    ->find($preselected['notification_id'])?->product;
            }
            return view('purchases.create-compact', compact('suppliers', 'preselected', 'product'));
        }

        if (count($preselectedItems) > 1) {
            // Opened from "Create Purchase for Selected" → show ONE simple form
            // with a pre-filled row per notified product. The Supplier, quantity
            // (preset to the suggested shortfall) and unit price are all
            // editable; the product itself is read-only.
            return view('purchases.create-selected', compact('suppliers', 'preselectedItems'));
        }

        return view('purchases.create', compact('suppliers', 'categories', 'products', 'productsJson', 'notifications', 'notificationsJson', 'preselected', 'preselectedItems'));
    }

    /**
     * Purchase Officer: save a new purchase as a DRAFT.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can create purchases.');
        }

        // Validate the submitted data.
        $request->validate([
            'stock_notification_id' => 'nullable|exists:stock_notifications,id', // optional
            'stock_notification_ids' => 'nullable|array', // optional multi-select
            'stock_notification_ids.*' => 'exists:stock_notifications,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1', // at least one line
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Collect every notification that should be attached to this purchase
        // (single "Create Purchase" OR multiple "Create Purchase for Selected").
        $notificationIds = collect(array_merge(
            $request->stock_notification_ids ?? [],
            $request->stock_notification_id ? [$request->stock_notification_id] : [],
        ))->unique()->values();

        if ($notificationIds->isNotEmpty()) {
            $fulfilled = StockNotification::whereIn('id', $notificationIds)->where('fulfilled', true)->count();
            if ($fulfilled > 0) {
                return back()->withInput()->with('error', 'One of these notifications has already been fulfilled.');
            }
        }

        // --- Transaction: all DB writes succeed together, or none do. ---
        // If any step fails, rollback() undoes everything, keeping data consistent.
        DB::beginTransaction();

        try {
            // Calculate totals by looping over the line items.
            $total = 0;
            $totalQty = 0;
            foreach ($request->items as $item) {
                $total += $item['quantity'] * $item['unit_cost']; // line total = qty × cost
                $totalQty += $item['quantity'];
            }

            // Create the purchase header row.
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'stock_notification_id' => $request->stock_notification_id,
                'reference_number' => Purchase::generateReferenceNumber(), // e.g. PUR-00005
                'status' => 'draft',
                'total_amount' => $total,
                'total_quantity' => $totalQty,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Remember which notification(s) this purchase resolves (if any).
            if ($notificationIds->isNotEmpty()) {
                StockNotification::whereIn('id', $notificationIds)
                    ->where('fulfilled', false)
                    ->update(['purchase_id' => $purchase->id]);
            }

            // Create one PurchaseItem row per line item.
            foreach ($request->items as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'type' => $item['type'] ?? null,
                ]);
            }

            DB::commit(); // all good → save permanently

            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Purchase {$purchase->reference_number} created as draft.");
        } catch (\Exception $e) {
            DB::rollBack(); // something failed → undo everything
            return back()->withInput()->with('error', 'Failed to create purchase: ' . $e->getMessage());
        }
    }

    /**
     * Shows the detail page for ONE purchase (used by all roles to view).
     */
    public function show(Purchase $purchase)
    {
        // Load all related data needed by the view.
        $purchase->load(['supplier', 'items.product', 'creator', 'approver', 'receiver']);

        // If this user arrived here by clicking a notification, the purchase
        // "form" (Approve / Receive / Edit buttons) is now on screen — so mark
        // their unread alerts linked to this purchase as read.
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->where('purchase_id', $purchase->id)
            ->update(['is_read' => true]);

        return view('purchases.show', compact('purchase'));
    }

    /**
     * Purchase Officer: show the "Edit Draft" form.
     */
    public function edit(Purchase $purchase)
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can edit purchases.');
        }

        // Only DRAFT purchases can be edited (once submitted, it's locked).
        if (!in_array($purchase->status, ['draft'])) {
            return back()->with('error', 'Only draft purchases can be edited.');
        }

        // Data for the edit form.
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $products = Product::where('status', true)->orderBy('name')->get();
        $purchase->load('items.product');
        $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->product_code, 'brand' => $p->brand, 'price' => $p->purchase_price])->values();
        $existingItemsJson = $purchase->items->map(fn($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_cost' => $item->unit_cost, 'type' => $item->type])->values();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'productsJson', 'existingItemsJson'));
    }

    /**
     * Purchase Officer: save changes to a draft purchase.
     */
    public function update(Request $request, Purchase $purchase)
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can update purchases.');
        }

        if (!in_array($purchase->status, ['draft'])) {
            return back()->with('error', 'Only draft purchases can be updated.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $total = 0;
            $totalQty = 0;
            foreach ($request->items as $item) {
                $total += $item['quantity'] * $item['unit_cost'];
                $totalQty += $item['quantity'];
            }

            // Update the header totals/supplier/notes.
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'total_amount' => $total,
                'total_quantity' => $totalQty,
                'notes' => $request->notes,
            ]);

            // Simplest approach: delete all old line items, then re-create them
            // from the submitted (edited) list.
            $purchase->items()->delete();
            foreach ($request->items as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'type' => $item['type'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Purchase {$purchase->reference_number} updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update purchase: ' . $e->getMessage());
        }
    }

    /**
     * Purchase Officer: delete a draft purchase.
     */
    public function destroy(Purchase $purchase)
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can delete purchases.');
        }

        // Only drafts can be deleted.
        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be deleted.');
        }

        // Remove the line items first (FK constraint), then the header.
        $purchase->items()->delete();
        $purchase->delete();

        return back()->with('success', 'Purchase deleted successfully.');
    }

    /**
     * Purchase Officer: submit a draft — moves it to "pending".
     * This hands it over to the Inventory Manager.
     */
    public function submit(Purchase $purchase)
    {
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can submit purchases.');
        }

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be submitted.');
        }

        // Change draft → pending.
        $purchase->update(['status' => 'pending']);

        // Notify all Finance users that a purchase is awaiting financial approval.
        NotificationService::notifyRole(
            'finance',
            'purchase',
            'New Purchase Submitted',
            "Purchase {$purchase->reference_number} (Supplier: {$purchase->supplier?->name}) was submitted and is awaiting your approval.",
            null,
            $purchase->id
        );

        return back()->with('success', "Purchase {$purchase->reference_number} submitted. Waiting for Finance to approve.");
    }

    /**
     * Inventory Manager: receive the physical stock after Finance has approved.
     * Marks the purchase as "received", generates a RECEIPT number, and THIS is
     * where the actual stock numbers are increased (since the goods have
     * physically arrived); a stock movement (ledger) record is created per item.
     */
    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'approved') {
            return back()->with('error', 'Only approved purchases can be received.');
        }

        DB::beginTransaction();

        try {
            $purchase->update([
                'status' => 'received',
                'received_by' => Auth::id(),          // who received it
                'received_at' => now(),               // when
                'receipt_number' => Purchase::generateReceiptNumber(), // e.g. REC-00001
            ]);

            // For every item in this purchase, add its quantity to stock.
            foreach ($purchase->items as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)->first();

                if ($inventory) {
                    // Product already has an inventory record → just add the quantity.
                    $inventory->increment('quantity', $item->quantity);
                } else {
                    // First time this product ever gets stock → create an inventory row.
                    Inventory::create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'minimum_stock' => 0,
                    ]);
                }

                // Record a ledger entry so we have an audit trail of the change.
                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'purchase',
                    'quantity' => $item->quantity,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => "Purchase {$purchase->reference_number} (Receipt: {$purchase->receipt_number}) received. Stock updated.",
                    'created_by' => Auth::id(),
                ]);
            }

            // Mark the original low-stock notification(s) as resolved
            // (fulfilled) — works for both single and multi-select purchases.
            StockNotification::where('purchase_id', $purchase->id)->update(['fulfilled' => true]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to receive: ' . $e->getMessage());
        }

        // Clean up any earlier "workflow alert" notifications linked to this
        // purchase (e.g. the original "awaiting approval" alert) so they don't
        // linger as stale action items. The receive confirmations below are
        // created fresh after this cleanup and are kept.
        Notification::where('purchase_id', $purchase->id)
            ->where('type', 'purchase')
            ->delete();

        // Notify all Finance users that the stock has been received.
        NotificationService::notifyRole(
            'finance',
            'purchase',
            'Purchase Received',
            "Purchase {$purchase->reference_number} was received (Receipt: {$purchase->receipt_number}) and stock has been updated."
        );

        // Also let the purchase officer who created it know it was received.
        NotificationService::notifyUser(
            $purchase->created_by,
            'purchase',
            'Purchase Received',
            "Your purchase {$purchase->reference_number} has been received and stock updated.",
            null,
            $purchase->id
        );

        return back()->with('success', "Purchase {$purchase->reference_number} received. Receipt {$purchase->receipt_number} generated and stock updated.");
    }

    /**
     * Finance: approve a pending purchase.
     * This is a financial approval ONLY — no stock changes here. Once Finance
     * approves, the purchase moves to "approved" and the Inventory Manager
     * physically receives the goods (stock is updated at that point).
     */
    public function approve(Purchase $purchase)
    {
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Only pending purchases can be approved.');
        }

        // Mark as finance-approved + who/when.
        $purchase->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Clear the original "awaiting approval" workflow alert now that Finance
        // has acted; the "approved" confirmations created below are kept.
        Notification::where('purchase_id', $purchase->id)
            ->where('type', 'purchase')
            ->delete();

        // Notify all Inventory Managers that the purchase is approved and
        // physically ready to be received into stock.
        NotificationService::notifyRole(
            'inventory-manager',
            'approval',
            'Purchase Approved — Awaiting Receipt',
            "Purchase {$purchase->reference_number} was approved by Finance and is now ready for you to receive into stock.",
            null,
            $purchase->id
        );

        // Also let the purchase officer know finance approved it.
        NotificationService::notifyUser(
            $purchase->created_by,
            'approval',
            'Purchase Approved',
            "Purchase {$purchase->reference_number} was approved by Finance. Waiting for Inventory to receive the goods.",
            null,
            $purchase->id
        );

        return back()->with('success', "Purchase {$purchase->reference_number} approved. Awaiting Inventory Manager to receive the stock.");
    }

    /**
     * Reject a purchase (used by Finance on pending, or Inventory Manager on approved).
     * Simply moves the status to "rejected".
     */
    public function reject(Purchase $purchase)
    {
        // Only pending or approved purchases can be rejected.
        if (!in_array($purchase->status, ['pending', 'approved'])) {
            return back()->with('error', 'Only pending or approved purchases can be rejected.');
        }

        $previousStatus = $purchase->status;

        $purchase->update(['status' => 'rejected']);

        // Notify the purchase officer who created it about the rejection,
        // describing who rejected it (Finance vs Inventory).
        $stage = $previousStatus === 'pending' ? 'Finance' : 'Inventory Manager';
        NotificationService::notifyUser(
            $purchase->created_by,
            'rejection',
            'Purchase Rejected',
            "Purchase {$purchase->reference_number} was rejected by the {$stage}.",
            null,
            $purchase->id
        );

        return back()->with('success', "Purchase {$purchase->reference_number} rejected.");
    }
}
