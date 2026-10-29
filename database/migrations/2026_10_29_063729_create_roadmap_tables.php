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
        Schema::create('roadmap_resources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('source');
            $table->text('usage');
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('title');
            $table->text('goal');
            $table->json('blocks');
            $table->json('notes');
            $table->timestamps();
        });

        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->foreignId('stage_id')->constrained();
            $table->unsignedTinyInteger('first_week');
            $table->unsignedTinyInteger('last_week');
            $table->text('focus');
            $table->text('gate');
            $table->timestamps();
        });

        Schema::create('weeks', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->foreignId('month_id')->constrained();
            $table->string('title');
            $table->json('details');
            $table->timestamps();
        });

        Schema::create('roadmap_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->foreignId('week_id')->constrained();
            $table->unsignedTinyInteger('day');
            $table->string('label');
            $table->text('task');
            $table->boolean('is_rest')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadmap_days');
        Schema::dropIfExists('weeks');
        Schema::dropIfExists('months');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('roadmap_resources');
    }
};
