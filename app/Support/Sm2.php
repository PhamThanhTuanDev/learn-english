<?php

namespace App\Support;

use App\Enums\Rating;

/**
 * The SuperMemo-2 algorithm (D20).
 *
 * After a successful answer the next interval is 1 day, then 6 days, then the
 * previous interval times the ease factor, rounded up. The ease factor then
 * moves by the answer's quality and never drops below 1.3. A forgotten card
 * starts its repetitions again, due the same day, with its ease unchanged.
 */
final class Sm2
{
    public const INITIAL_EASE = 2.5;

    public const MIN_EASE = 1.3;

    /**
     * @return array{ease_factor: float, interval_days: int, repetitions: int}
     */
    public static function next(float $easeFactor, int $intervalDays, int $repetitions, Rating $rating): array
    {
        $quality = $rating->quality();

        if ($quality < 3) {
            return ['ease_factor' => $easeFactor, 'interval_days' => 0, 'repetitions' => 0];
        }

        // round() before ceil() so float noise such as 15.000000000000002
        // does not push an interval up a day.
        return [
            'ease_factor' => max(self::MIN_EASE, round($easeFactor + 0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02), 2)),
            'interval_days' => match ($repetitions) {
                0 => 1,
                1 => 6,
                default => (int) ceil(round($intervalDays * $easeFactor, 6)),
            },
            'repetitions' => $repetitions + 1,
        ];
    }
}
