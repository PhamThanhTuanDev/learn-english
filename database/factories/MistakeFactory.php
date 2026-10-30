<?php

namespace Database\Factories;

use App\Enums\MistakeCategory;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mistake>
 */
class MistakeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wrong' => 'She go to work by bus.',
            'correct' => 'She goes to work by bus.',
            'category' => MistakeCategory::Grammar,
            'note' => null,
            'week' => 1,
            'occurrences' => 1,
        ];
    }
}
