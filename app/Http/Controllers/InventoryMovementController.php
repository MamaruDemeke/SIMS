<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use Illuminate\Http\Request;

/**
 * Handles the Stock Movements (history/ledger) page.
 * This is a read-only screen showing the audit trail of all stock changes.
 * Protected by the "role:inventory_movements" middleware.
 */
class InventoryMovementController extends Controller
{
    /**
     * Lists stock movements with filters for search, type, and date range.
     */
    public function index(Request $request)
    {
        // Query all movements, pre-loading the product and the user who made the move.
        $query = InventoryMovement::with('product', 'creator');

        // Search by product name or code (filter on the related product).
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filter by movement type (purchase, sale, return, adjustment...).
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by start date (created_at >= date_from).
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter by end date (created_at <= date_to).
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Show newest movements first, 20 per page.
        $movements = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Record that this user just viewed the movements page, so the sidebar
        // badge ("new movements") no longer counts them as new going forward.
        $user = auth()->user();
        $user->forceFill(['movements_viewed_at' => now()])->save();

        return view('inventory.movements', compact('movements'));
    }
}
