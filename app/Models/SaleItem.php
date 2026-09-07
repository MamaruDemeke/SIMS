<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents one line within a Sale in the sale_items table.
 * A sale (the invoice) contains many items (one row per product sold).
 */
class SaleItem extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'sale_id',    // the sale this item belongs to (foreign key)
        'product_id', // which product is being sold
        'quantity',   // how many units
        'unit_price', // price per single unit
        'type',       // optional product type
        'diameter',   // optional rebar diameter
        'size',       // optional size
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',      // whole number
            'unit_price' => 'decimal:2',  // money with 2 decimals
        ];
    }

    /**
     * Relationship: a SaleItem belongs to one Sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship: a SaleItem belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor ($item->line_total).
     * Computes the total for this line: quantity × unit price.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
