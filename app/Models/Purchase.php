<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Purchase order (a request order to buy stock) in the purchases table.
 * A purchase goes through a workflow: draft → pending → approved → received (or rejected).
 */
class Purchase extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'supplier_id',            // which supplier we are buying from
        'stock_notification_id',  // the low-stock alert that triggered this purchase (if any)
        'reference_number',       // internal order number, e.g. PUR-00001
        'receipt_number',         // receipt number generated when stock is received, e.g. REC-00001
        'status',                 // draft | pending | received | approved | rejected
        'total_amount',           // sum of all line totals
        'total_quantity',         // sum of all quantities
        'notes',                  // extra text from the purchase officer
        'created_by',             // user id who created it
        'approved_by',            // user id who approved it (finance)
        'approved_at',            // datetime of approval
        'received_by',            // user id who received the stock (inventory manager)
        'received_at',            // datetime of receiving
    ];

    // Defines how columns are typed/formatted when read from the DB.
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',  // money with 2 decimals
            'total_quantity' => 'decimal:2',
            'approved_at' => 'datetime',    // gives DateTime objects
            'received_at' => 'datetime',
        ];
    }

    /**
     * Relationship: a Purchase belongs to one Supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: one Purchase has many PurchaseItem rows (the lines/products bought).
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Relationship: the User who created the purchase (custom foreign key).
     * Usage: $purchase->creator->name
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: the User who approved the purchase.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship: the User who received the stock.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Relationship: the StockNotification (low-stock alert) this purchase is linked to.
     */
    public function stockNotification()
    {
        return $this->belongsTo(StockNotification::class);
    }

    /**
     * Static helper: generates the next reference number like "PUR-00002".
     * It reads the latest purchase's number, adds 1, and pads it with zeros.
     */
    public static function generateReferenceNumber(): string
    {
        $last = self::latest('id')->value('reference_number'); // get the last REF value
        $num = 1;                                              // default if none yet
        if ($last && preg_match('/PUR-(\d+)/', $last, $m)) {   // extract the number part
            $num = (int) $m[1] + 1;                            // increment it
        }
        // str_pad makes it 5 digits with leading zeros, e.g. 1 → "00001"
        return 'PUR-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Static helper: generates the next receipt number like "REC-00001".
     * Same logic as generateReferenceNumber but with the REC- prefix.
     */
    public static function generateReceiptNumber(): string
    {
        $last = self::whereNotNull('receipt_number')->latest('id')->value('receipt_number');
        $num = 1;
        if ($last && preg_match('/REC-(\d+)/', $last, $m)) {
            $num = (int) $m[1] + 1;
        }
        return 'REC-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * "Accessor" (getStatusLabelAttribute → $purchase->status_label).
     * Turns the raw status code into a friendly display label.
     */
    public function getStatusLabelAttribute(): string
    {
        // match is like a switch statement.
        return match($this->status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'received' => 'Received',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Accessor ($purchase->status_variant).
     * Returns a color name used by CSS badges to show the status visually.
     */
    public function getStatusVariantAttribute(): string
    {
        return match($this->status) {
            'draft' => 'default',   // gray
            'pending' => 'warning', // yellow
            'received' => 'info',   // blue
            'approved' => 'success',// green
            'rejected' => 'danger', // red
            default => 'default',
        };
    }
}
