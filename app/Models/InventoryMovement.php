<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a historical stock movement (an event that changed stock) 
 * in the inventory_movements table.
 * Examples of movement types: purchase (stock added), sale (stock removed),
 * customer_return, supplier_return, adjustment.
 * This is an audit/ledger trail — each change to stock gets a new row.
 */
class InventoryMovement extends Model
{
    // Tell Eloquent the exact table name.
    protected $table = 'inventory_movements';

    // Which columns can be mass-filled.
    protected $fillable = [
        'product_id',      // which product moved
        'type',            // purchase | sale | customer_return | supplier_return | adjustment
        'quantity',        // how many units moved (positive = added, meaningful sign per type)
        'reference_type',  // optional: the type of source document (e.g. 'App\Models\Purchase')
        'reference_id',    // optional: the id of that source document
        'note',            // human-readable note explaining the movement
        'created_by',      // user who caused the movement
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reference_id' => 'integer',
        ];
    }

    /**
     * Relationship: a movement belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: the User who created the movement (custom foreign key).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * "Polymorphic" relationship: the movement can link to ANY type of source
     * (a Purchase, a Sale, etc.). Laravel figures out the model from reference_type.
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Accessor ($movement->type_label).
     * Converts the raw type code into a readable label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'Purchase',
            'sale' => 'Sale',
            'customer_return' => 'Customer Return',
            'supplier_return' => 'Supplier Return',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->type),
        };
    }

    /**
     * Accessor ($movement->type_color).
     * Returns a color name for badges to visually distinguish movement types.
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'success',       // green
            'sale' => 'info',              // blue
            'customer_return' => 'purple',
            'supplier_return' => 'warning',// yellow
            'adjustment' => 'default',     // gray
            default => 'default',
        };
    }
}
