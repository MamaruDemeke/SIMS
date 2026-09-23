<?php

namespace App\Http\Controllers;

// Imports: models and helpers used.
use App\Models\Inventory;
use App\Models\Product;
use App\Services\StockService; // a helper class for stock-related logic
use Illuminate\Http\Request;
/**
 * Handles the Inventory (current stock) pages.
 * Protected by the "role:inventory" middleware.
 */
class InventoryController extends Controller
{
    /**
     * Lists current stock, with filters for search and stock status
     * (out of stock / low / good).
     */
    public function index(Request $request)
    {
        // Query all inventory rows, pre-loading each product and its category.
        $query = Inventory::with('product.category');

        // Search by product name or product code.
        if ($request->filled('search')) {
            $search = $request->search;
            // whereHas: filter by something on the RELATED product table.
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filter by stock health status.
        if ($request->filled('stock_status')) {
            $status = $request->stock_status;
            if ($status === 'out') {
                $query->where('quantity', '<=', 0);                        // out of stock
            } elseif ($status === 'low') {
                // low = quantity is between 1 and minimum_stock
                $query->where('quantity', '>', 0)->whereColumn('quantity', '<', 'minimum_stock');
            } elseif ($status === 'good') {
                // good = quantity at or above minimum_stock
                $query->whereColumn('quantity', '>=', 'minimum_stock');
            }
        }

        // Order by quantity ascending (lowest stock first) so urgent items appear top.
        $inventory = $query->orderBy('quantity')->paginate(15)->withQueryString();

        return view('inventory.index', compact('inventory'));
    }

    /**
     * Shows the form to edit ONE inventory record (used to set the minimum_stock).
     */
    public function edit(Inventory $inventory)
    {
        return view('inventory.edit', compact('inventory'));
    }

    /**
     * Updates an inventory record's minimum_stock, then re-checks low-stock alerts.
     */
    public function update(Request $request, Inventory $inventory)
    {
        // Only the minimum_stock can be edited here (quantity changes happen via purchases/sales).
        $validated = $request->validate([
            'minimum_stock' => 'required|integer|min:0',
        ]);

        $inventory->update($validated);

        // After changing the threshold, re-evaluate whether this product is now low/out of stock
        // and create a notification if needed.
        $stockService = app(StockService::class);
        $stockService->checkLowStock($inventory);

        return redirect()->route('inventory.index')
            ->with('success', 'Stock settings updated successfully.');
    }
}
