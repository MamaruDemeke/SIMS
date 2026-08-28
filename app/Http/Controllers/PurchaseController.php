<?php

namespace App\Http\Controllers;

// Imports: all models and helpers used in this controller.
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockNotification;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles the Purchase workflow — the heart of how new stock gets bought.
 *
 * The workflow (statuses) is:
 *   draft     → Purchase Officer creates & edits
 *   pending   → Purchase Officer "submits"; waits for Inventory Manager
 *   received  → Inventory Manager "receives" the physical goods → generates Receipt # → goes to Finance
 *   approved  → Finance "approves" → ONLY NOW does stock actually increase
 *   rejected  → cancelled at any of the pending/received stages
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
     * Inventory Manager: list purchases that are "pending" (awaiting physical receipt).
     */
    public function pendingList(Request $request)
    {
        // Only status = pending.
        $query = Purchase::with('supplier', 'creator')->where('status', 'pending')->latest();

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
     * Finance: list purchases that are "received" (awaiting financial approval).
     * Also eager-loads the receiver so Finance can see who received the goods.
     */
    public function receiptList(Request $request)
    {
        $query = Purchase::with('supplier', 'creator', 'receiver')->where('status', 'received')->latest();

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
     * It pre-fills the form with pending stock notifications (which products need buying).
     */
    public function create()
    {
        // Only Purchase Officers may create purchases.
        if (Auth::user()->role?->slug !== 'purchase-officer') {
            abort(403, 'Only Purchase Officer can create purchases.');
        }

        // Active suppliers to choose from.
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        // Pending (not yet fulfilled) stock notifications — the "shopping list".
        $notifications = StockNotification::with('product')->pending()->latest()->get();

        // Unique product ids from those notifications → the products selectable.
        $productIds = $notifications->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->orderBy('name')->get();

        // Convert collections to JSON so JavaScript (in the form) can use them dynamically.
        $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->product_code, 'price' => $p->purchase_price])->values();
        $notificationsJson = $notifications->map(fn($n) => ['id' => $n->id, 'product_id' => $n->product_id, 'type' => $n->type, 'message' => $n->message, 'current_quantity' => $n->current_quantity])->values();

        return view('purchases.create', compact('suppliers', 'products', 'productsJson', 'notifications', 'notificationsJson'));
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
            'stock_notification_id' => 'required|exists:stock_notifications,id', // which alert we're resolving
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1', // at least one line
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Fetch the notification; make sure it isn't already fulfilled.
        $notification = StockNotification::findOrFail($request->stock_notification_id);
        if ($notification->fulfilled) {
            return back()->withInput()->with('error', 'This notification has already been fulfilled.');
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
                'stock_notification_id' => $notification->id,
                'reference_number' => Purchase::generateReferenceNumber(), // e.g. PUR-00005
                'status' => 'draft',
                'total_amount' => $total,
                'total_quantity' => $totalQty,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Remember which notification this purchase resolves.
            $notification->update(['purchase_id' => $purchase->id]);

            // Create one PurchaseItem row per line item.
            foreach ($request->items as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'type' => $item['type'] ?? null,
                    'diameter' => $item['diameter'] ?? null,
                    'size' => $item['size'] ?? null,
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
        $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->product_code, 'price' => $p->purchase_price])->values();
        $existingItemsJson = $purchase->items->map(fn($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_cost' => $item->unit_cost, 'type' => $item->type, 'diameter' => $item->diameter, 'size' => $item->size])->values();

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
                    'diameter' => $item['diameter'] ?? null,
                    'size' => $item['size'] ?? null,
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

        return back()->with('success', "Purchase {$purchase->reference_number} submitted. Waiting for Inventory Manager to receive.");
    }

    /**
     * Inventory Manager: receive the physical stock.
     * Marks the purchase as "received" and generates a RECEIPT number for Finance.
     * NOTE: Stock quantity is NOT changed here — that happens on approve().
     */
    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Only pending purchases can be received.');
        }

        $purchase->update([
            'status' => 'received',
            'received_by' => Auth::id(),          // who received it
            'received_at' => now(),               // when
            'receipt_number' => Purchase::generateReceiptNumber(), // e.g. REC-00001
        ]);

        return back()->with('success', "Purchase {$purchase->reference_number} received. Receipt {$purchase->receipt_number} sent to Finance.");
    }

    /**
     * Finance: approve a received purchase.
     * THIS is where the actual stock numbers are increased,
     * and a stock movement (ledger) record is created for each item.
     */
    public function approve(Purchase $purchase)
    {
        if ($purchase->status !== 'received') {
            return back()->with('error', 'Only received purchases can be approved.');
        }

        DB::beginTransaction();

        try {
            // Mark as approved + who/when.
            $purchase->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
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
                    'note' => "Purchase {$purchase->reference_number} (Receipt: {$purchase->receipt_number}) approved. Stock updated.",
                    'created_by' => Auth::id(),
                ]);
            }

            // Mark the original low-stock notification as resolved (fulfilled).
            if ($purchase->stock_notification_id) {
                StockNotification::where('id', $purchase->stock_notification_id)->update(['fulfilled' => true]);
            }

            DB::commit();

            return back()->with('success', "Purchase {$purchase->reference_number} approved. Stock updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Reject a purchase (used by Inventory Manager on pending, or Finance on received).
     * Simply moves the status to "rejected".
     */
    public function reject(Purchase $purchase)
    {
        // Only pending or received purchases can be rejected.
        if (!in_array($purchase->status, ['pending', 'received'])) {
            return back()->with('error', 'Only pending or received purchases can be rejected.');
        }

        $purchase->update(['status' => 'rejected']);

        return back()->with('success', "Purchase {$purchase->reference_number} rejected.");
    }
}
