<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the diameter/length concepts from products and order lines,
     * and add a "brand" attribute to products instead.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['diameter', 'length']);
            $table->string('brand')->nullable()->after('name');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['diameter', 'size']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['diameter', 'size']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('diameter')->nullable();
            $table->string('length')->nullable();
            $table->dropColumn('brand');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('diameter')->nullable();
            $table->string('size')->nullable();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('diameter')->nullable();
            $table->string('size')->nullable();
        });
    }
};