<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockNotification;
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
            'product_id' => 'required|exists:products,id',
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
            'type' => $type,
            'message' => $message,
            'notified_by' => Auth::id(),
        ]);

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

        return view('stock-notifications.pending', compact('notifications'));
    }
}
