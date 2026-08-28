<?php

namespace App\Services;

// Imports: models and helpers this service uses.
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * A "Service" is a helper class that groups related business logic so controllers
 * stay thin and reusable. This one handles stock-related tasks:
 *   1. recording a stock movement and updating the current quantity
 *   2. checking whether a product is low/out of stock and creating alerts
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
     * Checks a product's stock level and creates a notification if it is
     * out of stock or running low. Called after any stock change.
     */
    public function checkLowStock(Inventory $inventory): void
    {
        $product = $inventory->product;

        // If the product record was somehow missing, nothing to check.
        if (!$product) {
            return;
        }

        // Out of stock (zero or negative).
        if ($inventory->quantity <= 0) {
            $this->createNotification(
                'out_of_stock',
                'Out of Stock',
                "{$product->name} ({$product->product_code}) is out of stock.",
                $product->id
            );
        }
        // Low stock (below minimum but still positive).
        elseif ($inventory->quantity < $inventory->minimum_stock) {
            $this->createNotification(
                'low_stock',
                'Low Stock Alert',
                "{$product->name} ({$product->product_code}) is running low. Current: {$inventory->quantity} {$product->unit}, Minimum: {$inventory->minimum_stock} {$product->unit}.",
                $product->id
            );
        }
        // Otherwise stock is fine — no notification.
    }

    /**
     * Private helper: actually saves a notification row to the database.
     */
    private function createNotification(string $type, string $title, string $message, int $productId): void
    {
        Notification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'product_id' => $productId,
            'is_read' => false, // starts as unread → shows the red badge
        ]);
    }
}
