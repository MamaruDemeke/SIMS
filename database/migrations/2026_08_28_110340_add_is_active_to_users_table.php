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
        Schema::table('users', function (Blueprint $table) {
            // Add an "active/deactivated" flag. Defaults to true (active) so
            // existing users stay active. after('role_id') places the column nice.
            $table->boolean('is_active')->default(true)->after('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the column if we ever roll this migration back.
            $table->dropColumn('is_active');
        });
    }
};
