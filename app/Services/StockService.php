<?php

namespace App\Services;

// Imports: models and helpers this service uses.
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * A "Service" is a helper class that groups related business logic so controllers
 * stay thin and reusable. This one handles stock-related tasks:
 *   1. recording a stock movement and updating the current quantity
 *   2. checking whether a product is low/out of stock
 *
 * It's used by the InventoryController (and would be used by future sales code).
 */
class StockService
{
    /**
     * Records a stock movement AND updates the actual inventory quantity.
     *
     * @param int         $productId     which product moved
     * @param string      $type          sale | supplier_return (subtract) / other (add)
     * @param int         $quantity      how many units moved
     * @param string|null $referenceType source model, e.g. 'App\Models\Purchase'
     * @param int|null    $referenceId   id of that source record
     * @param string|null $note          human note
     * @return InventoryMovement          the created movement record
     */
    public function recordMovement(
        int $productId,
        string $type,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null
    ): InventoryMovement {
        // Find the product's current inventory row.
        $inventory = Inventory::where('product_id', $productId)->first();

        if ($inventory) {
            // Sales and supplier returns REMOVE stock; everything else ADDS stock.
            if ($type === 'sale' || $type === 'supplier_return') {
                $inventory->quantity -= $quantity;
            } else {
                $inventory->quantity += $quantity;
            }

            // Save the updated quantity back to the DB.
            $inventory->save();

            // After changing the level, re-check whether it's now low/out of stock.
            $this->checkLowStock($inventory);
        }

        // Create a ledger/audit row describing the movement.
        return InventoryMovement::create([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'created_by' => Auth::id(), // who did it
        ]);
    }

    /**
     * Checks a product's stock level. Called after any stock change.
     *
     * NOTE: This no longer auto-creates a bell "Low Stock Alert" notification.
     * Purchase requests are only created deliberately by the Inventory Manager
     * via the stock-alerts "notify" button, so the purchase officer sees exactly
     * one (non-duplicate) purchase request rather than a redundant bell alert.
     */
    public function checkLowStock(Inventory $inventory): void
    {
        $product = $inventory->product;

        // If the product record was somehow missing, nothing to check.
        if (!$product) {
            return;
        }

        // Stock levels no longer fire a bell notification here. The Inventory
        // Manager decides when to send a purchase request via the stock alerts page.
    }
}
