<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('default_type')->nullable()->after('address');
            $table->string('default_diameter')->nullable()->after('default_type');
            $table->string('default_size')->nullable()->after('default_diameter');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['default_type', 'default_diameter', 'default_size']);
        });
    }
};
