<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a stock notification (a request to purchase a low/out-of-stock product)
 * in the stock_notifications table.
 * The Inventory Manager creates these when stock is low; the Purchase Officer
 * sees them and creates a Purchase to fix it. Once fulfilled, they are marked done.
 */
class StockNotification extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'product_id',        // which product needs restocking
        'current_quantity',  // stock level when the alert was created
        'minimum_stock',     // the minimum threshold that triggered it
        'type',              // 'out_of_stock' | 'low_stock'
        'message',           // human-readable alert text
        'fulfilled',         // true once a purchase has been approved to fix it
        'notified_by',       // user (Inventory Manager) who sent it
        'purchase_id',       // the purchase created to resolve it (once created)
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'fulfilled' => 'boolean',
        ];
    }

    /**
     * Relationship: a notification belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: the User who sent the notification (custom foreign key).
     */
    public function notifier()
    {
        return $this->belongsTo(User::class, 'notified_by');
    }

    /**
     * Relationship: the Purchase created to resolve this notification (if any).
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Query scope: only gets notifications that have NOT been fulfilled yet.
     * Usage: StockNotification::pending()  → only open/unresolved alerts.
     */
    public function scopePending($query)
    {
        return $query->where('fulfilled', false);
    }

    /**
     * Accessor ($notification->type_label).
     * Shows "Out of Stock" or "Low Stock" depending on the type code.
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'out_of_stock' ? 'Out of Stock' : 'Low Stock';
    }

    /**
     * Accessor ($notification->type_variant).
     * Returns a color for the status badge (danger vs warning).
     */
    public function getTypeVariantAttribute(): string
    {
        return $this->type === 'out_of_stock' ? 'danger' : 'warning';
    }
}
