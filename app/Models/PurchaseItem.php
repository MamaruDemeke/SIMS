<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents one line within a Purchase order in the purchase_items table.
 * A purchase (the order) contains many items (one row per product bought).
 */
class PurchaseItem extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'purchase_id', // the purchase this item belongs to (foreign key)
        'product_id',  // which product is being bought
        'quantity',    // how many units
        'unit_cost',   // cost per single unit
        'type',        // optional product type
        'diameter',    // optional rebar diameter
        'size',        // optional size
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',   // whole number
            'unit_cost' => 'decimal:2', // money with 2 decimals
        ];
    }

    /**
     * Relationship: a PurchaseItem belongs to one Purchase.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relationship: a PurchaseItem belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor ($item->line_total).
     * Computes the total cost of this line: quantity × unit cost.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }
}
