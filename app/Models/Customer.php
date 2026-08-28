<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a customer (a person/company who buys from you) in the customers table.
 */
class Customer extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = ['name', 'phone', 'email', 'address', 'status'];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Relationship: one Customer has many Sales.
     * NOTE: The Sale model is not created yet — this is a placeholder for future
     * functionality. Calling $customer->sales before the Sale model exists will fail.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
