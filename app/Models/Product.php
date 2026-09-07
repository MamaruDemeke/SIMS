<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single product (item you buy/sell) in the products table.
 * Example: "Rebar 12mm Grade 60" with a price and a category.
 */
class Product extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = [
        'category_id',     // which category this product belongs to (foreign key)
        'product_code',    // short unique code, e.g. "RB-12"
        'name',            // display name
        'unit',            // unit of measure, e.g. "pcs", "ton", "m"
        'diameter',        // optional: for rebar, the diameter in mm
        'length',          // optional: length in meters
        'grade',           // optional: steel grade, e.g. 60
        'purchase_price',  // how much we pay the supplier
        'selling_price',   // how much we charge customers
        'minimum_stock',   // alert when stock goes below this number
        'description',     // optional extra text
        'status',          // active (true) or inactive (false)
    ];

    // Defines how columns are typed/formatted when read from the DB.
    protected function casts(): array
    {
        return [
            'status' => 'boolean',          // true/false
            'purchase_price' => 'decimal:2', // always show 2 decimal places
            'selling_price' => 'decimal:2',
            'minimum_stock' => 'integer',   // whole number
        ];
    }

    /**
     * Relationship: a Product belongs to one Category.
     * Usage: $product->category -> the Category object (or null).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: a Product has ONE inventory (stock) record.
     * Usage: $product->inventory -> a single Inventory object.
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Relationship: many-to-many — a product can be sold by many suppliers,
     * a supplier sells many products. Uses the supplier_product pivot table.
     * Usage: $product->suppliers -> collection of Supplier objects.
     */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product', 'product_id', 'supplier_id');
    }

    /**
     * Query scope: allows filtering products by a search term.
     * A "scope" is a reusable query condition. Usage: Product::search('rebar')
     * This searches name, product_code, and description for the text.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('product_code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
