<?php

namespace Database\Factories;

use App\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word' => fake()->unique()->lexify('????????'),
            'part_of_speech' => 'noun',
            'meaning' => fake()->sentence(3),
            'level' => 'A1',
        ];
    }

    /**
     * A word at the given CEFR level, or without one.
     */
    public function level(?string $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }
}
