<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Sale (an invoice of products sold to a customer) in the sales table.
 * A sale goes through a Finance approval workflow:
 *   pending  -> awaiting Finance approval (no stock change yet)
 *   approved -> approved by Finance; stock is deducted on approval
 *   rejected -> rejected by Finance; stock was never touched
 * One sale contains many sale_items (one row per product sold).
 */
class Sale extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'customer_id',      // which customer bought (foreign key)
        'reference_number', // internal sale number, e.g. SAL-00001
        'status',           // pending | approved | rejected
        'total_amount',     // sum of all line totals
        'total_quantity',   // sum of all quantities
        'notes',            // extra text
        'created_by',       // the sales officer who recorded it
        'approved_by',      // the finance user who approved it
        'approved_at',      // when it was approved
        'sold_at',          // datetime of the sale
    ];

    // Defines how columns are typed/formatted when read from the DB.
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',   // money with 2 decimals
            'total_quantity' => 'decimal:2',
            'approved_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * Relationship: a Sale belongs to one Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: one Sale has many SaleItem rows (the lines/products sold).
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relationship: the User who recorded the sale (custom foreign key).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: the User (Finance) who approved the sale.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Static helper: generates the next sale number like "SAL-00002".
     */
    public static function generateReferenceNumber(): string
    {
        $last = self::latest('id')->value('reference_number');
        $num = 1;
        if ($last && preg_match('/SAL-(\d+)/', $last, $m)) {
            $num = (int) $m[1] + 1;
        }
        return 'SAL-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Accessor ($sale->status_label).
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Accessor ($sale->status_variant).
     */
    public function getStatusVariantAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning', // yellow/amber
            'approved' => 'success', // green
            'rejected' => 'danger',  // red
            default => 'default',
        };
    }
}
