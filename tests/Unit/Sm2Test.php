<?php

namespace Tests\Unit;

use App\Enums\Rating;
use App\Support\Sm2;
use PHPUnit\Framework\TestCase;

class Sm2Test extends TestCase
{
    public function test_good_answers_space_a_new_card_1_day_then_6_days_then_by_the_ease_factor(): void
    {
        $this->assertSame(['ease_factor' => 2.5, 'interval_days' => 1, 'repetitions' => 1], Sm2::next(Sm2::INITIAL_EASE, 0, 0, Rating::Good));
        $this->assertSame(['ease_factor' => 2.5, 'interval_days' => 6, 'repetitions' => 2], Sm2::next(2.5, 1, 1, Rating::Good));
        $this->assertSame(['ease_factor' => 2.5, 'interval_days' => 15, 'repetitions' => 3], Sm2::next(2.5, 6, 2, Rating::Good));
        // 15 × 2.5 = 37.5, rounded up.
        $this->assertSame(['ease_factor' => 2.5, 'interval_days' => 38, 'repetitions' => 4], Sm2::next(2.5, 15, 3, Rating::Good));
    }

    public function test_hard_lowers_and_easy_raises_the_ease_factor_after_using_the_old_one(): void
    {
        $this->assertSame(['ease_factor' => 2.36, 'interval_days' => 15, 'repetitions' => 3], Sm2::next(2.5, 6, 2, Rating::Hard));
        $this->assertSame(['ease_factor' => 2.6, 'interval_days' => 15, 'repetitions' => 3], Sm2::next(2.5, 6, 2, Rating::Easy));
        $this->assertSame(['ease_factor' => 2.6, 'interval_days' => 1, 'repetitions' => 1], Sm2::next(2.5, 0, 0, Rating::Easy));
    }

    public function test_the_interval_rounds_up_to_whole_days(): void
    {
        // 6 × 2.36 = 14.16.
        $this->assertSame(15, Sm2::next(2.36, 6, 2, Rating::Good)['interval_days']);
    }

    public function test_the_ease_factor_never_drops_below_1_3(): void
    {
        $this->assertSame(['ease_factor' => 1.3, 'interval_days' => 14, 'repetitions' => 6], Sm2::next(1.35, 10, 5, Rating::Hard));
    }

    public function test_again_restarts_the_repetitions_due_today_and_keeps_the_ease_factor(): void
    {
        $this->assertSame(['ease_factor' => 2.2, 'interval_days' => 0, 'repetitions' => 0], Sm2::next(2.2, 40, 6, Rating::Again));
    }
}
