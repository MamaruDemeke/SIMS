<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft','pending','received','finance_approved','approved','rejected') NOT NULL DEFAULT 'draft'");

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_by');
            $table->timestamp('received_at')->nullable()->after('approved_at');
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('received_at');
            $table->timestamp('finance_approved_at')->nullable()->after('finance_approved_by');
            $table->decimal('total_quantity', 12, 2)->nullable()->after('total_amount');
            $table->string('receipt_number')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropForeign(['finance_approved_by']);
            $table->dropColumn(['received_by', 'received_at', 'finance_approved_by', 'finance_approved_at', 'total_quantity', 'receipt_number']);
        });

        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft'");
    }
};
