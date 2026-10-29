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
        // One flashcard per learner and word, scheduled with SM-2 (D20).
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained();
            $table->decimal('ease_factor', 4, 2)->default(2.5);
            // Not "interval": that is a reserved word in MariaDB.
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->unsignedSmallInteger('lapses')->default(0);
            // The learner's calendar date the card is due on, not a UTC instant.
            $table->date('due_on');
            $table->dateTime('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'word_id']);
            $table->index(['user_id', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
