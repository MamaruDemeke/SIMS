<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products are classified by grade, so the same base product code is allowed
 * to repeat across different grades. This swaps the old single-column unique
 * constraint on `product_code` for a composite unique on `(product_code, grade)`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove the "one code only" rule.
            $table->dropUnique('products_product_code_unique');

            // Allow the same code in different grades, but not the same code+grade twice.
            $table->unique(['product_code', 'grade'], 'products_product_code_grade_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_product_code_grade_unique');

            // Restore the single-column unique on product_code.
            $table->unique('product_code');
        });
    }
};
