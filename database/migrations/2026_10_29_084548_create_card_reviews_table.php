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
        // One row per answer, for retention stats (P4-05) and a later move to FSRS (D20).
        Schema::create('card_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            // The card's schedule after this answer.
            $table->decimal('ease_factor', 4, 2);
            $table->unsignedInteger('interval_days');
            $table->date('due_on');
            // dateTime, not timestamp (D16).
            $table->dateTime('reviewed_at');

            $table->index(['user_id', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_reviews');
    }
};
