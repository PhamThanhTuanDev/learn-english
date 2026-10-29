<?php

namespace App\Enums;

/**
 * The four answer buttons of a flashcard review.
 */
enum Rating: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;

    /**
     * The button's label on the review screen.
     */
    public function label(): string
    {
        return match ($this) {
            self::Again => 'Quên',
            self::Hard => 'Khó',
            self::Good => 'Nhớ',
            self::Easy => 'Dễ',
        };
    }

    /**
     * The SM-2 response quality (0–5). Below 3 means the card was forgotten.
     */
    public function quality(): int
    {
        return match ($this) {
            self::Again => 1,
            self::Hard => 3,
            self::Good => 4,
            self::Easy => 5,
        };
    }
}
