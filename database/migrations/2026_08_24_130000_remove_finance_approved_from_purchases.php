<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['finance_approved_by']);
            $table->dropColumn(['finance_approved_by', 'finance_approved_at']);
        });

        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft','pending','received','approved','rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('received_at');
            $table->timestamp('finance_approved_at')->nullable()->after('finance_approved_by');
        });

        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft','pending','received','finance_approved','approved','rejected') NOT NULL DEFAULT 'draft'");
    }
};
