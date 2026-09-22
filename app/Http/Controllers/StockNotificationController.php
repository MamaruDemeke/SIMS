<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\StockNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles Stock Notifications (low/out-of-stock alerts that trigger purchases).
 *
 * Two roles use this:
 *   Inventory Manager → index() + notify()   (create alerts when stock is low)
 *   Purchase Officer  → pending()            (see open alerts to buy stock)
 */
class StockNotificationController extends Controller
{
    /**
     * Inventory Manager: show low-stock & out-of-stock products,
     * plus any notifications this manager has already sent.
     */
    public function index()
    {
        // Active products whose inventory quantity is between 1 and minimum (low, but not zero).
        $lowStockItems = Product::with('inventory')
            ->where('status', true)
            ->whereHas('inventory', function ($q) {
                $q->whereColumn('quantity', '<', 'minimum_stock')
                ->where('quantity', '>', 0);
            })
            ->get();

        // Active products with zero or negative stock (fully out).
        $outOfStockItems = Product::with('inventory')
            ->where('status', true)
            ->whereHas('inventory', function ($q) {
                $q->where('quantity', '<=', 0);
            })
            ->get();

        // Notifications this manager previously sent (for history).
        $sentNotifications = StockNotification::with('product', 'purchase')
            ->where('notified_by', Auth::id())
            ->latest()
            ->paginate(20);

        return view('stock-notifications.index', compact('lowStockItems', 'outOfStockItems', 'sentNotifications'));
    }

    /**
     * Inventory Manager: create a stock notification for a specific product.
     * This basically says "this product needs purchasing".
     */
    public function notify(Request $request)
    {
        $request->validate([
            'product_id'          => 'required|exists:products,id',
            'suggested_quantity'  => 'nullable|integer|min:1',
        ]);

        // Load the product with its inventory record.
        $product = Product::with('inventory')->findOrFail($request->product_id);
        $inventory = $product->inventory;

        // Can't notify (and can't check stock) if there's no inventory record.
        if (!$inventory) {
            return back()->with('error', 'No inventory record for this product.');
        }

        // Prevent duplicate alerts: if this product already has an OPEN (unfulfilled)
        // notification, refuse to create another one.
        $existingPending = StockNotification::where('product_id', $product->id)
            ->where('fulfilled', false)
            ->first();

        if ($existingPending) {
            return back()->with('error', 'A pending notification already exists for this product.');
        }

        // Decide the type based on current quantity.
        $type = $inventory->quantity <= 0 ? 'out_of_stock' : 'low_stock';

        // Build a human-readable message.
        $message = $type === 'out_of_stock'
            ? "{$product->name} is out of stock (0 {$product->unit}). Purchase needed."
            : "{$product->name} is low on stock ({$inventory->quantity} {$product->unit}, minimum: {$inventory->minimum_stock}). Purchase needed.";

        // Save the notification.
        StockNotification::create([
            'product_id' => $product->id,
            'current_quantity' => $inventory->quantity,
            'minimum_stock' => $inventory->minimum_stock,
            'suggested_quantity' => $request->filled('suggested_quantity') ? (int) $request->suggested_quantity : null,
            'type' => $type,
            'message' => $message,
            'notified_by' => Auth::id(),
        ]);

        // Deliver an in-app notification to every Purchase Officer, so it shows
        // up as unread on their bell icon.
        NotificationService::notifyRole(
            'purchase-officer',
            $type,
            'Purchase request: ' . $product->name,
            $message,
            $product->id
        );

        return back()->with('success', "Stock notification sent for {$product->name}.");
    }

    /**
     * Purchase Officer: view the open (pending) notifications — the "to-do list"
     * of products that need to be purchased.
     */
    public function pending()
    {
        // Only open notifications, newest first, with product + notifier loaded.
        $notifications = StockNotification::with('product', 'notifier')
            ->pending()
            ->latest()
            ->paginate(20);

        // The Purchase Officer has now seen the pending requests (the "form").
        // Clear their unread in-app stock notifications so the bell badge resets.
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->whereNotNull('product_id')
            ->update(['is_read' => true]);

        return view('stock-notifications.pending', compact('notifications'));
    }

    /**
     * Inventory Manager: delete selected sent notifications (bulk delete).
     * Only notifications this manager sent can be deleted.
     */
    public function destroySelected(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        $count = StockNotification::where('notified_by', Auth::id())
            ->whereIn('id', $ids)
            ->delete();

        return back()->with('success', $count . ' notification(s) deleted.');
    }

    /**
     * Inventory Manager: notify the Purchase Officer to buy a (possibly new) product.
     * Unlike notify(), this works even when the product has no inventory record yet,
     * so a brand-new product can be flagged for purchase immediately.
     */
    public function notifyPurchase(Request $request, Product $product)
    {
        // Optional quantity the manager wants to buy — becomes the default qty
        // on the purchase form.
        $request->validate([
            'suggested_quantity' => 'nullable|integer|min:1',
        ]);

        // Prevent duplicates: refuse if this product already has an open alert.
        $existingPending = StockNotification::where('product_id', $product->id)
            ->where('fulfilled', false)
            ->first();

        if ($existingPending) {
            return back()->with('error', 'A pending notification already exists for this product.');
        }

        $inventory = $product->inventory;
        $quantity = $inventory->quantity ?? 0;
        $minimum = $inventory->minimum_stock ?? 0;

        $needsPurchase = $quantity <= 0;
        $type = $needsPurchase ? 'out_of_stock' : 'low_stock';
        $message = $needsPurchase
            ? "{$product->name} needs to be purchased (currently no stock). New product."
            : "{$product->name} is low on stock ({$quantity} {$product->unit}, minimum: {$minimum}). Purchase needed.";

        StockNotification::create([
            'product_id' => $product->id,
            'current_quantity' => $quantity,
            'minimum_stock' => $minimum,
            'suggested_quantity' => $request->filled('suggested_quantity') ? (int) $request->suggested_quantity : null,
            'type' => $type,
            'message' => $message,
            'notified_by' => Auth::id(),
        ]);

        // Deliver an in-app notification to every Purchase Officer so it shows
        // up as unread on their bell icon.
        NotificationService::notifyRole(
            'purchase-officer',
            $type,
            'Purchase request: ' . $product->name,
            $message,
            $product->id
        );

        return back()->with('success', "Purchase Officer notified to buy {$product->name}.");
    }
}
