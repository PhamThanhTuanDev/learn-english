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
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('wrong');
            $table->text('correct');
            $table->string('category', 20);
            $table->string('note')->nullable();
            // Roadmap week 1–104; empty when logged outside the roadmap.
            $table->unsignedTinyInteger('week')->nullable();
            $table->unsignedSmallInteger('occurrences')->default(1);
            // dateTime, not timestamp: see the day_completions migration.
            $table->dateTime('last_repeated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
