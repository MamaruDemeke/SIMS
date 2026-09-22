<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_notifications', function (Blueprint $table) {
            $table->integer('suggested_quantity')->nullable()->after('minimum_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_notifications', function (Blueprint $table) {
            $table->dropColumn('suggested_quantity');
        });
    }
};
