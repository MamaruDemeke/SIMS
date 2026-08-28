<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('current_quantity');
            $table->integer('minimum_stock');
            $table->enum('type', ['low_stock', 'out_of_stock']);
            $table->text('message')->nullable();
            $table->boolean('fulfilled')->default(false);
            $table->foreignId('notified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notifications');
    }
};
