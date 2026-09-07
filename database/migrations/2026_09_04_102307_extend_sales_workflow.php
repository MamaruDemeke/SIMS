<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes the status enum so sales go through a Finance approval
     * workflow (pending -> approved -> rejected) instead of being
     * completed instantly. Stock is only deducted on approval.
     *
     * NOTE: we widen the enum to a superset with raw SQL first, convert the
     * existing rows, then narrow it. This avoids MySQL strict-mode rejecting
     * the new values while the old enum is still active.
     */
    public function up(): void
    {
        // 1) Widen the enum to include BOTH old and new values so existing
        //    rows ('completed'/'cancelled') remain valid during conversion.
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed','cancelled','pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        // 2) Convert existing rows to the new workflow states.
        DB::table('sales')->where('status', 'completed')->update(['status' => 'approved']);
        DB::table('sales')->where('status', 'cancelled')->update(['status' => 'rejected']);

        // 3) Narrow the enum to the final workflow values.
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        // 4) Add approval tracking columns (Laravel FK helper).
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('approved_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approved_at']);
        });

        // Widen back, convert, then narrow to the original values.
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('sales')->where('status', 'approved')->update(['status' => 'completed']);
        DB::table('sales')->where('status', 'rejected')->update(['status' => 'cancelled']);
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed','cancelled') NOT NULL DEFAULT 'completed'");
    }
};
