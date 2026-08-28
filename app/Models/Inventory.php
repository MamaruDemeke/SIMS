<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents the current stock level of one product in the "inventory" table.
 * There is ONE inventory row per product, holding the live quantity on hand.
 */
class Inventory extends Model
{
    // Tell Eloquent the exact table name (normally would be "inventories").
    protected $table = 'inventory';

    // Which columns can be mass-filled.
    protected $fillable = ['product_id', 'quantity', 'minimum_stock'];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',       // whole number of units in stock
            'minimum_stock' => 'integer',  // the "danger" threshold
        ];
    }

    /**
     * Relationship: an Inventory record belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor ($inventory->stock_status).
     * Returns a short status string used to color/classify stock:
     * - "out"  → quantity is zero or negative (out of stock)
     * - "low"  → quantity below minimum (getting low)
     * - "good" → quantity is fine
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out';
        }

        if ($this->quantity < $this->minimum_stock) {
            return 'low';
        }

        return 'good';
    }
}
