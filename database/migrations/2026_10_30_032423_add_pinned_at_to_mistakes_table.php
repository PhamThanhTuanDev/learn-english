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
        Schema::table('mistakes', function (Blueprint $table) {
            // Set while the mistake is one of the (at most 3) priorities to fix.
            $table->dateTime('pinned_at')->nullable()->after('last_repeated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropColumn('pinned_at');
        });
    }
};
