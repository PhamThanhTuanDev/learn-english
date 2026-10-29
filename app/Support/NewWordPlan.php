<?php

namespace App\Support;

/**
 * How many new words a roadmap study day adds and in which order the CEFR
 * levels are drawn (D21).
 *
 * Each stage has its own levels. When they run out (B1 has about 600 words
 * but stage 3 needs about 1,500), lower levels still left come next, then the
 * higher levels, and words without a level come last.
 */
final class NewWordPlan
{
    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Revision weeks before a gate, which only review old cards.
     */
    public const REVISION_WEEKS = [13, 26, 39, 47, 65, 69];

    /**
     * The mock test weeks of stage 5, which halve the new words.
     */
    public const MOCK_TEST_WEEKS = [73, 78, 82, 86, 91, 95];

    /**
     * New words per study day. Stage 6 only reviews old cards.
     */
    public static function quota(int $stage, int $week): int
    {
        if (in_array($week, self::REVISION_WEEKS, true)) {
            return 0;
        }

        return match ($stage) {
            1 => 8,
            2 => 10,
            3 => 12,
            4 => 15,
            5 => in_array($week, self::MOCK_TEST_WEEKS, true) ? 5 : 10,
            default => 0,
        };
    }

    /**
     * The stage's own levels. The CSV does not mark the AWL words of stage 5,
     * so it takes the C1 and C2 words left.
     *
     * @return list<string>
     */
    public static function stageLevels(int $stage): array
    {
        return match ($stage) {
            1 => ['A1'],
            2 => ['A1', 'A2'],
            3 => ['B1'],
            4 => ['B2', 'C1'],
            5 => ['C1', 'C2'],
            default => [],
        };
    }

    /**
     * All levels in the order they are drawn: the stage's own, then the lower
     * ones, then the higher ones. Words without a level follow the last one.
     *
     * @return list<string>
     */
    public static function levelOrder(int $stage): array
    {
        $own = self::stageLevels($stage);

        if ($own === []) {
            return [];
        }

        $first = (int) array_search($own[0], self::LEVELS, true);
        $lower = array_slice(self::LEVELS, 0, $first);
        $higher = array_diff(array_slice(self::LEVELS, $first), $own);

        return [...$own, ...$lower, ...array_values($higher)];
    }
}
