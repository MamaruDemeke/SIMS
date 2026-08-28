<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a system notification in the notifications table.
 * These are app-wide alerts, e.g. "Product X is out of stock".
 * (Do NOT confuse with the Notification model from Laravel's notification system —
 * this is a custom, simpler in-app notification record.)
 */
class Notification extends Model
{
    // Tell Eloquent the exact table name.
    protected $table = 'notifications';

    // Which columns can be mass-filled.
    protected $fillable = [
        'type',       // e.g. 'out_of_stock' | 'low_stock'
        'title',      // short headline
        'message',    // body text
        'product_id', // related product (if any)
        'user_id',    // which user it targets (if any)
        'is_read',    // whether the user has seen it (false = unread, shown as a red dot badge)
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Relationship: a notification may belong to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: a notification may belong to one User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marks this notification as read (sets is_read = true).
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Query scope: only gets UNREAD notifications.
     * Usage: Notification::unread()->count()  → how many unread alerts exist.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
