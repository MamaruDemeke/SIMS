<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a product category (a grouping/label) in the categories table.
 * Example: "Rebar", "Nails", "Wire" — each product belongs to one category.
 */
class Category extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = ['name', 'description', 'status'];

    // Defines how columns are typed when read from the DB.
    protected function casts(): array
    {
        return [
            'status' => 'boolean', // 1/0 in DB becomes true/false in PHP
        ];
    }

    /**
     * Relationship: one Category has many Products.
     * Usage: $category->products -> collection of products in this category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
