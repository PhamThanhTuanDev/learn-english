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
        // Shared dictionary loaded by app:import-vocabulary, so no user_id (D19).
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            // Empty string rather than null when unknown, so the unique key
            // still matches on the next import.
            $table->string('part_of_speech', 50)->default('');
            $table->string('pronunciation')->nullable();
            $table->text('meaning');
            $table->text('definition')->nullable();
            $table->text('definition_vi')->nullable();
            $table->text('example')->nullable();
            $table->text('example_vi')->nullable();
            $table->string('level', 2)->nullable()->index();
            $table->timestamps();

            $table->unique(['word', 'part_of_speech']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
