<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a supplier (a company/vendor you buy goods from) in the suppliers table.
 */
class Supplier extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'status',
        'default_type',       // optional: default product type this supplier provides
        'default_diameter',   // optional: default rebar diameter
        'default_size',       // optional: default size
    ];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Relationship: one Supplier has many Purchases (orders placed with them).
     * Usage: $supplier->purchases -> collection of Purchase objects.
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
