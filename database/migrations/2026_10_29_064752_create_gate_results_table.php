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
        // One result per month; retaking the gate overwrites it.
        // Detailed scores per skill belong to P6-01.
        Schema::create('gate_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('month_id')->constrained();
            $table->boolean('passed');
            $table->text('note')->nullable();
            // dateTime, not timestamp (see create_day_completions_table).
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->unique(['user_id', 'month_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_results');
    }
};
